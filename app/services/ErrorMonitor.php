<?php

final class ErrorMonitor
{
    private static string $requestId = '';
    private static bool $handling = false;

    public static function register(): void
    {
        self::$requestId = bin2hex(random_bytes(8));
        if (!headers_sent()) header('X-Request-ID: ' . self::$requestId);
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) return false;
            self::record('error', 'php_error', $message, ['severity' => $severity, 'file' => $file, 'line' => $line]);
            return false;
        });
        set_exception_handler(static function (Throwable $error): void {
            self::record('critical', 'uncaught_exception', $error->getMessage(), ['class' => $error::class, 'file' => $error->getFile(), 'line' => $error->getLine()]);
            if (PHP_SAPI === 'cli') {
                fwrite(STDERR, $error::class . ': ' . $error->getMessage() . PHP_EOL);
                exit(1);
            }
            http_response_code(500);
            echo APP_ENV === 'development' ? '500 - ' . e($error->getMessage()) : '500 - Something went wrong. Reference: ' . e(self::$requestId);
        });
        register_shutdown_function(static function (): void {
            $error = error_get_last();
            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) self::record('critical', 'fatal_error', $error['message'], $error);
        });
    }

    public static function record(string $severity, string $type, string $message, array $context = []): void
    {
        if (self::$handling) return;
        self::$handling = true;
        try {
            $entry = ['timestamp' => gmdate('c'), 'request_id' => self::$requestId, 'severity' => $severity, 'type' => $type, 'message' => $message, 'context' => self::sanitize($context)];
            $directory = ROOT_PATH . '/storage/logs';
            if (!is_dir($directory)) @mkdir($directory, 0775, true);
            @file_put_contents($directory . '/app-' . date('Y-m-d') . '.log', json_encode($entry, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);
            try {
                $statement = Database::connection()->prepare('INSERT INTO system_events (request_id,severity,event_type,message,context_json) VALUES (?,?,?,?,?)');
                $statement->execute([self::$requestId ?: null, $severity, mb_substr($type, 0, 100), mb_substr($message, 0, 1000), json_encode(self::sanitize($context), JSON_UNESCAPED_SLASHES)]);
            } catch (Throwable) {}
        } finally { self::$handling = false; }
    }

    public static function requestId(): string { return self::$requestId; }

    private static function sanitize(array $context): array
    {
        $blocked = ['password','password_hash','token','secret','authorization','cookie','smtp_password'];
        foreach ($context as $key => $value) {
            if (in_array(strtolower((string) $key), $blocked, true)) $context[$key] = '[redacted]';
            elseif (is_array($value)) $context[$key] = self::sanitize($value);
        }
        return $context;
    }
}
