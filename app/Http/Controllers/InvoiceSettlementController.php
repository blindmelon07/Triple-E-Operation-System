<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CashRegisterSession;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SalePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceSettlementController extends Controller
{
    public function outstandingInvoices(Customer $customer): \Illuminate\Http\JsonResponse
    {
        if (! auth()->user()->hasPermissionTo('RecordPaymentSale')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $invoices = Sale::where('customer_id', $customer->id)
            ->where('payment_status', '!=', 'paid')
            ->where('is_voided', false)
            ->orderBy('due_date')
            ->get()
            ->map(fn (Sale $sale) => [
                'id'             => $sale->id,
                'invoice_number' => 'INV-'.str_pad((string) $sale->id, 6, '0', STR_PAD_LEFT),
                'date'           => $sale->date?->toDateString(),
                'due_date'       => $sale->due_date?->toDateString(),
                'total'          => (float) $sale->total,
                'amount_paid'    => (float) $sale->amount_paid,
                'balance'        => $sale->balance,
                'days_overdue'   => $sale->days_overdue,
                'aging_bucket'   => $sale->aging_bucket,
                'payment_status' => $sale->payment_status,
            ]);

        return response()->json(['success' => true, 'invoices' => $invoices]);
    }

    public function settle(Request $request): \Illuminate\Http\JsonResponse
    {
        if (! auth()->user()->hasPermissionTo('RecordPaymentSale')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'customer_id'              => 'required|exists:customers,id',
            'cash_register_session_id' => 'required|exists:cash_register_sessions,id',
            'payment_method'           => 'required|string|in:cash,gcash,paymaya,card,bank,check',
            'reference_number'         => 'nullable|string|max:100',
            'payments'                 => 'required|array|min:1',
            'payments.*.sale_id'       => 'required|distinct|exists:sales,id',
            'payments.*.amount'        => 'required|numeric|min:0.01',
        ]);

        $session = CashRegisterSession::where('id', $validated['cash_register_session_id'])
            ->open()
            ->forUser(auth()->id())
            ->first();

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => 'No open register session found. Please open your register before settling payments.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $isCash = $validated['payment_method'] === 'cash';
            $results = [];

            foreach ($validated['payments'] as $paymentInput) {
                $sale = Sale::where('id', $paymentInput['sale_id'])->lockForUpdate()->first();

                if ((int) $sale->customer_id !== (int) $validated['customer_id']) {
                    throw new \Exception("Invoice #{$sale->id} does not belong to the selected customer.");
                }
                if ($sale->is_voided) {
                    throw new \Exception("Invoice #{$sale->id} has been voided.");
                }
                if ($sale->payment_status === 'paid') {
                    throw new \Exception("Invoice #{$sale->id} is already fully paid.");
                }

                $amount = (float) $paymentInput['amount'];
                $balance = $sale->balance;

                if ($amount > $balance + 0.001) {
                    throw new \Exception('Payment of ₱'.number_format($amount, 2)." exceeds the balance of ₱".number_format($balance, 2)." for Invoice #{$sale->id}.");
                }

                $newAmountPaid = (float) $sale->amount_paid + $amount;
                $balanceAfter = (float) $sale->total - $newAmountPaid;

                $sale->update([
                    'amount_paid'    => $newAmountPaid,
                    'payment_status' => $newAmountPaid >= (float) $sale->total ? 'paid' : 'partial',
                    'payment_method' => $validated['payment_method'],
                    'paid_date'      => now()->toDateString(),
                ]);

                $payment = SalePayment::create([
                    'sale_id'                  => $sale->id,
                    'amount'                   => $amount,
                    'payment_method'           => $validated['payment_method'],
                    'reference_number'         => $validated['reference_number'] ?? null,
                    'cash_register_session_id' => $session->id,
                    'recorded_by_id'           => auth()->id(),
                    'balance_after'            => $balanceAfter,
                ]);

                // Only the amount actually collected now counts toward the register —
                // never the invoice's original total, since credit-term sales were
                // never added to any session at creation.
                $session->addSale($amount, $isCash);

                AuditLog::create([
                    'user_id'         => auth()->id(),
                    'user_name'       => auth()->user()?->name,
                    'action'          => 'settled_invoice_payment',
                    'auditable_type'  => Sale::class,
                    'auditable_id'    => $sale->id,
                    'auditable_label' => "Sale #{$sale->id}",
                    'new_values'      => [
                        'amount_paid_now'          => $amount,
                        'payment_method'           => $validated['payment_method'],
                        'new_balance'              => $balanceAfter,
                        'cash_register_session_id' => $session->id,
                    ],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                $results[] = [
                    'sale_id'   => $sale->id,
                    'amount'    => $amount,
                    'balance'   => $balanceAfter,
                    'print_url' => route('pos.print-payment-receipt', $payment->id),
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment settled successfully.',
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function printPaymentReceipt(SalePayment $payment): \Illuminate\Contracts\View\View
    {
        $payment->load(['sale.customer', 'recordedBy']);

        return view('pos.payment-receipt-print', compact('payment'));
    }
}
