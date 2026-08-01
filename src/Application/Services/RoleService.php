<?php

namespace App\Application\Services;

class RoleService
{
    public function __construct(private AuthService $authService)
    {
    }

    public function getPermissions(?string $token): array
    {
        if ($token === null || $token === '') {
            return [];
        }

        if ($this->authService->isMasterApiKey($token)) {
            return ['products:read', 'products:write'];
        }

        if ($this->authService->isValidApiKey($token)) {
            $payload = $this->authService->decodeJwtPayload($token);
            if (is_array($payload) && isset($payload['role'])) {
                return $this->mapRoleToPermissions((string) $payload['role']);
            }
        }

        return [];
    }

    public function canAccess(?string $token, string $permission): bool
    {
        return in_array($permission, $this->getPermissions($token), true);
    }

    private function mapRoleToPermissions(string $role): array
    {
        return match ($role) {
            'admin' => ['products:read', 'products:write'],
            'editor' => ['products:read', 'products:write'],
            'viewer' => ['products:read'],
            default => []
        };
    }
}
