<?php

namespace App\Core;

class Response
{
    private int $statusCode;
    private array $headers = [];
    private mixed $content;

    public function __construct(mixed $content = '', int $statusCode = 200)
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->setHeader('Content-Type', 'application/json; charset=UTF-8');
    }

    public static function json(mixed $data, int $statusCode = 200): self
    {
        return new self($data, $statusCode);
    }

    public static function html(string $content, int $statusCode = 200): self
    {
        $response = new self($content, $statusCode);
        $response->setHeader('Content-Type', 'text/html; charset=UTF-8');

        return $response;
    }

    public static function empty(int $statusCode = 204): self
    {
        return new self('', $statusCode);
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function send(): void
    {
        // Prevent sending headers if they're already sent
        if (!headers_sent()) {
            http_response_code($this->statusCode);
            
            // CORS Headers for API
            if (!isset($this->headers['Access-Control-Allow-Origin'])) {
                $this->headers['Access-Control-Allow-Origin'] = '*';
                $this->headers['Access-Control-Allow-Methods'] = 'GET, POST, PUT, DELETE, OPTIONS';
                $this->headers['Access-Control-Allow-Headers'] = 'Content-Type, Authorization, X-Requested-With, X-Api-Key, X-API-Key, Accept, Origin';
            }

            foreach ($this->headers as $name => $value) {
                header("$name: $value");
            }
        }

        // Handle preflight OPTIONS requests gracefully
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            return;
        }

        if (is_array($this->content) || is_object($this->content)) {
            echo json_encode($this->content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            echo $this->content;
        }
    }
}
