<?php

namespace App\Domain\Repositories;

interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?array;
}
