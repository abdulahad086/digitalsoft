<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'cost_price',
        'sale_price',
        'tax_percentage',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'cost_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
    ];

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }
}

