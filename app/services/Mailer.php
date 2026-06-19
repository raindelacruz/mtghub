<?php

class Mailer
{
    public static function send(string $recipient, string $subject, string $body): void
    {
        $config = require CONFIG_PATH . DIRECTORY_SEPARATOR . 'mail.php';
        self::validate($recipient, $subject, $config);

        $socket = @stream_socket_client(
            'tcp://' . $config['host'] . ':' . $config['port'],
            $errorNumber,
            $errorMessage,
            max(1, $config['timeout'])
        );
        if (!is_resource($socket)) {
            throw new RuntimeException('Could not connect to the configured SMTP server.');
        }

        stream_set_timeout($socket, max(1, $config['timeout']));
        try {
            self::expect($socket, [220]);
            self::command($socket, 'EHLO mtghub.local', [250]);

            if ($config['encryption'] === 'tls') {
                self::command($socket, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('Could not establish an encrypted SMTP connection.');
                }
                self::command($socket, 'EHLO mtghub.local', [250]);
            }

            self::command($socket, 'AUTH LOGIN', [334]);
            self::command($socket, base64_encode($config['username']), [334]);
            self::command($socket, base64_encode($config['password']), [235]);
            self::command($socket, 'MAIL FROM:<' . $config['from_email'] . '>', [250]);
            self::command($socket, 'RCPT TO:<' . $recipient . '>', [250, 251]);
            self::command($socket, 'DATA', [354]);

            $headers = [
                'Date: ' . date(DATE_RFC2822),
                'From: ' . self::encodeHeader($config['from_name']) . ' <' . $config['from_email'] . '>',
                'To: <' . $recipient . '>',
                'Subject: ' . self::encodeHeader($subject),
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
                'Content-Transfer-Encoding: 8bit',
            ];
            $normalizedBody = str_replace(["\r\n", "\r"], "\n", $body);
            $normalizedBody = preg_replace('/^\./m', '..', $normalizedBody) ?? $normalizedBody;
            fwrite($socket, implode("\r\n", $headers) . "\r\n\r\n" . str_replace("\n", "\r\n", $normalizedBody) . "\r\n.\r\n");
            self::expect($socket, [250]);
            self::command($socket, 'QUIT', [221]);
        } finally {
            fclose($socket);
        }
    }

    private static function validate(string $recipient, string $subject, array $config): void
    {
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $subject)) {
            throw new InvalidArgumentException('Invalid email recipient or subject.');
        }
        if (!filter_var($config['from_email'], FILTER_VALIDATE_EMAIL) || $config['username'] === '' || $config['password'] === '') {
            throw new RuntimeException('SMTP credentials are not configured.');
        }
        if (!in_array($config['encryption'], ['tls', 'none'], true)) {
            throw new RuntimeException('Unsupported SMTP encryption setting.');
        }
    }

    private static function command($socket, string $command, array $expectedCodes): void
    {
        fwrite($socket, $command . "\r\n");
        self::expect($socket, $expectedCodes);
    }

    private static function expect($socket, array $expectedCodes): void
    {
        $response = '';
        do {
            $line = fgets($socket, 515);
            if ($line === false) {
                throw new RuntimeException('The SMTP server closed the connection unexpectedly.');
            }
            $response .= $line;
        } while (isset($line[3]) && $line[3] === '-');

        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            throw new RuntimeException('SMTP request failed with response code ' . $code . '.');
        }
    }

    private static function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }
}
