<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LargeCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categoryIds = Category::pluck('id')->toArray();
        if (empty($categoryIds)) {
            $categories = [
                ['name' => 'Smart Electronics', 'slug' => 'smart-electronics', 'is_active' => true],
                ['name' => 'Laptops & Computers', 'slug' => 'laptops-computers', 'is_active' => true],
                ['name' => 'Home Appliances', 'slug' => 'home-appliances', 'is_active' => true],
                ['name' => 'Fashion & Apparel', 'slug' => 'fashion-apparel', 'is_active' => true],
                ['name' => 'Sports & Outdoors', 'slug' => 'sports-outdoors', 'is_active' => true],
            ];
            foreach ($categories as $cat) {
                Category::create($cat);
            }
            $categoryIds = Category::pluck('id')->toArray();
        }

        $brandIds = Brand::pluck('id')->toArray();
        if (empty($brandIds)) {
            $brands = [
                ['name' => 'Apple Inc.', 'slug' => 'apple-inc', 'is_active' => true],
                ['name' => 'Samsung', 'slug' => 'samsung', 'is_active' => true],
                ['name' => 'Dell Technologies', 'slug' => 'dell-technologies', 'is_active' => true],
                ['name' => 'Nike', 'slug' => 'nike', 'is_active' => true],
                ['name' => 'Sony', 'slug' => 'sony', 'is_active' => true],
            ];
            foreach ($brands as $b) {
                Brand::create($b);
            }
            $brandIds = Brand::pluck('id')->toArray();
        }

        DB::disableQueryLog();
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $totalProducts = 50000;
        $chunkSize = 1000;
        $chunks = $totalProducts / $chunkSize;

        $colors = ['Black', 'White', 'Silver', 'Navy', 'Titanium'];
        $sizes = ['128GB', '256GB', '512GB', 'M', 'L'];

        $this->command->info("Starting bulk insertion of {$totalProducts} products...");

        for ($c = 1; $c <= $chunks; $c++) {
            $productsBatch = [];
            $now = now();

            for ($i = 1; $i <= $chunkSize; $i++) {
                $title = fake()->words(3, true) . ' ' . fake()->randomNumber(4);
                $productsBatch[] = [
                    'category_id' => $categoryIds[array_rand($categoryIds)],
                    'brand_id' => $brandIds[array_rand($brandIds)],
                    'title' => ucfirst($title),
                    'slug' => Str::slug($title) . '-' . Str::random(6),
                    'description' => fake()->sentence(12),
                    'base_price' => fake()->randomFloat(2, 50, 2000),
                    'status' => 'published',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            Product::insert($productsBatch);

            $lastInsertedIds = Product::orderBy('id', 'desc')->take($chunkSize)->pluck('id')->reverse()->values();

            $variantsBatch = [];
            foreach ($lastInsertedIds as $productId) {
                for ($v = 0; $v < 2; $v++) {
                    $variantsBatch[] = [
                        'product_id' => $productId,
                        'sku' => 'SKU-' . strtoupper(Str::random(10)),
                        'price' => fake()->randomFloat(2, 50, 2100),
                        'stock' => fake()->numberBetween(0, 150),
                        'color' => $colors[array_rand($colors)],
                        'size' => $sizes[array_rand($sizes)],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            ProductVariant::insert($variantsBatch);

            $this->command->comment("Chunk {$c}/{$chunks} inserted (" . ($c * $chunkSize) . " products).");
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $this->command->info("50,000 Products and 100,000 Variants seeded successfully!");
    }
}