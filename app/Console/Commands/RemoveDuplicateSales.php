<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\VoidRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RemoveDuplicateSales extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sales:remove-duplicates
                            {--from= : Start date (Y-m-d), defaults to 30 days ago}
                            {--to= : End date (Y-m-d), defaults to today}
                            {--seconds=30 : Max seconds between two identical sales to count as a double entry}
                            {--reason=Duplicate entry (double-submitted sale) : Void reason saved on each duplicate}
                            {--only= : Comma-separated sale ids — limit voiding to these duplicates}
                            {--apply : Void the duplicates (without it, only a preview is shown)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find sales recorded twice by a double-submitted checkout and void the extra copies (restores stock and register totals)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $from = Carbon::parse($this->option('from') ?? now()->subDays(30))->startOfDay();
        $to = Carbon::parse($this->option('to') ?? now())->endOfDay();
        $window = (int) $this->option('seconds');

        $sales = Sale::with(['sale_items', 'customer'])
            ->where('is_voided', false)
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        // Walk sales oldest-first; a sale is a duplicate when the previous sale with the
        // exact same customer, register, payment and items was made moments before it.
        // The first copy is always kept.
        $lastSeen = [];
        $duplicates = [];
        foreach ($sales as $sale) {
            $key = $this->fingerprint($sale);

            if (isset($lastSeen[$key]) && $lastSeen[$key]->created_at->diffInSeconds($sale->created_at) <= $window) {
                $duplicates[] = ['sale' => $sale, 'original' => $lastSeen[$key], 'skip' => $this->skipReason($sale)];
            }

            $lastSeen[$key] = $sale;
        }

        if (empty($duplicates)) {
            $this->info("No duplicate sales found between {$from->toDateString()} and {$to->toDateString()}.");

            return Command::SUCCESS;
        }

        $this->table(
            ['Duplicate', 'Copy of', 'Created', 'Gap (s)', 'Customer', 'Total', 'Payment', 'Action'],
            collect($duplicates)->map(fn ($d) => [
                '#'.str_pad($d['sale']->id, 6, '0', STR_PAD_LEFT),
                '#'.str_pad($d['original']->id, 6, '0', STR_PAD_LEFT),
                $d['sale']->created_at->toDateTimeString(),
                (int) $d['original']->created_at->diffInSeconds($d['sale']->created_at),
                $d['sale']->customer?->name ?? 'Walk-in',
                number_format((float) $d['sale']->total, 2),
                $d['sale']->payment_method,
                $d['skip'] ? "SKIP: {$d['skip']}" : 'void',
            ])
        );

        $only = array_filter(array_map('intval', explode(',', (string) $this->option('only'))));
        $toVoid = array_filter($duplicates, fn ($d) => ! $d['skip'] && (empty($only) || in_array($d['sale']->id, $only, true)));
        $amount = array_sum(array_map(fn ($d) => (float) $d['sale']->total, $toVoid));
        $this->line(count($toVoid).' duplicate(s) to void, totalling ₱'.number_format($amount, 2).'.');

        if (! $this->option('apply')) {
            $this->info('Preview only — run again with --apply to void them.');

            return Command::SUCCESS;
        }

        foreach ($toVoid as $d) {
            $sale = $d['sale'];

            DB::transaction(function () use ($sale, $d) {
                $reversed = $sale->voidAndReverse($this->option('reason'), "Duplicate of sale #{$d['original']->id} voided (sales:remove-duplicates)");

                AuditLog::create([
                    'user_id' => null,
                    'user_name' => 'system (sales:remove-duplicates)',
                    'action' => 'voided_duplicate_sale',
                    'auditable_type' => $sale->getMorphClass(),
                    'auditable_id' => $sale->getKey(),
                    'auditable_label' => "Sale #{$sale->id}",
                    'new_values' => [
                        'duplicate_of' => $d['original']->id,
                        'total' => $sale->total,
                        'payments_reversed' => $reversed,
                        'reason' => $this->option('reason'),
                    ],
                ]);
            });
        }

        $this->info('Voided '.count($toVoid).' duplicate sale(s).');

        return Command::SUCCESS;
    }

    /**
     * Everything that has to match for two sales to be the same checkout.
     */
    private function fingerprint(Sale $sale): string
    {
        $items = $sale->sale_items
            ->map(fn ($i) => implode(':', [
                $i->product_id ?? 'm:'.$i->product_description,
                $i->unit,
                (float) $i->quantity,
                (float) $i->unit_price,
                (float) $i->discount_amount,
                (int) $i->discount_is_flat,
            ]))
            ->sort()
            ->implode('|');

        return implode('#', [
            $sale->customer_id,
            $sale->cash_register_session_id,
            $sale->payment_method,
            (float) $sale->total,
            (float) $sale->delivery_fee,
            $sale->payment_term_days,
            $items,
        ]);
    }

    /**
     * A duplicate that has since been worked on (paid, delivered, partly voided)
     * needs a person to look at it — voiding it automatically could undo real activity.
     */
    private function skipReason(Sale $sale): ?string
    {
        if ($sale->sale_items->contains(fn ($i) => $i->is_voided)) {
            return 'has voided/exchanged items';
        }

        if (VoidRequest::where('sale_id', $sale->id)->where('status', 'pending')->exists()) {
            return 'void request pending';
        }

        if (SalePayment::where('sale_id', $sale->id)->where('created_at', '>', $sale->created_at->copy()->addMinute())->exists()) {
            return 'has later payments';
        }

        if ($sale->delivery()->exists()) {
            return 'has a delivery';
        }

        return null;
    }
}
