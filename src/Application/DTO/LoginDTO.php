<?php

namespace App\Application\DTO;

use InvalidArgumentException;

readonly class LoginDTO
{
    public function __construct(public string $email, public string $password)
    {
    }

    public static function fromArray(array $data): self
    {
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('A valid email is required.');
        }

        if ($password === '') {
            throw new InvalidArgumentException('Password is required.');
        }

        return new self($email, $password);
    }
}
