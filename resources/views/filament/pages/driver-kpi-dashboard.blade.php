<x-filament-panels::page>
    {{-- KPI Stats Cards using inline styles for reliability --}}
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
        @php
            $stats = $this->getKpiStats();
        @endphp

        {{-- Total Deliveries --}}
        <x-filament::section>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <x-filament::icon icon="heroicon-o-truck" style="width: 1.5rem; height: 1.5rem; color: #3b82f6;" />
                <div>
                    <p style="font-size: 0.875rem; color: #6b7280;">Total Deliveries</p>
                    <p style="font-size: 1.5rem; font-weight: bold;">{{ number_format($stats['total_deliveries']) }}</p>
                </div>
            </div>
        </x-filament::section>

        {{-- Completed --}}
        <x-filament::section>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <x-filament::icon icon="heroicon-o-check-circle" style="width: 1.5rem; height: 1.5rem; color: #10b981;" />
                <div>
                    <p style="font-size: 0.875rem; color: #6b7280;">Completed</p>
                    <p style="font-size: 1.5rem; font-weight: bold; color: #10b981;">{{ number_format($stats['completed_deliveries']) }}</p>
                </div>
            </div>
        </x-filament::section>

        {{-- Success Rate --}}
        <x-filament::section>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <x-filament::icon icon="heroicon-o-chart-pie" style="width: 1.5rem; height: 1.5rem; color: #f59e0b;" />
                <div>
                    <p style="font-size: 0.875rem; color: #6b7280;">Success Rate</p>
                    <p style="font-size: 1.5rem; font-weight: bold; color: #f59e0b;">{{ $stats['success_rate'] }}%</p>
                </div>
            </div>
        </x-filament::section>

        {{-- Average Rating --}}
        <x-filament::section>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <x-filament::icon icon="heroicon-o-star" style="width: 1.5rem; height: 1.5rem; color: #06b6d4;" />
                <div>
                    <p style="font-size: 0.875rem; color: #6b7280;">Avg Rating</p>
                    <p style="font-size: 1.5rem; font-weight: bold; color: #06b6d4;">{{ $stats['average_rating'] }} @if($stats['average_rating'] !== 'N/A')⭐@endif</p>
                </div>
            </div>
        </x-filament::section>

        {{-- Pending --}}
        <x-filament::section>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <x-filament::icon icon="heroicon-o-clock" style="width: 1.5rem; height: 1.5rem; color: #6b7280;" />
                <div>
                    <p style="font-size: 0.875rem; color: #6b7280;">Pending</p>
                    <p style="font-size: 1.5rem; font-weight: bold;">{{ number_format($stats['pending_deliveries']) }}</p>
                </div>
            </div>
        </x-filament::section>

        {{-- Failed --}}
        <x-filament::section>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <x-filament::icon icon="heroicon-o-x-circle" style="width: 1.5rem; height: 1.5rem; color: #ef4444;" />
                <div>
                    <p style="font-size: 0.875rem; color: #6b7280;">Failed</p>
                    <p style="font-size: 1.5rem; font-weight: bold; color: #ef4444;">{{ number_format($stats['failed_deliveries']) }}</p>
                </div>
            </div>
        </x-filament::section>

        {{-- Active Drivers --}}
        <x-filament::section>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <x-filament::icon icon="heroicon-o-users" style="width: 1.5rem; height: 1.5rem; color: #3b82f6;" />
                <div>
                    <p style="font-size: 0.875rem; color: #6b7280;">Active Drivers</p>
                    <p style="font-size: 1.5rem; font-weight: bold;">{{ $stats['active_drivers'] }}</p>
                </div>
            </div>
        </x-filament::section>

        {{-- Avg Delivery Time --}}
        <x-filament::section>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <x-filament::icon icon="heroicon-o-bolt" style="width: 1.5rem; height: 1.5rem; color: #10b981;" />
                <div>
                    <p style="font-size: 0.875rem; color: #6b7280;">Avg Delivery Time</p>
                    <p style="font-size: 1.5rem; font-weight: bold;">{{ $stats['avg_delivery_time'] }}</p>
                </div>
            </div>
        </x-filament::section>
    </div>

    {{-- Driver Performance Table --}}
    <x-filament::section>
        <x-slot name="heading">Driver Performance</x-slot>
        <x-slot name="description">Individual driver delivery statistics</x-slot>
        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>
