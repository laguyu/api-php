<?php

namespace App\Application\DTO;

use InvalidArgumentException;

readonly class RefreshTokenDTO
{
    public function __construct(public string $refreshToken)
    {
    }

    public static function fromArray(array $data): self
    {
        $refreshToken = trim((string) ($data['refresh_token'] ?? ''));

        if ($refreshToken === '') {
            throw new InvalidArgumentException('refresh_token is required.');
        }

        return new self($refreshToken);
    }
}
