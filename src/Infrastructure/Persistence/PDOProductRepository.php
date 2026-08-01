<?php

namespace App\Infrastructure\Persistence;

use App\Core\Database\DatabaseConnectionInterface;
use App\Domain\Entities\Product;
use App\Domain\Repositories\ProductRepositoryInterface;
use PDO;

class PDOProductRepository implements ProductRepositoryInterface
{
    private PDO $db;
    private ProductMapper $productMapper;

    public function __construct(DatabaseConnectionInterface $dbConnection, ?ProductMapper $productMapper = null)
    {
        $this->db = $dbConnection->getConnection();
        $this->productMapper = $productMapper ?? new ProductMapper();
    }

    public function findAll(): array
    {
        $stmt = $this->db->prepare('SELECT * FROM products ORDER BY id DESC');
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $products = [];
        foreach ($rows as $row) {
            $products[] = $this->productMapper->toEntity($row);
        }

        return $products;
    }

    public function findById(int $id): ?Product
    {
        $stmt = $this->db->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return $this->productMapper->toEntity($row);
    }

    public function save(Product $product): Product
    {
        $stmt = $this->db->prepare('
            INSERT INTO products (name, description, price, created_at)
            VALUES (:name, :description, :price, :created_at)
        ');

        $stmt->execute([
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'created_at' => $product->getCreatedAt()
        ]);

        $id = (int) $this->db->lastInsertId();

        return new Product(
            $id,
            $product->getName(),
            $product->getDescription(),
            $product->getPrice(),
            $product->getCreatedAt()
        );
    }

    public function update(Product $product): Product
    {
        $stmt = $this->db->prepare('
            UPDATE products
            SET name = :name, description = :description, price = :price
            WHERE id = :id
        ');

        $stmt->execute([
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice()
        ]);

        return $product;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM products WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
