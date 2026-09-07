<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Report Mode --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Report Type</label>
            <div class="flex gap-2">
                <button
                    type="button"
                    wire:click="$set('reportMode', 'data')"
                    class="px-4 py-2 text-sm font-medium rounded-lg {{ $reportMode === 'data' ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300' }}"
                >Custom Data Report</button>
                <button
                    type="button"
                    wire:click="$set('reportMode', 'supplier_statement')"
                    class="px-4 py-2 text-sm font-medium rounded-lg {{ $reportMode === 'supplier_statement' ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300' }}"
                >Supplier Statement of Account</button>
            </div>
        </div>

        @if($reportMode === 'supplier_statement')
            {{-- Supplier Statement of Account --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Supplier</label>
                        <select wire:model.live="supplierId" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="">Choose a supplier...</option>
                            @foreach($this->supplierOptions() as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From Month/Date</label>
                        <input type="date" wire:model.live="statementDateFrom" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To Month/Date</label>
                        <input type="date" wire:model.live="statementDateTo" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    </div>
                    <div class="flex items-end">
                        <button
                            type="button"
                            wire:click="generateStatement"
                            wire:loading.attr="disabled"
                            wire:target="generateStatement"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-500 text-white text-sm font-medium rounded-lg shadow-sm disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="generateStatement">Generate Statement</span>
                            <span wire:loading wire:target="generateStatement">Generating...</span>
                        </button>
                    </div>
                </div>
            </div>

            @if($statementGenerated)
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6 space-y-6">
                    @forelse($statementMonths as $month)
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">{{ $month['label'] }}</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm border border-gray-200 dark:border-gray-700">
                                    <thead>
                                        <tr class="bg-blue-100 dark:bg-blue-900/40">
                                            <th class="text-left px-3 py-2 font-semibold text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700">Date</th>
                                            <th class="text-left px-3 py-2 font-semibold text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700">SI #</th>
                                            <th class="text-left px-3 py-2 font-semibold text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700">P.O #</th>
                                            <th class="text-right px-3 py-2 font-semibold text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700">Total</th>
                                            <th class="text-right px-3 py-2 font-semibold text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700">Total Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $runningTotal = 0; @endphp
                                        @foreach($month['purchases'] as $purchase)
                                            @php $runningTotal += (float) $purchase->total; @endphp
                                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                                <td class="px-3 py-2 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700">{{ $purchase->date->format('j-M-y') }}</td>
                                                <td class="px-3 py-2 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700">{{ $purchase->si_number ?: '—' }}</td>
                                                <td class="px-3 py-2 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700">{{ $purchase->po_number ?: '—' }}</td>
                                                <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700">{{ number_format($purchase->total, 2) }}</td>
                                                <td class="px-3 py-2 text-right text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700">{{ number_format($runningTotal, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No purchase activity for this supplier in the selected period.</p>
                    @endforelse
                </div>
            @endif
        @else
        {{-- Module + Filters --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6 space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Data Source</label>
                <select
                    wire:model.live="module"
                    class="w-full max-w-sm rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                    <option value="">Choose what to report on...</option>
                    @foreach($this->moduleOptions() as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            @if($module)
                {{-- Columns --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Columns to include</label>
                        <div class="flex gap-3 text-xs">
                            <button type="button" wire:click="selectAllColumns" class="text-primary-600 hover:underline">Select all</button>
                            <button type="button" wire:click="clearColumns" class="text-gray-500 hover:underline">Clear</button>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
                        @foreach($this->columnOptions() as $key => $label)
                            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input
                                    type="checkbox"
                                    value="{{ $key }}"
                                    wire:model.live="selectedColumns"
                                    class="rounded border-gray-300 dark:border-gray-700 text-primary-600 focus:ring-primary-500"
                                >
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Filters --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    @if($this->moduleHasDateRange())
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From Date</label>
                            <input type="date" wire:model.live="dateFrom" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To Date</label>
                            <input type="date" wire:model.live="dateTo" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        </div>
                    @endif

                    @foreach($this->filterDefinitions() as $filterKey => $filterDef)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $filterDef['label'] }}</label>
                            <select
                                wire:model.live="filterValues.{{ $filterKey }}"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            >
                                <option value="">All</option>
                                @foreach(\App\Support\ReportBuilder\ReportModules::resolveFilterOptions($filterDef) as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>

                <div>
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
            @endif
        </div>

        {{-- Results --}}
        @if($generated)
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ number_format($totalMatchCount) }} {{ $totalMatchCount === 1 ? 'row' : 'rows' }} found
                        @if($resultCount < $totalMatchCount)
                            <span class="text-gray-400">— exports cover the first {{ number_format($resultCount) }} (PDF capped smaller still to keep the file safe to render); narrow your filters to reach the rest</span>
                        @endif
                    </h3>

                    @if($resultCount > 0)
                        <div class="flex items-center gap-2 text-sm">
                            <label class="text-gray-500 dark:text-gray-400">Per page</label>
                            <select wire:model.live="perPage" class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm text-sm py-1">
                                @foreach($this->perPageOptions() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>

                @if($totalMatchCount === 0)
                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">No records match those filters.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    @foreach($this->orderedSelectedColumns() as $key)
                                        <th class="text-left px-3 py-2 font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                            {{ $this->columnOptions()[$key] }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->paginatedRows() as $row)
                                    <tr class="border-b border-gray-100 dark:border-gray-800">
                                        @foreach($row as $value)
                                            <td class="px-3 py-2 text-gray-600 dark:text-gray-400 whitespace-nowrap">{{ $value }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @php $lastPage = $this->lastPage(); @endphp
                    @if($lastPage > 1)
                        <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Showing {{ number_format((($page - 1) * $perPage) + 1) }}–{{ number_format(min($page * $perPage, $resultCount)) }}
                                of {{ number_format($resultCount) }}
                            </p>
                            <div class="flex items-center gap-1">
                                <button
                                    type="button"
                                    wire:click="previousPage"
                                    @disabled($page <= 1)
                                    class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-800"
                                >Previous</button>
                                <span class="px-3 text-sm text-gray-500 dark:text-gray-400">Page {{ $page }} of {{ $lastPage }}</span>
                                <button
                                    type="button"
                                    wire:click="nextPage"
                                    @disabled($page >= $lastPage)
                                    class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-gray-800"
                                >Next</button>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        @endif
        @endif
    </div>
</x-filament-panels::page>
