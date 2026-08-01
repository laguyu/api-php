<?php

namespace App\Core;

use Exception;

class Router
{
    private array $routes = [];

    public function hasRoute(string $method, string $uri): bool
    {
        if (strtoupper($method) === 'OPTIONS') {
            return true;
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== strtoupper($method)) {
                continue;
            }

            $pattern = $this->convertToRegex($route['path']);
            if (preg_match($pattern, $uri)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add a route.
     */
    public function add(string $method, string $path, mixed $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler
        ];
    }

    public function get(string $path, mixed $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, mixed $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function put(string $path, mixed $handler): void
    {
        $this->add('PUT', $path, $handler);
    }

    public function delete(string $path, mixed $handler): void
    {
        $this->add('DELETE', $path, $handler);
    }

    /**
     * Dispatch the current request.
     */
    public function dispatch(Request $request, Container $container): Response
    {
        $requestMethod = $request->getMethod();
        $requestUri = $request->getUri();

        // Handle OPTIONS request for preflight CORS checking
        if ($requestMethod === 'OPTIONS') {
            return Response::empty(200);
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $requestMethod) {
                continue;
            }

            // Convert path expression like /api/products/{id} to regex
            $pattern = $this->convertToRegex($route['path']);

            if (preg_match($pattern, $requestUri, $matches)) {
                // Extract named parameter matches
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                return $this->executeHandler($route['handler'], $params, $request, $container);
            }
        }

        return Response::json(['error' => 'Not Found', 'message' => "Route {$requestMethod} {$requestUri} not found."], 404);
    }

    /**
     * Convert simple route expression to regex.
     */
    private function convertToRegex(string $path): string
    {
        // Replace {paramName} with named capture group (?P<paramName>[^/]+)
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        
        // Escape standard slashes
        return '#^' . $pattern . '$#s';
    }

    /**
     * Execute route handler (can be callable, or array [Controller, action]).
     */
    private function executeHandler(mixed $handler, array $params, Request $request, Container $container): Response
    {
        if (is_callable($handler)) {
            return call_user_func_array($handler, [$request, ...array_values($params)]);
        }

        if (is_array($handler) && count($handler) === 2) {
            [$controllerClass, $method] = $handler;

            if ($container->has($controllerClass) || class_exists($controllerClass)) {
                $controller = $container->get($controllerClass);

                if (method_exists($controller, $method)) {
                    // Inject request first, followed by route parameters
                    return call_user_func_array([$controller, $method], [$request, ...array_values($params)]);
                }

                throw new Exception("Method {$method} not found in controller {$controllerClass}.");
            }

            throw new Exception("Controller class {$controllerClass} not found.");
        }

        throw new Exception("Invalid route handler defined.");
    }
}
