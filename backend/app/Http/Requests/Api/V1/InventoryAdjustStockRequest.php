<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class InventoryAdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'delta' => ['required', 'integer', 'not_in:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}

