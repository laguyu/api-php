<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Application\Services\AuthService;
use App\Application\Services\AuthenticationService;
use App\Core\Database\SQLiteConnection;
use App\Core\Exceptions\HttpException;
use App\Infrastructure\Persistence\PDOAuthSessionRepository;
use App\Infrastructure\Persistence\PDOUserRepository;

function assertAuth(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function runAuthTest(string $name, callable $callback): void
{
    try {
        $callback();
        echo "PASS: {$name}\n";
    } catch (Throwable $e) {
        echo "FAIL: {$name} - {$e->getMessage()}\n";
        exit(1);
    }
}

$authService = new AuthService('super-secret', 'test-secret');
$connection = new SQLiteConnection();
$userRepository = new PDOUserRepository($connection);
$sessionRepository = new PDOAuthSessionRepository($connection);
$authenticationService = new AuthenticationService($userRepository, $sessionRepository, $authService);

runAuthTest('Login returns access and refresh tokens', function () use ($authenticationService) {
    $tokens = $authenticationService->login('admin@example.com', 'Admin123!');

    assertAuth(isset($tokens['access_token']) && is_string($tokens['access_token']), 'access_token is required.');
    assertAuth(isset($tokens['refresh_token']) && is_string($tokens['refresh_token']), 'refresh_token is required.');
    assertAuth(($tokens['role'] ?? '') === 'admin', 'Expected admin role for default admin user.');
});

runAuthTest('Refresh returns a new token pair', function () use ($authenticationService) {
    $tokens = $authenticationService->login('editor@example.com', 'Editor123!');
    $refreshed = $authenticationService->refresh($tokens['refresh_token']);

    assertAuth(isset($refreshed['access_token']) && is_string($refreshed['access_token']), 'Refreshed access token is required.');
    assertAuth(isset($refreshed['refresh_token']) && is_string($refreshed['refresh_token']), 'Refreshed refresh token is required.');
    assertAuth(($refreshed['role'] ?? '') === 'editor', 'Expected editor role after refresh.');

    try {
        $authenticationService->refresh($tokens['refresh_token']);
        throw new RuntimeException('Expected used refresh token to be invalidated.');
    } catch (HttpException $e) {
        assertAuth($e->getStatusCode() === 401, 'Expected 401 for reused refresh token.');
    }
});

runAuthTest('Login rejects invalid credentials', function () use ($authenticationService) {
    try {
        $authenticationService->login('admin@example.com', 'WrongPassword');
        throw new RuntimeException('Expected invalid credentials to throw.');
    } catch (HttpException $e) {
        assertAuth($e->getStatusCode() === 401, 'Expected 401 for invalid credentials.');
    }
});

runAuthTest('Logout revokes refresh token', function () use ($authenticationService) {
    $tokens = $authenticationService->login('viewer@example.com', 'Viewer123!');
    $authenticationService->logout($tokens['refresh_token']);

    try {
        $authenticationService->refresh($tokens['refresh_token']);
        throw new RuntimeException('Expected revoked refresh token to be rejected.');
    } catch (HttpException $e) {
        assertAuth($e->getStatusCode() === 401, 'Expected 401 for revoked refresh token.');
    }
});

echo "Auth integration tests passed.\n";
