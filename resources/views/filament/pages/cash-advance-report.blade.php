@php
    $th = 'px-3 py-2 font-semibold text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700';
    $td = 'px-3 py-2 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700';
    $tf = 'px-3 py-2 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700';
    $money = fn ($v) => number_format((float) $v, 2);
    $blank = fn ($v) => (float) $v != 0 ? number_format((float) $v, 2) : '';
@endphp

<x-filament-panels::page>
    @include('filament.pages.partials.company-header')
    <div class="space-y-6">
        {{-- Filters --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6 space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Employee</label>
                    <select wire:model.live="employeeId" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">All Employees</option>
                        @foreach($this->employeeOptions() as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From Date</label>
                    <input type="date" wire:model.live="dateFrom" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To Date</label>
                    <input type="date" wire:model.live="dateTo" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                </div>
                <div class="flex items-end">
                    <button
                        type="button"
                        wire:click="generate"
                        wire:loading.attr="disabled"
                        wire:target="generate"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-500 text-white text-sm font-medium rounded-lg shadow-sm disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="generate">Generate Report</span>
                        <span wire:loading wire:target="generate">Generating...</span>
                    </button>
                </div>
            </div>

            {{-- View toggle --}}
            <div class="inline-flex rounded-lg ring-1 ring-gray-300 dark:ring-gray-700 overflow-hidden text-sm">
                <button type="button" wire:click="$set('mode', 'summary')"
                    class="px-4 py-2 font-medium {{ $mode === 'summary' ? 'bg-primary-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300' }}">
                    Summary
                </button>
                <button type="button" wire:click="$set('mode', 'ledger')"
                    class="px-4 py-2 font-medium border-l border-gray-300 dark:border-gray-700 {{ $mode === 'ledger' ? 'bg-primary-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300' }}">
                    Ledger per Employee
                </button>
            </div>
        </div>

        {{-- Results --}}
        @if($generated)
            @if(empty($summary))
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No cash advance activity found for those filters.</p>
                </div>
            @elseif($mode === 'summary')
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm border border-gray-200 dark:border-gray-700">
                            <thead>
                                <tr class="bg-blue-100 dark:bg-blue-900/40">
                                    <th class="text-left {{ $th }}">Employee</th>
                                    <th class="text-right {{ $th }}">Beginning Balance</th>
                                    <th class="text-right {{ $th }}">Cash Advances</th>
                                    <th class="text-right {{ $th }}">Deductions</th>
                                    <th class="text-right {{ $th }}">Ending Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($summary as $r)
                                    <tr>
                                        <td class="{{ $td }}">{{ $r['employee'] }}</td>
                                        <td class="text-right {{ $td }}">{{ $money($r['beginning']) }}</td>
                                        <td class="text-right {{ $td }}">{{ $money($r['advances']) }}</td>
                                        <td class="text-right {{ $td }}">{{ $money($r['deductions']) }}</td>
                                        <td class="text-right font-semibold text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700 px-3 py-2">{{ $money($r['ending']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-50 dark:bg-gray-800 font-semibold">
                                    <td class="{{ $tf }}">Grand Total</td>
                                    <td class="text-right {{ $tf }}">{{ $money($summaryTotals['beginning']) }}</td>
                                    <td class="text-right {{ $tf }}">{{ $money($summaryTotals['advances']) }}</td>
                                    <td class="text-right {{ $tf }}">{{ $money($summaryTotals['deductions']) }}</td>
                                    <td class="text-right {{ $tf }}">{{ $money($summaryTotals['ending']) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @else
                @foreach($ledgers as $l)
                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
                        <div class="flex items-baseline justify-between mb-3">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ $l['employee'] }}</h3>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Balance: <span class="font-semibold {{ $l['totals']['ending'] > 0 ? 'text-danger-600 dark:text-danger-400' : 'text-success-600 dark:text-success-400' }}">₱{{ $money($l['totals']['ending']) }}</span></span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm border border-gray-200 dark:border-gray-700">
                                <thead>
                                    <tr class="bg-blue-100 dark:bg-blue-900/40">
                                        <th class="text-left {{ $th }}">Date</th>
                                        <th class="text-left {{ $th }}">Reference</th>
                                        <th class="text-left {{ $th }}">Particulars</th>
                                        <th class="text-right {{ $th }}">Cash Advance</th>
                                        <th class="text-right {{ $th }}">Deduction</th>
                                        <th class="text-right {{ $th }}">Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="italic">
                                        <td class="{{ $td }}" colspan="3">Beginning balance</td>
                                        <td class="{{ $td }}"></td>
                                        <td class="{{ $td }}"></td>
                                        <td class="text-right {{ $td }}">{{ $money($l['beginning']) }}</td>
                                    </tr>
                                    @foreach($l['rows'] as $r)
                                        <tr>
                                            <td class="{{ $td }}">{{ $r['date'] }}</td>
                                            <td class="{{ $td }}">{{ $r['reference'] }}</td>
                                            <td class="{{ $td }}">{{ $r['particulars'] }}</td>
                                            <td class="text-right {{ $td }}">{{ $blank($r['advance']) }}</td>
                                            <td class="text-right {{ $td }}">{{ $blank($r['deduction']) }}</td>
                                            <td class="text-right font-semibold text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700 px-3 py-2">{{ $money($r['balance']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="bg-gray-50 dark:bg-gray-800 font-semibold">
                                        <td class="{{ $tf }}" colspan="3">Total</td>
                                        <td class="text-right {{ $tf }}">{{ $money($l['totals']['advances']) }}</td>
                                        <td class="text-right {{ $tf }}">{{ $money($l['totals']['deductions']) }}</td>
                                        <td class="text-right {{ $tf }}">{{ $money($l['totals']['ending']) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @endforeach
            @endif
        @endif
    </div>
</x-filament-panels::page>
