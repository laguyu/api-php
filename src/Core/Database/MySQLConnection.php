<?php

namespace App\Core\Database;

use PDO;
use Exception;

class MySQLConnection implements DatabaseConnectionInterface
{
    private ?PDO $connection = null;
    private ?string $runtimeSslCaPath = null;

    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $config = require __DIR__ . '/../../../config.php';
            $dbConfig = $config['database']['mysql'] ?? null;

            if (!$dbConfig) {
                throw new Exception("MySQL configuration is missing in config.php");
            }

            $this->connection = $this->createConnection($dbConfig);

            if ((bool) ($dbConfig['init_schema'] ?? true)) {
                // Create schema only when explicitly enabled.
                $this->initializeSchema((bool) ($dbConfig['seed_default_users'] ?? true));
            }
        }

        return $this->connection;
    }

    private function createConnection(array $dbConfig): PDO
    {
        $host = (string) ($dbConfig['host'] ?? '127.0.0.1');
        $port = (string) ($dbConfig['port'] ?? '3306');
        $dbname = (string) ($dbConfig['dbname'] ?? 'apiphp');
        $username = (string) ($dbConfig['username'] ?? 'root');
        $password = (string) ($dbConfig['password'] ?? '');

        $options = $this->buildConnectionOptions($dbConfig);

        if ((bool) ($dbConfig['auto_create_database'] ?? false)) {
            $bootstrapDsn = "mysql:host={$host};port={$port};charset=utf8mb4";
            $bootstrap = new PDO($bootstrapDsn, $username, $password, $options);
            $quotedDbName = str_replace('`', '``', $dbname);
            $bootstrap->exec("CREATE DATABASE IF NOT EXISTS `{$quotedDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

        return new PDO($dsn, $username, $password, $options);
    }

    private function buildConnectionOptions(array $dbConfig): array
    {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $sslCa = $this->resolveSslCaPath((string) ($dbConfig['ssl_ca'] ?? ''));
        $sslMode = strtolower((string) ($dbConfig['ssl_mode'] ?? ''));

        if ($sslCa !== '' && defined('PDO::MYSQL_ATTR_SSL_CA')) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
        }

        if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
            // Do not force strict cert validation when no CA is configured.
            $defaultVerify = $sslCa !== '';
            $verifyServerCert = (bool) ($dbConfig['ssl_verify_server_cert'] ?? $defaultVerify);

            if ($sslCa === '' && ($sslMode === 'required' || $sslMode === 'preferred' || $sslMode === '')) {
                $verifyServerCert = false;
            }

            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = $verifyServerCert;
        }

        if (defined('PDO::MYSQL_ATTR_SSL_MODE')) {
            if ($sslMode !== '') {
                $modeMap = [
                    'disabled' => defined('PDO::MYSQL_ATTR_SSL_MODE_DISABLED') ? PDO::MYSQL_ATTR_SSL_MODE_DISABLED : null,
                    'preferred' => defined('PDO::MYSQL_ATTR_SSL_MODE_PREFERRED') ? PDO::MYSQL_ATTR_SSL_MODE_PREFERRED : null,
                    'required' => defined('PDO::MYSQL_ATTR_SSL_MODE_REQUIRED') ? PDO::MYSQL_ATTR_SSL_MODE_REQUIRED : null,
                    'verify_ca' => defined('PDO::MYSQL_ATTR_SSL_MODE_VERIFY_CA') ? PDO::MYSQL_ATTR_SSL_MODE_VERIFY_CA : null,
                    'verify_identity' => defined('PDO::MYSQL_ATTR_SSL_MODE_VERIFY_IDENTITY') ? PDO::MYSQL_ATTR_SSL_MODE_VERIFY_IDENTITY : null,
                ];

                $key = strtolower($sslMode);
                if (isset($modeMap[$key]) && $modeMap[$key] !== null) {
                    $options[PDO::MYSQL_ATTR_SSL_MODE] = $modeMap[$key];
                }
            }
        }

        return $options;
    }

    private function resolveSslCaPath(string $sslCa): string
    {
        $trimmed = trim($sslCa);
        if ($trimmed === '') {
            return '';
        }

        $pemContent = str_replace(["\\r\\n", "\\n", "\\r"], PHP_EOL, $trimmed);

        if (str_contains($pemContent, '-----BEGIN CERTIFICATE-----')) {
            if ($this->runtimeSslCaPath !== null) {
                return $this->runtimeSslCaPath;
            }

            $tmpPath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mysql-ca.pem';
            @file_put_contents($tmpPath, $pemContent . PHP_EOL);
            $this->runtimeSslCaPath = is_file($tmpPath) ? $tmpPath : '';

            return $this->runtimeSslCaPath;
        }

        return $trimmed;
    }

    private function initializeSchema(bool $seedDefaultUsers): void
    {
        $productsSql = "
            CREATE TABLE IF NOT EXISTS products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                price DECIMAL(10, 2) NOT NULL,
                created_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";

        $usersSql = "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                role VARCHAR(32) NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";

        $authSessionsSql = "
            CREATE TABLE IF NOT EXISTS auth_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                jti VARCHAR(64) NOT NULL UNIQUE,
                user_id INT NOT NULL,
                email VARCHAR(255) NOT NULL,
                refresh_token_hash VARCHAR(128) NOT NULL,
                expires_at DATETIME NOT NULL,
                revoked_at DATETIME NULL,
                ip_address VARCHAR(64) NULL,
                created_at DATETIME NOT NULL,
                INDEX idx_auth_sessions_expires_at (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";

        $this->connection->exec($productsSql);
        $this->connection->exec($usersSql);
        $this->connection->exec($authSessionsSql);

        if ($seedDefaultUsers) {
            $this->seedUsers();
        }
    }

    private function seedUsers(): void
    {
        $users = [
            ['admin@example.com', 'Admin123!', 'admin'],
            ['editor@example.com', 'Editor123!', 'editor'],
            ['viewer@example.com', 'Viewer123!', 'viewer'],
        ];

        $checkStmt = $this->connection->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
        $insertStmt = $this->connection->prepare(
            'INSERT INTO users (email, password_hash, role, is_active, created_at) VALUES (:email, :password_hash, :role, 1, :created_at)'
        );

        foreach ($users as [$email, $password, $role]) {
            $checkStmt->execute(['email' => $email]);
            $exists = (int) $checkStmt->fetchColumn() > 0;

            if (!$exists) {
                $insertStmt->execute([
                    'email' => $email,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => $role,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }
}
