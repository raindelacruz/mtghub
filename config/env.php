<?php

static $environmentLoaded = false;
if (!$environmentLoaded) {
    $environmentLoaded = true;
    $environmentFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';

    if (is_file($environmentFile) && is_readable($environmentFile)) {
        foreach (file($environmentFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = array_map('trim', explode('=', $line, 2));
            if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $name) || getenv($name) !== false) {
                continue;
            }

            if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
                $value = substr($value, 1, -1);
            }

            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
        }
    }
}

function config_env(string $name, mixed $default = null): mixed
{
    $value = getenv($name);
    return $value === false || $value === '' ? $default : $value;
}
