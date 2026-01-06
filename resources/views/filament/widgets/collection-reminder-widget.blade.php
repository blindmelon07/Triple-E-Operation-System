<x-filament-widgets::widget>
    @php
        $data = $this->getCollectionData();
        $hasData = $data['receivables']->count() > 0 || $data['payables']->count() > 0;
    @endphp

    @if($hasData)
        <x-filament::section>
            <x-slot name="heading">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="color: #f59e0b;">📅</span>
                    <span style="color: #f59e0b; font-weight: 600;">Collection & Payment Reminders</span>
                </div>
            </x-slot>
            <x-slot name="description">
                Accounts due within the next 7 days - plan your collections and payments
            </x-slot>

            {{-- Due Today Alerts --}}
            @if($data['receivables_due_today'] > 0 || $data['payables_due_today'] > 0)
                <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 0.75rem; padding: 1rem; margin-bottom: 1rem; border-left: 4px solid #f59e0b;">
                    <p style="font-weight: 600; color: #92400e; display: flex; align-items: center; gap: 0.5rem;">
                        <span>🔔</span> Due Today:
                        @if($data['receivables_due_today'] > 0)
                            <span style="background: #fbbf24; color: #78350f; padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 0.75rem;">
                                {{ $data['receivables_due_today'] }} invoice(s) to collect
                            </span>
                        @endif
                        @if($data['payables_due_today'] > 0)
                            <span style="background: #fb923c; color: #7c2d12; padding: 0.125rem 0.5rem; border-radius: 9999px; font-size: 0.75rem;">
                                {{ $data['payables_due_today'] }} bill(s) to pay
                            </span>
                        @endif
                    </p>
                </div>
            @endif

            {{-- Summary Stats --}}
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
                {{-- To Collect --}}
                <div style="background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border-radius: 0.75rem; padding: 1rem; border-left: 4px solid #10b981;">
                    <p style="font-size: 0.75rem; color: #065f46; font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em;">To Collect (7 days)</p>
                    <p style="font-size: 1.5rem; font-weight: bold; color: #059669;">₱{{ number_format($data['total_due_soon_receivables'], 2) }}</p>
                    <p style="font-size: 0.875rem; color: #047857;">{{ $data['receivables']->count() }} invoice(s) due soon</p>
                </div>

                {{-- To Pay --}}
                <div style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-radius: 0.75rem; padding: 1rem; border-left: 4px solid #3b82f6;">
                    <p style="font-size: 0.75rem; color: #1e40af; font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em;">To Pay (7 days)</p>
                    <p style="font-size: 1.5rem; font-weight: bold; color: #2563eb;">₱{{ number_format($data['total_due_soon_payables'], 2) }}</p>
                    <p style="font-size: 0.875rem; color: #1d4ed8;">{{ $data['payables']->count() }} bill(s) due soon</p>
                </div>
            </div>

            {{-- Due Soon Items List --}}
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
                {{-- Receivables to Collect --}}
                @if($data['receivables']->count() > 0)
                    <div>
                        <h4 style="font-weight: 600; margin-bottom: 0.75rem; color: #059669;">💰 To Collect from Customers</h4>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            @foreach($data['receivables'] as $item)
                                <div style="background: #fff; border: 1px solid {{ $item['urgency'] === 'due-today' ? '#fbbf24' : ($item['urgency'] === 'urgent' ? '#fcd34d' : '#d1fae5') }}; border-radius: 0.5rem; padding: 0.75rem; display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <p style="font-weight: 600; font-size: 0.875rem;">{{ $item['reference'] }}</p>
                                        <p style="font-size: 0.75rem; color: #6b7280;">{{ $item['name'] }}</p>
                                    </div>
                                    <div style="text-align: right;">
                                        <p style="font-weight: 600; color: #059669;">₱{{ number_format($item['amount'], 2) }}</p>
                                        <p style="font-size: 0.75rem; color: {{ $item['urgency'] === 'due-today' ? '#b45309' : ($item['urgency'] === 'urgent' ? '#d97706' : '#059669') }};">
                                            @if($item['days_until_due'] == 0)
                                                Due today!
                                            @else
                                                Due in {{ $item['days_until_due'] }} day(s)
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Payables to Pay --}}
                @if($data['payables']->count() > 0)
                    <div>
                        <h4 style="font-weight: 600; margin-bottom: 0.75rem; color: #2563eb;">💳 To Pay to Suppliers</h4>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                            @foreach($data['payables'] as $item)
                                <div style="background: #fff; border: 1px solid {{ $item['urgency'] === 'due-today' ? '#fbbf24' : ($item['urgency'] === 'urgent' ? '#fcd34d' : '#dbeafe') }}; border-radius: 0.5rem; padding: 0.75rem; display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <p style="font-weight: 600; font-size: 0.875rem;">{{ $item['reference'] }}</p>
                                        <p style="font-size: 0.75rem; color: #6b7280;">{{ $item['name'] }}</p>
                                    </div>
                                    <div style="text-align: right;">
                                        <p style="font-weight: 600; color: #2563eb;">₱{{ number_format($item['amount'], 2) }}</p>
                                        <p style="font-size: 0.75rem; color: {{ $item['urgency'] === 'due-today' ? '#b45309' : ($item['urgency'] === 'urgent' ? '#d97706' : '#2563eb') }};">
                                            @if($item['days_until_due'] == 0)
                                                Due today!
                                            @else
                                                Due in {{ $item['days_until_due'] }} day(s)
                                            @endif
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
