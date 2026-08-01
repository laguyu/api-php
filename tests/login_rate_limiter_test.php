<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Application\Services\LoginRateLimiter;

function assertLimit(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function runLimitTest(string $name, callable $callback): void
{
    try {
        $callback();
        echo "PASS: {$name}\n";
    } catch (Throwable $e) {
        echo "FAIL: {$name} - {$e->getMessage()}\n";
        exit(1);
    }
}

$storagePath = __DIR__ . '/../storage/login_rate_limit_test.json';
if (file_exists($storagePath)) {
    unlink($storagePath);
}

$limiter = new LoginRateLimiter(3, 60, 60, $storagePath);
$ip = '127.0.0.10';

runLimitTest('Rate limiter blocks after max attempts', function () use ($limiter, $ip) {
    assertLimit(!$limiter->tooManyAttempts($ip), 'Should not be blocked initially.');
    $limiter->hit($ip);
    $limiter->hit($ip);
    assertLimit(!$limiter->tooManyAttempts($ip), 'Should not be blocked before max attempts.');
    $limiter->hit($ip);
    assertLimit($limiter->tooManyAttempts($ip), 'Should be blocked after reaching max attempts.');
});

runLimitTest('Rate limiter clear removes block for IP', function () use ($limiter, $ip) {
    $limiter->clear($ip);
    assertLimit(!$limiter->tooManyAttempts($ip), 'Should be cleared after successful login.');
});

if (file_exists($storagePath)) {
    unlink($storagePath);
}

echo "Login rate limiter tests passed.\n";
