<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'product_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class, 'product_id', 'product_id')
            ->whereColumn('inventory_movements.branch_id', 'inventories.branch_id');
    }
}

