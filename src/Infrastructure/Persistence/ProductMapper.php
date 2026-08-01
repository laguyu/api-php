<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Entities\Product;

class ProductMapper
{
    public function toEntity(array $row): Product
    {
        return new Product(
            (int) $row['id'],
            (string) $row['name'],
            array_key_exists('description', $row) ? $row['description'] : null,
            (float) $row['price'],
            (string) $row['created_at']
        );
    }
}
