<?php

namespace App\Application\Services;

use App\Core\Exceptions\HttpException;
use App\Domain\Repositories\AuthSessionRepositoryInterface;
use App\Domain\Repositories\UserRepositoryInterface;

class AuthenticationService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private AuthSessionRepositoryInterface $sessionRepository,
        private AuthService $authService
    ) {
    }

    public function login(string $email, string $password, ?string $ipAddress = null): array
    {
        $this->sessionRepository->cleanupExpired();

        $user = $this->userRepository->findByEmail($email);
        if ($user === null || !$user['is_active']) {
            throw new HttpException('Invalid credentials.', 401);
        }

        if (!password_verify($password, $user['password_hash'])) {
            throw new HttpException('Invalid credentials.', 401);
        }

        $tokens = $this->authService->issueTokenPair(
            (string) $user['id'],
            (string) $user['email'],
            (string) $user['role']
        );

        $this->sessionRepository->createSession(
            (string) $tokens['refresh_jti'],
            (int) $user['id'],
            (string) $user['email'],
            hash('sha256', (string) $tokens['refresh_token']),
            $this->authService->getRefreshExpiryDateTime(),
            $ipAddress
        );

        unset($tokens['refresh_jti']);

        return $tokens;
    }

    public function refresh(string $refreshToken, ?string $ipAddress = null): array
    {
        $this->sessionRepository->cleanupExpired();

        $payload = $this->authService->decodeJwtPayload($refreshToken);
        if ($payload === null || ($payload['type'] ?? '') !== 'refresh') {
            throw new HttpException('Invalid refresh token.', 401);
        }

        $jti = (string) ($payload['jti'] ?? '');
        if ($jti === '') {
            throw new HttpException('Invalid refresh token.', 401);
        }

        $session = $this->sessionRepository->findActiveByJti($jti);
        if ($session === null) {
            throw new HttpException('Invalid refresh token.', 401);
        }

        if (!hash_equals((string) $session['refresh_token_hash'], hash('sha256', $refreshToken))) {
            throw new HttpException('Invalid refresh token.', 401);
        }

        $email = (string) ($payload['email'] ?? '');
        if ($email === '') {
            throw new HttpException('Invalid refresh token.', 401);
        }

        $user = $this->userRepository->findByEmail($email);
        if ($user === null || !$user['is_active']) {
            throw new HttpException('Invalid refresh token.', 401);
        }

        $this->sessionRepository->revokeByJti($jti);

        $tokens = $this->authService->issueTokenPair(
            (string) $user['id'],
            (string) $user['email'],
            (string) $user['role']
        );

        $this->sessionRepository->createSession(
            (string) $tokens['refresh_jti'],
            (int) $user['id'],
            (string) $user['email'],
            hash('sha256', (string) $tokens['refresh_token']),
            $this->authService->getRefreshExpiryDateTime(),
            $ipAddress
        );

        unset($tokens['refresh_jti']);

        return $tokens;
    }

    public function logout(string $refreshToken): void
    {
        $this->sessionRepository->cleanupExpired();

        $payload = $this->authService->decodeJwtPayload($refreshToken);
        if ($payload === null || ($payload['type'] ?? '') !== 'refresh') {
            throw new HttpException('Invalid refresh token.', 401);
        }

        $this->sessionRepository->revokeByHash(hash('sha256', $refreshToken));
    }
}
