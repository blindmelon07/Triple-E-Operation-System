<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CashRegisterSession;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Sale;
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

        $requests = VoidRequest::with(['sale.customer', 'requestedBy'])
            ->pending()
            ->orderBy('created_at')
            ->get()
            ->map(fn ($vr) => [
                'id'            => $vr->id,
                'sale_id'       => $vr->sale_id,
                'sale_total'    => $vr->sale->total,
                'customer_name' => $vr->sale->customer?->name ?? 'Walk-in Customer',
                'void_reason'   => $vr->void_reason,
                'requested_by'  => $vr->requestedBy?->name ?? 'Unknown',
                'created_at'    => $vr->created_at->toDateTimeString(),
            ]);

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

        if ($voidRequest->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Request is no longer pending.'], 422);
        }

        $sale = $voidRequest->sale;

        if ($sale->is_voided) {
            return response()->json(['success' => false, 'message' => 'Sale is already voided.'], 422);
        }

        try {
            DB::beginTransaction();

            $sale->load('sale_items');

            // Restore inventory
            foreach ($sale->sale_items as $item) {
                if ($item->is_manual || ! $item->product_id) {
                    continue;
                }

                $inventory = Inventory::where('product_id', $item->product_id)->first();
                if ($inventory) {
                    $inventory->increment('quantity', $item->quantity);
                }

                InventoryMovement::create([
                    'product_id'     => $item->product_id,
                    'type'           => 'in',
                    'quantity'       => $item->quantity,
                    'reason'         => 'Void',
                    'reference_id'   => $sale->id,
                    'reference_type' => Sale::class,
                    'notes'          => 'Sale voided via POS (manager approved)',
                ]);
            }

            // Reverse register session totals for paid non-credit sales
            $wasCreditSale = ! empty($sale->payment_term_days);
            if (! $wasCreditSale && $sale->payment_status === 'paid' && $sale->cash_register_session_id) {
                $session = CashRegisterSession::find($sale->cash_register_session_id);
                if ($session) {
                    $isCash = $sale->payment_method === 'cash';
                    $session->reverseSale((float) $sale->total, $isCash);
                }
            }

            $sale->update([
                'is_voided'   => true,
                'voided_at'   => now(),
                'void_reason' => $voidRequest->void_reason,
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
                    'void_reason' => $voidRequest->void_reason,
                    'total'       => $sale->total,
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
