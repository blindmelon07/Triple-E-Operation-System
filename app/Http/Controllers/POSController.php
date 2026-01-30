<?php

namespace App\Http\Controllers;

use App\Enums\QuotationStatus;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
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
            })
            ->sortByDesc(function ($product) {
                // Sort by stock quantity (descending), products with stock appear first
                return $product['inventory']->quantity ?? 0;
            })
            ->values(); // Reset array keys

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

            AuditLog::create([
                'user_id'         => auth()->id(),
                'user_name'       => auth()->user()?->name,
                'action'          => 'completed_sale',
                'auditable_type'  => Sale::class,
                'auditable_id'    => $sale->id,
                'auditable_label' => "Sale #{$sale->id}",
                'new_values'      => [
                    'total'          => $validated['total'],
                    'payment_method' => $validated['payment_method'],
                    'items_count'    => count($validated['items']),
                    'customer_id'    => $validated['customer_id'] ?? null,
                ],
                'ip_address'      => $request->ip(),
                'user_agent'      => $request->userAgent(),
            ]);

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

    public function storeCustomer(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'contact_person' => 'nullable|string|max:255',
        ]);

        try {
            $customer = Customer::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'] ?? null,
                'contact_person' => $validated['contact_person'] ?? null,
                'payment_term_days' => 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Customer added successfully',
                'customer' => $customer,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function createQuotation(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:products,id',
            'items.*.is_manual' => 'nullable|boolean',
            'items.*.name' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.unit' => 'required|string',
            'total' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'valid_days' => 'nullable|integer|min:1|max:365',
        ]);

        try {
            DB::beginTransaction();

            $validDays = $validated['valid_days'] ?? 30;

            $quotation = Quotation::create([
                'customer_id' => $validated['customer_id'],
                'date' => now(),
                'valid_until' => now()->addDays($validDays),
                'total' => $validated['total'],
                'notes' => $validated['notes'] ?? null,
                'status' => 'pending',
            ]);

            foreach ($validated['items'] as $item) {
                $itemPrice = $item['unit_price'] * $item['quantity'];
                $isManual = $item['is_manual'] ?? false;

                QuotationItem::create([
                    'quotation_id' => $quotation->id,
                    'product_id' => $isManual ? null : $item['id'],
                    'product_description' => $isManual ? $item['name'] : null,
                    'is_manual' => $isManual,
                    'unit' => $item['unit'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'price' => $itemPrice,
                ]);
            }

            DB::commit();

            AuditLog::create([
                'user_id'         => auth()->id(),
                'user_name'       => auth()->user()?->name,
                'action'          => 'created_quotation',
                'auditable_type'  => Quotation::class,
                'auditable_id'    => $quotation->id,
                'auditable_label' => "Quotation {$quotation->quotation_number}",
                'new_values'      => [
                    'total'       => $validated['total'],
                    'items_count' => count($validated['items']),
                    'customer_id' => $validated['customer_id'] ?? null,
                ],
                'ip_address'      => $request->ip(),
                'user_agent'      => $request->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Quotation created successfully',
                'quotation_id' => $quotation->id,
                'quotation_number' => $quotation->quotation_number,
                'print_url' => route('pos.print-quotation', $quotation->id),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function printQuotation(Quotation $quotation): \Illuminate\Contracts\View\View
    {
        $quotation->load(['customer', 'quotation_items.product']);

        // Check if quotation is approved before allowing print
        $isApproved = $quotation->status === QuotationStatus::Approved->value;

        return view('pos.quotation-print', compact('quotation', 'isApproved'));
    }

    public function printReceipt(Sale $sale, Request $request): \Illuminate\Contracts\View\View
    {
        $sale->load(['customer', 'sale_items.product']);
        $type = $request->query('type', 'delivery'); // 'delivery' or 'pickup'

        return view('pos.receipt-print', compact('sale', 'type'));
    }

    public function getRecentSales(): \Illuminate\Http\JsonResponse
    {
        try {
            $sales = Sale::with(['customer', 'sale_items'])
                ->withCount('sale_items')
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            return response()->json([
                'success' => true,
                'sales' => $sales,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
