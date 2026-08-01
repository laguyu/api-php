<?php

namespace App\Infrastructure\Persistence;

use App\Core\Database\DatabaseConnectionInterface;
use App\Domain\Repositories\AuthSessionRepositoryInterface;
use PDO;

class PDOAuthSessionRepository implements AuthSessionRepositoryInterface
{
    private PDO $db;

    public function __construct(DatabaseConnectionInterface $dbConnection)
    {
        $this->db = $dbConnection->getConnection();
    }

    public function createSession(
        string $jti,
        int $userId,
        string $email,
        string $refreshTokenHash,
        string $expiresAt,
        ?string $ipAddress = null
    ): void {
        $stmt = $this->db->prepare(
            'INSERT INTO auth_sessions (jti, user_id, email, refresh_token_hash, expires_at, ip_address, created_at)
             VALUES (:jti, :user_id, :email, :refresh_token_hash, :expires_at, :ip_address, :created_at)'
        );

        $stmt->execute([
            'jti' => $jti,
            'user_id' => $userId,
            'email' => $email,
            'refresh_token_hash' => $refreshTokenHash,
            'expires_at' => $expiresAt,
            'ip_address' => $ipAddress,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function findActiveByJti(string $jti): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, jti, user_id, email, refresh_token_hash, expires_at, revoked_at
             FROM auth_sessions
             WHERE jti = :jti AND revoked_at IS NULL AND expires_at > :now
             LIMIT 1'
        );

        $stmt->execute([
            'jti' => $jti,
            'now' => date('Y-m-d H:i:s'),
        ]);

        $session = $stmt->fetch();
        if (!$session) {
            return null;
        }

        return [
            'id' => (int) $session['id'],
            'jti' => (string) $session['jti'],
            'user_id' => (int) $session['user_id'],
            'email' => (string) $session['email'],
            'refresh_token_hash' => (string) $session['refresh_token_hash'],
            'expires_at' => (string) $session['expires_at'],
            'revoked_at' => $session['revoked_at'],
        ];
    }

    public function revokeByJti(string $jti): void
    {
        $stmt = $this->db->prepare('UPDATE auth_sessions SET revoked_at = :revoked_at WHERE jti = :jti AND revoked_at IS NULL');
        $stmt->execute([
            'jti' => $jti,
            'revoked_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function revokeByHash(string $refreshTokenHash): void
    {
        $stmt = $this->db->prepare(
            'UPDATE auth_sessions
             SET revoked_at = :revoked_at
             WHERE refresh_token_hash = :refresh_token_hash AND revoked_at IS NULL'
        );

        $stmt->execute([
            'refresh_token_hash' => $refreshTokenHash,
            'revoked_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function cleanupExpired(): int
    {
        $stmt = $this->db->prepare('DELETE FROM auth_sessions WHERE expires_at <= :now');
        $stmt->execute(['now' => date('Y-m-d H:i:s')]);

        return $stmt->rowCount();
    }
}
