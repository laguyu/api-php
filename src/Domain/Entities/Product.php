<?php

namespace App\Domain\Entities;

use InvalidArgumentException;

class Product
{
    public function __construct(
        private ?int $id,
        private string $name,
        private ?string $description,
        private float $price,
        private string $createdAt
    ) {
        $this->validate();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /**
     * Validate product business rules.
     */
    public function validate(): void
    {
        if (empty(trim($this->name))) {
            throw new InvalidArgumentException("Product name cannot be empty.");
        }

        if ($this->price < 0) {
            throw new InvalidArgumentException("Product price cannot be negative.");
        }
    }

    /**
     * Convert the entity to array for JSON serialization.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'created_at' => $this->createdAt
        ];
    }
}
