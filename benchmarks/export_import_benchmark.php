<?php

declare(strict_types=1);

/**
 * Million-row export simulation benchmark.
 *
 * Example:
 * DATAFLOW_BENCH_ROWS=1000000 DATAFLOW_BENCH_BATCH=5000 php benchmarks/export_import_benchmark.php
 */

$rows = max(1, (int) ($_ENV['DATAFLOW_BENCH_ROWS'] ?? $_SERVER['DATAFLOW_BENCH_ROWS'] ?? 1000000));
$batchSize = max(100, (int) ($_ENV['DATAFLOW_BENCH_BATCH'] ?? $_SERVER['DATAFLOW_BENCH_BATCH'] ?? 5000));
$mode = (string) ($_ENV['DATAFLOW_BENCH_MODE'] ?? $_SERVER['DATAFLOW_BENCH_MODE'] ?? 'sqlite');
$maxWriteRows = max(0, (int) ($_ENV['DATAFLOW_BENCH_MAX_WRITE_ROWS'] ?? $_SERVER['DATAFLOW_BENCH_MAX_WRITE_ROWS'] ?? 500000));
$outputPath = (string) ($_ENV['DATAFLOW_BENCH_OUTPUT'] ?? $_SERVER['DATAFLOW_BENCH_OUTPUT'] ?? __DIR__.'/output/export-simulation.csv');

$supportedModes = ['sqlite', 'mysql', 'postgresql', 'pgsql', 'virtual'];
if (! in_array($mode, $supportedModes, true)) {
    throw new InvalidArgumentException(sprintf(
        'Unsupported DATAFLOW_BENCH_MODE "%s". Supported modes: %s',
        $mode,
        implode(', ', $supportedModes),
    ));
}

if ($mode === 'pgsql') {
    $mode = 'postgresql';
}

if ($mode === 'sqlite' && $rows > 10000000) {
    // Auto-upgrade to virtual mode for ultra-large simulations to avoid pathological local disk churn.
    $mode = 'virtual';
}

$outputDir = dirname($outputPath);
if (! is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}


$stream = fopen($outputPath, 'wb');
if (! is_resource($stream)) {
    throw new RuntimeException('Unable to open benchmark output path: '.$outputPath);
}

fputcsv($stream, ['id', 'tenant_id', 'name', 'status', 'created_at']);

$inserted = 0;
$exportedRows = 0;
$writtenRows = 0;

$seedStart = microtime(true);

if (in_array($mode, ['sqlite', 'mysql', 'postgresql'], true)) {
    $pdo = connectPdo($mode, $outputDir);

    if ($mode === 'sqlite') {
        $pdo->exec('DROP TABLE IF EXISTS users');

        $pdo->exec('CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tenant_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            status TEXT NOT NULL,
            created_at TEXT NOT NULL
        )');
    }

    if ($mode === 'mysql') {
        $pdo->exec('DROP TABLE IF EXISTS users');

        $pdo->exec('CREATE TABLE users (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            tenant_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            status VARCHAR(32) NOT NULL,
            created_at DATETIME NOT NULL,
            KEY idx_status_name_tenant (status, name, tenant_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }

    if ($mode === 'postgresql') {
        $pdo->exec('DROP TABLE IF EXISTS users');

        $pdo->exec('CREATE TABLE users (
            id BIGSERIAL PRIMARY KEY,
            tenant_id BIGINT NOT NULL,
            name TEXT NOT NULL,
            status TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL
        )');

        $pdo->exec('CREATE INDEX idx_users_status_name_tenant ON users (status, name, tenant_id)');
    }

    $insert = $pdo->prepare('INSERT INTO users (tenant_id, name, status, created_at) VALUES (:tenant_id, :name, :status, :created_at)');

    while ($inserted < $rows) {
        $pdo->beginTransaction();

        $limit = min($batchSize, $rows - $inserted);

        for ($offset = 0; $offset < $limit; $offset++) {
            $index = $inserted + $offset + 1;
            $insert->execute([
                ':tenant_id' => ($index % 4) + 1,
                ':name' => 'User '.$index,
                ':status' => $index % 3 === 0 ? 'inactive' : 'active',
                ':created_at' => date('Y-m-d H:i:s', time() - ($index % 86400)),
            ]);
        }

        $pdo->commit();
        $inserted += $limit;
    }

    $seedElapsed = microtime(true) - $seedStart;

    $exportStart = microtime(true);

    $query = $pdo->prepare('SELECT id, tenant_id, name, status, created_at
    FROM users
    WHERE status = :status
      AND (
          name LIKE :prefix
          OR (tenant_id = :tenant AND id % 11 = 0)
      )
    ORDER BY id');

    $query->execute([
        ':status' => 'active',
        ':prefix' => 'User 1%',
        ':tenant' => 2,
    ]);

    while (($row = $query->fetch(PDO::FETCH_ASSOC)) !== false) {
        $exportedRows++;

        if ($maxWriteRows === 0 || $writtenRows < $maxWriteRows) {
            fputcsv($stream, $row);
            $writtenRows++;
        }
    }

    $exportElapsed = microtime(true) - $exportStart;
} else {
    // Virtual mode: generate and stream deterministic row shapes without materializing table state.
    $inserted = $rows;
    $seedElapsed = microtime(true) - $seedStart;

    $exportStart = microtime(true);

    for ($index = 1; $index <= $rows; $index++) {
        $tenantId = ($index % 4) + 1;
        $status = $index % 3 === 0 ? 'inactive' : 'active';
        $name = 'User '.$index;

        $matches = $status === 'active' && (str_starts_with($name, 'User 1') || ($tenantId === 2 && $index % 11 === 0));

        if (! $matches) {
            continue;
        }

        $exportedRows++;

        if ($maxWriteRows === 0 || $writtenRows < $maxWriteRows) {
            fputcsv($stream, [
                'id' => $index,
                'tenant_id' => $tenantId,
                'name' => $name,
                'status' => $status,
                'created_at' => date('Y-m-d H:i:s', time() - ($index % 86400)),
            ]);
            $writtenRows++;
        }
    }

    $exportElapsed = microtime(true) - $exportStart;
}

fclose($stream);

$totalElapsed = $seedElapsed + $exportElapsed;

fwrite(STDOUT, "benchmark=export_simulation\n");
fwrite(STDOUT, 'mode='.$mode."\n");
fwrite(STDOUT, 'rows_requested='.$rows."\n");
fwrite(STDOUT, 'rows_inserted='.$inserted."\n");
fwrite(STDOUT, 'rows_exported='.$exportedRows."\n");
fwrite(STDOUT, 'rows_written='.$writtenRows."\n");
fwrite(STDOUT, 'max_write_rows='.$maxWriteRows."\n");
fwrite(STDOUT, 'batch_size='.$batchSize."\n");
fwrite(STDOUT, 'seed_seconds='.number_format($seedElapsed, 4, '.', '')."\n");
fwrite(STDOUT, 'export_seconds='.number_format($exportElapsed, 4, '.', '')."\n");
fwrite(STDOUT, 'total_seconds='.number_format($totalElapsed, 4, '.', '')."\n");
fwrite(STDOUT, 'export_rows_per_second='.number_format($exportedRows / max($exportElapsed, 0.0001), 2, '.', '')."\n");
fwrite(STDOUT, 'peak_memory_mb='.number_format(memory_get_peak_usage(true) / 1024 / 1024, 2, '.', '')."\n");
fwrite(STDOUT, 'output_path='.$outputPath."\n");

/**
 * @return PDO
 */
function connectPdo(string $mode, string $outputDir): PDO
{
    if ($mode === 'sqlite') {
        $dbPath = $outputDir.'/export-simulation.sqlite';
        @unlink($dbPath);
        $pdo = new PDO('sqlite:'.$dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

    if ($mode === 'mysql') {
        $host = (string) ($_ENV['DATAFLOW_BENCH_DB_HOST'] ?? $_SERVER['DATAFLOW_BENCH_DB_HOST'] ?? '127.0.0.1');
        $port = (int) ($_ENV['DATAFLOW_BENCH_DB_PORT'] ?? $_SERVER['DATAFLOW_BENCH_DB_PORT'] ?? 3306);
        $database = (string) ($_ENV['DATAFLOW_BENCH_DB_DATABASE'] ?? $_SERVER['DATAFLOW_BENCH_DB_DATABASE'] ?? 'laravel_dataflow_bench');
        $username = (string) ($_ENV['DATAFLOW_BENCH_DB_USERNAME'] ?? $_SERVER['DATAFLOW_BENCH_DB_USERNAME'] ?? 'root');
        $password = (string) ($_ENV['DATAFLOW_BENCH_DB_PASSWORD'] ?? $_SERVER['DATAFLOW_BENCH_DB_PASSWORD'] ?? '');

        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $database),
            $username,
            $password,
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

    $host = (string) ($_ENV['DATAFLOW_BENCH_DB_HOST'] ?? $_SERVER['DATAFLOW_BENCH_DB_HOST'] ?? '127.0.0.1');
    $port = (int) ($_ENV['DATAFLOW_BENCH_DB_PORT'] ?? $_SERVER['DATAFLOW_BENCH_DB_PORT'] ?? 5432);
    $database = (string) ($_ENV['DATAFLOW_BENCH_DB_DATABASE'] ?? $_SERVER['DATAFLOW_BENCH_DB_DATABASE'] ?? 'laravel_dataflow_bench');
    $username = (string) ($_ENV['DATAFLOW_BENCH_DB_USERNAME'] ?? $_SERVER['DATAFLOW_BENCH_DB_USERNAME'] ?? 'postgres');
    $password = (string) ($_ENV['DATAFLOW_BENCH_DB_PASSWORD'] ?? $_SERVER['DATAFLOW_BENCH_DB_PASSWORD'] ?? '');

    $pdo = new PDO(
        sprintf('pgsql:host=%s;port=%d;dbname=%s', $host, $port, $database),
        $username,
        $password,
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    return $pdo;
}

