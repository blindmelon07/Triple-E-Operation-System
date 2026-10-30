<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\SalePayment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemovePaymentWithholding extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:remove-withholding
                            {payment : The sale_payments id}
                            {--reason= : Why it is being removed (saved to the audit log)}
                            {--apply : Write the change (without it, only a preview is shown)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove a tax-withheld amount entered by mistake on an invoice payment, putting it back on the customer balance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $payment = SalePayment::with('sale.customer')->find($this->argument('payment'));

        if (! $payment) {
            $this->error('Payment not found.');

            return Command::FAILURE;
        }

        $withholding = round((float) $payment->withholding_amount, 2);

        if ($withholding <= 0) {
            $this->warn("Payment #{$payment->id} has no withholding to remove.");

            return Command::SUCCESS;
        }

        $sale = $payment->sale;
        $newAmountPaid = round((float) $sale->amount_paid - $withholding, 2);
        $newStatus = $newAmountPaid >= (float) $sale->total - 0.001 ? 'paid' : ($newAmountPaid > 0 ? 'partial' : 'unpaid');

        $this->table(['', 'Before', 'After'], [
            ['Customer', $sale->customer?->name, ''],
            ["Sale #{$sale->id} total", number_format((float) $sale->total, 2), ''],
            ['Payment amount', number_format((float) $payment->amount, 2), number_format((float) $payment->amount, 2)],
            ['Tax withheld', number_format($withholding, 2), '0.00'],
            ['Sale amount paid', number_format((float) $sale->amount_paid, 2), number_format($newAmountPaid, 2)],
            ['Sale status', $sale->payment_status, $newStatus],
            ['Payment balance after', number_format((float) $payment->balance_after, 2), number_format((float) $payment->balance_after + $withholding, 2)],
        ]);

        if (! $this->option('apply')) {
            $this->info('Preview only — run again with --apply to save.');

            return Command::SUCCESS;
        }

        DB::transaction(function () use ($payment, $sale, $withholding, $newAmountPaid, $newStatus) {
            // Withheld tax never went into the cash register, so only the
            // payment row and the sale's paid amount need to change.
            $payment->update([
                'withholding_amount' => 0,
                'balance_after' => (float) $payment->balance_after + $withholding,
            ]);

            $sale->update([
                'amount_paid' => $newAmountPaid,
                'payment_status' => $newStatus,
            ]);

            AuditLog::create([
                'user_id' => null,
                'user_name' => 'system (payments:remove-withholding)',
                'action' => 'removed_withholding',
                'auditable_type' => $sale->getMorphClass(),
                'auditable_id' => $sale->getKey(),
                'auditable_label' => "Sale #{$sale->id}",
                'old_values' => ['withholding_amount' => $withholding, 'sale_payment_id' => $payment->id],
                'new_values' => [
                    'withholding_amount' => 0,
                    'amount_paid' => $newAmountPaid,
                    'payment_status' => $newStatus,
                    'reason' => $this->option('reason'),
                ],
            ]);
        });

        $this->info("Removed ₱".number_format($withholding, 2)." withholding from payment #{$payment->id}. Sale #{$sale->id} is now {$newStatus}.");

        return Command::SUCCESS;
    }
}
