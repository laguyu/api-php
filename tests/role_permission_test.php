<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Application\Services\AuthService;
use App\Application\Services\RoleService;

function assertRole(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$auth = new AuthService('super-secret', 'test-secret');
$roleService = new RoleService($auth);

$viewerToken = $auth->createJwtToken('viewer-user', 3600, 'viewer');
$adminToken = $auth->createJwtToken('admin-user', 3600, 'admin');

assertRole($roleService->canAccess($viewerToken, 'products:read'), 'Viewer should be able to read products.');
assertRole(!$roleService->canAccess($viewerToken, 'products:write'), 'Viewer should not be able to write products.');
assertRole($roleService->canAccess($adminToken, 'products:write'), 'Admin should be able to write products.');

echo "Role permission tests passed.\n";
