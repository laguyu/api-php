<?php

namespace App\Domain\Repositories;

interface AuthSessionRepositoryInterface
{
    public function createSession(
        string $jti,
        int $userId,
        string $email,
        string $refreshTokenHash,
        string $expiresAt,
        ?string $ipAddress = null
    ): void;

    public function findActiveByJti(string $jti): ?array;

    public function revokeByJti(string $jti): void;

    public function revokeByHash(string $refreshTokenHash): void;

    public function cleanupExpired(): int;
}
