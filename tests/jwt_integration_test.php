<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Application\Middlewares\AuthMiddleware;
use App\Application\Services\AuthService;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;

function assertTrueJwt(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function runJwtTest(string $name, callable $callback): void
{
    try {
        $callback();
        echo "PASS: {$name}\n";
    } catch (Throwable $e) {
        echo "FAIL: {$name} - {$e->getMessage()}\n";
        exit(1);
    }
}

runJwtTest('JWT tokens are created and validated', function () {
    $auth = new AuthService('super-secret', 'test-secret');
    $token = $auth->createJwtToken('demo-user');

    assertTrueJwt($auth->isValidApiKey($token), 'JWT should validate with the configured secret.');
});

runJwtTest('Auth middleware allows requests with a valid JWT', function () {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/api/products';
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . (new AuthService('super-secret', 'test-secret'))->createJwtToken('demo-user');
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

    $auth = new AuthService('super-secret', 'test-secret');
    $middleware = new AuthMiddleware($auth, new Logger(__DIR__ . '/../logs/jwt-test.log'));
    $request = new Request();

    ob_start();
    $response = $middleware->handle($request, function (Request $request): Response {
        return Response::json(['ok' => true]);
    });
    $response->send();
    $output = ob_get_clean();
    $decoded = json_decode($output, true);

    assertTrueJwt(is_array($decoded) && ($decoded['ok'] ?? false) === true, 'Valid JWT should pass through middleware.');
});

echo "JWT integration tests passed.\n";
