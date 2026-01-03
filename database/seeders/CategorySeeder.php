<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Hand Tools',
            'Power Tools', 
            'Fasteners',
            'Paint',
            'Plumbing',
            'Electrical',
            'Garden',
            'Safety',
            'Building Materials',
            'Adhesives'
        ];

        foreach ($categories as $category) {
            \App\Models\Category::firstOrCreate(
                ['name' => $category],
                ['description' => 'Category for ' . $category]
            );
        }
    }
}
