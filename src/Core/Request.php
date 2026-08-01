<?php

namespace App\Core;

class Request
{
    private string $method;
    private string $uri;
    private array $queryParams;
    private array $body;
    private array $headers;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Extract URI and strip query parameters
        $rawUri = $_SERVER['REQUEST_URI'] ?? '/';
        $parsedUrl = parse_url($rawUri);
        
        // Normalize path: strip trailing slashes, keep '/' if empty
        $path = $parsedUrl['path'] ?? '/';
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }
        $this->uri = $path;

        $this->queryParams = $_GET;
        $this->headers = $this->extractHeaders();
        $this->body = $this->parseBody();
    }

    /**
     * Parse raw request body (supporting JSON).
     */
    private function parseBody(): array
    {
        if (in_array($this->method, ['POST', 'PUT', 'PATCH'])) {
            $contentType = $this->headers['Content-Type'] ?? '';
            if (str_contains($contentType, 'application/json')) {
                $rawInput = file_get_contents('php://input');
                $decoded = json_decode($rawInput, true);
                return is_array($decoded) ? $decoded : [];
            }
            return $_POST;
        }
        return [];
    }

    /**
     * Extract HTTP request headers.
     */
    private function extractHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$name] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $key))));
                $headers[$name] = $value;
            }
        }
        return $headers;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function getQueryParam(string $key, mixed $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    public function getBody(): array
    {
        return $this->body;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->queryParams[$key] ?? $default;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name, mixed $default = null): mixed
    {
        return $this->headers[$name] ?? $default;
    }
}
