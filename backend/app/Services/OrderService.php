<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderService
{
    /**
     * Concurrency strategy:
     * - Wrap the entire operation in a DB transaction.
     * - Lock all inventory rows for the branch + involved products using SELECT ... FOR UPDATE.
     * - Re-check availability under lock, then deduct, then write order + movement logs.
     * This prevents overselling when two users race to buy the last stock.
     */
    public function createOrder(User $actor, int $branchId, array $itemsInput): Order
    {
        $items = collect($itemsInput)->map(function ($row) {
            return [
                'product_id' => (int) ($row['product_id'] ?? 0),
                'quantity' => (int) ($row['quantity'] ?? 0),
            ];
        })->filter(fn ($r) => $r['product_id'] > 0 && $r['quantity'] > 0)->values();

        if ($items->isEmpty()) {
            throw new RuntimeException('Order must contain at least one item.');
        }

        return DB::transaction(function () use ($actor, $branchId, $items) {
            if ($actor->branch_id && $actor->branch_id !== $branchId) {
                // Branch Manager / Sales User can only order for their branch
                throw new RuntimeException('You cannot create orders for another branch.');
            }

            $productIds = $items->pluck('product_id')->unique()->values();

            /** @var Collection<int, Product> $products */
            $products = Product::query()
                ->whereIn('id', $productIds)
                ->where('is_active', true)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== $productIds->count()) {
                throw new RuntimeException('One or more products are invalid or inactive.');
            }

            // Lock inventory rows (and create missing rows with 0) in deterministic order
            $productIds->sort()->values()->each(function (int $pid) use ($branchId) {
                $inv = Inventory::query()
                    ->where(['branch_id' => $branchId, 'product_id' => $pid])
                    ->lockForUpdate()
                    ->first();

                if (! $inv) {
                    Inventory::create([
                        'branch_id' => $branchId,
                        'product_id' => $pid,
                        'quantity' => 0,
                    ]);

                    // lock the row we just created
                    Inventory::query()
                        ->where(['branch_id' => $branchId, 'product_id' => $pid])
                        ->lockForUpdate()
                        ->firstOrFail();
                }
            });

            $inventories = Inventory::query()
                ->where('branch_id', $branchId)
                ->whereIn('product_id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('product_id');

            // Validate stock under lock
            foreach ($items as $item) {
                $inv = $inventories->get($item['product_id']);
                if (! $inv || $inv->quantity < $item['quantity']) {
                    throw new RuntimeException('Insufficient stock for one or more items.');
                }
            }

            // Calculate totals
            $subtotal = '0.00';
            $taxTotal = '0.00';
            $lineRows = [];

            foreach ($items as $item) {
                $product = $products->get($item['product_id']);
                $qty = $item['quantity'];

                $unit = (string) $product->sale_price;
                $taxPct = (string) $product->tax_percentage;

                $lineSubtotal = bcmul($unit, (string) $qty, 2);
                $lineTax = bcdiv(bcmul($lineSubtotal, $taxPct, 4), '100', 2);
                $lineTotal = bcadd($lineSubtotal, $lineTax, 2);

                $subtotal = bcadd($subtotal, $lineSubtotal, 2);
                $taxTotal = bcadd($taxTotal, $lineTax, 2);

                $lineRows[] = [
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $unit,
                    'tax_percentage' => $taxPct,
                    'line_subtotal' => $lineSubtotal,
                    'line_tax' => $lineTax,
                    'line_total' => $lineTotal,
                ];
            }

            $grand = bcadd($subtotal, $taxTotal, 2);

            /** @var Order $order */
            $order = Order::create([
                'branch_id' => $branchId,
                'user_id' => $actor->id,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grand,
                'ordered_at' => now(),
            ]);

            foreach ($lineRows as $row) {
                OrderItem::create(['order_id' => $order->id] + $row);
            }

            // Deduct inventory + log movements
            foreach ($items as $item) {
                /** @var Inventory $inv */
                $inv = $inventories->get($item['product_id']);
                $inv->quantity -= $item['quantity'];
                if ($inv->quantity < 0) {
                    throw new RuntimeException('Inventory cannot go negative.');
                }
                $inv->save();

                InventoryMovement::create([
                    'branch_id' => $branchId,
                    'product_id' => $item['product_id'],
                    'user_id' => $actor->id,
                    'type' => 'sale',
                    'quantity_delta' => -$item['quantity'],
                    'reference_id' => $order->id,
                    'reference_type' => Order::class,
                ]);
            }

            return $order->fresh(['items.product', 'branch', 'user']);
        });
    }
}

