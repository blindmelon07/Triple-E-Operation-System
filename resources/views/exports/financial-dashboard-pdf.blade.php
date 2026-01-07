<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Financial Dashboard Report</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 15px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #1e40af;
            padding-bottom: 15px;
        }
        .header h1 {
            color: #1e40af;
            margin: 0;
            font-size: 22px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
            font-size: 12px;
        }
        .meta {
            font-size: 10px;
            color: #666;
            margin-bottom: 15px;
        }
        .metrics-grid {
            width: 100%;
            margin-bottom: 20px;
        }
        .metrics-grid td {
            width: 25%;
            padding: 5px;
            vertical-align: top;
        }
        .metric-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        .metric-card.revenue {
            background-color: #eff6ff;
            border-color: #bfdbfe;
        }
        .metric-card.profit {
            background-color: #f0fdf4;
            border-color: #bbf7d0;
        }
        .metric-card.expense {
            background-color: #fff7ed;
            border-color: #fed7aa;
        }
        .metric-card.net {
            background-color: #f0fdf4;
            border-color: #bbf7d0;
        }
        .metric-card.net.loss {
            background-color: #fef2f2;
            border-color: #fecaca;
        }
        .metric-label {
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .metric-value {
            font-size: 18px;
            font-weight: bold;
            color: #111827;
        }
        .metric-sub {
            font-size: 9px;
            color: #6b7280;
            margin-top: 5px;
        }
        .profit-text { color: #059669; }
        .loss-text { color: #dc2626; }
        
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1e40af;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        table.data-table th {
            background-color: #f3f4f6;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            border-bottom: 1px solid #e5e7eb;
        }
        table.data-table td {
            padding: 8px;
            border-bottom: 1px solid #f3f4f6;
        }
        table.data-table .amount {
            text-align: right;
            font-family: 'DejaVu Sans Mono', monospace;
        }
        
        .two-column {
            width: 100%;
        }
        .two-column td {
            width: 50%;
            vertical-align: top;
            padding: 0 10px;
        }
        .two-column td:first-child {
            padding-left: 0;
        }
        .two-column td:last-child {
            padding-right: 0;
        }
        
        .accounts-box {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
        }
        .accounts-box h4 {
            margin: 0 0 10px 0;
            font-size: 12px;
            color: #374151;
        }
        .accounts-box .value {
            font-size: 20px;
            font-weight: bold;
        }
        .accounts-box.receivable .value {
            color: #d97706;
        }
        .accounts-box.payable .value {
            color: #dc2626;
        }
        
        .footer {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            font-size: 9px;
            color: #6b7280;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Financial Dashboard</h1>
        <p>Period: {{ ucwords(str_replace('_', ' ', $period)) }}</p>
    </div>

    <div class="meta">
        Generated: {{ $generatedAt }}
    </div>

    <!-- Key Metrics -->
    <table class="metrics-grid">
        <tr>
            <td>
                <div class="metric-card revenue">
                    <div class="metric-label">Total Revenue</div>
                    <div class="metric-value">₱{{ number_format($dashboardData['revenue'] ?? 0, 2) }}</div>
                    <div class="metric-sub">Collections: ₱{{ number_format($dashboardData['collections'] ?? 0, 2) }}</div>
                </div>
            </td>
            <td>
                <div class="metric-card profit">
                    <div class="metric-label">Gross Profit</div>
                    <div class="metric-value">₱{{ number_format($dashboardData['gross_profit'] ?? 0, 2) }}</div>
                    <div class="metric-sub">Margin: {{ number_format($dashboardData['gross_profit_margin'] ?? 0, 1) }}%</div>
                </div>
            </td>
            <td>
                <div class="metric-card expense">
                    <div class="metric-label">Operating Expenses</div>
                    <div class="metric-value">₱{{ number_format(($dashboardData['expenses'] ?? 0) + ($dashboardData['maintenance_costs'] ?? 0), 2) }}</div>
                    <div class="metric-sub">Maintenance: ₱{{ number_format($dashboardData['maintenance_costs'] ?? 0, 2) }}</div>
                </div>
            </td>
            <td>
                <div class="metric-card net {{ ($dashboardData['net_profit'] ?? 0) < 0 ? 'loss' : '' }}">
                    <div class="metric-label">Net {{ ($dashboardData['net_profit'] ?? 0) >= 0 ? 'Profit' : 'Loss' }}</div>
                    <div class="metric-value {{ ($dashboardData['net_profit'] ?? 0) >= 0 ? 'profit-text' : 'loss-text' }}">₱{{ number_format(abs($dashboardData['net_profit'] ?? 0), 2) }}</div>
                    <div class="metric-sub">Margin: {{ number_format($dashboardData['net_profit_margin'] ?? 0, 1) }}%</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Accounts -->
    <table class="two-column">
        <tr>
            <td>
                <div class="accounts-box receivable">
                    <h4>Accounts Receivable (Outstanding)</h4>
                    <div class="value">₱{{ number_format($dashboardData['accounts_receivable'] ?? 0, 2) }}</div>
                </div>
            </td>
            <td>
                <div class="accounts-box payable">
                    <h4>Accounts Payable (To Pay)</h4>
                    <div class="value">₱{{ number_format($dashboardData['accounts_payable'] ?? 0, 2) }}</div>
                </div>
            </td>
        </tr>
    </table>

    <br>

    <!-- Two Column Layout for Expenses and Trend -->
    <table class="two-column">
        <tr>
            <td>
                @if($expensesByCategory && count($expensesByCategory) > 0)
                    <div class="section">
                        <div class="section-title">Expense Breakdown</div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th style="text-align: right;">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $totalExpenses = $expensesByCategory->sum('total'); @endphp
                                @foreach($expensesByCategory as $expense)
                                    <tr>
                                        <td>{{ $expense->category }}</td>
                                        <td class="amount">₱{{ number_format($expense->total, 2) }}</td>
                                    </tr>
                                @endforeach
                                <tr style="font-weight: bold; background-color: #f9fafb;">
                                    <td>Total</td>
                                    <td class="amount">₱{{ number_format($totalExpenses, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endif
            </td>
            <td>
                @if($monthlyTrend && count($monthlyTrend) > 0)
                    <div class="section">
                        <div class="section-title">Monthly Trend</div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th style="text-align: right;">Revenue</th>
                                    <th style="text-align: right;">Expenses</th>
                                    <th style="text-align: right;">Profit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($monthlyTrend->take(6) as $month)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($month->month . '-01')->format('M Y') }}</td>
                                        <td class="amount">₱{{ number_format($month->revenue, 2) }}</td>
                                        <td class="amount">₱{{ number_format($month->expenses, 2) }}</td>
                                        <td class="amount {{ $month->profit >= 0 ? 'profit-text' : 'loss-text' }}">
                                            {{ $month->profit >= 0 ? '' : '-' }}₱{{ number_format(abs($month->profit), 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </td>
        </tr>
    </table>

    <div class="footer">
        <p>This report was automatically generated. Please verify all figures before making business decisions.</p>
    </div>
</body>
</html>
