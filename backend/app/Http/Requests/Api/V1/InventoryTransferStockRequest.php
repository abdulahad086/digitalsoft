<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class InventoryTransferStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_branch_id' => ['required', 'integer', 'exists:branches,id', 'different:to_branch_id'],
            'to_branch_id' => ['required', 'integer', 'exists:branches,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}

