<?php

namespace App\Application\Services;

class AuthService
{
    private const ACCESS_TTL_SECONDS = 3600;
    private const REFRESH_TTL_SECONDS = 604800;

    public function __construct(private string $apiKey = '', private string $jwtSecret = '')
    {
    }

    public function createJwtToken(
        string $subject,
        int $ttlSeconds = self::ACCESS_TTL_SECONDS,
        string $role = 'viewer',
        array $extraClaims = []
    ): string
    {
        $issuedAt = time();
        $expiration = $issuedAt + $ttlSeconds;

        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $payload = [
            'iss' => 'solid-api',
            'sub' => $subject,
            'iat' => $issuedAt,
            'exp' => $expiration,
            'role' => $role,
        ];

        foreach ($extraClaims as $key => $value) {
            $payload[(string) $key] = $value;
        }

        $encodedHeader = self::base64UrlEncode(json_encode($header, JSON_UNESCAPED_UNICODE));
        $encodedPayload = self::base64UrlEncode(json_encode($payload, JSON_UNESCAPED_UNICODE));
        $signature = self::base64UrlEncode(hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $this->jwtSecret, true));

        return $encodedHeader . '.' . $encodedPayload . '.' . $signature;
    }

    public function isValidApiKey(?string $providedKey): bool
    {
        if ($providedKey === null || trim((string) $providedKey) === '') {
            return false;
        }

        if ($this->isMasterApiKey($providedKey)) {
            return true;
        }

        return $this->isValidJwtToken($providedKey);
    }

    public function isValidJwtToken(?string $token): bool
    {
        return $this->decodeJwtPayload($token) !== null;
    }

    public function decodeJwtPayload(?string $token): ?array
    {
        if ($this->jwtSecret === '' || !is_string($token) || $token === '') {
            return null;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $providedSignature] = $parts;
        $expectedSignature = self::base64UrlEncode(hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $this->jwtSecret, true));

        if (!hash_equals($expectedSignature, $providedSignature)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($encodedPayload), true);
        if (!is_array($payload)) {
            return null;
        }

        if (!isset($payload['exp']) || !is_numeric($payload['exp'])) {
            return null;
        }

        if ((int) $payload['exp'] <= time()) {
            return null;
        }

        return $payload;
    }

    public function isMasterApiKey(?string $providedKey): bool
    {
        if ($providedKey === null || trim((string) $providedKey) === '') {
            return false;
        }

        return $this->apiKey !== '' && hash_equals($this->apiKey, $providedKey);
    }

    public function issueTokenPair(string $subject, string $email, string $role): array
    {
        $refreshJti = bin2hex(random_bytes(16));

        $accessToken = $this->createJwtToken($subject, self::ACCESS_TTL_SECONDS, $role, [
            'email' => $email,
            'type' => 'access',
        ]);

        $refreshToken = $this->createJwtToken($subject, self::REFRESH_TTL_SECONDS, $role, [
            'email' => $email,
            'type' => 'refresh',
            'jti' => $refreshJti,
        ]);

        return [
            'token_type' => 'Bearer',
            'access_token' => $accessToken,
            'expires_in' => self::ACCESS_TTL_SECONDS,
            'refresh_token' => $refreshToken,
            'refresh_expires_in' => self::REFRESH_TTL_SECONDS,
            'role' => $role,
            'email' => $email,
            'refresh_jti' => $refreshJti,
        ];
    }

    public function getRefreshExpiryDateTime(): string
    {
        return date('Y-m-d H:i:s', time() + self::REFRESH_TTL_SECONDS);
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'), true) ?: '';
    }
}
