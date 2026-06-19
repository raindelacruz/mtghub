<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/init.php';
$config = require CONFIG_PATH . '/database.php';
$directory = ROOT_PATH . '/storage/backups';
if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) throw new RuntimeException('Cannot create backup directory.');
$filename = 'mtghub-' . date('Ymd-His') . '.sql';
$path = $directory . '/' . $filename;
$filesPath = $directory . '/' . basename($filename, '.sql') . '-files';
$dump = dirname(PHP_BINARY) . '/../mysql/bin/mysqldump.exe';
if (!is_file($dump)) $dump = 'mysqldump';
$run = Database::connection()->prepare("INSERT INTO backup_runs (filename,status) VALUES (?,'started')");
$run->execute([$filename]);
$runId = (int) Database::connection()->lastInsertId();

try {
    $output = fopen($path, 'wb');
    if ($output === false) throw new RuntimeException('Cannot open backup output.');
    $command = [$dump, '--user=' . $config['username'], '--single-transaction', '--routines', '--triggers', '--default-character-set=' . $config['charset'], '--no-create-db', $config['database']];
    if (!in_array($config['host'], ['127.0.0.1','localhost'], true)) array_splice($command, 1, 0, ['--host=' . $config['host']]);
    $processEnv = getenv(); $processEnv['MYSQL_PWD'] = $config['password'];
    $process = proc_open($command, [1 => $output, 2 => ['pipe', 'w']], $pipes, null, $processEnv);
    if (!is_resource($process)) throw new RuntimeException('Cannot launch mysqldump.');
    $error = stream_get_contents($pipes[2]); fclose($pipes[2]);
    $code = proc_close($process); fclose($output);
    if ($code !== 0 || !is_file($path) || filesize($path) < 100) throw new RuntimeException('mysqldump failed: ' . trim((string) $error));
    $manifest = [];
    $proofSource = ROOT_PATH . '/storage/payment_proofs';
    if (is_dir($proofSource)) {
        mkdir($filesPath, 0770, true);
        foreach (new DirectoryIterator($proofSource) as $item) {
            if (!$item->isFile()) continue;
            $target = $filesPath . '/' . $item->getFilename();
            if (!copy($item->getPathname(), $target)) throw new RuntimeException('Could not back up private upload ' . $item->getFilename());
            $manifest[$item->getFilename()] = hash_file('sha256', $target);
        }
        file_put_contents($filesPath . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }
    $size = filesize($path); $checksum = hash_file('sha256', $path);
    Database::connection()->prepare("UPDATE backup_runs SET status='completed',file_size=?,checksum_sha256=?,completed_at=NOW() WHERE id=?")->execute([$size, $checksum, $runId]);
    foreach (glob($directory . '/mtghub-*.sql') ?: [] as $old) if (filemtime($old) < time() - 30 * 86400) {
        @unlink($old);
        $companion = $directory . '/' . basename($old, '.sql') . '-files';
        if (is_dir($companion)) foreach (new DirectoryIterator($companion) as $item) if ($item->isFile()) @unlink($item->getPathname());
        @rmdir($companion);
    }
    echo "Backup complete: $path\nSHA-256: $checksum\n";
} catch (Throwable $error) {
    @unlink($path);
    Database::connection()->prepare("UPDATE backup_runs SET status='failed',details=?,completed_at=NOW() WHERE id=?")->execute([mb_substr($error->getMessage(), 0, 1000), $runId]);
    throw $error;
}
