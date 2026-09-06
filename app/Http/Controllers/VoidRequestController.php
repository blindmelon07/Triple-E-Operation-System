<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CashRegisterSession;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\VoidRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoidRequestController extends Controller
{
    public function pending(): \Illuminate\Http\JsonResponse
    {
        if (! auth()->user()->hasAnyRole(['admin', 'super_admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $requests = VoidRequest::with(['sale.customer', 'saleItem.product', 'replacementProduct', 'requestedBy'])
            ->pending()
            ->orderBy('created_at')
            ->get()
            ->map(function ($vr) {
                // Both item voids and exchanges target a single line item, so the
                // outgoing item's details are shown for either.
                $isItemLevel  = $vr->sale_item_id !== null;
                $isExchange   = $vr->isItemExchange();
                $newItemPrice = $isExchange
                    ? round((float) $vr->replacement_unit_price * (float) $vr->replacement_quantity, 2)
                    : null;

                return [
                    'id'            => $vr->id,
                    'sale_id'       => $vr->sale_id,
                    'sale_total'    => $vr->sale->total,
                    'customer_name' => $vr->sale->customer?->name ?? 'Walk-in Customer',
                    'void_reason'   => $vr->void_reason,
                    'requested_by'  => $vr->requestedBy?->name ?? 'Unknown',
                    'created_at'    => $vr->created_at->toDateTimeString(),
                    'is_item_void'  => $vr->isItemVoid(),
                    'is_exchange'   => $isExchange,
                    'item_name'     => $isItemLevel
                        ? ($vr->saleItem?->is_manual ? $vr->saleItem?->product_description : $vr->saleItem?->product?->name)
                        : null,
                    'item_quantity' => $isItemLevel ? $vr->saleItem?->quantity : null,
                    'item_price'    => $isItemLevel ? $vr->saleItem?->price : null,

                    'replacement_name'     => $isExchange ? $vr->replacementProduct?->name : null,
                    'replacement_quantity' => $isExchange ? $vr->replacement_quantity : null,
                    'replacement_unit'     => $isExchange ? $vr->replacement_unit : null,
                    'replacement_price'    => $newItemPrice,
                    'price_difference'     => $isExchange
                        ? round($newItemPrice - (float) $vr->saleItem?->price, 2)
                        : null,
                ];
            });

        return response()->json(['success' => true, 'requests' => $requests]);
    }

    public function pendingCount(): \Illuminate\Http\JsonResponse
    {
        if (! auth()->user()->hasAnyRole(['admin', 'super_admin'])) {
            return response()->json(['count' => 0]);
        }

        return response()->json(['count' => VoidRequest::pending()->count()]);
    }

    public function status(VoidRequest $voidRequest): \Illuminate\Http\JsonResponse
    {
        // Cashier can only check their own requests
        if ($voidRequest->requested_by_id !== auth()->id()
            && ! auth()->user()->hasAnyRole(['admin', 'super_admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        return response()->json([
            'success'          => true,
            'status'           => $voidRequest->status,
            'rejection_reason' => $voidRequest->rejection_reason,
        ]);
    }

    public function approve(Request $request, VoidRequest $voidRequest): \Illuminate\Http\JsonResponse
    {
        if (! auth()->user()->hasAnyRole(['admin', 'super_admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        try {
            DB::beginTransaction();

            // Lock the row so concurrent approval requests queue up instead of racing
            $voidRequest = VoidRequest::where('id', $voidRequest->id)
                ->lockForUpdate()
                ->first();

            if ($voidRequest->status !== 'pending') {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Request is no longer pending.'], 422);
            }

            if ($voidRequest->isItemExchange()) {
                return $this->approveItemExchange($request, $voidRequest);
            }

            if ($voidRequest->isItemVoid()) {
                return $this->approveItemVoid($request, $voidRequest);
            }

            $sale = $voidRequest->sale;

            if ($sale->is_voided) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Sale is already voided.'], 422);
            }

            $sale->load('sale_items.product');

            // Restore inventory. Skip any item already individually voided —
            // its stock was already restored when that item-void was approved.
            foreach ($sale->sale_items as $item) {
                if ($item->is_manual || ! $item->product_id || $item->is_voided) {
                    continue;
                }

                $baseQuantity = $item->quantity * $item->product->conversionFactorFor($item->unit);

                $inventory = Inventory::where('product_id', $item->product_id)->first();
                if ($inventory) {
                    $inventory->increment('quantity', $baseQuantity);
                }

                InventoryMovement::create([
                    'product_id'     => $item->product_id,
                    'type'           => 'in',
                    'quantity'       => $baseQuantity,
                    'reason'         => 'Void',
                    'reference_id'   => $sale->id,
                    'reference_type' => Sale::class,
                    'notes'          => 'Sale voided via POS (manager approved)',
                ]);
            }

            // Reverse every register session this sale's money ever touched. Payments
            // collected after creation (or a partial down payment logged at creation)
            // are recorded in sale_payments, each stamped with the session it was
            // added to — reverse those individually rather than assuming one session.
            $loggedPayments = SalePayment::where('sale_id', $sale->id)->get();
            foreach ($loggedPayments as $payment) {
                if ($payment->cash_register_session_id) {
                    $paymentSession = CashRegisterSession::find($payment->cash_register_session_id);
                    if ($paymentSession) {
                        $paymentSession->reverseSale((float) $payment->amount, $payment->payment_method === 'cash');
                    }
                }
            }

            // Money collected at sale creation is only logged as a sale_payments row
            // when it was a partial down payment on a credit sale. A non-credit sale
            // paid in full, or a credit sale whose down payment covered the total, adds
            // straight to its creation session with no ledger row — recover that amount
            // as whatever's left of amount_paid after accounting for logged payments.
            $unloggedInitial = round((float) $sale->amount_paid - (float) $loggedPayments->sum('amount'), 2);
            if ($unloggedInitial > 0.01 && $sale->cash_register_session_id) {
                $creationSession = CashRegisterSession::find($sale->cash_register_session_id);
                if ($creationSession) {
                    $creationSession->reverseSale($unloggedInitial, $sale->payment_method === 'cash');
                }
            }

            $sale->update([
                'is_voided'      => true,
                'voided_at'      => now(),
                'void_reason'    => $voidRequest->void_reason,
                'amount_paid'    => 0,
                'payment_status' => 'unpaid',
            ]);

            $voidRequest->update([
                'status'       => 'approved',
                'reviewed_by_id' => auth()->id(),
                'reviewed_at'  => now(),
            ]);

            DB::commit();

            AuditLog::create([
                'user_id'         => auth()->id(),
                'user_name'       => auth()->user()?->name,
                'action'          => 'approved_void_request',
                'auditable_type'  => VoidRequest::class,
                'auditable_id'    => $voidRequest->id,
                'auditable_label' => "Void Request #{$voidRequest->id} for Sale #{$sale->id}",
                'new_values'      => [
                    'void_reason'      => $voidRequest->void_reason,
                    'total'            => $sale->total,
                    'payments_reversed' => (float) $loggedPayments->sum('amount') + max(0, $unloggedInitial),
                ],
                'ip_address'      => $request->ip(),
                'user_agent'      => $request->userAgent(),
            ]);

            return response()->json(['success' => true, 'message' => 'Void approved successfully.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Approve a request to void a single line item out of an otherwise-still-valid
     * sale: restores that item's stock, shrinks the sale's total by the item's
     * price, and — if that portion had already been collected — refunds it by
     * reversing the amount from whichever register session(s) originally took it
     * in (oldest money first), without touching the sale's transaction count.
     *
     * Runs inside the same DB transaction / lock that approve() already opened.
     */
    private function approveItemVoid(Request $request, VoidRequest $voidRequest): \Illuminate\Http\JsonResponse
    {
        $saleItem = SaleItem::where('id', $voidRequest->sale_item_id)
            ->lockForUpdate()
            ->first();

        if (! $saleItem) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Item no longer exists.'], 422);
        }

        if ($saleItem->is_voided) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Item has already been voided.'], 422);
        }

        $sale = $saleItem->sale;

        if (! $sale || $sale->is_voided) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Sale is already voided.'], 422);
        }

        // Restore inventory for this item only
        if (! $saleItem->is_manual && $saleItem->product_id) {
            $product = $saleItem->product;
            $baseQuantity = $saleItem->quantity * ($product?->conversionFactorFor($saleItem->unit) ?? 1);

            $inventory = Inventory::where('product_id', $saleItem->product_id)->first();
            if ($inventory) {
                $inventory->increment('quantity', $baseQuantity);
            }

            InventoryMovement::create([
                'product_id'     => $saleItem->product_id,
                'type'           => 'in',
                'quantity'       => $baseQuantity,
                'reason'         => 'Item Void',
                'reference_id'   => $sale->id,
                'reference_type' => Sale::class,
                'notes'          => "Item voided via POS (manager approved) — Sale #{$sale->id}",
            ]);
        }

        $itemAmount   = (float) $saleItem->price;
        $oldTotal     = (float) $sale->total;
        $newTotal     = max(0, round($oldTotal - $itemAmount, 2));

        $oldAmountPaid = (float) $sale->amount_paid;
        $newAmountPaid = min($oldAmountPaid, $newTotal);
        $refundAmount  = round($oldAmountPaid - $newAmountPaid, 2);

        $newPaymentStatus = match (true) {
            $newAmountPaid >= $newTotal - 0.01 => 'paid',
            $newAmountPaid > 0 => 'partial',
            default => 'unpaid',
        };

        $this->refundOldestFirst($sale, $refundAmount, $oldAmountPaid, $voidRequest->cash_register_session_id);

        $saleItem->update([
            'is_voided'   => true,
            'voided_at'   => now(),
            'void_reason' => $voidRequest->void_reason,
        ]);

        $sale->update([
            'total'          => $newTotal,
            'amount_paid'    => $newAmountPaid,
            'payment_status' => $newPaymentStatus,
        ]);

        $voidRequest->update([
            'status'         => 'approved',
            'reviewed_by_id' => auth()->id(),
            'reviewed_at'    => now(),
        ]);

        DB::commit();

        AuditLog::create([
            'user_id'         => auth()->id(),
            'user_name'       => auth()->user()?->name,
            'action'          => 'approved_item_void_request',
            'auditable_type'  => VoidRequest::class,
            'auditable_id'    => $voidRequest->id,
            'auditable_label' => "Item Void Request #{$voidRequest->id} for Sale #{$sale->id} (item #{$saleItem->id})",
            'new_values'      => [
                'void_reason'    => $voidRequest->void_reason,
                'item_amount'    => $itemAmount,
                'old_total'      => $oldTotal,
                'new_total'      => $newTotal,
                'refund_amount'  => max(0, $refundAmount),
            ],
            'ip_address'      => $request->ip(),
            'user_agent'      => $request->userAgent(),
        ]);

        return response()->json(['success' => true, 'message' => 'Item void approved successfully.']);
    }

    /**
     * Approve a request to swap one line item of a completed sale for a different
     * product: the outgoing item is marked voided and its stock returned, a fresh
     * line item is written for the replacement (whose stock is taken), and the
     * sale's total is re-struck around the price difference — collecting the extra
     * from the customer or refunding them, depending on which way it went.
     *
     * Runs inside the same DB transaction / lock that approve() already opened.
     */
    private function approveItemExchange(Request $request, VoidRequest $voidRequest): \Illuminate\Http\JsonResponse
    {
        $saleItem = SaleItem::where('id', $voidRequest->sale_item_id)
            ->lockForUpdate()
            ->first();

        if (! $saleItem) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Item no longer exists.'], 422);
        }

        if ($saleItem->is_voided) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Item has already been voided.'], 422);
        }

        $sale = $saleItem->sale;

        if (! $sale || $sale->is_voided) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Sale is already voided.'], 422);
        }

        $replacement = Product::find($voidRequest->replacement_product_id);

        if (! $replacement) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Replacement product no longer exists.'], 422);
        }

        $newQuantity  = (float) $voidRequest->replacement_quantity;
        $newUnit      = $voidRequest->replacement_unit;
        $newUnitPrice = (float) $voidRequest->replacement_unit_price;
        $newBaseQty   = $newQuantity * $replacement->conversionFactorFor($newUnit);
        $newItemPrice = round($newUnitPrice * $newQuantity, 2);

        $itemAmount = (float) $saleItem->price;

        // Return the outgoing item's stock first — the replacement may well be the
        // same product in a different unit or quantity, in which case the stock
        // check below has to see the returned units.
        if (! $saleItem->is_manual && $saleItem->product_id) {
            $oldProduct  = $saleItem->product;
            $oldBaseQty  = $saleItem->quantity * ($oldProduct?->conversionFactorFor($saleItem->unit) ?? 1);
            $oldInventory = Inventory::where('product_id', $saleItem->product_id)->first();

            if ($oldInventory) {
                $oldInventory->increment('quantity', $oldBaseQty);
            }

            InventoryMovement::create([
                'product_id'     => $saleItem->product_id,
                'type'           => 'in',
                'quantity'       => $oldBaseQty,
                'reason'         => 'Item Exchange',
                'reference_id'   => $sale->id,
                'reference_type' => Sale::class,
                'notes'          => "Item exchanged out via POS (manager approved) — Sale #{$sale->id}",
            ]);
        }

        $replacementInventory = Inventory::where('product_id', $replacement->id)->first();

        if ($replacementInventory && (float) $replacementInventory->quantity < $newBaseQty) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => "Not enough stock for replacement product: {$replacement->name}",
            ], 422);
        }

        $saleItem->update([
            'is_voided'   => true,
            'voided_at'   => now(),
            'void_reason' => 'Exchanged: ' . $voidRequest->void_reason,
        ]);

        // Creating the SaleItem both takes the replacement's stock (SaleItem::booted)
        // and logs its own outgoing movement (SaleItemObserver), so nothing is done
        // by hand for the incoming side beyond relabelling that movement below.
        $newSaleItem = SaleItem::create([
            'sale_id'          => $sale->id,
            'product_id'       => $replacement->id,
            'is_manual'        => false,
            'unit'             => $newUnit,
            'unit_price'       => $newUnitPrice,
            'discount_amount'  => 0,
            'discount_is_flat' => false,
            'quantity'         => $newQuantity,
            'price'            => $newItemPrice,
        ]);

        // Relabel the observer's row so the stock ledger reads as an exchange rather
        // than an ordinary sale — writing our own would double-log the same movement.
        $outgoingMovement = InventoryMovement::where('reference_type', Sale::class)
            ->where('reference_id', $sale->id)
            ->where('product_id', $replacement->id)
            ->where('type', 'out')
            ->orderByDesc('id')
            ->first();

        if ($outgoingMovement) {
            $outgoingMovement->update([
                'reason' => 'Item Exchange',
                'notes'  => "Item exchanged in via POS (manager approved) — Sale #{$sale->id}",
            ]);
        }

        $oldTotal      = (float) $sale->total;
        $newTotal      = max(0, round($oldTotal - $itemAmount + $newItemPrice, 2));
        $oldAmountPaid = (float) $sale->amount_paid;
        $wasFullyPaid  = $oldAmountPaid >= $oldTotal - 0.01;

        $collectedNow  = 0.0;
        $refundAmount  = 0.0;

        if ($newTotal > $oldAmountPaid) {
            if ($wasFullyPaid) {
                // The sale was already settled, so the customer pays the difference
                // over the counter now. That money is posted to whichever session is
                // open right now for the cashier processing the exchange — which may
                // not be the same session (or even the same day) that took the sale's
                // original payment — no SalePayment row, for the same reason
                // completeSale() skips one on a paid sale: it would double-count
                // against the sale itself in the reports.
                $collectedNow = round($newTotal - $oldAmountPaid, 2);
                $session = CashRegisterSession::find($voidRequest->cash_register_session_id);

                if ($session) {
                    $session->addAmount($collectedNow, $sale->payment_method === 'cash');
                }

                $newAmountPaid = $newTotal;
            } else {
                // Sale still has an open balance — the extra just increases what's owed.
                $newAmountPaid = $oldAmountPaid;
            }
        } else {
            // Replacement is cheaper: hand back whatever is now overpaid, reversing it
            // out of the session(s) that took it in.
            $newAmountPaid = min($oldAmountPaid, $newTotal);
            $refundAmount  = round($oldAmountPaid - $newAmountPaid, 2);

            $this->refundOldestFirst($sale, $refundAmount, $oldAmountPaid, $voidRequest->cash_register_session_id);
        }

        $newPaymentStatus = match (true) {
            $newAmountPaid >= $newTotal - 0.01 => 'paid',
            $newAmountPaid > 0 => 'partial',
            default => 'unpaid',
        };

        $sale->update([
            'total'          => $newTotal,
            'amount_paid'    => $newAmountPaid,
            'payment_status' => $newPaymentStatus,
        ]);

        $voidRequest->update([
            'status'         => 'approved',
            'reviewed_by_id' => auth()->id(),
            'reviewed_at'    => now(),
        ]);

        DB::commit();

        AuditLog::create([
            'user_id'         => auth()->id(),
            'user_name'       => auth()->user()?->name,
            'action'          => 'approved_item_exchange_request',
            'auditable_type'  => VoidRequest::class,
            'auditable_id'    => $voidRequest->id,
            'auditable_label' => "Item Exchange Request #{$voidRequest->id} for Sale #{$sale->id} (item #{$saleItem->id} → #{$newSaleItem->id})",
            'new_values'      => [
                'reason'            => $voidRequest->void_reason,
                'old_item_amount'   => $itemAmount,
                'new_item_amount'   => $newItemPrice,
                'replacement'       => $replacement->name,
                'old_total'         => $oldTotal,
                'new_total'         => $newTotal,
                'collected_now'     => $collectedNow,
                'refund_amount'     => $refundAmount,
            ],
            'ip_address'      => $request->ip(),
            'user_agent'      => $request->userAgent(),
        ]);

        $difference = round($newItemPrice - $itemAmount, 2);
        $message = match (true) {
            $difference > 0.01  => 'Exchange approved. Collect ₱' . number_format(abs($difference), 2) . ' from the customer.',
            $difference < -0.01 => 'Exchange approved. Refund ₱' . number_format(abs($difference), 2) . ' to the customer.',
            default             => 'Exchange approved. No price difference.',
        };

        return response()->json([
            'success'    => true,
            'message'    => $message,
            'difference' => $difference,
        ]);
    }

    /**
     * Pay back part of what a sale already collected, reversing the oldest money
     * first: the initial collection at sale creation (when it wasn't logged as a
     * SalePayment row), then each later settlement in order.
     *
     * Every reversal posts against $actingSessionId — the register session that is
     * open right now and doing the refund — rather than whichever session
     * originally took each payment in. A void/exchange can be requested against a
     * sale from a previous, already-closed shift, and that shift's totals were
     * already reconciled and reported on at close; rewriting them after the fact
     * would silently desync a closed session from its own closure report. The
     * money is physically leaving today's drawer, so today's session is what
     * should reflect it.
     */
    private function refundOldestFirst(Sale $sale, float $refundAmount, float $oldAmountPaid, ?int $actingSessionId): void
    {
        $actingSession = $actingSessionId ? CashRegisterSession::find($actingSessionId) : null;

        if (! $actingSession) {
            return;
        }

        $loggedPayments  = SalePayment::where('sale_id', $sale->id)->orderBy('created_at')->get();
        $unloggedInitial = round($oldAmountPaid - (float) $loggedPayments->sum('amount'), 2);
        $remaining       = $refundAmount;

        if ($remaining > 0.01 && $unloggedInitial > 0.01) {
            $take = min($remaining, $unloggedInitial);
            $actingSession->reverseAmount($take, $sale->payment_method === 'cash');
            $remaining = round($remaining - $take, 2);
        }

        foreach ($loggedPayments as $payment) {
            if ($remaining <= 0.01) {
                break;
            }
            $take = min($remaining, (float) $payment->amount);
            $actingSession->reverseAmount($take, $payment->payment_method === 'cash');
            $remaining = round($remaining - $take, 2);
        }
    }

    public function reject(Request $request, VoidRequest $voidRequest): \Illuminate\Http\JsonResponse
    {
        if (! auth()->user()->hasAnyRole(['admin', 'super_admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:255',
        ]);

        if ($voidRequest->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Request is no longer pending.'], 422);
        }

        $voidRequest->update([
            'status'           => 'rejected',
            'reviewed_by_id'   => auth()->id(),
            'reviewed_at'      => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        AuditLog::create([
            'user_id'         => auth()->id(),
            'user_name'       => auth()->user()?->name,
            'action'          => 'rejected_void_request',
            'auditable_type'  => VoidRequest::class,
            'auditable_id'    => $voidRequest->id,
            'auditable_label' => "Void Request #{$voidRequest->id} for Sale #{$voidRequest->sale_id}",
            'new_values'      => ['rejection_reason' => $validated['rejection_reason']],
            'ip_address'      => $request->ip(),
            'user_agent'      => $request->userAgent(),
        ]);

        return response()->json(['success' => true, 'message' => 'Void request rejected.']);
    }

    public function cancel(VoidRequest $voidRequest): \Illuminate\Http\JsonResponse
    {
        if ($voidRequest->requested_by_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if ($voidRequest->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Request is no longer pending.'], 422);
        }

        $voidRequest->update(['status' => 'rejected', 'rejection_reason' => 'Cancelled by cashier']);

        return response()->json(['success' => true, 'message' => 'Void request cancelled.']);
    }
}
