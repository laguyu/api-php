<?php

namespace App\Application\Services;

class LoginRateLimiter
{
    public function __construct(
        private int $maxAttempts = 5,
        private int $windowSeconds = 900,
        private int $blockSeconds = 900,
        private string $storagePath = __DIR__ . '/../../../storage/login_rate_limit.json'
    ) {
    }

    public function tooManyAttempts(string $ip): bool
    {
        $store = $this->readStore();
        $now = time();
        $entry = $store[$ip] ?? null;

        if ($entry === null) {
            return false;
        }

        $blockedUntil = (int) ($entry['blocked_until'] ?? 0);
        if ($blockedUntil > $now) {
            return true;
        }

        $firstAttempt = (int) ($entry['first_attempt_at'] ?? 0);
        if ($firstAttempt > 0 && ($now - $firstAttempt) > $this->windowSeconds) {
            unset($store[$ip]);
            $this->writeStore($store);
            return false;
        }

        return false;
    }

    public function hit(string $ip): void
    {
        $store = $this->readStore();
        $now = time();

        if (!isset($store[$ip])) {
            $store[$ip] = [
                'count' => 0,
                'first_attempt_at' => $now,
                'blocked_until' => 0,
            ];
        }

        $entry = $store[$ip];

        if (($now - (int) ($entry['first_attempt_at'] ?? $now)) > $this->windowSeconds) {
            $entry['count'] = 0;
            $entry['first_attempt_at'] = $now;
            $entry['blocked_until'] = 0;
        }

        $entry['count'] = (int) ($entry['count'] ?? 0) + 1;

        if ($entry['count'] >= $this->maxAttempts) {
            $entry['blocked_until'] = $now + $this->blockSeconds;
        }

        $store[$ip] = $entry;
        $this->writeStore($store);
    }

    public function clear(string $ip): void
    {
        $store = $this->readStore();
        if (isset($store[$ip])) {
            unset($store[$ip]);
            $this->writeStore($store);
        }
    }

    private function readStore(): array
    {
        if (!file_exists($this->storagePath)) {
            return [];
        }

        $raw = file_get_contents($this->storagePath);
        if ($raw === false || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function writeStore(array $store): void
    {
        $directory = dirname($this->storagePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($this->storagePath, json_encode($store, JSON_UNESCAPED_UNICODE), LOCK_EX);
    }
}
