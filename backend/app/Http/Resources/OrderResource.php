<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'branch' => $this->whenLoaded('branch', fn () => [
                'id' => $this->branch?->id,
                'name' => $this->branch?->name,
            ]),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
            ]),
            'subtotal' => (string) $this->subtotal,
            'tax_total' => (string) $this->tax_total,
            'grand_total' => (string) $this->grand_total,
            'ordered_at' => $this->ordered_at,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($it) => [
                'id' => $it->id,
                'product' => $it->relationLoaded('product') ? [
                    'id' => $it->product?->id,
                    'name' => $it->product?->name,
                    'sku' => $it->product?->sku,
                ] : null,
                'quantity' => (int) $it->quantity,
                'unit_price' => (string) $it->unit_price,
                'tax_percentage' => (string) $it->tax_percentage,
                'line_subtotal' => (string) $it->line_subtotal,
                'line_tax' => (string) $it->line_tax,
                'line_total' => (string) $it->line_total,
            ])),
        ];
    }
}

