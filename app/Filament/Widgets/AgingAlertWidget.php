<?php

namespace App\Filament\Widgets;

use App\Models\Purchase;
use App\Models\Sale;
use Filament\Widgets\Widget;

class AgingAlertWidget extends Widget
{
    protected string $view = 'filament.widgets.aging-alert-widget';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function getAgingData(): array
    {
        // Get overdue customer invoices (Accounts Receivable)
        $overdueReceivables = Sale::where('payment_status', '!=', 'paid')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->with('customer')
            ->orderBy('due_date', 'asc')
            ->limit(10)
            ->get()
            ->map(function ($sale) {
                $daysOverdue = now()->diffInDays($sale->due_date);

                return [
                    'id' => $sale->id,
                    'type' => 'receivable',
                    'reference' => "INV-{$sale->id}",
                    'name' => $sale->customer?->name ?? 'Walk-in',
                    'amount' => $sale->balance,
                    'due_date' => $sale->due_date->format('M d, Y'),
                    'days_overdue' => $daysOverdue,
                    'severity' => $this->getSeverity($daysOverdue),
                ];
            });

        // Get overdue supplier bills (Accounts Payable)
        $overduePayables = Purchase::where('payment_status', '!=', 'paid')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->with('supplier')
            ->orderBy('due_date', 'asc')
            ->limit(10)
            ->get()
            ->map(function ($purchase) {
                $daysOverdue = now()->diffInDays($purchase->due_date);

                return [
                    'id' => $purchase->id,
                    'type' => 'payable',
                    'reference' => "PO-{$purchase->id}",
                    'name' => $purchase->supplier?->name ?? 'Unknown',
                    'amount' => $purchase->balance,
                    'due_date' => $purchase->due_date->format('M d, Y'),
                    'days_overdue' => $daysOverdue,
                    'severity' => $this->getSeverity($daysOverdue),
                ];
            });

        $totalOverdueReceivables = Sale::where('payment_status', '!=', 'paid')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->get()
            ->sum('balance');

        $totalOverduePayables = Purchase::where('payment_status', '!=', 'paid')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->get()
            ->sum('balance');

        return [
            'receivables' => $overdueReceivables,
            'payables' => $overduePayables,
            'total_overdue_receivables' => $totalOverdueReceivables,
            'total_overdue_payables' => $totalOverduePayables,
            'receivables_count' => Sale::where('payment_status', '!=', 'paid')
                ->whereNotNull('due_date')
                ->where('due_date', '<', now())
                ->count(),
            'payables_count' => Purchase::where('payment_status', '!=', 'paid')
                ->whereNotNull('due_date')
                ->where('due_date', '<', now())
                ->count(),
        ];
    }

    protected function getSeverity(int $daysOverdue): string
    {
        return match (true) {
            $daysOverdue > 90 => 'critical',
            $daysOverdue > 60 => 'high',
            $daysOverdue > 30 => 'medium',
            default => 'low',
        };
    }
}
