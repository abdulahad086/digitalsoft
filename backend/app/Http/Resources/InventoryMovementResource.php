<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product?->id,
                'name' => $this->product?->name,
                'sku' => $this->product?->sku,
            ]),
            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ] : null),
            'type' => $this->type,
            'quantity_delta' => (int) $this->quantity_delta,
            'reference_id' => $this->reference_id,
            'reference_type' => $this->reference_type,
            'note' => $this->note,
            'created_at' => $this->created_at,
        ];
    }
}

