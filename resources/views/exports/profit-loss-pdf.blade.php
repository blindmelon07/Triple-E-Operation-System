<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Profit & Loss Report</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #1e40af;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #1e40af;
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 11px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background-color: #f3f4f6;
            font-weight: bold;
            color: #374151;
        }
        .section-header {
            background-color: #1e40af;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }
        .section-header td {
            padding: 12px 10px;
        }
        .amount {
            text-align: right;
            font-family: 'DejaVu Sans Mono', monospace;
        }
        .total-row {
            font-weight: bold;
            background-color: #f9fafb;
        }
        .total-row td {
            border-top: 2px solid #374151;
        }
        .profit {
            color: #059669;
        }
        .loss {
            color: #dc2626;
        }
        .net-result {
            font-size: 16px;
            background-color: #f0fdf4;
        }
        .net-result.loss {
            background-color: #fef2f2;
        }
        .indent {
            padding-left: 30px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 10px;
            color: #6b7280;
            text-align: center;
        }
        .summary-box {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        .summary-box h3 {
            margin: 0 0 10px 0;
            color: #1e40af;
        }
        .summary-grid {
            display: table;
            width: 100%;
        }
        .summary-item {
            display: table-cell;
            width: 25%;
            text-align: center;
            padding: 10px;
        }
        .summary-item .label {
            font-size: 10px;
            color: #6b7280;
            margin-bottom: 5px;
        }
        .summary-item .value {
            font-size: 16px;
            font-weight: bold;
            color: #111827;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Profit & Loss Statement</h1>
        <p>For the Period {{ \Carbon\Carbon::parse($startDate)->format('F d, Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('F d, Y') }}</p>
    </div>

    <div class="meta">
        <span>Generated: {{ $generatedAt }}</span>
    </div>

    <!-- Summary Box -->
    <div class="summary-box">
        <h3>Summary</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="label">Revenue</div>
                <div class="value">₱{{ number_format($reportData['revenue'] ?? 0, 2) }}</div>
            </div>
            <div class="summary-item">
                <div class="label">Gross Profit</div>
                <div class="value">₱{{ number_format($reportData['gross_profit'] ?? 0, 2) }}</div>
            </div>
            <div class="summary-item">
                <div class="label">Expenses</div>
                <div class="value">₱{{ number_format(($reportData['expenses'] ?? 0) + ($reportData['maintenance_costs'] ?? 0), 2) }}</div>
            </div>
            <div class="summary-item">
                <div class="label">Net {{ ($reportData['net_profit'] ?? 0) >= 0 ? 'Profit' : 'Loss' }}</div>
                <div class="value {{ ($reportData['net_profit'] ?? 0) >= 0 ? 'profit' : 'loss' }}">₱{{ number_format(abs($reportData['net_profit'] ?? 0), 2) }}</div>
            </div>
        </div>
    </div>

    <!-- Detailed Report -->
    <table>
        <!-- Revenue Section -->
        <tr class="section-header">
            <td colspan="2">REVENUE</td>
        </tr>
        <tr>
            <td>Sales Revenue</td>
            <td class="amount">₱{{ number_format($reportData['revenue'] ?? 0, 2) }}</td>
        </tr>
        <tr class="total-row">
            <td><strong>Total Revenue</strong></td>
            <td class="amount"><strong>₱{{ number_format($reportData['revenue'] ?? 0, 2) }}</strong></td>
        </tr>

        <!-- Cost of Goods Sold Section -->
        <tr class="section-header">
            <td colspan="2">COST OF GOODS SOLD</td>
        </tr>
        <tr>
            <td>Cost of Goods Sold</td>
            <td class="amount">₱{{ number_format($reportData['cost_of_goods_sold'] ?? 0, 2) }}</td>
        </tr>
        <tr class="total-row">
            <td><strong>Total COGS</strong></td>
            <td class="amount"><strong>₱{{ number_format($reportData['cost_of_goods_sold'] ?? 0, 2) }}</strong></td>
        </tr>

        <!-- Gross Profit -->
        <tr class="total-row">
            <td><strong>GROSS PROFIT</strong></td>
            <td class="amount {{ ($reportData['gross_profit'] ?? 0) >= 0 ? 'profit' : 'loss' }}">
                <strong>₱{{ number_format($reportData['gross_profit'] ?? 0, 2) }}</strong>
                <br><small>({{ number_format($reportData['gross_profit_margin'] ?? 0, 1) }}% margin)</small>
            </td>
        </tr>

        <!-- Operating Expenses Section -->
        <tr class="section-header">
            <td colspan="2">OPERATING EXPENSES</td>
        </tr>
        @if(isset($reportData['expenses_by_category']) && count($reportData['expenses_by_category']) > 0)
            @foreach($reportData['expenses_by_category'] as $expense)
                <tr>
                    <td class="indent">{{ $expense->category }}</td>
                    <td class="amount">₱{{ number_format($expense->total, 2) }}</td>
                </tr>
            @endforeach
        @endif
        <tr>
            <td class="indent">Maintenance & Repairs</td>
            <td class="amount">₱{{ number_format($reportData['maintenance_costs'] ?? 0, 2) }}</td>
        </tr>
        <tr class="total-row">
            <td><strong>Total Operating Expenses</strong></td>
            <td class="amount"><strong>₱{{ number_format(($reportData['expenses'] ?? 0) + ($reportData['maintenance_costs'] ?? 0), 2) }}</strong></td>
        </tr>

        <!-- Operating Profit -->
        <tr class="total-row">
            <td><strong>OPERATING PROFIT</strong></td>
            <td class="amount {{ ($reportData['operating_profit'] ?? 0) >= 0 ? 'profit' : 'loss' }}">
                <strong>₱{{ number_format($reportData['operating_profit'] ?? 0, 2) }}</strong>
            </td>
        </tr>

        <!-- Net Profit/Loss -->
        <tr class="net-result {{ ($reportData['net_profit'] ?? 0) >= 0 ? '' : 'loss' }}">
            <td><strong>NET {{ ($reportData['net_profit'] ?? 0) >= 0 ? 'PROFIT' : 'LOSS' }}</strong></td>
            <td class="amount {{ ($reportData['net_profit'] ?? 0) >= 0 ? 'profit' : 'loss' }}">
                <strong>₱{{ number_format(abs($reportData['net_profit'] ?? 0), 2) }}</strong>
                <br><small>({{ number_format($reportData['net_profit_margin'] ?? 0, 1) }}% margin)</small>
            </td>
        </tr>
    </table>

    <div class="footer">
        <p>This report was automatically generated. Please verify all figures before making business decisions.</p>
    </div>
</body>
</html>
