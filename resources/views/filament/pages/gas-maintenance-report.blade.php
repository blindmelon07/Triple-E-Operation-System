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
                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No gas or maintenance activity found for those filters.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm border border-gray-200 dark:border-gray-700">
                            <thead>
                                <tr class="bg-blue-100 dark:bg-blue-900/40">
                                    <th class="text-left px-3 py-2 font-semibold text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700">Vehicle</th>
                                    <th class="text-right px-3 py-2 font-semibold text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700">Fuel Cost</th>
                                    <th class="text-right px-3 py-2 font-semibold text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700">Fuel Liters</th>
                                    <th class="text-right px-3 py-2 font-semibold text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700">Maintenance Cost</th>
                                    <th class="text-right px-3 py-2 font-semibold text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $vehicle)
                                    <tr class="border-b border-gray-100 dark:border-gray-800">
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700">
                                            {{ $vehicle['plate_number'] }}
                                            <span class="block text-xs text-gray-400 dark:text-gray-500">{{ $vehicle['full_name'] }}</span>
                                        </td>
                                        <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700">{{ number_format($vehicle['fuel_total'], 2) }}</td>
                                        <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700">{{ number_format($vehicle['fuel_liters'], 2) }} L</td>
                                        <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700">{{ number_format($vehicle['maintenance_total'], 2) }}</td>
                                        <td class="px-3 py-2 text-right font-semibold text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700">{{ number_format($vehicle['grand_total'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-50 dark:bg-gray-800 font-semibold">
                                    <td class="px-3 py-2 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700">Grand Total</td>
                                    <td class="px-3 py-2 text-right text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700">{{ number_format($totals['fuel_total'], 2) }}</td>
                                    <td class="px-3 py-2 text-right text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700">{{ number_format($totals['fuel_liters'], 2) }} L</td>
                                    <td class="px-3 py-2 text-right text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700">{{ number_format($totals['maintenance_total'], 2) }}</td>
                                    <td class="px-3 py-2 text-right text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700">{{ number_format($totals['grand_total'], 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>
