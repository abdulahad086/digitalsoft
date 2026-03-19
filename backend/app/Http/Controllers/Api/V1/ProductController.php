<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ProductStoreRequest;
use App\Http\Requests\Api\V1\ProductUpdateRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $products)
    {
    }

    public function index(Request $request)
    {
        $perPage = (int) ($request->query('per_page', 15));
        $perPage = max(1, min(100, $perPage));

        $paginator = $this->products->paginate([
            'search' => $request->query('search'),
            'is_active' => $request->query('is_active'),
        ], $perPage);

        return ProductResource::collection($paginator);
    }

    public function store(ProductStoreRequest $request)
    {
        $product = $this->products->create($request->validated());
        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    public function show(Product $product)
    {
        return new ProductResource($product);
    }

    public function update(ProductUpdateRequest $request, Product $product)
    {
        $product = $this->products->update($product, $request->validated());
        return new ProductResource($product);
    }

    public function destroy(Product $product)
    {
        $this->products->delete($product);
        return response()->json(['message' => 'Deleted']);
    }
}

