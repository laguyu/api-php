<?php

namespace App\Application\Controllers;

use App\Application\Docs\ApiDocumentationService;
use App\Core\Request;
use App\Core\Response;

class DocsController
{
    public function __construct(private ApiDocumentationService $documentationService)
    {
    }

    public function index(Request $request): Response
    {
        return Response::json($this->documentationService->getDocumentation($this->resolveBaseUrl($request)));
    }

    public function openApi(Request $request): Response
    {
        return Response::json($this->documentationService->getOpenApiSpec($this->resolveBaseUrl($request)));
    }

    public function swagger(Request $request): Response
    {
        return Response::html($this->renderSwaggerUi());
    }

    private function renderSwaggerUi(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Swagger UI - Solid API</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.17.2/swagger-ui.css" />
    <style>
        body { margin: 0; background: #f7f9fc; }
        #swagger-ui { max-width: 1200px; margin: 0 auto; padding: 20px; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5.17.2/swagger-ui-bundle.js"></script>
    <script>
        window.onload = () => {
            SwaggerUIBundle({
                url: '/api/docs/openapi.json',
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [SwaggerUIBundle.presets.apis],
            });
        };
    </script>
</body>
</html>
HTML;
    }

    private function resolveBaseUrl(Request $request): string
    {
        $forwardedProto = $request->getHeader('X-Forwarded-Proto');
        $scheme = is_string($forwardedProto) && $forwardedProto !== ''
            ? trim(explode(',', $forwardedProto)[0])
            : ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');

        $forwardedHost = $request->getHeader('X-Forwarded-Host');
        $host = is_string($forwardedHost) && $forwardedHost !== ''
            ? trim(explode(',', $forwardedHost)[0])
            : ($request->getHeader('Host') ?? ($_SERVER['HTTP_HOST'] ?? '127.0.0.1:8040'));

        return $scheme . '://' . $host;
    }
}
