<?php

declare(strict_types=1);

/**
 * Enterprise-style large-scale benchmark with joins + indexes.
 *
 * Example:
 * DATAFLOW_ENT_USERS=250000 DATAFLOW_ENT_ORDERS_PER_USER=4 DATAFLOW_ENT_ITEMS_PER_ORDER=2 php benchmarks/enterprise_join_benchmark.php
 */

$users = max(1000, (int) ($_ENV['DATAFLOW_ENT_USERS'] ?? $_SERVER['DATAFLOW_ENT_USERS'] ?? 250000));
$ordersPerUser = max(1, (int) ($_ENV['DATAFLOW_ENT_ORDERS_PER_USER'] ?? $_SERVER['DATAFLOW_ENT_ORDERS_PER_USER'] ?? 4));
$itemsPerOrder = max(1, (int) ($_ENV['DATAFLOW_ENT_ITEMS_PER_ORDER'] ?? $_SERVER['DATAFLOW_ENT_ITEMS_PER_ORDER'] ?? 2));
$batchSize = max(500, (int) ($_ENV['DATAFLOW_ENT_BATCH'] ?? $_SERVER['DATAFLOW_ENT_BATCH'] ?? 5000));
$windowSeconds = max(1, (int) ($_ENV['DATAFLOW_ENT_WINDOW_SECONDS'] ?? $_SERVER['DATAFLOW_ENT_WINDOW_SECONDS'] ?? 31536000));
$minGrossCents = max(0, (int) ($_ENV['DATAFLOW_ENT_MIN_GROSS_CENTS'] ?? $_SERVER['DATAFLOW_ENT_MIN_GROSS_CENTS'] ?? 0));
$progressEvery = max(0, (int) ($_ENV['DATAFLOW_ENT_PROGRESS_EVERY'] ?? $_SERVER['DATAFLOW_ENT_PROGRESS_EVERY'] ?? 100000));
$outputPath = (string) ($_ENV['DATAFLOW_ENT_OUTPUT'] ?? $_SERVER['DATAFLOW_ENT_OUTPUT'] ?? __DIR__.'/output/enterprise-join-export.csv');
$mode = (string) ($_ENV['DATAFLOW_ENT_MODE'] ?? $_SERVER['DATAFLOW_ENT_MODE'] ?? 'sqlite');

$supportedModes = ['sqlite', 'mysql', 'postgresql', 'pgsql', 'mariadb'];
if (! in_array($mode, $supportedModes, true)) {
    throw new InvalidArgumentException(sprintf(
        'Unsupported DATAFLOW_ENT_MODE "%s". Supported modes: %s',
        $mode,
        implode(', ', $supportedModes),
    ));
}

if ($mode === 'pgsql') {
    $mode = 'postgresql';
}

$outputDir = dirname($outputPath);
if (! is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

$pdo = connectPdo($mode, $outputDir);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($mode === 'sqlite') {
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA synchronous = NORMAL');
}

$schemaStart = microtime(true);
createSchema($pdo, $mode);

$tenantInsert = $pdo->prepare('INSERT INTO tenants (id, name) VALUES (?, ?)');
for ($tenant = 1; $tenant <= 10; $tenant++) {
    $tenantInsert->execute([$tenant, 'Tenant '.$tenant]);
}

$schemaElapsed = microtime(true) - $schemaStart;

$seedStart = microtime(true);

$userInsert = $pdo->prepare('INSERT INTO users (tenant_id, name, email, status, created_at) VALUES (?, ?, ?, ?, ?)');
$orderInsert = $pdo->prepare('INSERT INTO orders (user_id, tenant_id, status, total_cents, created_at) VALUES (?, ?, ?, ?, ?)');
$itemInsert = $pdo->prepare('INSERT INTO order_items (order_id, sku, quantity, unit_price_cents, created_at) VALUES (?, ?, ?, ?, ?)');

$insertedUsers = 0;
$insertedOrders = 0;
$insertedItems = 0;

while ($insertedUsers < $users) {
    $pdo->beginTransaction();

    $chunk = min($batchSize, $users - $insertedUsers);

    for ($i = 0; $i < $chunk; $i++) {
        $globalIndex = $insertedUsers + $i + 1;
        $tenantId = ($globalIndex % 10) + 1;
        $status = $globalIndex % 5 === 0 ? 'inactive' : 'active';
        $createdAt = date('Y-m-d H:i:s', time() - ($globalIndex % 200000));

        $userInsert->execute([
            $tenantId,
            'Enterprise User '.$globalIndex,
            'enterprise.user.'.$globalIndex.'@example.test',
            $status,
            $createdAt,
        ]);

        $userId = (int) $pdo->lastInsertId();

        for ($orderNo = 1; $orderNo <= $ordersPerUser; $orderNo++) {
            $orderStatus = ($orderNo % 3 === 0) ? 'cancelled' : 'paid';
            $totalCents = 0;

            // Create deterministic order amount before insert.
            for ($itemNo = 1; $itemNo <= $itemsPerOrder; $itemNo++) {
                $quantity = (($globalIndex + $orderNo + $itemNo) % 3) + 1;
                $unitPrice = 500 + (($globalIndex + $itemNo) % 4500);
                $totalCents += $quantity * $unitPrice;
            }

            $orderInsert->execute([
                $userId,
                $tenantId,
                $orderStatus,
                $totalCents,
                $createdAt,
            ]);

            $orderId = (int) $pdo->lastInsertId();
            $insertedOrders++;

            for ($itemNo = 1; $itemNo <= $itemsPerOrder; $itemNo++) {
                $quantity = (($globalIndex + $orderNo + $itemNo) % 3) + 1;
                $unitPrice = 500 + (($globalIndex + $itemNo) % 4500);

                $itemInsert->execute([
                    $orderId,
                    sprintf('SKU-%04d-%02d-%02d', $tenantId, $orderNo, $itemNo),
                    $quantity,
                    $unitPrice,
                    $createdAt,
                ]);

                $insertedItems++;
            }
        }
    }

    $pdo->commit();
    $insertedUsers += $chunk;

    if ($progressEvery > 0 && $insertedUsers % $progressEvery === 0) {
        fwrite(STDOUT, 'progress_seeded_users='.$insertedUsers."\n");
    }
}

$seedElapsed = microtime(true) - $seedStart;

$exportStart = microtime(true);

$fromDate = date('Y-m-d H:i:s', time() - $windowSeconds);
$quotedFromDate = $pdo->quote($fromDate);
$quotedUserStatus = $pdo->quote('active');
$quotedOrderStatus = $pdo->quote('paid');
$safeMinGrossCents = max(0, $minGrossCents);

$joinSql = <<<SQL
SELECT
    u.id AS user_id,
    u.tenant_id,
    t.name AS tenant_name,
    u.name AS user_name,
    COUNT(DISTINCT o.id) AS order_count,
    SUM(oi.quantity * oi.unit_price_cents) AS gross_cents,
    MAX(o.created_at) AS last_order_at
FROM users u
INNER JOIN tenants t ON t.id = u.tenant_id
INNER JOIN orders o ON o.user_id = u.id
INNER JOIN order_items oi ON oi.order_id = o.id
WHERE u.status = {$quotedUserStatus}
    AND o.status = {$quotedOrderStatus}
    AND u.created_at >= {$quotedFromDate}
GROUP BY u.id, u.tenant_id, t.name, u.name
HAVING SUM(oi.quantity * oi.unit_price_cents) > {$safeMinGrossCents}
ORDER BY gross_cents DESC
SQL;

$planRows = explainRows($pdo, $mode, $joinSql);

$queryStmt = $pdo->query($joinSql);

$stream = fopen($outputPath, 'wb');
if (! is_resource($stream)) {
    throw new RuntimeException('Unable to open enterprise benchmark output path: '.$outputPath);
}

fputcsv($stream, ['user_id', 'tenant_id', 'tenant_name', 'user_name', 'order_count', 'gross_cents', 'last_order_at']);

$exportedRows = 0;
while (($row = $queryStmt->fetch(PDO::FETCH_ASSOC)) !== false) {
    fputcsv($stream, $row);
    $exportedRows++;
}

fclose($stream);

$exportElapsed = microtime(true) - $exportStart;
$totalElapsed = $schemaElapsed + $seedElapsed + $exportElapsed;

fwrite(STDOUT, "benchmark=enterprise_join_export\n");
fwrite(STDOUT, 'mode='.$mode."\n");
fwrite(STDOUT, 'users='.$insertedUsers."\n");
fwrite(STDOUT, 'orders='.$insertedOrders."\n");
fwrite(STDOUT, 'order_items='.$insertedItems."\n");
fwrite(STDOUT, 'result_rows='.$exportedRows."\n");
fwrite(STDOUT, 'batch_size='.$batchSize."\n");
fwrite(STDOUT, 'window_seconds='.$windowSeconds."\n");
fwrite(STDOUT, 'min_gross_cents='.$minGrossCents."\n");
fwrite(STDOUT, 'schema_seconds='.number_format($schemaElapsed, 4, '.', '')."\n");
fwrite(STDOUT, 'seed_seconds='.number_format($seedElapsed, 4, '.', '')."\n");
fwrite(STDOUT, 'export_seconds='.number_format($exportElapsed, 4, '.', '')."\n");
fwrite(STDOUT, 'total_seconds='.number_format($totalElapsed, 4, '.', '')."\n");
fwrite(STDOUT, 'rows_per_second='.number_format($exportedRows / max($exportElapsed, 0.0001), 2, '.', '')."\n");
fwrite(STDOUT, 'peak_memory_mb='.number_format(memory_get_peak_usage(true) / 1024 / 1024, 2, '.', '')."\n");
fwrite(STDOUT, 'output_path='.$outputPath."\n");

foreach ($planRows as $index => $detail) {
    fwrite(STDOUT, 'query_plan_'.($index + 1).'='.$detail."\n");
}

/**
 * @return PDO
 */
function connectPdo(string $mode, string $outputDir): PDO
{
    if ($mode === 'sqlite') {
        $dbPath = $outputDir.'/enterprise-join-benchmark.sqlite';
        @unlink($dbPath);

        return new PDO('sqlite:'.$dbPath);
    }

    $host = (string) ($_ENV['DATAFLOW_ENT_DB_HOST'] ?? $_SERVER['DATAFLOW_ENT_DB_HOST'] ?? '127.0.0.1');
    $database = (string) ($_ENV['DATAFLOW_ENT_DB_DATABASE'] ?? $_SERVER['DATAFLOW_ENT_DB_DATABASE'] ?? 'laravel_dataflow_bench');
    $username = (string) ($_ENV['DATAFLOW_ENT_DB_USERNAME'] ?? $_SERVER['DATAFLOW_ENT_DB_USERNAME'] ?? 'root');
    $password = (string) ($_ENV['DATAFLOW_ENT_DB_PASSWORD'] ?? $_SERVER['DATAFLOW_ENT_DB_PASSWORD'] ?? '');

    if (in_array($mode, ['mysql', 'mariadb'], true)) {
        $port = (int) ($_ENV['DATAFLOW_ENT_DB_PORT'] ?? $_SERVER['DATAFLOW_ENT_DB_PORT'] ?? 3306);

        return new PDO(
            sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $database),
            $username,
            $password,
        );
    }

    $port = (int) ($_ENV['DATAFLOW_ENT_DB_PORT'] ?? $_SERVER['DATAFLOW_ENT_DB_PORT'] ?? 5432);
    $pgUser = (string) ($_ENV['DATAFLOW_ENT_DB_USERNAME'] ?? $_SERVER['DATAFLOW_ENT_DB_USERNAME'] ?? 'postgres');

    return new PDO(
        sprintf('pgsql:host=%s;port=%d;dbname=%s', $host, $port, $database),
        $pgUser,
        $password,
    );
}

function createSchema(PDO $pdo, string $mode): void
{
    if ($mode === 'sqlite') {
        $pdo->exec('CREATE TABLE tenants (
            id INTEGER PRIMARY KEY,
            name TEXT NOT NULL
        )');

        $pdo->exec('CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tenant_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            status TEXT NOT NULL,
            created_at TEXT NOT NULL
        )');

        $pdo->exec('CREATE TABLE orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            tenant_id INTEGER NOT NULL,
            status TEXT NOT NULL,
            total_cents INTEGER NOT NULL,
            created_at TEXT NOT NULL
        )');

        $pdo->exec('CREATE TABLE order_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            sku TEXT NOT NULL,
            quantity INTEGER NOT NULL,
            unit_price_cents INTEGER NOT NULL,
            created_at TEXT NOT NULL
        )');
    } elseif (in_array($mode, ['mysql', 'mariadb'], true)) {
        $pdo->exec('DROP TABLE IF EXISTS order_items');
        $pdo->exec('DROP TABLE IF EXISTS orders');
        $pdo->exec('DROP TABLE IF EXISTS users');
        $pdo->exec('DROP TABLE IF EXISTS tenants');

        $pdo->exec('CREATE TABLE tenants (
            id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
            name VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        $pdo->exec('CREATE TABLE users (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            tenant_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            status VARCHAR(32) NOT NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        $pdo->exec('CREATE TABLE orders (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            tenant_id BIGINT UNSIGNED NOT NULL,
            status VARCHAR(32) NOT NULL,
            total_cents INT NOT NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

        $pdo->exec('CREATE TABLE order_items (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            order_id BIGINT UNSIGNED NOT NULL,
            sku VARCHAR(64) NOT NULL,
            quantity INT NOT NULL,
            unit_price_cents INT NOT NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    } else {
        $pdo->exec('DROP TABLE IF EXISTS order_items');
        $pdo->exec('DROP TABLE IF EXISTS orders');
        $pdo->exec('DROP TABLE IF EXISTS users');
        $pdo->exec('DROP TABLE IF EXISTS tenants');

        $pdo->exec('CREATE TABLE tenants (
            id BIGINT PRIMARY KEY,
            name TEXT NOT NULL
        )');

        $pdo->exec('CREATE TABLE users (
            id BIGSERIAL PRIMARY KEY,
            tenant_id BIGINT NOT NULL,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            status TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL
        )');

        $pdo->exec('CREATE TABLE orders (
            id BIGSERIAL PRIMARY KEY,
            user_id BIGINT NOT NULL,
            tenant_id BIGINT NOT NULL,
            status TEXT NOT NULL,
            total_cents INT NOT NULL,
            created_at TIMESTAMP NOT NULL
        )');

        $pdo->exec('CREATE TABLE order_items (
            id BIGSERIAL PRIMARY KEY,
            order_id BIGINT NOT NULL,
            sku TEXT NOT NULL,
            quantity INT NOT NULL,
            unit_price_cents INT NOT NULL,
            created_at TIMESTAMP NOT NULL
        )');
    }

    $pdo->exec('CREATE INDEX idx_users_tenant_status_created ON users(tenant_id, status, created_at)');
    $pdo->exec('CREATE INDEX idx_orders_user_status_created ON orders(user_id, status, created_at)');
    $pdo->exec('CREATE INDEX idx_orders_tenant_created ON orders(tenant_id, created_at)');
    $pdo->exec('CREATE INDEX idx_order_items_order_id ON order_items(order_id)');
}

/**
 * @return array<int, string>
 */
function explainRows(PDO $pdo, string $mode, string $sql): array
{
    if ($mode === 'sqlite') {
        $rows = $pdo->query('EXPLAIN QUERY PLAN '.$sql)->fetchAll(PDO::FETCH_ASSOC);

        return array_values(array_map(
            static fn (array $row): string => (string) ($row['detail'] ?? json_encode($row, JSON_THROW_ON_ERROR)),
            $rows,
        ));
    }

    if (in_array($mode, ['mysql', 'mariadb'], true)) {
        $rows = $pdo->query('EXPLAIN '.$sql)->fetchAll(PDO::FETCH_ASSOC);

        return array_values(array_map(static function (array $row): string {
            $parts = [];

            foreach ($row as $key => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                $parts[] = $key.'='.$value;
            }

            return implode(' | ', $parts);
        }, $rows));
    }

    $rows = $pdo->query('EXPLAIN '.$sql)->fetchAll(PDO::FETCH_ASSOC);

    return array_values(array_map(static function (array $row): string {
        $value = $row['QUERY PLAN'] ?? reset($row);

        return (string) $value;
    }, $rows));
}
