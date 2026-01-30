<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filters --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Period</label>
                    <select
                        wire:model.live="period"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    >
                        @foreach(\App\Filament\Pages\ProfitLossReport::getPeriodOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                @if($period === 'custom')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                        <input
                            type="date"
                            wire:model.live="startDate"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
                        <input
                            type="date"
                            wire:model.live="endDate"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        >
                    </div>
                @endif

                <div class="flex items-end">
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Profit Status Banner --}}
        @if(isset($reportData['is_profitable']))
            <div class="rounded-xl p-6 {{ $reportData['is_profitable'] ? 'bg-green-50 dark:bg-green-900/20 ring-1 ring-green-200 dark:ring-green-800' : 'bg-red-50 dark:bg-red-900/20 ring-1 ring-red-200 dark:ring-red-800' }}">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0">
                        @if($reportData['is_profitable'])
                            <x-filament::icon icon="heroicon-o-arrow-trending-up" class="w-12 h-12 text-green-600 dark:text-green-400" />
                        @else
                            <x-filament::icon icon="heroicon-o-arrow-trending-down" class="w-12 h-12 text-red-600 dark:text-red-400" />
                        @endif
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold {{ $reportData['is_profitable'] ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200' }}">
                            {{ $reportData['is_profitable'] ? 'Your Business is Profitable!' : 'Your Business is Operating at a Loss' }}
                        </h3>
                        <p class="text-sm {{ $reportData['is_profitable'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            Net Profit Margin: {{ number_format($reportData['net_profit_margin'], 1) }}%
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm {{ $reportData['is_profitable'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">Net Profit/Loss</p>
                        <p class="text-3xl font-bold {{ $reportData['is_profitable'] ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                            ₱{{ number_format(abs($reportData['net_profit']), 2) }}
                            @if(!$reportData['is_profitable'])
                                <span class="text-lg">(Loss)</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            {{-- Revenue Card --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
                <div class="flex items-center gap-3">
                    <div class="p-3 rounded-lg bg-blue-100 dark:bg-blue-900/50">
                        <x-filament::icon icon="heroicon-o-banknotes" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Revenue</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">₱{{ number_format($reportData['revenue']['total'] ?? 0, 2) }}</p>
                    </div>
                </div>
            </div>

            {{-- COGS Card --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
                <div class="flex items-center gap-3">
                    <div class="p-3 rounded-lg bg-amber-100 dark:bg-amber-900/50">
                        <x-filament::icon icon="heroicon-o-cube" class="w-6 h-6 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Cost of Goods Sold</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">₱{{ number_format($reportData['cost_of_goods_sold']['total'] ?? 0, 2) }}</p>
                    </div>
                </div>
            </div>

            {{-- Gross Profit Card --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
                <div class="flex items-center gap-3">
                    <div class="p-3 rounded-lg bg-green-100 dark:bg-green-900/50">
                        <x-filament::icon icon="heroicon-o-chart-bar" class="w-6 h-6 text-green-600 dark:text-green-400" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Gross Profit</p>
                        <p class="text-2xl font-bold {{ ($reportData['gross_profit'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            ₱{{ number_format($reportData['gross_profit'] ?? 0, 2) }}
                        </p>
                        <p class="text-xs text-gray-400">{{ number_format($reportData['gross_profit_margin'] ?? 0, 1) }}% margin</p>
                    </div>
                </div>
            </div>

            {{-- Operating Expenses Card --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
                <div class="flex items-center gap-3">
                    <div class="p-3 rounded-lg bg-red-100 dark:bg-red-900/50">
                        <x-filament::icon icon="heroicon-o-arrow-trending-down" class="w-6 h-6 text-red-600 dark:text-red-400" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Operating Expenses</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">₱{{ number_format($reportData['operating_expenses']['total'] ?? 0, 2) }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Detailed Report --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Income Statement --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Income Statement</h3>
                </div>
                <div class="p-6">
                    <table class="w-full">
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            {{-- Revenue Section --}}
                            <tr>
                                <td class="py-3 font-semibold text-gray-900 dark:text-white" colspan="2">Revenue</td>
                            </tr>
                            <tr>
                                <td class="py-2 pl-4 text-gray-600 dark:text-gray-400">Sales Revenue</td>
                                <td class="py-2 text-right text-gray-900 dark:text-white">₱{{ number_format($reportData['revenue']['sales'] ?? 0, 2) }}</td>
                            </tr>
                            <tr class="bg-gray-50 dark:bg-gray-800">
                                <td class="py-2 pl-4 font-medium text-gray-700 dark:text-gray-300">Total Revenue</td>
                                <td class="py-2 text-right font-semibold text-gray-900 dark:text-white">₱{{ number_format($reportData['revenue']['total'] ?? 0, 2) }}</td>
                            </tr>

                            {{-- COGS Section --}}
                            <tr>
                                <td class="py-3 font-semibold text-gray-900 dark:text-white" colspan="2">Cost of Goods Sold</td>
                            </tr>
                            <tr>
                                <td class="py-2 pl-4 text-gray-600 dark:text-gray-400">Product Costs</td>
                                <td class="py-2 text-right text-gray-900 dark:text-white">₱{{ number_format($reportData['cost_of_goods_sold']['purchases'] ?? 0, 2) }}</td>
                            </tr>
                            <tr class="bg-gray-50 dark:bg-gray-800">
                                <td class="py-2 pl-4 font-medium text-gray-700 dark:text-gray-300">Total COGS</td>
                                <td class="py-2 text-right font-semibold text-gray-900 dark:text-white">(₱{{ number_format($reportData['cost_of_goods_sold']['total'] ?? 0, 2) }})</td>
                            </tr>

                            {{-- Gross Profit --}}
                            <tr class="bg-blue-50 dark:bg-blue-900/20">
                                <td class="py-3 font-bold text-blue-800 dark:text-blue-200">Gross Profit</td>
                                <td class="py-3 text-right font-bold {{ ($reportData['gross_profit'] ?? 0) >= 0 ? 'text-blue-800 dark:text-blue-200' : 'text-red-600' }}">
                                    ₱{{ number_format($reportData['gross_profit'] ?? 0, 2) }}
                                    <span class="text-xs">({{ number_format($reportData['gross_profit_margin'] ?? 0, 1) }}%)</span>
                                </td>
                            </tr>

                            {{-- Operating Expenses Section --}}
                            <tr>
                                <td class="py-3 font-semibold text-gray-900 dark:text-white" colspan="2">Operating Expenses</td>
                            </tr>
                            <tr>
                                <td class="py-2 pl-4 text-gray-600 dark:text-gray-400">General Expenses</td>
                                <td class="py-2 text-right text-gray-900 dark:text-white">₱{{ number_format($reportData['operating_expenses']['general_expenses'] ?? 0, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="py-2 pl-4 text-gray-600 dark:text-gray-400">Maintenance & Repairs</td>
                                <td class="py-2 text-right text-gray-900 dark:text-white">₱{{ number_format($reportData['operating_expenses']['maintenance'] ?? 0, 2) }}</td>
                            </tr>
                            <tr class="bg-gray-50 dark:bg-gray-800">
                                <td class="py-2 pl-4 font-medium text-gray-700 dark:text-gray-300">Total Operating Expenses</td>
                                <td class="py-2 text-right font-semibold text-gray-900 dark:text-white">(₱{{ number_format($reportData['operating_expenses']['total'] ?? 0, 2) }})</td>
                            </tr>

                            {{-- Net Profit --}}
                            <tr class="{{ ($reportData['net_profit'] ?? 0) >= 0 ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30' }}">
                                <td class="py-4 font-bold {{ ($reportData['net_profit'] ?? 0) >= 0 ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200' }}">
                                    Net {{ ($reportData['net_profit'] ?? 0) >= 0 ? 'Profit' : 'Loss' }}
                                </td>
                                <td class="py-4 text-right font-bold text-lg {{ ($reportData['net_profit'] ?? 0) >= 0 ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200' }}">
                                    ₱{{ number_format(abs($reportData['net_profit'] ?? 0), 2) }}
                                    <span class="text-xs">({{ number_format(abs($reportData['net_profit_margin'] ?? 0), 1) }}%)</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Expenses by Category --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Expenses by Category</h3>
                </div>
                <div class="p-6">
                    @if(isset($reportData['expenses_by_category']) && count($reportData['expenses_by_category']) > 0)
                        @php
                            $totalExpenses = $reportData['expenses_by_category']->sum('total');
                            $colors = ['bg-red-500', 'bg-orange-500', 'bg-yellow-500', 'bg-green-500', 'bg-blue-500', 'bg-indigo-500', 'bg-purple-500', 'bg-pink-500'];
                        @endphp
                        <div class="space-y-4">
                            @foreach($reportData['expenses_by_category'] as $index => $expense)
                                @php
                                    $percentage = $totalExpenses > 0 ? ($expense->total / $totalExpenses) * 100 : 0;
                                    $color = $colors[$index % count($colors)];
                                @endphp
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $expense->category }}</span>
                                        <span class="text-sm text-gray-600 dark:text-gray-400">
                                            ₱{{ number_format($expense->total, 2) }}
                                            <span class="text-xs text-gray-400">({{ number_format($percentage, 1) }}%)</span>
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div class="{{ $color }} h-2 rounded-full transition-all duration-300" style="width: {{ $percentage }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-gray-900 dark:text-white">Total</span>
                                <span class="font-bold text-gray-900 dark:text-white">₱{{ number_format($totalExpenses, 2) }}</span>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <x-filament::icon icon="heroicon-o-chart-pie" class="w-12 h-12 mx-auto mb-3 opacity-50" />
                            <p>No expenses recorded for this period</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Key Metrics --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Key Financial Metrics</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Gross Margin</p>
                    <p class="text-3xl font-bold {{ ($reportData['gross_profit_margin'] ?? 0) >= 20 ? 'text-green-600' : (($reportData['gross_profit_margin'] ?? 0) >= 10 ? 'text-yellow-600' : 'text-red-600') }}">
                        {{ number_format($reportData['gross_profit_margin'] ?? 0, 1) }}%
                    </p>
                    <p class="text-xs text-gray-400 mt-1">
                        @if(($reportData['gross_profit_margin'] ?? 0) >= 30)
                            Excellent
                        @elseif(($reportData['gross_profit_margin'] ?? 0) >= 20)
                            Good
                        @elseif(($reportData['gross_profit_margin'] ?? 0) >= 10)
                            Fair
                        @else
                            Needs Improvement
                        @endif
                    </p>
                </div>

                <div class="text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Operating Margin</p>
                    <p class="text-3xl font-bold {{ ($reportData['operating_profit_margin'] ?? 0) >= 15 ? 'text-green-600' : (($reportData['operating_profit_margin'] ?? 0) >= 5 ? 'text-yellow-600' : 'text-red-600') }}">
                        {{ number_format($reportData['operating_profit_margin'] ?? 0, 1) }}%
                    </p>
                    <p class="text-xs text-gray-400 mt-1">
                        @if(($reportData['operating_profit_margin'] ?? 0) >= 20)
                            Excellent
                        @elseif(($reportData['operating_profit_margin'] ?? 0) >= 10)
                            Good
                        @elseif(($reportData['operating_profit_margin'] ?? 0) >= 0)
                            Fair
                        @else
                            Loss
                        @endif
                    </p>
                </div>

                <div class="text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Net Profit Margin</p>
                    <p class="text-3xl font-bold {{ ($reportData['net_profit_margin'] ?? 0) >= 10 ? 'text-green-600' : (($reportData['net_profit_margin'] ?? 0) >= 0 ? 'text-yellow-600' : 'text-red-600') }}">
                        {{ number_format($reportData['net_profit_margin'] ?? 0, 1) }}%
                    </p>
                    <p class="text-xs text-gray-400 mt-1">
                        @if(($reportData['net_profit_margin'] ?? 0) >= 15)
                            Excellent
                        @elseif(($reportData['net_profit_margin'] ?? 0) >= 5)
                            Good
                        @elseif(($reportData['net_profit_margin'] ?? 0) >= 0)
                            Break-even
                        @else
                            Loss
                        @endif
                    </p>
                </div>

                <div class="text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Expense Ratio</p>
                    @php
                        $expenseRatio = ($reportData['revenue']['total'] ?? 0) > 0 
                            ? (($reportData['operating_expenses']['total'] ?? 0) / ($reportData['revenue']['total'] ?? 1)) * 100 
                            : 0;
                    @endphp
                    <p class="text-3xl font-bold {{ $expenseRatio <= 30 ? 'text-green-600' : ($expenseRatio <= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                        {{ number_format($expenseRatio, 1) }}%
                    </p>
                    <p class="text-xs text-gray-400 mt-1">
                        @if($expenseRatio <= 25)
                            Very Efficient
                        @elseif($expenseRatio <= 40)
                            Efficient
                        @elseif($expenseRatio <= 60)
                            Average
                        @else
                            High Expenses
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
