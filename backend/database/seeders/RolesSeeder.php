<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Support\RoleNames;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([RoleNames::SUPER_ADMIN, RoleNames::BRANCH_MANAGER, RoleNames::SALES_USER] as $name) {
            Role::query()->firstOrCreate(['name' => $name]);
        }
    }
}

