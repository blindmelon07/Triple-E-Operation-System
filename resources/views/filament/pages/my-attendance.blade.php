<x-filament-panels::page>
    @php
        $today = $this->getTodayAttendance();
    @endphp

    {{-- Today's Status Cards --}}
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
        {{-- Today's Date --}}
        <x-filament::section>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <x-filament::icon icon="heroicon-o-calendar" style="width: 1.5rem; height: 1.5rem; color: #3b82f6;" />
                <div>
                    <p style="font-size: 0.875rem; color: #6b7280;">Today's Date</p>
                    <p style="font-size: 1.25rem; font-weight: bold;">{{ now()->format('M d, Y') }}</p>
                    <p style="font-size: 0.75rem; color: #9ca3af;">{{ now()->format('l') }}</p>
                </div>
            </div>
        </x-filament::section>

        {{-- Time In --}}
        <x-filament::section>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <x-filament::icon icon="heroicon-o-arrow-right-on-rectangle" style="width: 1.5rem; height: 1.5rem; color: #10b981;" />
                <div>
                    <p style="font-size: 0.875rem; color: #6b7280;">Time In</p>
                    <p style="font-size: 1.25rem; font-weight: bold; color: #10b981;">
                        {{ $today && $today->time_in ? \Carbon\Carbon::parse($today->time_in)->format('h:i A') : '--:--' }}
                    </p>
                </div>
            </div>
        </x-filament::section>

        {{-- Time Out --}}
        <x-filament::section>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <x-filament::icon icon="heroicon-o-arrow-left-on-rectangle" style="width: 1.5rem; height: 1.5rem; color: #ef4444;" />
                <div>
                    <p style="font-size: 0.875rem; color: #6b7280;">Time Out</p>
                    <p style="font-size: 1.25rem; font-weight: bold; color: #ef4444;">
                        {{ $today && $today->time_out ? \Carbon\Carbon::parse($today->time_out)->format('h:i A') : '--:--' }}
                    </p>
                </div>
            </div>
        </x-filament::section>

        {{-- Status --}}
        <x-filament::section>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <x-filament::icon icon="heroicon-o-clock" style="width: 1.5rem; height: 1.5rem; color: #f59e0b;" />
                <div>
                    <p style="font-size: 0.875rem; color: #6b7280;">Status</p>
                    <p style="font-size: 1.25rem; font-weight: bold;">
                        {{ $today ? $today->status->getLabel() : 'Not Clocked In' }}
                    </p>
                    @if($today && $today->total_hours)
                        <p style="font-size: 0.75rem; color: #9ca3af;">{{ $today->total_hours }} hrs</p>
                    @endif
                </div>
            </div>
        </x-filament::section>
    </div>

    {{-- Clock In/Out Buttons --}}
    <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
        @if(!$today || !$today->time_in)
            <x-filament::button wire:click="clockIn" color="success" icon="heroicon-o-arrow-right-on-rectangle" size="lg">
                Clock In
            </x-filament::button>
        @elseif(!$today->time_out)
            <x-filament::button wire:click="clockOut" color="danger" icon="heroicon-o-arrow-left-on-rectangle" size="lg">
                Clock Out
            </x-filament::button>
        @else
            <x-filament::button disabled color="gray" icon="heroicon-o-check-circle" size="lg">
                Attendance Complete for Today
            </x-filament::button>
        @endif
    </div>

    {{-- Recent Attendance History --}}
    <x-filament::section>
        <x-slot name="heading">My Attendance History</x-slot>
        {{ $this->table }}
    </x-filament::section>
</x-filament-panels::page>
