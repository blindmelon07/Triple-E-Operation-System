<?php

namespace App\Filament\Widgets;

use App\Models\Purchase;
use App\Models\Sale;
use Filament\Widgets\Widget;

class CollectionReminderWidget extends Widget
{
    protected string $view = 'filament.widgets.collection-reminder-widget';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function getCollectionData(): array
    {
        $today = now()->startOfDay();
        $nextWeek = now()->addDays(7)->endOfDay();

        // Invoices due soon (within 7 days) - for collection follow-up
        $dueSoonReceivables = Sale::where('payment_status', '!=', 'paid')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$today, $nextWeek])
            ->with('customer')
            ->orderBy('due_date', 'asc')
            ->limit(10)
            ->get()
            ->map(function ($sale) {
                $daysUntilDue = now()->diffInDays($sale->due_date, false);

                return [
                    'id' => $sale->id,
                    'type' => 'receivable',
                    'reference' => "INV-{$sale->id}",
                    'name' => $sale->customer?->name ?? 'Walk-in',
                    'amount' => $sale->balance,
                    'due_date' => $sale->due_date->format('M d, Y'),
                    'days_until_due' => max(0, $daysUntilDue),
                    'urgency' => $this->getUrgency($daysUntilDue),
                ];
            });

        // Bills due soon (within 7 days) - for payment planning
        $dueSoonPayables = Purchase::where('payment_status', '!=', 'paid')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$today, $nextWeek])
            ->with('supplier')
            ->orderBy('due_date', 'asc')
            ->limit(10)
            ->get()
            ->map(function ($purchase) {
                $daysUntilDue = now()->diffInDays($purchase->due_date, false);

                return [
                    'id' => $purchase->id,
                    'type' => 'payable',
                    'reference' => "PO-{$purchase->id}",
                    'name' => $purchase->supplier?->name ?? 'Unknown',
                    'amount' => $purchase->balance,
                    'due_date' => $purchase->due_date->format('M d, Y'),
                    'days_until_due' => max(0, $daysUntilDue),
                    'urgency' => $this->getUrgency($daysUntilDue),
                ];
            });

        // Get totals
        $totalDueSoonReceivables = Sale::where('payment_status', '!=', 'paid')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$today, $nextWeek])
            ->get()
            ->sum('balance');

        $totalDueSoonPayables = Purchase::where('payment_status', '!=', 'paid')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$today, $nextWeek])
            ->get()
            ->sum('balance');

        // Due today counts
        $dueToday = Sale::where('payment_status', '!=', 'paid')
            ->whereNotNull('due_date')
            ->whereDate('due_date', $today)
            ->count();

        $payableDueToday = Purchase::where('payment_status', '!=', 'paid')
            ->whereNotNull('due_date')
            ->whereDate('due_date', $today)
            ->count();

        return [
            'receivables' => $dueSoonReceivables,
            'payables' => $dueSoonPayables,
            'total_due_soon_receivables' => $totalDueSoonReceivables,
            'total_due_soon_payables' => $totalDueSoonPayables,
            'receivables_due_today' => $dueToday,
            'payables_due_today' => $payableDueToday,
        ];
    }

    protected function getUrgency(int $daysUntilDue): string
    {
        return match (true) {
            $daysUntilDue <= 0 => 'due-today',
            $daysUntilDue <= 2 => 'urgent',
            $daysUntilDue <= 5 => 'soon',
            default => 'normal',
        };
    }
}
