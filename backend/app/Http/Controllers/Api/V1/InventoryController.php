<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\InventoryAddStockRequest;
use App\Http\Requests\Api\V1\InventoryAdjustStockRequest;
use App\Http\Requests\Api\V1\InventoryTransferStockRequest;
use App\Http\Resources\InventoryMovementResource;
use App\Http\Resources\InventoryResource;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(private readonly InventoryService $inventory)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $branchId = (int) $request->query('branch_id', $user->branch_id);
        if ($user->branch_id && $branchId !== (int) $user->branch_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $perPage = (int) ($request->query('per_page', 15));
        $perPage = max(1, min(100, $perPage));

        $paginator = $this->inventory->listForBranch($branchId, [
            'search' => $request->query('search'),
            'low_stock_below' => $request->query('low_stock_below'),
        ], $perPage);

        return InventoryResource::collection($paginator);
    }

    public function movements(Request $request)
    {
        $user = $request->user();
        $branchId = (int) $request->query('branch_id', $user->branch_id);
        if ($user->branch_id && $branchId !== (int) $user->branch_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $perPage = (int) ($request->query('per_page', 20));
        $perPage = max(1, min(100, $perPage));

        $paginator = $this->inventory->movements($branchId, [
            'product_id' => $request->query('product_id'),
            'type' => $request->query('type'),
        ], $perPage);

        return InventoryMovementResource::collection($paginator);
    }

    public function addStock(InventoryAddStockRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();
        if ($user->branch_id && (int) $data['branch_id'] !== (int) $user->branch_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $inv = $this->inventory->addStock($user, (int) $data['branch_id'], (int) $data['product_id'], (int) $data['quantity'], $data['note'] ?? null);
        return new InventoryResource($inv);
    }

    public function adjustStock(InventoryAdjustStockRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();
        if ($user->branch_id && (int) $data['branch_id'] !== (int) $user->branch_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $inv = $this->inventory->adjustStock($user, (int) $data['branch_id'], (int) $data['product_id'], (int) $data['delta'], $data['note'] ?? null);
        return new InventoryResource($inv);
    }

    public function transferStock(InventoryTransferStockRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        // Branch manager can only transfer from their own branch
        if ($user->branch_id && (int) $data['from_branch_id'] !== (int) $user->branch_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $result = $this->inventory->transferStock(
            $user,
            (int) $data['from_branch_id'],
            (int) $data['to_branch_id'],
            (int) $data['product_id'],
            (int) $data['quantity'],
            $data['note'] ?? null
        );

        return response()->json([
            'from' => new InventoryResource($result['from']),
            'to' => new InventoryResource($result['to']),
        ]);
    }
}

