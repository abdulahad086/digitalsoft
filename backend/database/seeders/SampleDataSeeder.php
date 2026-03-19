<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Support\RoleNames;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $superRole = Role::query()->where('name', RoleNames::SUPER_ADMIN)->firstOrFail();
        $mgrRole = Role::query()->where('name', RoleNames::BRANCH_MANAGER)->firstOrFail();
        $salesRole = Role::query()->where('name', RoleNames::SALES_USER)->firstOrFail();

        $branchA = Branch::query()->firstOrCreate(
            ['name' => 'Downtown Branch'],
            ['address' => '123 Main St', 'manager_user_id' => null]
        );
        $branchB = Branch::query()->firstOrCreate(
            ['name' => 'Uptown Branch'],
            ['address' => '500 North Ave', 'manager_user_id' => null]
        );

        $super = User::query()->firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role_id' => $superRole->id,
                'branch_id' => null,
            ]
        );

        $manager = User::query()->firstOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name' => 'Branch Manager',
                'password' => Hash::make('password'),
                'role_id' => $mgrRole->id,
                'branch_id' => $branchA->id,
            ]
        );

        $sales = User::query()->firstOrCreate(
            ['email' => 'sales@example.com'],
            [
                'name' => 'Sales User',
                'password' => Hash::make('password'),
                'role_id' => $salesRole->id,
                'branch_id' => $branchA->id,
            ]
        );

        $branchA->manager_user_id = $manager->id;
        $branchA->save();

        $products = [
            ['name' => 'USB-C Cable', 'sku' => 'SKU-USB-C-01', 'cost_price' => 3.50, 'sale_price' => 7.99, 'tax_percentage' => 5, 'is_active' => true],
            ['name' => 'Wireless Mouse', 'sku' => 'SKU-MOUSE-01', 'cost_price' => 9.00, 'sale_price' => 19.99, 'tax_percentage' => 5, 'is_active' => true],
            ['name' => 'Notebook A5', 'sku' => 'SKU-NOTE-A5', 'cost_price' => 1.20, 'sale_price' => 3.49, 'tax_percentage' => 0, 'is_active' => true],
            ['name' => 'Old Product (Inactive)', 'sku' => 'SKU-OLD-00', 'cost_price' => 1.00, 'sale_price' => 1.50, 'tax_percentage' => 0, 'is_active' => false],
        ];

        foreach ($products as $p) {
            $product = Product::query()->firstOrCreate(['sku' => $p['sku']], $p);

            foreach ([$branchA, $branchB] as $branch) {
                Inventory::query()->firstOrCreate(
                    ['branch_id' => $branch->id, 'product_id' => $product->id],
                    ['quantity' => $branch->id === $branchA->id ? 50 : 20]
                );
            }
        }
    }
}

