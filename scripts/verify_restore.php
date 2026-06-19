<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/init.php';
$config = require CONFIG_PATH . '/database.php';
$files = glob(ROOT_PATH . '/storage/backups/mtghub-*.sql') ?: [];
rsort($files, SORT_STRING);
$path = $argv[1] ?? ($files[0] ?? '');
if (!is_file($path)) throw new RuntimeException('Provide a valid backup file or run backup.php first.');
$expected = Database::connection()->prepare('SELECT checksum_sha256 FROM backup_runs WHERE filename=? AND status IN (\'completed\',\'restored\') ORDER BY id DESC LIMIT 1');
$expected->execute([basename($path)]);
$checksum = hash_file('sha256', $path);
if (($recorded = $expected->fetchColumn()) && !hash_equals((string) $recorded, $checksum)) throw new RuntimeException('Backup checksum mismatch.');
$filesPath = dirname($path) . '/' . basename($path, '.sql') . '-files';
if (is_file($filesPath . '/manifest.json')) {
    $manifest = json_decode((string) file_get_contents($filesPath . '/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
    foreach ($manifest as $name => $fileChecksum) {
        $upload = $filesPath . '/' . basename((string) $name);
        if (!is_file($upload) || !hash_equals((string) $fileChecksum, hash_file('sha256', $upload))) throw new RuntimeException('Private-upload backup checksum mismatch: ' . $name);
    }
}

$testDb = 'mtghub_restore_' . bin2hex(random_bytes(4));
$mysql = dirname(PHP_BINARY) . '/../mysql/bin/mysql.exe';
if (!is_file($mysql)) $mysql = 'mysql';
$env = getenv(); $env['MYSQL_PWD'] = $config['password'];
$run = static function (array $command, ?string $input = null) use ($env): string {
    $descriptors = [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']];
    $process = proc_open($command, $descriptors, $pipes, null, $env);
    if (!is_resource($process)) throw new RuntimeException('Could not launch database client.');
    if ($input !== null) fwrite($pipes[0], $input); fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]); $error = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
    $code = proc_close($process);
    if ($code !== 0) throw new RuntimeException(trim($error));
    return trim($output);
};

try {
    $base = [$mysql, '--user=' . $config['username'], '--default-character-set=' . $config['charset']];
    if (!in_array($config['host'], ['127.0.0.1','localhost'], true)) array_splice($base, 1, 0, ['--host=' . $config['host']]);
    $run($base, "CREATE DATABASE `$testDb` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $dump = file_get_contents($path); if ($dump === false) throw new RuntimeException('Cannot read backup.');
    $run([...$base, $testDb], $dump);
    $tables = $run([...$base, '--batch', '--skip-column-names', $testDb], "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$testDb';");
    $checks = $run([...$base, '--batch', '--skip-column-names', $testDb], 'SELECT (SELECT COUNT(*) FROM users),(SELECT COUNT(*) FROM orders),(SELECT COUNT(*) FROM wallet_transactions),(SELECT COUNT(*) FROM schema_migrations);');
    if ((int) $tables < 20) throw new RuntimeException('Restore contains too few tables: ' . $tables);
    Database::connection()->prepare("UPDATE backup_runs SET status='restored',details=?,completed_at=NOW() WHERE filename=? AND checksum_sha256=?")->execute(['Restore drill passed; counts users/orders/wallet/migrations: ' . $checks, basename($path), $checksum]);
    echo "Restore verification passed for " . basename($path) . ". Tables: $tables. Counts: $checks\n";
} finally {
    try { $run($base, "DROP DATABASE IF EXISTS `$testDb`;"); } catch (Throwable) {}
}
