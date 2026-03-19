<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ReportingService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly ReportingService $reports)
    {
    }

    public function show(Request $request)
    {
        $user = $request->user();
        if (! $user->branch_id) {
            return response()->json([
                'message' => 'Super Admin must select a branch for dashboard via reports endpoint.',
            ], 200);
        }

        return response()->json($this->reports->branchDashboard((int) $user->branch_id));
    }

    public function reports(Request $request)
    {
        $user = $request->user();
        $branchId = (int) $request->query('branch_id', $user->branch_id);

        if ($user->branch_id && $branchId !== (int) $user->branch_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (! $branchId) {
            return response()->json(['message' => 'branch_id is required for Super Admin.'], 422);
        }

        return response()->json($this->reports->branchDashboard($branchId));
    }
}

