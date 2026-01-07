<?php

namespace App\Services;

use App\Enums\ProductUnit;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductImportService
{
    /**
     * Import products from a CSV file.
     *
     * @return array{success: bool, imported: int, message: string}
     */
    public function importFromCsv(string $filePath): array
    {
        // Try to find the file using Storage facade first
        if (Storage::disk('local')->exists($filePath)) {
            $path = Storage::disk('local')->path($filePath);
        } elseif (Storage::disk('public')->exists($filePath)) {
            $path = Storage::disk('public')->path($filePath);
        } else {
            // Try multiple possible locations for the uploaded file
            $possiblePaths = [
                storage_path('app/public/'.$filePath),
                storage_path('app/'.$filePath),
                storage_path('app/livewire-tmp/'.$filePath),
                storage_path('app/public/livewire-tmp/'.$filePath),
            ];

            $path = null;
            foreach ($possiblePaths as $possiblePath) {
                if (file_exists($possiblePath)) {
                    $path = $possiblePath;
                    break;
                }
            }
        }

        if (! $path || ! file_exists($path)) {
            return [
                'success' => false,
                'imported' => 0,
                'message' => 'File not found.',
            ];
        }

        $file = fopen($path, 'r');

        // Remove BOM if present
        $bom = fread($file, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($file);
        }

        $firstRow = fgetcsv($file);

        if (! $firstRow) {
            fclose($file);

            return [
                'success' => false,
                'imported' => 0,
                'message' => 'Invalid CSV file.',
            ];
        }

        // Check if first row is a header or data
        // If the first column contains "name" (case insensitive), treat as header
        $firstColLower = strtolower(trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $firstRow[0] ?? '')));
        $hasHeader = in_array($firstColLower, ['name', 'product', 'product name', 'item', 'item name']);

        if ($hasHeader) {
            // Use first row as header
            $header = array_map(function ($col) {
                $col = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $col);

                return strtolower(trim($col));
            }, $firstRow);
            $dataRows = [];
        } else {
            // No header - use default column mapping: Name, Price
            $header = ['name', 'price', 'category', 'stock', 'unit'];
            // First row is data, so we need to process it
            rewind($file);
            // Skip BOM again if present
            $bom = fread($file, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($file);
            }
        }

        $imported = 0;
        $skipped = 0;

        DB::beginTransaction();

        try {
            // Get or create a default supplier for imports
            $defaultSupplier = Supplier::firstOrCreate(
                ['name' => 'Import Supplier'],
                ['contact_info' => 'Auto-created for product imports']
            );

            // Get or create a default category for imports without category
            $defaultCategory = Category::firstOrCreate(
                ['name' => 'Uncategorized']
            );

            while (($row = fgetcsv($file)) !== false) {
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Pad row with empty values if it has fewer columns than header
                while (count($row) < count($header)) {
                    $row[] = '';
                }

                // Trim to header length if row has more columns
                $row = array_slice($row, 0, count($header));

                $data = array_combine($header, $row);

                // Validate required fields
                if (empty(trim($data['name'] ?? ''))) {
                    $skipped++;
                    continue; // Skip rows without required name
                }

                $category = null;
                if (! empty($data['category'])) {
                    $category = Category::firstOrCreate(['name' => trim($data['category'])]);
                }

                $unitValue = strtolower(trim($data['unit'] ?? 'piece'));
                $unit = ProductUnit::tryFrom($unitValue) ?? ProductUnit::Piece;

                $stock = (int) ($data['stock'] ?? 0);

                // Parse price - remove commas and other formatting
                $priceString = $data['price'] ?? '0';
                $priceString = preg_replace('/[^0-9.]/', '', $priceString); // Remove everything except numbers and decimal point
                $price = (float) $priceString;

                $product = Product::create([
                    'name' => trim($data['name']),
                    'category_id' => $category?->id ?? $defaultCategory->id,
                    'supplier_id' => $defaultSupplier->id,
                    'price' => $price,
                    'cost_price' => 0,
                    'quantity' => $stock,
                    'unit' => $unit,
                ]);

                Inventory::create([
                    'product_id' => $product->id,
                    'quantity' => $stock,
                ]);

                $imported++;
            }

            DB::commit();

            $message = "Successfully imported {$imported} products.";
            if ($skipped > 0) {
                $message .= " Skipped {$skipped} rows.";
            }

            return [
                'success' => true,
                'imported' => $imported,
                'message' => $message,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'imported' => 0,
                'message' => 'Error: '.$e->getMessage(),
            ];
        } finally {
            fclose($file);
            @unlink($path);
        }
    }
}
