<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filters --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6 space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Vehicle</label>
                    <select wire:model.live="vehicleId" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">All Vehicles</option>
                        @foreach($this->vehicleOptions() as $id => $name)
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
        </div>

        {{-- Results --}}
        @if($generated)
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
                @if(empty($rows))
                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No gas activity found for those filters.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm border border-gray-200 dark:border-gray-700">
                            <thead>
                                <tr class="bg-blue-100 dark:bg-blue-900/40">
                                    <th class="text-left px-3 py-2 font-semibold text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700">Date</th>
                                    <th class="text-left px-3 py-2 font-semibold text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700">Truck/Unit</th>
                                    <th class="text-left px-3 py-2 font-semibold text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700">SI /DR #</th>
                                    <th class="text-left px-3 py-2 font-semibold text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700">Gas Station</th>
                                    <th class="text-right px-3 py-2 font-semibold text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700">Ltrs</th>
                                    <th class="text-right px-3 py-2 font-semibold text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700">Unit</th>
                                    <th class="text-right px-3 py-2 font-semibold text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700">Amount</th>
                                    <th class="text-right px-3 py-2 font-semibold text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $r)
                                    <tr class="border-b border-gray-100 dark:border-gray-800">
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700">{{ $r['date'] }}</td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700">
                                            {{ $r['truck'] }}
                                            <span class="block text-xs text-gray-400 dark:text-gray-500">{{ $r['plate_number'] }}</span>
                                        </td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700">{{ $r['si_dr_number'] }}</td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700">{{ $r['fuel_station'] }}</td>
                                        <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700">{{ number_format($r['liters'], 2) }}</td>
                                        <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700">{{ number_format($r['unit_price'], 2) }}</td>
                                        <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700">{{ number_format($r['amount'], 2) }}</td>
                                        <td class="px-3 py-2 text-right font-semibold text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700">{{ number_format($r['running_total'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-50 dark:bg-gray-800 font-semibold">
                                    <td class="px-3 py-2 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700" colspan="4">Grand Total</td>
                                    <td class="px-3 py-2 text-right text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700">{{ number_format($totals['liters'], 2) }}</td>
                                    <td class="px-3 py-2 text-right text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700"></td>
                                    <td class="px-3 py-2 text-right text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700">{{ number_format($totals['amount'], 2) }}</td>
                                    <td class="px-3 py-2 text-right text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700">{{ number_format($totals['amount'], 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>
