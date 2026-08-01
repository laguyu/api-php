<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Application\DTO\CreateProductDTO;
use App\Application\Services\AuthService;

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function runTest(string $name, callable $callback): void
{
    try {
        $callback();
        echo "PASS: {$name}\n";
    } catch (Throwable $e) {
        echo "FAIL: {$name} - {$e->getMessage()}\n";
        exit(1);
    }
}

runTest('CreateProductDTO sanitizes and validates input', function () {
    $dto = CreateProductDTO::fromArray([
        'name' => '  Laptop  ',
        'description' => '<script>alert(1)</script> Product description',
        'price' => '19.99'
    ]);

    assertTrue($dto->name === 'Laptop', 'Name should be trimmed.');
    assertTrue($dto->description === 'Product description', 'HTML should be stripped.');
    assertTrue($dto->price === 19.99, 'Price should be cast to float.');
});

runTest('CreateProductDTO rejects invalid price', function () {
    try {
        CreateProductDTO::fromArray(['name' => 'Book', 'price' => -1]);
        throw new RuntimeException('Expected invalid price to throw.');
    } catch (InvalidArgumentException $e) {
        assertTrue(str_contains($e->getMessage(), 'price'), 'Expected price validation message.');
    }
});

runTest('AuthService validates configured API keys', function () {
    $auth = new AuthService('super-secret');
    assertTrue($auth->isValidApiKey('super-secret'), 'Configured API key should validate.');
    assertTrue(!$auth->isValidApiKey('wrong'), 'Wrong key should fail.');
});

echo "All tests passed.\n";
