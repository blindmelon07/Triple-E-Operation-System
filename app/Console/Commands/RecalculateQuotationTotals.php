<?php

namespace App\Console\Commands;

use App\Models\Quotation;
use Illuminate\Console\Command;

class RecalculateQuotationTotals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quotations:recalculate-totals';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate totals for all quotations based on their items';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Recalculating quotation totals...');

        $quotations = Quotation::with('quotation_items')->get();
        $count = 0;

        foreach ($quotations as $quotation) {
            $total = $quotation->quotation_items->sum('price');
            $quotation->updateQuietly(['total' => $total]);
            $count++;
        }

        $this->info("Successfully recalculated totals for {$count} quotations.");

        return Command::SUCCESS;
    }
}
