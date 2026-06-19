<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/env.php';
$config = require dirname(__DIR__) . '/config/database.php';
$pdo = new PDO(sprintf('mysql:host=%s;dbname=%s;charset=%s', $config['host'], $config['database'], $config['charset']), $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (version VARCHAR(100) PRIMARY KEY, checksum_sha256 CHAR(64) NOT NULL, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
$applied = $pdo->query('SELECT version, checksum_sha256 FROM schema_migrations')->fetchAll(PDO::FETCH_KEY_PAIR);
$files = glob(dirname(__DIR__) . '/database/migrations/*.sql') ?: [];
sort($files, SORT_STRING);

foreach ($files as $file) {
    $version = basename($file, '.sql');
    $sql = file_get_contents($file);
    if ($sql === false) throw new RuntimeException('Cannot read migration ' . $version);
    $checksum = hash('sha256', $sql);
    if (isset($applied[$version])) {
        if (!hash_equals($applied[$version], $checksum)) throw new RuntimeException('Applied migration changed: ' . $version);
        echo "Already applied: $version\n";
        continue;
    }
    try {
        foreach (array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [])) as $statement) $pdo->exec($statement);
        $record = $pdo->prepare('INSERT INTO schema_migrations (version, checksum_sha256) VALUES (?, ?)');
        $record->execute([$version, $checksum]);
        echo "Applied: $version\n";
    } catch (Throwable $error) {
        throw $error;
    }
}
