<?php

namespace App\Application\Controllers;

use App\Application\DTO\CreateProductDTO;
use App\Application\Services\ProductService;
use App\Core\Exceptions\HttpException;
use App\Core\Request;
use App\Core\Response;
use Exception;
use InvalidArgumentException;

class ProductController
{
    public function __construct(private ProductService $productService)
    {
    }

    /**
     * GET /api/products
     */
    public function index(Request $request): Response
    {
        try {
            $products = $this->productService->getAllProducts();

            return Response::json($products);
        } catch (Exception $e) {
            return Response::json(['error' => 'Server Error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/products/{id}
     */
    public function show(Request $request, string $id): Response
    {
        try {
            $product = $this->productService->getProductById((int) $id);
            if (!$product) {
                return Response::json(['error' => 'Not Found', 'message' => "Product with ID {$id} not found."], 404);
            }

            return Response::json($product);
        } catch (Exception $e) {
            return Response::json(['error' => 'Server Error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/products
     */
    public function store(Request $request): Response
    {
        try {
            $dto = CreateProductDTO::fromArray($request->getBody());
            $product = $this->productService->createProduct($dto);

            return Response::json($product, 201);
        } catch (InvalidArgumentException $e) {
            return Response::json(['error' => 'Bad Request', 'message' => $e->getMessage()], 400);
        } catch (Exception $e) {
            return Response::json(['error' => 'Server Error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * PUT /api/products/{id}
     */
    public function update(Request $request, string $id): Response
    {
        try {
            $product = $this->productService->updateProduct((int) $id, $request->getBody());

            return Response::json($product);
        } catch (InvalidArgumentException $e) {
            return Response::json(['error' => 'Bad Request', 'message' => $e->getMessage()], 400);
        } catch (HttpException $e) {
            return Response::json(['error' => 'Not Found', 'message' => $e->getMessage()], $e->getStatusCode());
        } catch (Exception $e) {
            return Response::json(['error' => 'Server Error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/products/{id}
     */
    public function destroy(Request $request, string $id): Response
    {
        try {
            $this->productService->deleteProduct((int) $id);

            return Response::empty(204);
        } catch (HttpException $e) {
            return Response::json(['error' => 'Not Found', 'message' => $e->getMessage()], $e->getStatusCode());
        } catch (Exception $e) {
            return Response::json(['error' => 'Server Error', 'message' => $e->getMessage()], 500);
        }
    }
}
