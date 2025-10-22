<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Shop;
use App\Models\Category;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed shops
        $shops = [
            'Supermarket',
            'Restaurant',
            'Online Shop',
            'Convenience Store',
            'Others'
        ];

        foreach ($shops as $shop) {
            Shop::create(['name' => $shop]);
        }

        // Seed categories
        $categories = [
            'Food',
            'Drink',
            'Daily Goods',
            'Entertainment',
            'Transportation',
            'Others'
        ];

        foreach ($categories as $category) {
            Category::create(['name' => $category]);
        }
    }
}
