<?php

namespace App\Application\Docs;

class ApiDocumentationService
{
    private array $endpoints = [];
    private const DEFAULT_BASE_URL = 'http://127.0.0.1:8040';

    public function __construct()
    {
        $this->registerEndpoints();
    }

    public function getDocumentation(?string $baseUrl = null): array
    {
        $resolvedBaseUrl = $baseUrl ?: self::DEFAULT_BASE_URL;

        return [
            'name' => 'Solid API',
            'version' => '1.0.0',
            'description' => 'Documentación y pruebas rápidas para la API REST',
            'base_url' => $resolvedBaseUrl,
            'endpoints' => $this->endpoints,
        ];
    }

    public function getOpenApiSpec(?string $baseUrl = null): array
    {
        $resolvedBaseUrl = $baseUrl ?: self::DEFAULT_BASE_URL;
        $paths = [];

        foreach ($this->endpoints as $endpoint) {
            $method = strtolower($endpoint['method']);
            $pathKey = $endpoint['path'];

            $pathItem = [
                $method => [
                    'summary' => $endpoint['summary'],
                    'description' => $endpoint['description'] ?? 'Endpoint documentado para pruebas rápidas.',
                    'tags' => $endpoint['tags'] ?? ['api'],
                    'operationId' => $this->buildOperationId($endpoint['method'], $endpoint['path']),
                    'parameters' => $this->buildParameters($endpoint['path']),
                    'responses' => $this->buildResponses($endpoint),
                    'security' => $endpoint['auth'] === 'required'
                        ? [
                            ['BearerAuth' => []],
                            ['ApiKeyAuth' => []],
                        ]
                        : [],
                ],
            ];

            if (!empty($endpoint['body'])) {
                $pathItem[$method]['requestBody'] = [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => $this->buildSchema($endpoint['body']),
                            'example' => $endpoint['example'] ?? $this->buildExample($endpoint['body']),
                        ],
                    ],
                ];
            }

            $paths[$pathKey] = array_merge($paths[$pathKey] ?? [], $pathItem);
        }

        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'Solid API',
                'version' => '1.0.0',
                'description' => 'Swagger/OpenAPI para probar los endpoints de la API desde un navegador.',
            ],
            'servers' => [
                ['url' => $resolvedBaseUrl],
            ],
            'paths' => $paths,
            'components' => [
                'securitySchemes' => [
                    'BearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT',
                        'description' => 'Pega aqui el access_token obtenido en /api/auth/login.',
                    ],
                    'ApiKeyAuth' => [
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => 'X-API-Key',
                        'description' => 'Opcion alternativa para pruebas rapidas con API key fija.',
                    ],
                ],
            ],
        ];
    }

    private function registerEndpoints(): void
    {
        $this->add('POST', '/api/auth/login', [
            'summary' => 'Iniciar sesión',
            'description' => 'Autentica un usuario y devuelve access/refresh tokens.',
            'body' => [
                'email' => 'string',
                'password' => 'string',
            ],
            'example' => [
                'email' => 'editor@example.com',
                'password' => 'Editor123!',
            ],
            'response' => [
                'access_token' => 'string',
                'refresh_token' => 'string',
                'role' => 'string',
            ],
            'auth' => 'none',
            'tags' => ['auth'],
        ]);

        $this->add('POST', '/api/auth/refresh', [
            'summary' => 'Renovar sesión',
            'description' => 'Renueva el access token usando un refresh token válido.',
            'body' => [
                'refresh_token' => 'string',
            ],
            'example' => [
                'refresh_token' => 'refresh-token-example',
            ],
            'response' => [
                'access_token' => 'string',
                'refresh_token' => 'string',
            ],
            'auth' => 'none',
            'tags' => ['auth'],
        ]);

        $this->add('POST', '/api/auth/logout', [
            'summary' => 'Cerrar sesión',
            'description' => 'Revoca un refresh token active.',
            'body' => [
                'refresh_token' => 'string',
            ],
            'example' => [
                'refresh_token' => 'refresh-token-example',
            ],
            'response' => [
                'message' => 'string',
            ],
            'auth' => 'none',
            'tags' => ['auth'],
        ]);

        $this->add('GET', '/api/products', [
            'summary' => 'Listar productos',
            'description' => 'Devuelve la lista de productos disponibles.',
            'response' => [
                ['id' => 'int', 'name' => 'string', 'price' => 'float', 'stock' => 'int'],
            ],
            'auth' => 'required',
            'tags' => ['products'],
        ]);

        $this->add('GET', '/api/products/{id}', [
            'summary' => 'Ver producto',
            'description' => 'Devuelve un producto por su identificador.',
            'response' => [
                'id' => 'int',
                'name' => 'string',
                'price' => 'float',
                'stock' => 'int',
            ],
            'auth' => 'required',
            'tags' => ['products'],
        ]);

        $this->add('POST', '/api/products', [
            'summary' => 'Crear producto',
            'description' => 'Crea un producto nuevo.',
            'body' => [
                'name' => 'string',
                'price' => 'float',
                'stock' => 'int',
            ],
            'example' => [
                'name' => 'Producto demo',
                'price' => 19.99,
                'stock' => 10,
            ],
            'response' => [
                'id' => 'int',
                'name' => 'string',
                'price' => 'float',
                'stock' => 'int',
            ],
            'auth' => 'required',
            'tags' => ['products'],
        ]);

        $this->add('PUT', '/api/products/{id}', [
            'summary' => 'Actualizar producto',
            'description' => 'Actualiza un producto existente.',
            'body' => [
                'name' => 'string',
                'price' => 'float',
                'stock' => 'int',
            ],
            'example' => [
                'name' => 'Producto actualizado',
                'price' => 24.99,
                'stock' => 5,
            ],
            'response' => [
                'id' => 'int',
                'name' => 'string',
                'price' => 'float',
                'stock' => 'int',
            ],
            'auth' => 'required',
            'tags' => ['products'],
        ]);

        $this->add('DELETE', '/api/products/{id}', [
            'summary' => 'Eliminar producto',
            'description' => 'Elimina un producto por su identificador.',
            'response' => [
                'message' => 'string',
            ],
            'auth' => 'required',
            'tags' => ['products'],
        ]);
    }

    private function add(string $method, string $path, array $meta): void
    {
        $this->endpoints[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'summary' => $meta['summary'] ?? 'Sin descripción',
            'description' => $meta['description'] ?? null,
            'body' => $meta['body'] ?? [],
            'example' => $meta['example'] ?? null,
            'response' => $meta['response'] ?? [],
            'auth' => $meta['auth'] ?? 'none',
            'tags' => $meta['tags'] ?? ['api'],
        ];
    }

    private function buildOperationId(string $method, string $path): string
    {
        $cleanPath = preg_replace('/[^a-zA-Z0-9]+/', ' ', $path);
        $cleanPath = trim($cleanPath);
        return strtolower($method) . '_' . str_replace(' ', '_', $cleanPath);
    }

    private function buildParameters(string $path): array
    {
        preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $path, $matches);
        $parameters = [];

        foreach ($matches[1] as $param) {
            $parameters[] = [
                'name' => $param,
                'in' => 'path',
                'required' => true,
                'schema' => ['type' => 'string'],
            ];
        }

        return $parameters;
    }

    private function buildResponses(array $endpoint): array
    {
        $responses = [];
        $statusCode = $endpoint['method'] === 'DELETE' ? '200' : '200';

        $responses[$statusCode] = [
            'description' => 'Respuesta exitosa',
            'content' => [
                'application/json' => [
                    'schema' => $this->buildSchema($endpoint['response']),
                ],
            ],
        ];

        $responses['400'] = [
            'description' => 'Datos inválidos',
        ];

        if ($endpoint['auth'] === 'required') {
            $responses['401'] = [
                'description' => 'No autorizado',
            ];
        }

        return $responses;
    }

    private function buildSchema(mixed $value): array
    {
        if (is_array($value)) {
            if ($this->isList($value)) {
                return [
                    'type' => 'array',
                    'items' => $this->buildSchema($value[0] ?? []),
                ];
            }

            $properties = [];
            foreach ($value as $key => $item) {
                $properties[$key] = $this->buildSchema($item);
            }

            return [
                'type' => 'object',
                'properties' => $properties,
            ];
        }

        return ['type' => $this->schemaType($value)];
    }

    private function buildExample(array $body): array
    {
        $example = [];
        foreach ($body as $key => $value) {
            $example[$key] = $this->exampleValue($value);
        }

        return $example;
    }

    private function exampleValue(mixed $value): mixed
    {
        if ($value === 'int' || $value === 'integer') {
            return 1;
        }

        if ($value === 'float' || $value === 'double' || $value === 'number') {
            return 1.5;
        }

        if ($value === 'bool' || $value === 'boolean') {
            return true;
        }

        if ($value === 'array') {
            return [];
        }

        if (is_array($value)) {
            return $this->buildExample($value);
        }

        return 'string';
    }

    private function schemaType(mixed $value): string
    {
        if ($value === 'int' || $value === 'integer') {
            return 'integer';
        }

        if ($value === 'float' || $value === 'double' || $value === 'number') {
            return 'number';
        }

        if ($value === 'bool' || $value === 'boolean') {
            return 'boolean';
        }

        if ($value === 'array') {
            return 'array';
        }

        return 'string';
    }

    private function isList(array $value): bool
    {
        return array_is_list($value);
    }
}
