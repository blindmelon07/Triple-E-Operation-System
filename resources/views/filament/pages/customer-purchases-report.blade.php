<x-filament-panels::page>
    @include('filament.pages.partials.company-header')
    @php
        $th = 'px-3 py-2 font-semibold text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700';
        $td = 'px-3 py-2 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700';
    @endphp
    <div class="space-y-6">
        {{-- Filters --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6 space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Customer</label>
                    <select wire:model.live="customerId" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">— Select customer —</option>
                        @foreach($this->customerOptions() as $id => $name)
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
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6 space-y-4">
                @if(empty($groups))
                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No purchases found for this customer in that period.</p>
                @else
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $this->customerName() }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $totals['sales'] }} {{ \Illuminate\Support\Str::plural('transaction', $totals['sales']) }}
                            · {{ $totals['items'] }} {{ \Illuminate\Support\Str::plural('item', $totals['items']) }}
                            · Total <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($totals['amount'], 2) }}</span>
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm border border-gray-200 dark:border-gray-700">
                            <thead>
                                <tr class="bg-blue-100 dark:bg-blue-900/40">
                                    <th class="text-left {{ $th }}">Reference</th>
                                    <th class="text-left {{ $th }}">Item</th>
                                    <th class="text-left {{ $th }}">Unit</th>
                                    <th class="text-right {{ $th }}">Qty</th>
                                    <th class="text-right {{ $th }}">Unit Price</th>
                                    <th class="text-right {{ $th }}">Discount</th>
                                    <th class="text-right {{ $th }}">Amount</th>
                                    <th class="text-left {{ $th }}">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($groups as $group)
                                    <tr class="bg-gray-100 dark:bg-gray-800">
                                        <td colspan="8" class="px-3 py-2 font-semibold text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700">
                                            {{ $group['date'] }}
                                            <span class="ml-2 text-xs font-normal text-gray-500 dark:text-gray-400">{{ $group['sales_count'] }} {{ \Illuminate\Support\Str::plural('transaction', $group['sales_count']) }}</span>
                                        </td>
                                    </tr>
                                    @foreach($group['items'] as $item)
                                        <tr>
                                            <td class="{{ $td }}">{{ $item['reference_number'] }}</td>
                                            <td class="{{ $td }}">{{ $item['product'] }}</td>
                                            <td class="{{ $td }}">{{ $item['unit'] }}</td>
                                            <td class="text-right {{ $td }}">{{ rtrim(rtrim(number_format($item['quantity'], 2), '0'), '.') }}</td>
                                            <td class="text-right {{ $td }}">{{ number_format($item['unit_price'], 2) }}</td>
                                            <td class="text-right {{ $td }}">{{ $item['discount'] > 0 ? number_format($item['discount'], 2) : '—' }}</td>
                                            <td class="text-right {{ $td }}">{{ number_format($item['amount'], 2) }}</td>
                                            <td class="{{ $td }}">{{ $item['payment_status'] }}</td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td colspan="6" class="px-3 py-2 text-right font-semibold text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700">Subtotal — {{ $group['date'] }}</td>
                                        <td class="px-3 py-2 text-right font-semibold text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700">{{ number_format($group['subtotal'], 2) }}</td>
                                        <td class="border border-gray-200 dark:border-gray-700"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-50 dark:bg-gray-800 font-semibold">
                                    <td class="px-3 py-2 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700" colspan="6">Grand Total ({{ $totals['days'] }} {{ \Illuminate\Support\Str::plural('day', $totals['days']) }})</td>
                                    <td class="px-3 py-2 text-right text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700">{{ number_format($totals['amount'], 2) }}</td>
                                    <td class="border border-gray-200 dark:border-gray-700"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>
