<?php

namespace App\Application\DTO;

use InvalidArgumentException;

readonly class UpdateProductDTO
{
    public function __construct(
        public string $name,
        public ?string $description,
        public float $price
    ) {}

    public static function fromArray(array $data, array $existingData): self
    {
        $name = array_key_exists('name', $data)
            ? self::normalizeText($data['name'])
            : self::normalizeText($existingData['name'] ?? '');

        if ($name === '') {
            throw new InvalidArgumentException('The product name cannot be empty.');
        }

        $descriptionValue = array_key_exists('description', $data)
            ? $data['description']
            : ($existingData['description'] ?? null);

        $description = $descriptionValue === null ? null : self::normalizeText($descriptionValue);
        $price = array_key_exists('price', $data)
            ? self::normalizePrice($data['price'])
            : self::normalizePrice($existingData['price'] ?? 0);

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
