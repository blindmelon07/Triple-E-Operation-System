@php
    $report = $this->report();
    $th = 'px-3 py-2 font-semibold text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 text-center whitespace-nowrap';
    $td = 'px-3 py-2 border border-gray-200 dark:border-gray-700';
    $money = fn ($v) => number_format((float) $v, 2);
@endphp

<x-filament-panels::page>
    @include('filament.pages.partials.company-header')
    <div class="space-y-6">
        {{-- Filters --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Customer</label>
                    <select wire:model.live="customerId" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">All Customers</option>
                        @foreach($this->customerOptions() as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                    <select wire:model.live="status" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">All</option>
                        <option value="DUE">Due</option>
                        <option value="OVER DUE">Over Due</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <div class="w-full rounded-lg bg-gray-50 dark:bg-gray-800 px-4 py-2">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Total Amount Receivable</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">₱{{ $money($report['total']) }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Summary sheet --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
            <h2 class="text-center text-lg font-bold tracking-wide text-gray-900 dark:text-white mb-4">
                STATEMENT OF ACCOUNT SUMMARY REPORT ON HARDWARE – {{ now()->year }} (ACCOUNTS RECEIVABLES)
            </h2>

            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="{{ $th }}">NO.</th>
                            <th class="{{ $th }}">CUSTOMER NAME</th>
                            <th class="{{ $th }}">SOA No.</th>
                            <th class="{{ $th }}">BILLING DATE</th>
                            <th class="{{ $th }}">ACCOUNTS RECEIVABLE</th>
                            <th class="{{ $th }}">TOTAL AMOUNT</th>
                            <th class="{{ $th }}">STATUS</th>
                            <th class="{{ $th }} text-red-600">NOTES</th>
                            <th class="{{ $th }}"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['rows'] as $row)
                            @php
                                $overdue = $row['status'] === 'OVER DUE';
                                $rowClass = $overdue
                                    ? 'bg-sky-100 dark:bg-sky-950/40 text-red-600 dark:text-red-400 font-semibold'
                                    : 'text-gray-700 dark:text-gray-300';
                            @endphp
                            <tr class="{{ $rowClass }}" wire:key="ar-row-{{ $row['id'] }}">
                                <td class="{{ $td }} text-center">{{ $row['id'] }}</td>
                                <td class="{{ $td }} uppercase">{{ $row['name'] }}</td>
                                <td class="{{ $td }}">{{ $row['soa_number'] ?? '—' }}</td>
                                <td class="{{ $td }} text-center whitespace-nowrap">
                                    {{ $row['billing_date'] ? \Illuminate\Support\Carbon::parse($row['billing_date'])->format('j-M-y') : '—' }}
                                </td>
                                <td class="{{ $td }} text-right tabular-nums">{{ $money($row['receivable']) }}</td>
                                <td class="{{ $td }} text-right tabular-nums">{{ $money($row['running_total']) }}</td>
                                <td class="{{ $td }} text-center whitespace-nowrap">{{ $row['status'] }}</td>
                                <td class="{{ $td }} text-red-600 dark:text-red-400 font-semibold uppercase">{{ $row['notes'] }}</td>
                                <td class="{{ $td }} text-center">{{ ($this->editSoaAction)(['customer' => $row['id']]) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="{{ $td }} text-center text-gray-500 py-6">No outstanding receivables.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($report['rows']))
                        <tfoot>
                            <tr class="font-bold text-gray-900 dark:text-white">
                                <td colspan="4" class="{{ $td }} text-right bg-sky-50 dark:bg-sky-950/30">TOTAL AMOUNT RECEIVABLE:</td>
                                <td class="{{ $td }} text-right tabular-nums">{{ $money($report['total']) }}</td>
                                <td colspan="4" class="{{ $td }}"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                Rows in red are OVER DUE (at least one unpaid invoice is past its due date). "Total Amount" is the running total down the sheet.
                SOA No. and Billing Date are filled in when you generate a Statement of Account for the customer, or with the edit button.
            </p>
        </div>
    </div>

</x-filament-panels::page>
