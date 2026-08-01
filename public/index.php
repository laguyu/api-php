<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Application\Controllers\AuthController;
use App\Application\Controllers\DocsController;
use App\Application\Controllers\ProductController;
use App\Application\Middlewares\AuthMiddleware;
use App\Application\Services\AuthService;
use App\Application\Services\LoginRateLimiter;
use App\Core\Container;
use App\Core\Database\DatabaseConnectionInterface;
use App\Core\Database\MySQLConnection;
use App\Core\Database\PostgreSQLConnection;
use App\Core\Database\SQLiteConnection;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Domain\Repositories\ProductRepositoryInterface;
use App\Domain\Repositories\AuthSessionRepositoryInterface;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Infrastructure\Persistence\PDOAuthSessionRepository;
use App\Infrastructure\Persistence\PDOProductRepository;
use App\Infrastructure\Persistence\PDOUserRepository;
use App\Infrastructure\Persistence\ProductMapper;

$container = new Container();

$config = require __DIR__ . '/../config.php';
$driver = $config['active_driver'] ?? 'sqlite';

$driverMap = [
    'sqlite' => SQLiteConnection::class,
    'mysql' => MySQLConnection::class,
    'pgsql' => PostgreSQLConnection::class,
];

$connectionClass = $driverMap[$driver] ?? SQLiteConnection::class;

$container->singleton(DatabaseConnectionInterface::class, $connectionClass);
$container->singleton(AuthService::class, function () use ($config): AuthService {
    return new AuthService($config['auth']['api_key'] ?? '', $config['auth']['jwt_secret'] ?? '');
});
$container->singleton(LoginRateLimiter::class, function () use ($config): LoginRateLimiter {
    $rateLimit = $config['auth']['rate_limit'] ?? [];

    return new LoginRateLimiter(
        (int) ($rateLimit['max_attempts'] ?? 5),
        (int) ($rateLimit['window_seconds'] ?? 900),
        (int) ($rateLimit['block_seconds'] ?? 900)
    );
});
$container->singleton(Logger::class, Logger::class);
$container->singleton(ProductMapper::class, ProductMapper::class);
$container->bind(ProductRepositoryInterface::class, PDOProductRepository::class);
$container->bind(AuthSessionRepositoryInterface::class, PDOAuthSessionRepository::class);
$container->bind(UserRepositoryInterface::class, PDOUserRepository::class);

$request = new Request();
$router = new Router();

$router->get('/api/docs', [DocsController::class, 'index']);
$router->get('/api/docs/openapi.json', [DocsController::class, 'openApi']);
$router->get('/api/docs/swagger', [DocsController::class, 'swagger']);
$router->get('/api/docs/swagger-ui.html', [DocsController::class, 'swagger']);
$router->get('/api/products', [ProductController::class, 'index']);
$router->get('/api/products/{id}', [ProductController::class, 'show']);
$router->post('/api/products', [ProductController::class, 'store']);
$router->put('/api/products/{id}', [ProductController::class, 'update']);
$router->delete('/api/products/{id}', [ProductController::class, 'destroy']);
$router->post('/api/auth/login', [AuthController::class, 'login']);
$router->post('/api/auth/refresh', [AuthController::class, 'refresh']);
$router->post('/api/auth/logout', [AuthController::class, 'logout']);

try {
    if (!$router->hasRoute($request->getMethod(), $request->getUri())) {
        Response::json([
            'error' => 'Not Found',
            'message' => sprintf('Route %s %s not found.', $request->getMethod(), $request->getUri())
        ], 404)->send();
        return;
    }

    $authMiddleware = $container->get(AuthMiddleware::class);
    $response = $authMiddleware->handle($request, function (Request $request) use ($router, $container): Response {
        return $router->dispatch($request, $container);
    });
    $response->send();
} catch (Throwable $e) {
    $logger = $container->get(Logger::class);
    $logger->error($e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);

    $response = Response::json([
        'error' => 'Internal Server Error',
        'message' => $e->getMessage()
    ], 500);
    $response->send();
}
