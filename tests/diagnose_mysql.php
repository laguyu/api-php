<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config.php';

echo 'ACTIVE_DRIVER=' . ($config['active_driver'] ?? 'none') . PHP_EOL;
echo 'MYSQL_HOST=' . ($config['database']['mysql']['host'] ?? 'none') . PHP_EOL;
echo 'MYSQL_DB=' . ($config['database']['mysql']['dbname'] ?? 'none') . PHP_EOL;
echo 'MYSQL_AUTO_CREATE_DATABASE=' . (($config['database']['mysql']['auto_create_database'] ?? false) ? '1' : '0') . PHP_EOL;
echo 'MYSQL_INIT_SCHEMA=' . (($config['database']['mysql']['init_schema'] ?? false) ? '1' : '0') . PHP_EOL;

try {
    $connection = (new App\Core\Database\MySQLConnection())->getConnection();
    echo 'MYSQL_CONNECTION=OK' . PHP_EOL;

    $tables = $connection->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM);
    echo 'TABLES_COUNT=' . count($tables) . PHP_EOL;

    foreach ($tables as $table) {
        echo 'TABLE=' . (string) $table[0] . PHP_EOL;
    }
} catch (Throwable $e) {
    echo 'MYSQL_CONNECTION=ERROR' . PHP_EOL;
    echo 'ERROR_MESSAGE=' . $e->getMessage() . PHP_EOL;
}
