<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::create([
            'name' => 'knees Pad',
            'category_id' => '1',
            'brand_id' => '1',
            'price' => '25.00',
            'images' => 'https://cdn11.bigcommerce.com/s-ob1gxw6h/images/stencil/1280x1280/products/3300/10977/kneeboard-black__23431.1667854386.jpg?c=2',
            'description' => 'Nike Volleyball Streak Knee Pads Black'
        ]);
    }
}
