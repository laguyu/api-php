<?php

namespace App\Core\Database;

use PDO;
use Exception;

class PostgreSQLConnection implements DatabaseConnectionInterface
{
    private ?PDO $connection = null;

    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $config = require __DIR__ . '/../../../config.php';
            $dbConfig = $config['database']['pgsql'] ?? null;

            if (!$dbConfig) {
                throw new Exception("PostgreSQL configuration is missing in config.php");
            }

            $dsn = "pgsql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']}";
            
            $this->connection = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Create products table if it does not exist
            $this->initializeSchema();
        }

        return $this->connection;
    }

    private function initializeSchema(): void
    {
        $productsSql = "
            CREATE TABLE IF NOT EXISTS products (
                id SERIAL PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                price DECIMAL(10, 2) NOT NULL,
                created_at TIMESTAMP NOT NULL
            );
        ";

        $usersSql = "
            CREATE TABLE IF NOT EXISTS users (
                id SERIAL PRIMARY KEY,
                email VARCHAR(255) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                role VARCHAR(32) NOT NULL,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                created_at TIMESTAMP NOT NULL
            );
        ";

        $authSessionsSql = "
            CREATE TABLE IF NOT EXISTS auth_sessions (
                id SERIAL PRIMARY KEY,
                jti VARCHAR(64) NOT NULL UNIQUE,
                user_id INTEGER NOT NULL,
                email VARCHAR(255) NOT NULL,
                refresh_token_hash VARCHAR(128) NOT NULL,
                expires_at TIMESTAMP NOT NULL,
                revoked_at TIMESTAMP NULL,
                ip_address VARCHAR(64) NULL,
                created_at TIMESTAMP NOT NULL
            );
        ";

        $this->connection->exec($productsSql);
        $this->connection->exec($usersSql);
        $this->connection->exec($authSessionsSql);
        $this->seedUsers();
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
            'INSERT INTO users (email, password_hash, role, is_active, created_at) VALUES (:email, :password_hash, :role, TRUE, :created_at)'
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
