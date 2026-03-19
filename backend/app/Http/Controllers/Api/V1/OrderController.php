<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\OrderStoreRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders)
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

        $q = Order::query()
            ->with(['items.product', 'user', 'branch'])
            ->orderByDesc('id');

        if ($branchId) {
            $q->where('branch_id', $branchId);
        }

        return OrderResource::collection($q->paginate($perPage));
    }

    public function show(Request $request, Order $order)
    {
        $user = $request->user();
        if ($user->branch_id && (int) $order->branch_id !== (int) $user->branch_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return new OrderResource($order->load(['items.product', 'user', 'branch']));
    }

    public function store(OrderStoreRequest $request)
    {
        $order = $this->orders->createOrder(
            $request->user(),
            (int) $request->validated('branch_id'),
            $request->validated('items')
        );

        return (new OrderResource($order))->response()->setStatusCode(201);
    }
}

