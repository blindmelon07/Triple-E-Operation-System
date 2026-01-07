<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class POSController extends Controller
{
    public function index(): \Illuminate\Contracts\View\View
    {
        $products = Product::with(['category', 'inventory'])
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category_id' => $product->category_id,
                    'price' => $product->price,
                    'unit' => $product->unit->value,
                    'category' => $product->category,
                    'inventory' => $product->inventory,
                ];
            });

        $customers = Customer::orderBy('name')->get();
        $categories = Category::all();

        return view('pos.index', compact('products', 'customers', 'categories'));
    }

    public function completeSale(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'cash_received' => 'nullable|numeric',
            'change' => 'nullable|numeric',
        ]);

        try {
            DB::beginTransaction();

            // Create the sale
            $sale = Sale::create([
                'customer_id' => $validated['customer_id'],
                'date' => now(),
                'total' => $validated['total'],
            ]);

            // Create sale items
            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['id']);

                // Check if enough stock is available
                if ($product->inventory && $product->inventory->quantity < $item['quantity']) {
                    throw new \Exception("Not enough stock for product: {$product->name}");
                }

                // Calculate price based on unit type
                $itemPrice = $item['price'] * $item['quantity'];

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['id'],
                    'quantity' => $item['quantity'],
                    'price' => $itemPrice,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sale completed successfully',
                'sale_id' => $sale->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
