<?php

namespace App\Core\Database;

use PDO;
use Exception;

class SQLiteConnection implements DatabaseConnectionInterface
{
    private ?PDO $connection = null;
    private string $dbPath;

    public function __construct()
    {
        // Load configuration
        $config = require __DIR__ . '/../../../config.php';
        $path = $config['database']['sqlite']['path'] ?? null;

        if (!$path) {
            throw new Exception("SQLite database path is not defined in config.php");
        }

        $this->dbPath = $path;
    }

    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connection = new PDO("sqlite:" . $this->dbPath);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Initialize schema if not exists
            $this->initializeSchema();
        }

        return $this->connection;
    }

    private function initializeSchema(): void
    {
        $productsSql = "
            CREATE TABLE IF NOT EXISTS products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                description TEXT,
                price REAL NOT NULL,
                created_at TEXT NOT NULL
            );
        ";

        $usersSql = "
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                role TEXT NOT NULL,
                is_active INTEGER NOT NULL DEFAULT 1,
                created_at TEXT NOT NULL
            );
        ";

        $authSessionsSql = "
            CREATE TABLE IF NOT EXISTS auth_sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                jti TEXT NOT NULL UNIQUE,
                user_id INTEGER NOT NULL,
                email TEXT NOT NULL,
                refresh_token_hash TEXT NOT NULL,
                expires_at TEXT NOT NULL,
                revoked_at TEXT NULL,
                ip_address TEXT NULL,
                created_at TEXT NOT NULL
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
