<x-filament-panels::page>
    @php
        $stats = $this->getAgingStats();
    @endphp

    {{-- Summary Cards --}}
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 1.5rem;">
        {{-- Accounts Receivable Summary --}}
        <x-filament::section>
            <x-slot name="heading">
                <span style="color: #10b981;">Accounts Receivable (Customers Owe You)</span>
            </x-slot>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                <div style="text-align: center; padding: 0.5rem;">
                    <p style="font-size: 0.75rem; color: #6b7280;">Current</p>
                    <p style="font-size: 1.25rem; font-weight: bold; color: #10b981;">₱{{ number_format($stats['receivables']['current'], 2) }}</p>
                </div>
                <div style="text-align: center; padding: 0.5rem;">
                    <p style="font-size: 0.75rem; color: #6b7280;">1-30 Days</p>
                    <p style="font-size: 1.25rem; font-weight: bold; color: #f59e0b;">₱{{ number_format($stats['receivables']['1_30'], 2) }}</p>
                </div>
                <div style="text-align: center; padding: 0.5rem;">
                    <p style="font-size: 0.75rem; color: #6b7280;">31-60 Days</p>
                    <p style="font-size: 1.25rem; font-weight: bold; color: #f97316;">₱{{ number_format($stats['receivables']['31_60'], 2) }}</p>
                </div>
                <div style="text-align: center; padding: 0.5rem;">
                    <p style="font-size: 0.75rem; color: #6b7280;">61-90 Days</p>
                    <p style="font-size: 1.25rem; font-weight: bold; color: #ef4444;">₱{{ number_format($stats['receivables']['61_90'], 2) }}</p>
                </div>
                <div style="text-align: center; padding: 0.5rem;">
                    <p style="font-size: 0.75rem; color: #6b7280;">Over 90 Days</p>
                    <p style="font-size: 1.25rem; font-weight: bold; color: #dc2626;">₱{{ number_format($stats['receivables']['over_90'], 2) }}</p>
                </div>
                <div style="text-align: center; padding: 0.5rem; background: #f3f4f6; border-radius: 0.5rem;">
                    <p style="font-size: 0.75rem; color: #6b7280;">Total Outstanding</p>
                    <p style="font-size: 1.25rem; font-weight: bold;">₱{{ number_format($stats['receivables']['total'], 2) }}</p>
                </div>
            </div>
            @if($stats['receivables']['overdue_count'] > 0)
                <div style="margin-top: 1rem; padding: 0.75rem; background: #fef2f2; border-radius: 0.5rem; border-left: 4px solid #ef4444;">
                    <p style="color: #dc2626; font-weight: 500;">⚠️ {{ $stats['receivables']['overdue_count'] }} invoice(s) overdue</p>
                </div>
            @endif
        </x-filament::section>

        {{-- Accounts Payable Summary --}}
        <x-filament::section>
            <x-slot name="heading">
                <span style="color: #3b82f6;">Accounts Payable (You Owe Suppliers)</span>
            </x-slot>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                <div style="text-align: center; padding: 0.5rem;">
                    <p style="font-size: 0.75rem; color: #6b7280;">Current</p>
                    <p style="font-size: 1.25rem; font-weight: bold; color: #10b981;">₱{{ number_format($stats['payables']['current'], 2) }}</p>
                </div>
                <div style="text-align: center; padding: 0.5rem;">
                    <p style="font-size: 0.75rem; color: #6b7280;">1-30 Days</p>
                    <p style="font-size: 1.25rem; font-weight: bold; color: #f59e0b;">₱{{ number_format($stats['payables']['1_30'], 2) }}</p>
                </div>
                <div style="text-align: center; padding: 0.5rem;">
                    <p style="font-size: 0.75rem; color: #6b7280;">31-60 Days</p>
                    <p style="font-size: 1.25rem; font-weight: bold; color: #f97316;">₱{{ number_format($stats['payables']['31_60'], 2) }}</p>
                </div>
                <div style="text-align: center; padding: 0.5rem;">
                    <p style="font-size: 0.75rem; color: #6b7280;">61-90 Days</p>
                    <p style="font-size: 1.25rem; font-weight: bold; color: #ef4444;">₱{{ number_format($stats['payables']['61_90'], 2) }}</p>
                </div>
                <div style="text-align: center; padding: 0.5rem;">
                    <p style="font-size: 0.75rem; color: #6b7280;">Over 90 Days</p>
                    <p style="font-size: 1.25rem; font-weight: bold; color: #dc2626;">₱{{ number_format($stats['payables']['over_90'], 2) }}</p>
                </div>
                <div style="text-align: center; padding: 0.5rem; background: #f3f4f6; border-radius: 0.5rem;">
                    <p style="font-size: 0.75rem; color: #6b7280;">Total Outstanding</p>
                    <p style="font-size: 1.25rem; font-weight: bold;">₱{{ number_format($stats['payables']['total'], 2) }}</p>
                </div>
            </div>
            @if($stats['payables']['overdue_count'] > 0)
                <div style="margin-top: 1rem; padding: 0.75rem; background: #fef2f2; border-radius: 0.5rem; border-left: 4px solid #ef4444;">
                    <p style="color: #dc2626; font-weight: 500;">⚠️ {{ $stats['payables']['overdue_count'] }} bill(s) overdue</p>
                </div>
            @endif
        </x-filament::section>
    </div>

    {{-- Tab Buttons --}}
    <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
        <x-filament::button
            :color="$this->activeTab === 'receivables' ? 'primary' : 'gray'"
            wire:click="setActiveTab('receivables')"
        >
            Accounts Receivable (Customers)
        </x-filament::button>
        <x-filament::button
            :color="$this->activeTab === 'payables' ? 'primary' : 'gray'"
            wire:click="setActiveTab('payables')"
        >
            Accounts Payable (Suppliers)
        </x-filament::button>
    </div>

    {{-- Table --}}
    <x-filament::section>
        <x-slot name="heading">
            {{ $this->activeTab === 'receivables' ? 'Customer Invoices Aging' : 'Supplier Bills Aging' }}
        </x-slot>
        <x-slot name="description">
            {{ $this->activeTab === 'receivables' ? 'Unpaid invoices from customers based on their payment terms' : 'Unpaid bills to suppliers based on their payment terms' }}
        </x-slot>
        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>
