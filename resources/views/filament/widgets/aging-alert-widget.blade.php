<x-filament-widgets::widget>
    @php
        $data = $this->getAgingData();
    @endphp

    @if($data['receivables_count'] > 0 || $data['payables_count'] > 0)
        <x-filament::section>
            <x-slot name="heading">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="color: #ef4444;">⚠️</span>
                    <span style="color: #ef4444; font-weight: 600;">Overdue Alerts</span>
                </div>
            </x-slot>
            <x-slot name="description">
                Accounts that have passed their due date and require immediate attention
            </x-slot>

            {{-- Summary Stats --}}
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
                {{-- Overdue Receivables --}}
                <div style="background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); border-radius: 0.75rem; padding: 1rem; border-left: 4px solid #ef4444;">
                    <p style="font-size: 0.75rem; color: #991b1b; font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em;">Overdue Receivables</p>
                    <p style="font-size: 1.5rem; font-weight: bold; color: #dc2626;">₱{{ number_format($data['total_overdue_receivables'], 2) }}</p>
                    <p style="font-size: 0.875rem; color: #b91c1c;">{{ $data['receivables_count'] }} invoice(s) overdue</p>
                </div>

                {{-- Overdue Payables --}}
                <div style="background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); border-radius: 0.75rem; padding: 1rem; border-left: 4px solid #f97316;">
                    <p style="font-size: 0.75rem; color: #9a3412; font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em;">Overdue Payables</p>
                    <p style="font-size: 1.5rem; font-weight: bold; color: #ea580c;">₱{{ number_format($data['total_overdue_payables'], 2) }}</p>
                    <p style="font-size: 0.875rem; color: #c2410c;">{{ $data['payables_count'] }} bill(s) overdue</p>
                </div>
            </div>

            {{-- Overdue Items List --}}
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
                {{-- Receivables List --}}
                @if($data['receivables']->count() > 0)
                    <div>
                        <h4 style="font-weight: 600; margin-bottom: 0.75rem; color: #dc2626;">🧾 Customer Invoices Overdue</h4>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            @foreach($data['receivables'] as $item)
                                <div style="background: #fff; border: 1px solid #fecaca; border-radius: 0.5rem; padding: 0.75rem; display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <p style="font-weight: 600; font-size: 0.875rem;">{{ $item['reference'] }}</p>
                                        <p style="font-size: 0.75rem; color: #6b7280;">{{ $item['name'] }}</p>
                                    </div>
                                    <div style="text-align: right;">
                                        <p style="font-weight: 600; color: #dc2626;">₱{{ number_format($item['amount'], 2) }}</p>
                                        <p style="font-size: 0.75rem; color: {{ $item['severity'] === 'critical' ? '#7f1d1d' : ($item['severity'] === 'high' ? '#991b1b' : '#dc2626') }};">
                                            {{ $item['days_overdue'] }} days overdue
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Payables List --}}
                @if($data['payables']->count() > 0)
                    <div>
                        <h4 style="font-weight: 600; margin-bottom: 0.75rem; color: #ea580c;">📦 Supplier Bills Overdue</h4>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            @foreach($data['payables'] as $item)
                                <div style="background: #fff; border: 1px solid #fed7aa; border-radius: 0.5rem; padding: 0.75rem; display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <p style="font-weight: 600; font-size: 0.875rem;">{{ $item['reference'] }}</p>
                                        <p style="font-size: 0.75rem; color: #6b7280;">{{ $item['name'] }}</p>
                                    </div>
                                    <div style="text-align: right;">
                                        <p style="font-weight: 600; color: #ea580c;">₱{{ number_format($item['amount'], 2) }}</p>
                                        <p style="font-size: 0.75rem; color: {{ $item['severity'] === 'critical' ? '#7c2d12' : ($item['severity'] === 'high' ? '#9a3412' : '#ea580c') }};">
                                            {{ $item['days_overdue'] }} days overdue
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- View Full Report Link --}}
            <div style="margin-top: 1rem; text-align: center;">
                <a href="{{ route('filament.tos.pages.aging-report') }}" style="color: #3b82f6; font-size: 0.875rem; text-decoration: none; font-weight: 500;">
                    View Full Aging Report →
                </a>
            </div>
        </x-filament::section>
    @endif
</x-filament-widgets::widget>
