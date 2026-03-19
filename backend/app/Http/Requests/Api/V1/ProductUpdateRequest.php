<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'sku' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($productId)],
            'cost_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'sale_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'tax_percentage' => ['sometimes', 'required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['sometimes', 'required', 'boolean'],
        ];
    }
}

