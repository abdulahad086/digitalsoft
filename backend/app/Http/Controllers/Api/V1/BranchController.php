<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\BranchStoreRequest;
use App\Http\Requests\Api\V1\BranchUpdateRequest;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use App\Services\BranchService;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function __construct(private readonly BranchService $branches)
    {
    }

    public function index(Request $request)
    {
        $perPage = (int) ($request->query('per_page', 15));
        $perPage = max(1, min(100, $perPage));

        $paginator = $this->branches->paginate([
            'search' => $request->query('search'),
        ], $perPage);

        return BranchResource::collection($paginator);
    }

    public function store(BranchStoreRequest $request)
    {
        $branch = $this->branches->create($request->validated());
        return (new BranchResource($branch))->response()->setStatusCode(201);
    }

    public function show(Branch $branch)
    {
        return new BranchResource($branch->load('manager'));
    }

    public function update(BranchUpdateRequest $request, Branch $branch)
    {
        $branch = $this->branches->update($branch, $request->validated());
        return new BranchResource($branch);
    }

    public function destroy(Branch $branch)
    {
        $this->branches->delete($branch);
        return response()->json(['message' => 'Deleted']);
    }
}

