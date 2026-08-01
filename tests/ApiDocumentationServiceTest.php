<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Application\Docs\ApiDocumentationService;

$service = new ApiDocumentationService();
$spec = $service->getOpenApiSpec();

if (($spec['openapi'] ?? '') !== '3.0.3') {
    throw new RuntimeException('Expected OpenAPI version 3.0.3');
}

if (!isset($spec['paths']['/api/auth/login'])) {
    throw new RuntimeException('Expected login endpoint in OpenAPI spec');
}

if (!isset($spec['paths']['/api/products'])) {
    throw new RuntimeException('Expected products endpoint in OpenAPI spec');
}

if (!isset($spec['components']['securitySchemes']['BearerAuth'])) {
    throw new RuntimeException('Expected BearerAuth security scheme in OpenAPI spec');
}

$productSecurity = $spec['paths']['/api/products']['get']['security'] ?? [];
$supportsBearer = in_array(['BearerAuth' => []], $productSecurity, true);

if (!$supportsBearer) {
    throw new RuntimeException('Expected product endpoints to support BearerAuth');
}

echo "docs-ok\n";
