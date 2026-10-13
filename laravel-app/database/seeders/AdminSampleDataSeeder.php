<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedStaff();
        $this->seedIngredients();
        $this->seedStockMovements();
        $this->seedMenuItems();
        $this->seedRecipeLinks();
        $this->seedHealthyItem();
    }

    private function seedStaff(): void
    {
        $staff = [
            ['name' => 'Sample Cashier', 'email' => 'cashier_sample@example.com', 'role' => 'cashier'],
            ['name' => 'Sample Kitchen', 'email' => 'kitchen_sample@example.com', 'role' => 'kitchen'],
        ];

        foreach ($staff as $s) {
            DB::table('users')->updateOrInsert(
                ['email' => $s['email']],
                [
                    'name' => $s['name'],
                    'password' => Hash::make('Password123!'),
                    'role' => $s['role'],
                ]
            );
        }
    }

    private function seedIngredients(): void
    {
        $ingredients = [
            ['name' => 'Basmati Rice', 'unit' => 'kg', 'threshold' => 10.00, 'cost' => 85.00, 'shelf_life' => 365],
            ['name' => 'Vegetable Oil', 'unit' => 'L', 'threshold' => 5.00, 'cost' => 120.00, 'shelf_life' => 180],
            ['name' => 'Cumin Seeds', 'unit' => 'g', 'threshold' => 100.00, 'cost' => 0.50, 'shelf_life' => 365],
            ['name' => 'Fresh Cream', 'unit' => 'L', 'threshold' => 2.00, 'cost' => 200.00, 'shelf_life' => 7],
            ['name' => 'Chicken Breast', 'unit' => 'kg', 'threshold' => 5.00, 'cost' => 320.00, 'shelf_life' => 3],
            ['name' => 'Onions', 'unit' => 'kg', 'threshold' => 2.00, 'cost' => 60.00, 'shelf_life' => 14],
            ['name' => 'Garlic', 'unit' => 'g', 'threshold' => 100.00, 'cost' => 0.80, 'shelf_life' => 30],
            ['name' => 'All-purpose Flour', 'unit' => 'kg', 'threshold' => 5.00, 'cost' => 45.00, 'shelf_life' => 180],
            ['name' => 'Potatoes', 'unit' => 'kg', 'threshold' => 5.00, 'cost' => 40.00, 'shelf_life' => 21],
            ['name' => 'Green Peas', 'unit' => 'kg', 'threshold' => 2.00, 'cost' => 90.00, 'shelf_life' => 7],
        ];

        foreach ($ingredients as $i) {
            DB::table('ingredients')->updateOrInsert(
                ['ingredient_name' => $i['name']],
                [
                    'unit_of_measurement' => $i['unit'],
                    'restock_threshold' => $i['threshold'],
                    'unit_cost' => $i['cost'],
                    'shelf_life_days' => $i['shelf_life'],
                ]
            );
        }
    }

    private function seedStockMovements(): void
    {
        $now = Carbon::now();
        
        $getIds = function($name) {
            return DB::table('ingredients')->where('ingredient_name', $name)->value('ingredient_id');
        };

        $movements = [
            ['id' => $getIds('Basmati Rice'), 'type' => 'opening_balance', 'qty' => 100, 'exp' => $now->copy()->addDays(30), 'date' => $now->copy()->subDays(10)],
            ['id' => $getIds('Vegetable Oil'), 'type' => 'opening_balance', 'qty' => 10, 'exp' => $now->copy()->addDays(60), 'date' => $now->copy()->subDays(20)],
            ['id' => $getIds('Vegetable Oil'), 'type' => 'spoilage', 'qty' => -8, 'exp' => null, 'date' => $now->copy()->subDays(5)],
            ['id' => $getIds('Cumin Seeds'), 'type' => 'opening_balance', 'qty' => 500, 'exp' => $now->copy()->addDays(100), 'date' => $now->copy()->subDays(30)],
            ['id' => $getIds('Cumin Seeds'), 'type' => 'spoilage', 'qty' => -500, 'exp' => null, 'date' => $now->copy()->subDays(10)],
            ['id' => $getIds('Fresh Cream'), 'type' => 'delivery', 'qty' => 5, 'exp' => $now->copy()->subDays(3), 'date' => $now->copy()->subDays(10)],
            ['id' => $getIds('Chicken Breast'), 'type' => 'opening_balance', 'qty' => 20, 'exp' => $now->copy()->addDays(3), 'date' => $now->copy()->subDays(5)],
            ['id' => $getIds('Chicken Breast'), 'type' => 'delivery', 'qty' => 10, 'exp' => $now->copy()->addDays(5), 'date' => $now->copy()->subDay()],
            ['id' => $getIds('Onions'), 'type' => 'opening_balance', 'qty' => 10, 'exp' => $now->copy()->addDays(7), 'date' => $now->copy()->subDays(3)],
            ['id' => $getIds('Garlic'), 'type' => 'opening_balance', 'qty' => 500, 'exp' => $now->copy()->addDays(20), 'date' => $now->copy()->subDays(5)],
            ['id' => $getIds('All-purpose Flour'), 'type' => 'opening_balance', 'qty' => 20, 'exp' => $now->copy()->addDays(90), 'date' => $now->copy()->subDays(15)],
            ['id' => $getIds('Potatoes'), 'type' => 'opening_balance', 'qty' => 15, 'exp' => $now->copy()->addDays(14), 'date' => $now->copy()->subDays(5)],
            ['id' => $getIds('Green Peas'), 'type' => 'opening_balance', 'qty' => 5, 'exp' => $now->copy()->addDays(5), 'date' => $now->copy()->subDays(2)],
        ];

        foreach ($movements as $m) {
            if (!$m['id']) continue;
            DB::table('stock_movements')->insert([
                'ingredient_id' => $m['id'],
                'user_id' => DB::table('users')->first()->user_id,
                'movement_type' => $m['type'],
                'qty_changed' => $m['qty'],
                'recorded_at' => $m['date'] ?? now(),
                'expiration_date' => $m['exp'] ? $m['exp']->toDateString() : null,
                'delivery_date' => $m['type'] === 'delivery' ? $m['date']->toDateString() : null,
                'notes' => 'Sample data seeding',
            ]);
        }
    }

    private function seedMenuItems(): void
    {
        $items = [
            ['name' => 'Chicken Biryani', 'category' => 'Main Course', 'price' => 450.00],
            ['name' => 'Garlic Naan', 'category' => 'Breads', 'price' => 85.00],
            ['name' => 'Vegetable Samosas', 'category' => 'Appetizers', 'price' => 120.00],
            ['name' => 'Butter Chicken', 'category' => 'Main Course', 'price' => 480.00],
        ];

        foreach ($items as $item) {
            DB::table('menu_items')->updateOrInsert(
                ['item_name' => $item['name']],
                [
                    'category' => $item['category'],
                    'price' => $item['price'],
                    'is_available' => true,
                ]
            );
        }
    }

    private function seedRecipeLinks(): void
    {
        $getIngId = function($name) {
            return DB::table('ingredients')->where('ingredient_name', $name)->value('ingredient_id');
        };
        $getItemId = function($name) {
            return DB::table('menu_items')->where('item_name', $name)->value('menu_item_id');
        };

        $links = [
            ['item' => 'Chicken Biryani', 'ing' => 'Basmati Rice', 'qty' => 0.2],
            ['item' => 'Chicken Biryani', 'ing' => 'Chicken Breast', 'qty' => 0.2],
            ['item' => 'Chicken Biryani', 'ing' => 'Vegetable Oil', 'qty' => 0.05],
            ['item' => 'Chicken Biryani', 'ing' => 'Cumin Seeds', 'qty' => 5.0],
            ['item' => 'Chicken Biryani', 'ing' => 'Onions', 'qty' => 0.1],
            ['item' => 'Chicken Biryani', 'ing' => 'Garlic', 'qty' => 10.0],
            ['item' => 'Garlic Naan', 'ing' => 'All-purpose Flour', 'qty' => 0.15],
            ['item' => 'Garlic Naan', 'ing' => 'Garlic', 'qty' => 5.0],
            ['item' => 'Garlic Naan', 'ing' => 'Vegetable Oil', 'qty' => 0.02],
            ['item' => 'Vegetable Samosas', 'ing' => 'Potatoes', 'qty' => 0.1],
            ['item' => 'Vegetable Samosas', 'ing' => 'Green Peas', 'qty' => 0.05],
            ['item' => 'Vegetable Samosas', 'ing' => 'Vegetable Oil', 'qty' => 0.05],
            ['item' => 'Vegetable Samosas', 'ing' => 'Cumin Seeds', 'qty' => 2.0],
            ['item' => 'Butter Chicken', 'ing' => 'Chicken Breast', 'qty' => 0.2],
            ['item' => 'Butter Chicken', 'ing' => 'Fresh Cream', 'qty' => 0.1],
            ['item' => 'Butter Chicken', 'ing' => 'Vegetable Oil', 'qty' => 0.05],
        ];

        foreach ($links as $l) {
            $itemId = $getItemId($l['item']);
            $ingId = $getIngId($l['ing']);
            if ($itemId && $ingId) {
                DB::table('recipe_ingredients')->updateOrInsert(
                    ['menu_item_id' => $itemId, 'ingredient_id' => $ingId],
                    ['qty_consumed' => $l['qty'], 'is_active' => true]
                );
            }
        }
    }

    private function seedHealthyItem(): void {
        $itemName = 'Steamed Rice';
        DB::table('menu_items')->updateOrInsert(
            ['item_name' => $itemName],
            ['category' => 'Sides', 'price' => 50.00, 'is_available' => true]
        );
        $itemId = DB::table('menu_items')->where('item_name', $itemName)->value('menu_item_id');
        $ingId = DB::table('ingredients')->where('ingredient_name', 'Basmati Rice')->value('ingredient_id');
        if ($itemId && $ingId) {
            DB::table('recipe_ingredients')->updateOrInsert(
                ['menu_item_id' => $itemId, 'ingredient_id' => $ingId],
                ['qty_consumed' => 0.2, 'is_active' => true]
            );
        }
    }
}
