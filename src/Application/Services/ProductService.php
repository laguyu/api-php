<?php

namespace App\Application\Services;

use App\Application\DTO\CreateProductDTO;
use App\Application\DTO\UpdateProductDTO;
use App\Core\Exceptions\HttpException;
use App\Domain\Entities\Product;
use App\Domain\Repositories\ProductRepositoryInterface;

class ProductService
{
    public function __construct(private ProductRepositoryInterface $productRepository)
    {
    }

    public function getAllProducts(): array
    {
        $products = $this->productRepository->findAll();

        return array_map(fn (Product $product): array => $product->toArray(), $products);
    }

    public function getProductById(int $id): ?array
    {
        $product = $this->productRepository->findById($id);

        return $product ? $product->toArray() : null;
    }

    public function createProduct(CreateProductDTO $dto): array
    {
        $product = new Product(
            null,
            $dto->name,
            $dto->description,
            $dto->price,
            date('Y-m-d H:i:s')
        );

        $savedProduct = $this->productRepository->save($product);

        return $savedProduct->toArray();
    }

    public function updateProduct(int $id, array $data): array
    {
        $existingProduct = $this->productRepository->findById($id);

        if (!$existingProduct) {
            throw new HttpException("Product with ID {$id} not found.", 404);
        }

        $dto = UpdateProductDTO::fromArray($data, $existingProduct->toArray());

        $updatedProduct = new Product(
            $id,
            $dto->name,
            $dto->description,
            $dto->price,
            $existingProduct->getCreatedAt()
        );

        $savedProduct = $this->productRepository->update($updatedProduct);

        return $savedProduct->toArray();
    }

    public function deleteProduct(int $id): bool
    {
        $existingProduct = $this->productRepository->findById($id);

        if (!$existingProduct) {
            throw new HttpException("Product with ID {$id} not found.", 404);
        }

        return $this->productRepository->delete($id);
    }
}
