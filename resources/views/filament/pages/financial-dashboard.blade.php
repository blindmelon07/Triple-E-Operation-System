<x-filament-panels::page>
    @php
        $peso = '&#8369;';
    @endphp

    {{-- Period Selector --}}
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 style="font-size: 1.25rem; font-weight: 600;">Financial Overview</h2>
        <x-filament::input.wrapper>
            <x-filament::input.select wire:model.live="period">
                @foreach(\App\Filament\Pages\FinancialDashboard::getPeriodOptions() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>
    </div>

    {{-- Main Metrics Row --}}
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
        {{-- Revenue --}}
        <x-filament::section>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <x-filament::icon icon="heroicon-o-banknotes" style="width: 1.5rem; height: 1.5rem; color: #3b82f6;" />
                <div>
                    <p style="font-size: 0.875rem; color: #6b7280;">Total Revenue</p>
                    <p style="font-size: 1.5rem; font-weight: bold;">{!! $peso !!}{{ number_format($dashboardData['revenue'] ?? 0, 2) }}</p>
                    <p style="font-size: 0.75rem; color: #9ca3af;">Collections: {!! $peso !!}{{ number_format($dashboardData['collections'] ?? 0, 2) }}</p>
                </div>
            </div>
        </x-filament::section>

        {{-- Gross Profit --}}
        <x-filament::section>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <x-filament::icon icon="heroicon-o-arrow-trending-up" style="width: 1.5rem; height: 1.5rem; color: #10b981;" />
                <div>
                    <p style="font-size: 0.875rem; color: #6b7280;">Gross Profit</p>
                    <p style="font-size: 1.5rem; font-weight: bold; color: {{ ($dashboardData['gross_profit'] ?? 0) >= 0 ? '#10b981' : '#ef4444' }};">{!! $peso !!}{{ number_format($dashboardData['gross_profit'] ?? 0, 2) }}</p>
                    <p style="font-size: 0.75rem; color: #9ca3af;">Margin: {{ number_format($dashboardData['gross_profit_margin'] ?? 0, 1) }}%</p>
                </div>
            </div>
        </x-filament::section>

        {{-- Operating Expenses --}}
        <x-filament::section>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <x-filament::icon icon="heroicon-o-receipt-percent" style="width: 1.5rem; height: 1.5rem; color: #f59e0b;" />
                <div>
                    <p style="font-size: 0.875rem; color: #6b7280;">Operating Expenses</p>
                    <p style="font-size: 1.5rem; font-weight: bold;">{!! $peso !!}{{ number_format(($dashboardData['expenses'] ?? 0) + ($dashboardData['maintenance_costs'] ?? 0), 2) }}</p>
                    <p style="font-size: 0.75rem; color: #9ca3af;">Maintenance: {!! $peso !!}{{ number_format($dashboardData['maintenance_costs'] ?? 0, 2) }}</p>
                </div>
            </div>
        </x-filament::section>

        {{-- Net Profit --}}
        <x-filament::section>
            <div style="display: flex; align-items: center; gap: 1rem;">
                @if(($dashboardData['net_profit'] ?? 0) >= 0)
                    <x-filament::icon icon="heroicon-o-check-circle" style="width: 1.5rem; height: 1.5rem; color: #10b981;" />
                @else
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" style="width: 1.5rem; height: 1.5rem; color: #ef4444;" />
                @endif
                <div>
                    <p style="font-size: 0.875rem; color: #6b7280;">Net {{ ($dashboardData['net_profit'] ?? 0) >= 0 ? 'Profit' : 'Loss' }}</p>
                    <p style="font-size: 1.5rem; font-weight: bold; color: {{ ($dashboardData['net_profit'] ?? 0) >= 0 ? '#10b981' : '#ef4444' }};">{!! $peso !!}{{ number_format(abs($dashboardData['net_profit'] ?? 0), 2) }}</p>
                    <p style="font-size: 0.75rem; color: #9ca3af;">Margin: {{ number_format($dashboardData['net_profit_margin'] ?? 0, 1) }}%</p>
                </div>
            </div>
        </x-filament::section>
    </div>

    {{-- Receivables & Payables --}}
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
        {{-- Accounts Receivable --}}
        <x-filament::section>
            <x-slot name="heading">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>Accounts Receivable</span>
                    <x-filament::badge color="warning">Outstanding</x-filament::badge>
                </div>
            </x-slot>
            <p style="font-size: 1.875rem; font-weight: bold;">{!! $peso !!}{{ number_format($dashboardData['accounts_receivable'] ?? 0, 2) }}</p>
            <p style="font-size: 0.875rem; color: #6b7280; margin-top: 0.5rem;">Total amount owed by customers</p>
            <div style="margin-top: 1rem;">
                <x-filament::link :href="route('filament.tos.resources.sales.index')" icon="heroicon-m-arrow-right" icon-position="after">
                    View Sales
                </x-filament::link>
            </div>
        </x-filament::section>

        {{-- Accounts Payable --}}
        <x-filament::section>
            <x-slot name="heading">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>Accounts Payable</span>
                    <x-filament::badge color="danger">To Pay</x-filament::badge>
                </div>
            </x-slot>
            <p style="font-size: 1.875rem; font-weight: bold;">{!! $peso !!}{{ number_format($dashboardData['accounts_payable'] ?? 0, 2) }}</p>
            <p style="font-size: 0.875rem; color: #6b7280; margin-top: 0.5rem;">Total amount owed to suppliers</p>
            <div style="margin-top: 1rem;">
                <x-filament::link :href="route('filament.tos.resources.purchases.index')" icon="heroicon-m-arrow-right" icon-position="after">
                    View Purchases
                </x-filament::link>
            </div>
        </x-filament::section>
    </div>

    {{-- Monthly Trend --}}
    <x-filament::section style="margin-bottom: 1.5rem;">
        <x-slot name="heading">12-Month Financial Trend</x-slot>
        @if($monthlyTrend && count($monthlyTrend) > 0)
            <div style="overflow-x: auto;">
                <table style="width: 100%; font-size: 0.875rem; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <th style="padding: 0.75rem 0; text-align: left; font-weight: 500; color: #6b7280;">Month</th>
                            <th style="padding: 0.75rem 0; text-align: right; font-weight: 500; color: #6b7280;">Revenue</th>
                            <th style="padding: 0.75rem 0; text-align: right; font-weight: 500; color: #6b7280;">Expenses</th>
                            <th style="padding: 0.75rem 0; text-align: right; font-weight: 500; color: #6b7280;">Profit/Loss</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($monthlyTrend as $month)
                            <tr style="border-bottom: 1px solid #f3f4f6;">
                                <td style="padding: 0.75rem 0;">{{ \Carbon\Carbon::parse($month->month . '-01')->format('M Y') }}</td>
                                <td style="padding: 0.75rem 0; text-align: right;">{!! $peso !!}{{ number_format($month->revenue, 2) }}</td>
                                <td style="padding: 0.75rem 0; text-align: right;">{!! $peso !!}{{ number_format($month->expenses, 2) }}</td>
                                <td style="padding: 0.75rem 0; text-align: right; font-weight: 500; color: {{ $month->profit >= 0 ? '#10b981' : '#ef4444' }};">
                                    {{ $month->profit >= 0 ? '' : '-' }}{!! $peso !!}{{ number_format(abs($month->profit), 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div style="text-align: center; padding: 2rem;">
                <p style="color: #6b7280;">No financial data available yet</p>
            </div>
        @endif
    </x-filament::section>

    {{-- Expense Breakdown --}}
    @if($expensesByCategory && count($expensesByCategory) > 0)
        <x-filament::section style="margin-bottom: 1.5rem;">
            <x-slot name="heading">Expense Breakdown</x-slot>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                @php
                    $totalExpenses = $expensesByCategory->sum('total');
                @endphp
                @foreach($expensesByCategory as $category)
                    @php
                        $percentage = $totalExpenses > 0 ? ($category->total / $totalExpenses) * 100 : 0;
                    @endphp
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f9fafb; border-radius: 0.5rem;">
                        <div>
                            <p style="font-weight: 500;">{{ $category->category }}</p>
                            <p style="font-size: 0.875rem; color: #6b7280;">{{ number_format($percentage, 1) }}%</p>
                        </div>
                        <p style="font-weight: 600;">{!! $peso !!}{{ number_format($category->total, 2) }}</p>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif

    {{-- Quick Actions --}}
    <x-filament::section>
        <x-slot name="heading">Quick Actions</x-slot>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem;">
            <x-filament::button tag="a" :href="route('filament.tos.pages.profit-loss-report')" color="gray" outlined style="justify-content: center;">
                P&L Report
            </x-filament::button>
            
            <x-filament::button tag="a" :href="route('filament.tos.resources.expenses.create')" color="warning" outlined style="justify-content: center;">
                Add Expense
            </x-filament::button>
            
            <x-filament::button tag="a" :href="route('filament.tos.resources.sales.create')" color="success" outlined style="justify-content: center;">
                New Sale
            </x-filament::button>
            
            <x-filament::button tag="a" :href="route('filament.tos.resources.customers.index')" color="info" outlined style="justify-content: center;">
                Customers
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-panels::page>

