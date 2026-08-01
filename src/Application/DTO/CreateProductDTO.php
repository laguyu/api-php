<?php

namespace App\Application\DTO;

use InvalidArgumentException;

readonly class CreateProductDTO
{
    public function __construct(
        public string $name,
        public ?string $description,
        public float $price
    ) {}

    public static function fromArray(array $data): self
    {
        if (!array_key_exists('name', $data) || self::normalizeText($data['name']) === '') {
            throw new InvalidArgumentException('The product name is required.');
        }

        if (!array_key_exists('price', $data)) {
            throw new InvalidArgumentException('The product price is required.');
        }

        $name = self::normalizeText($data['name']);
        $description = isset($data['description']) ? self::normalizeText($data['description']) : null;
        $price = self::normalizePrice($data['price']);

        return new self($name, $description, $price);
    }

    private static function normalizeText(mixed $value): string
    {
        $text = trim((string) $value);
        $text = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $text);
        $text = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $text);
        $text = strip_tags($text);
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim($text);
    }

    private static function normalizePrice(mixed $value): float
    {
        if (!is_numeric($value)) {
            throw new InvalidArgumentException('The product price must be numeric.');
        }

        $price = (float) $value;

        if ($price < 0) {
            throw new InvalidArgumentException('The product price cannot be negative.');
        }

        return round($price, 2);
    }
}
