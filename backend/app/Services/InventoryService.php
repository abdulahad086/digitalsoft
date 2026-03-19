<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryService
{
    public function listForBranch(int $branchId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $q = Inventory::query()
            ->with(['product'])
            ->where('branch_id', $branchId);

        if (! empty($filters['search'])) {
            $s = (string) $filters['search'];
            $q->whereHas('product', function ($qq) use ($s) {
                $qq->where('name', 'like', "%{$s}%")
                    ->orWhere('sku', 'like', "%{$s}%");
            });
        }

        if (! empty($filters['low_stock_below'])) {
            $q->where('quantity', '<=', (int) $filters['low_stock_below']);
        }

        return $q->orderBy('quantity')->paginate($perPage);
    }

    public function movements(int $branchId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $q = InventoryMovement::query()
            ->with(['product', 'user'])
            ->where('branch_id', $branchId);

        if (! empty($filters['product_id'])) {
            $q->where('product_id', (int) $filters['product_id']);
        }

        if (! empty($filters['type'])) {
            $q->where('type', (string) $filters['type']);
        }

        return $q->orderByDesc('id')->paginate($perPage);
    }

    public function addStock(User $actor, int $branchId, int $productId, int $qty, ?string $note = null): Inventory
    {
        if ($qty <= 0) {
            throw new RuntimeException('Quantity must be positive.');
        }

        return DB::transaction(function () use ($actor, $branchId, $productId, $qty, $note) {
            $this->lockBranchProduct($branchId, $productId);

            $inventory = Inventory::query()->where([
                'branch_id' => $branchId,
                'product_id' => $productId,
            ])->lockForUpdate()->first();

            if (! $inventory) {
                $inventory = Inventory::create([
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                    'quantity' => 0,
                ]);
                $inventory = Inventory::query()->whereKey($inventory->id)->lockForUpdate()->firstOrFail();
            }

            $inventory->quantity += $qty;
            $inventory->save();

            InventoryMovement::create([
                'branch_id' => $branchId,
                'product_id' => $productId,
                'user_id' => $actor->id,
                'type' => 'add',
                'quantity_delta' => $qty,
                'note' => $note,
            ]);

            return $inventory->fresh(['product']);
        });
    }

    public function adjustStock(User $actor, int $branchId, int $productId, int $delta, ?string $note = null): Inventory
    {
        if ($delta === 0) {
            throw new RuntimeException('Delta must not be zero.');
        }

        return DB::transaction(function () use ($actor, $branchId, $productId, $delta, $note) {
            $this->lockBranchProduct($branchId, $productId);

            $inventory = Inventory::query()->where([
                'branch_id' => $branchId,
                'product_id' => $productId,
            ])->lockForUpdate()->first();

            if (! $inventory) {
                $inventory = Inventory::create([
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                    'quantity' => 0,
                ]);
                $inventory = Inventory::query()->whereKey($inventory->id)->lockForUpdate()->firstOrFail();
            }

            $newQty = $inventory->quantity + $delta;
            if ($newQty < 0) {
                throw new RuntimeException('Inventory cannot go negative.');
            }

            $inventory->quantity = $newQty;
            $inventory->save();

            InventoryMovement::create([
                'branch_id' => $branchId,
                'product_id' => $productId,
                'user_id' => $actor->id,
                'type' => 'adjust',
                'quantity_delta' => $delta,
                'note' => $note,
            ]);

            return $inventory->fresh(['product']);
        });
    }

    public function transferStock(User $actor, int $fromBranchId, int $toBranchId, int $productId, int $qty, ?string $note = null): array
    {
        if ($fromBranchId === $toBranchId) {
            throw new RuntimeException('Source and destination branches must differ.');
        }
        if ($qty <= 0) {
            throw new RuntimeException('Quantity must be positive.');
        }

        return DB::transaction(function () use ($actor, $fromBranchId, $toBranchId, $productId, $qty, $note) {
            // Prevent deadlocks: lock in deterministic order
            $a = [$fromBranchId, $toBranchId];
            sort($a);
            $this->lockBranchProduct($a[0], $productId);
            $this->lockBranchProduct($a[1], $productId);

            $from = $this->getOrCreateLockedInventory($fromBranchId, $productId);
            $to = $this->getOrCreateLockedInventory($toBranchId, $productId);

            if ($from->quantity < $qty) {
                throw new RuntimeException('Insufficient stock for transfer.');
            }

            $from->quantity -= $qty;
            $from->save();

            $to->quantity += $qty;
            $to->save();

            InventoryMovement::create([
                'branch_id' => $fromBranchId,
                'product_id' => $productId,
                'user_id' => $actor->id,
                'type' => 'transfer_out',
                'quantity_delta' => -$qty,
                'note' => $note,
                'reference_id' => $toBranchId,
                'reference_type' => Branch::class,
            ]);

            InventoryMovement::create([
                'branch_id' => $toBranchId,
                'product_id' => $productId,
                'user_id' => $actor->id,
                'type' => 'transfer_in',
                'quantity_delta' => $qty,
                'note' => $note,
                'reference_id' => $fromBranchId,
                'reference_type' => Branch::class,
            ]);

            return [
                'from' => $from->fresh(['product']),
                'to' => $to->fresh(['product']),
            ];
        });
    }

    private function getOrCreateLockedInventory(int $branchId, int $productId): Inventory
    {
        $inv = Inventory::query()->where(['branch_id' => $branchId, 'product_id' => $productId])
            ->lockForUpdate()
            ->first();

        if ($inv) {
            return $inv;
        }

        try {
            $created = Inventory::create([
                'branch_id' => $branchId,
                'product_id' => $productId,
                'quantity' => 0,
            ]);
        } catch (QueryException $e) {
            // Another transaction created it. Re-read with lock.
            $created = Inventory::query()->where(['branch_id' => $branchId, 'product_id' => $productId])
                ->lockForUpdate()
                ->firstOrFail();
        }

        return Inventory::query()->whereKey($created->id)->lockForUpdate()->firstOrFail();
    }

    private function lockBranchProduct(int $branchId, int $productId): void
    {
        // Ensure referenced rows exist (and lock them) so inventory row-level locks are stable.
        Branch::query()->whereKey($branchId)->lockForUpdate()->firstOrFail();
        Product::query()->whereKey($productId)->lockForUpdate()->firstOrFail();
    }
}

