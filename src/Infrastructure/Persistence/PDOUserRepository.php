<?php

namespace App\Infrastructure\Persistence;

use App\Core\Database\DatabaseConnectionInterface;
use App\Domain\Repositories\UserRepositoryInterface;
use PDO;

class PDOUserRepository implements UserRepositoryInterface
{
    private PDO $db;

    public function __construct(DatabaseConnectionInterface $dbConnection)
    {
        $this->db = $dbConnection->getConnection();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT id, email, password_hash, role, is_active FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            return null;
        }

        return [
            'id' => (int) $user['id'],
            'email' => (string) $user['email'],
            'password_hash' => (string) $user['password_hash'],
            'role' => (string) $user['role'],
            'is_active' => $this->toBool($user['is_active']),
        ];
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 't', 'yes'], true);
    }
}
