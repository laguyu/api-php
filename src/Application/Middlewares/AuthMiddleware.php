<?php

namespace App\Application\Middlewares;

use App\Application\Services\AuthService;
use App\Application\Services\RoleService;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;

class AuthMiddleware
{
    private array $publicRoutes = [
        'GET:/api/docs',
        'GET:/api/docs/openapi.json',
        'GET:/api/docs/swagger',
        'GET:/api/docs/swagger-ui.html',
        'POST:/api/auth/login',
        'POST:/api/auth/refresh',
        'POST:/api/auth/logout',
    ];

    public function __construct(
        private AuthService $authService,
        private Logger $logger,
        private ?RoleService $roleService = null
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        if ($request->getMethod() === 'OPTIONS') {
            return $next($request);
        }

        if ($this->isPublicRoute($request)) {
            return $next($request);
        }

        $apiKey = $this->resolveApiKey($request);

        if (!$this->authService->isValidApiKey($apiKey)) {
            $this->logger->error('Unauthorized access attempt.', [
                'uri' => $request->getUri(),
                'method' => $request->getMethod(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);

            return Response::json([
                'error' => 'Unauthorized',
                'message' => 'A valid X-API-Key or Authorization bearer token is required.'
            ], 401);
        }

        if ($request->getMethod() === 'POST' || $request->getMethod() === 'PUT' || $request->getMethod() === 'DELETE') {
            $roleService = $this->roleService;
            if ($roleService !== null && !$roleService->canAccess($apiKey, 'products:write')) {
                return Response::json([
                    'error' => 'Forbidden',
                    'message' => 'This role does not have permission to modify products.'
                ], 403);
            }
        }

        return $next($request);
    }

    private function isPublicRoute(Request $request): bool
    {
        $key = $request->getMethod() . ':' . $request->getUri();
        return in_array($key, $this->publicRoutes, true);
    }

    private function resolveApiKey(Request $request): ?string
    {
        $header = $request->getHeader('X-Api-Key');
        if (is_string($header) && $header !== '') {
            return $header;
        }

        $authorization = $request->getHeader('Authorization');
        if (is_string($authorization) && str_starts_with($authorization, 'Bearer ')) {
            return substr($authorization, 7);
        }

        return null;
    }
}
