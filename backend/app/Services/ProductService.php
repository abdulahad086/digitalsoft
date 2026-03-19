<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductService
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $q = Product::query();

        if (! empty($filters['search'])) {
            $s = (string) $filters['search'];
            $q->where(function ($qq) use ($s) {
                $qq->where('name', 'like', "%{$s}%")
                    ->orWhere('sku', 'like', "%{$s}%");
            });
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $q->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false);
        }

        return $q->orderBy('id', 'desc')->paginate($perPage);
    }

    public function create(array $data): Product
    {
        return Product::create($data)->fresh();
    }

    public function update(Product $product, array $data): Product
    {
        $product->fill($data)->save();
        return $product->fresh();
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }
}

