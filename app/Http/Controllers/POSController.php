<?php

namespace App\Http\Controllers;

use App\Enums\CashRegisterStatus;
use App\Enums\QuotationStatus;
use App\Models\AuditLog;
use App\Models\CashRegisterSession;
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
    public function index(Request $request): \Illuminate\Contracts\View\View
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
                return $product['inventory']->quantity ?? 0;
            })
            ->values();

        $customers = Customer::orderBy('name')->get();
        $categories = Category::all();

        // Check for open cash register session
        $registerSession = CashRegisterSession::open()
            ->forUser(auth()->id())
            ->first();

        // Load quotation items if converting a quotation
        $quotationCart = [];
        $quotationId = null;
        $quotationCustomerId = null;

        if ($request->has('quotation_id')) {
            $quotation = Quotation::with(['quotation_items.product', 'customer'])
                ->find($request->query('quotation_id'));

            if ($quotation && $quotation->status === QuotationStatus::Approved->value) {
                $quotationId = $quotation->id;
                $quotationCustomerId = $quotation->customer_id;

                foreach ($quotation->quotation_items as $item) {
                    $cartItem = [
                        'id' => $item->is_manual ? null : $item->product_id,
                        'is_manual' => (bool) $item->is_manual,
                        'name' => $item->is_manual ? $item->product_description : ($item->product?->name ?? $item->product_description),
                        'price' => (float) ($item->unit_price * $item->quantity),
                        'unit_price' => (float) $item->unit_price,
                        'unit' => $item->unit,
                        'quantity' => (float) $item->quantity,
                        'maxStock' => $item->is_manual ? 999999 : ($item->product?->inventory?->quantity ?? 0),
                    ];
                    $quotationCart[] = $cartItem;
                }
            }
        }

        return view('pos.index', compact(
            'products', 'customers', 'categories', 'registerSession',
            'quotationCart', 'quotationId', 'quotationCustomerId'
        ));
    }

    public function openRegister(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'opening_amount' => 'required|numeric|min:0',
        ]);

        // Check if user already has an open session
        $existing = CashRegisterSession::open()
            ->forUser(auth()->id())
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an open register session.',
            ], 422);
        }

        $session = CashRegisterSession::create([
            'user_id' => auth()->id(),
            'opening_amount' => $validated['opening_amount'],
            'opened_at' => now(),
            'status' => CashRegisterStatus::Open,
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'user_name' => auth()->user()?->name,
            'action' => 'opened_register',
            'auditable_type' => CashRegisterSession::class,
            'auditable_id' => $session->id,
            'auditable_label' => "Register Session #{$session->id}",
            'new_values' => [
                'opening_amount' => $validated['opening_amount'],
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Register opened successfully',
            'session' => $session,
        ]);
    }

    public function closeRegister(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'closing_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $session = CashRegisterSession::open()
            ->forUser(auth()->id())
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'No open register session found.',
            ], 422);
        }

        $session->close($validated['closing_amount'], $validated['notes'] ?? null);

        AuditLog::create([
            'user_id' => auth()->id(),
            'user_name' => auth()->user()?->name,
            'action' => 'closed_register',
            'auditable_type' => CashRegisterSession::class,
            'auditable_id' => $session->id,
            'auditable_label' => "Register Session #{$session->id}",
            'new_values' => [
                'closing_amount' => $validated['closing_amount'],
                'expected_amount' => $session->expected_amount,
                'discrepancy' => $session->discrepancy,
                'total_sales' => $session->total_sales,
                'total_cash_sales' => $session->total_cash_sales,
                'total_transactions' => $session->total_transactions,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Register closed successfully',
            'session' => $session->fresh(),
        ]);
    }

    public function registerStatus(): \Illuminate\Http\JsonResponse
    {
        $session = CashRegisterSession::open()
            ->forUser(auth()->id())
            ->first();

        if (!$session) {
            return response()->json([
                'success' => true,
                'open' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'open' => true,
            'session' => $session,
        ]);
    }

    public function completeSale(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'cash_register_session_id' => 'nullable|exists:cash_register_sessions,id',
            'quotation_id' => 'nullable|exists:quotations,id',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:products,id',
            'items.*.is_manual' => 'nullable|boolean',
            'items.*.name' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.unit' => 'required|string',
            'total' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'payment_term_days' => 'nullable|integer|in:5,10,15,30,60',
            'cash_received' => 'nullable|numeric',
            'change' => 'nullable|numeric',
        ]);

        try {
            DB::beginTransaction();

            // Create the sale
            $sale = Sale::create([
                'customer_id' => $validated['customer_id'],
                'cash_register_session_id' => $validated['cash_register_session_id'] ?? null,
                'date' => now(),
                'total' => $validated['total'],
                'payment_method' => $validated['payment_method'],
                'payment_term_days' => $validated['payment_term_days'] ?? null,
            ]);

            // Create sale items
            foreach ($validated['items'] as $item) {
                $isManual = $item['is_manual'] ?? false;
                $itemPrice = $item['unit_price'] * $item['quantity'];

                if (!$isManual) {
                    $product = Product::findOrFail($item['id']);

                    if ($product->inventory && $product->inventory->quantity < $item['quantity']) {
                        throw new \Exception("Not enough stock for product: {$product->name}");
                    }
                }

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $isManual ? null : $item['id'],
                    'product_description' => $isManual ? $item['name'] : null,
                    'is_manual' => $isManual,
                    'unit' => $item['unit'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'price' => $itemPrice,
                ]);
            }

            // Update cash register session totals
            if (!empty($validated['cash_register_session_id'])) {
                $session = CashRegisterSession::find($validated['cash_register_session_id']);
                if ($session && $session->status === CashRegisterStatus::Open) {
                    $isCash = in_array($validated['payment_method'], ['cash']);
                    $session->addSale((float) $validated['total'], $isCash);
                }
            }

            // Mark quotation as converted if applicable
            if (!empty($validated['quotation_id'])) {
                $quotation = Quotation::find($validated['quotation_id']);
                if ($quotation && $quotation->status === QuotationStatus::Approved->value) {
                    $quotation->update(['status' => QuotationStatus::ConvertedToSale->value]);
                }
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

            // Recalculate total now that all items exist.
            // QuotationObserver.saved fires during Quotation::create (before items are
            // created), which would reset the total to 0. We correct it here.
            $quotation->updateQuietly([
                'total' => $quotation->quotation_items()->sum('price'),
            ]);

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

        $isApproved = $quotation->status === QuotationStatus::Approved->value;

        return view('pos.quotation-print', compact('quotation', 'isApproved'));
    }

    public function printReceipt(Sale $sale, Request $request): \Illuminate\Contracts\View\View
    {
        $sale->load(['customer', 'sale_items.product']);
        $type = $request->query('type', 'delivery');

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
