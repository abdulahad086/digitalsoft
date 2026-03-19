<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BranchService
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $q = Branch::query()->with(['manager']);

        if (! empty($filters['search'])) {
            $s = (string) $filters['search'];
            $q->where(function ($qq) use ($s) {
                $qq->where('name', 'like', "%{$s}%")
                    ->orWhere('address', 'like', "%{$s}%");
            });
        }

        return $q->orderBy('id', 'desc')->paginate($perPage);
    }

    public function create(array $data): Branch
    {
        return DB::transaction(function () use ($data) {
            /** @var Branch $branch */
            $branch = Branch::create([
                'name' => $data['name'],
                'address' => $data['address'],
                'manager_user_id' => $data['manager_user_id'] ?? null,
            ]);

            if (! empty($data['manager_user_id'])) {
                User::query()
                    ->whereKey($data['manager_user_id'])
                    ->update(['branch_id' => $branch->id]);
            }

            return $branch->fresh(['manager']);
        });
    }

    public function update(Branch $branch, array $data): Branch
    {
        return DB::transaction(function () use ($branch, $data) {
            $branch->fill([
                'name' => $data['name'] ?? $branch->name,
                'address' => $data['address'] ?? $branch->address,
                'manager_user_id' => array_key_exists('manager_user_id', $data) ? $data['manager_user_id'] : $branch->manager_user_id,
            ])->save();

            if (array_key_exists('manager_user_id', $data)) {
                if ($data['manager_user_id']) {
                    User::query()->whereKey($data['manager_user_id'])->update(['branch_id' => $branch->id]);
                }
            }

            return $branch->fresh(['manager']);
        });
    }

    public function delete(Branch $branch): void
    {
        DB::transaction(function () use ($branch) {
            $branch->delete();
        });
    }
}

