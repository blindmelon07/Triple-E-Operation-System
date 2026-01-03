<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CleanupCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        // Get unique category names
        $uniqueCategories = Category::select('name')
            ->groupBy('name')
            ->get();

        $this->command->info('Found ' . $uniqueCategories->count() . ' unique category names');

        foreach ($uniqueCategories as $uniqueCategory) {
            // Get all categories with this name
            $duplicates = Category::where('name', $uniqueCategory->name)->get();
            
            if ($duplicates->count() > 1) {
                // Keep the first one
                $keepCategory = $duplicates->first();
                
                // Reassign products from duplicates to the one we're keeping
                foreach ($duplicates->skip(1) as $duplicate) {
                    Product::where('category_id', $duplicate->id)
                        ->update(['category_id' => $keepCategory->id]);
                    
                    $duplicate->delete();
                }
                
                $this->command->info("Merged " . ($duplicates->count() - 1) . " duplicates for: {$uniqueCategory->name}");
            }
        }

        $this->command->info('Cleanup complete! Total categories now: ' . Category::count());
    }
}
