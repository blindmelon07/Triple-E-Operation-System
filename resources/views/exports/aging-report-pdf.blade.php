<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Aging Report</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #1e40af;
            padding-bottom: 15px;
        }
        .header h1 {
            color: #1e40af;
            margin: 0 0 5px 0;
            font-size: 20px;
        }
        .header p {
            margin: 3px 0;
            color: #666;
            font-size: 10px;
        }
        .summary-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .summary-col {
            display: table-cell;
            width: 50%;
            padding-right: 10px;
            vertical-align: top;
        }
        .summary-col:last-child { padding-right: 0; padding-left: 10px; }
        .summary-box {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 10px;
        }
        .summary-box h3 {
            margin: 0 0 8px 0;
            font-size: 11px;
            padding-bottom: 6px;
            border-bottom: 1px solid #e5e7eb;
        }
        .summary-box.receivable h3 { color: #059669; }
        .summary-box.payable h3    { color: #3b82f6; }
        .bucket-row {
            display: table;
            width: 100%;
            margin-bottom: 3px;
        }
        .bucket-label { display: table-cell; color: #6b7280; }
        .bucket-value { display: table-cell; text-align: right; font-weight: bold; }
        .bucket-total .bucket-label,
        .bucket-total .bucket-value {
            font-weight: bold;
            color: #111827;
            border-top: 1px solid #e5e7eb;
            padding-top: 4px;
            margin-top: 4px;
        }
        .alert {
            margin-top: 8px;
            padding: 5px 8px;
            background: #fef2f2;
            border-left: 3px solid #ef4444;
            color: #dc2626;
            font-size: 9px;
        }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: white;
            background-color: #1e40af;
            padding: 8px 10px;
            margin: 20px 0 0 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background-color: #f3f4f6;
            font-weight: bold;
            color: #374151;
            padding: 7px 8px;
            text-align: left;
            border-bottom: 1px solid #d1d5db;
            font-size: 9px;
        }
        td {
            padding: 6px 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge {
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-current  { background: #d1fae5; color: #065f46; }
        .badge-1-30     { background: #fef3c7; color: #92400e; }
        .badge-31-60    { background: #ffedd5; color: #9a3412; }
        .badge-61-90    { background: #fee2e2; color: #991b1b; }
        .badge-over-90  { background: #fca5a5; color: #7f1d1d; }
        .overdue        { color: #dc2626; }
        .current-due    { color: #059669; }
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            font-size: 9px;
            color: #9ca3af;
            text-align: center;
        }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Accounts Receivable &amp; Payable Aging Report</h1>
        <p>Generated: {{ $generatedAt }}</p>
    </div>

    {{-- ── SUMMARY CARDS ──────────────────────────────────────── --}}
    <div class="summary-grid">
        <div class="summary-col">
            <div class="summary-box receivable">
                <h3>Accounts Receivable (Customers Owe You)</h3>
                <div class="bucket-row">
                    <span class="bucket-label">Current</span>
                    <span class="bucket-value" style="color:#059669;">₱{{ number_format($stats['receivables']['current'], 2) }}</span>
                </div>
                <div class="bucket-row">
                    <span class="bucket-label">1-30 Days</span>
                    <span class="bucket-value" style="color:#d97706;">₱{{ number_format($stats['receivables']['1_30'], 2) }}</span>
                </div>
                <div class="bucket-row">
                    <span class="bucket-label">31-60 Days</span>
                    <span class="bucket-value" style="color:#ea580c;">₱{{ number_format($stats['receivables']['31_60'], 2) }}</span>
                </div>
                <div class="bucket-row">
                    <span class="bucket-label">61-90 Days</span>
                    <span class="bucket-value" style="color:#dc2626;">₱{{ number_format($stats['receivables']['61_90'], 2) }}</span>
                </div>
                <div class="bucket-row">
                    <span class="bucket-label">Over 90 Days</span>
                    <span class="bucket-value" style="color:#991b1b;">₱{{ number_format($stats['receivables']['over_90'], 2) }}</span>
                </div>
                <div class="bucket-row bucket-total">
                    <span class="bucket-label">Total Outstanding</span>
                    <span class="bucket-value">₱{{ number_format($stats['receivables']['total'], 2) }}</span>
                </div>
                @if($stats['receivables']['overdue_count'] > 0)
                    <div class="alert">⚠ {{ $stats['receivables']['overdue_count'] }} invoice(s) overdue</div>
                @endif
            </div>
        </div>
        <div class="summary-col">
            <div class="summary-box payable">
                <h3>Accounts Payable (You Owe Suppliers)</h3>
                <div class="bucket-row">
                    <span class="bucket-label">Current</span>
                    <span class="bucket-value" style="color:#059669;">₱{{ number_format($stats['payables']['current'], 2) }}</span>
                </div>
                <div class="bucket-row">
                    <span class="bucket-label">1-30 Days</span>
                    <span class="bucket-value" style="color:#d97706;">₱{{ number_format($stats['payables']['1_30'], 2) }}</span>
                </div>
                <div class="bucket-row">
                    <span class="bucket-label">31-60 Days</span>
                    <span class="bucket-value" style="color:#ea580c;">₱{{ number_format($stats['payables']['31_60'], 2) }}</span>
                </div>
                <div class="bucket-row">
                    <span class="bucket-label">61-90 Days</span>
                    <span class="bucket-value" style="color:#dc2626;">₱{{ number_format($stats['payables']['61_90'], 2) }}</span>
                </div>
                <div class="bucket-row">
                    <span class="bucket-label">Over 90 Days</span>
                    <span class="bucket-value" style="color:#991b1b;">₱{{ number_format($stats['payables']['over_90'], 2) }}</span>
                </div>
                <div class="bucket-row bucket-total">
                    <span class="bucket-label">Total Outstanding</span>
                    <span class="bucket-value">₱{{ number_format($stats['payables']['total'], 2) }}</span>
                </div>
                @if($stats['payables']['overdue_count'] > 0)
                    <div class="alert">⚠ {{ $stats['payables']['overdue_count'] }} bill(s) overdue</div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── RECEIVABLES TABLE ───────────────────────────────────── --}}
    <div class="section-title">ACCOUNTS RECEIVABLE — Customer Invoices</div>
    <table>
        <thead>
            <tr>
                <th>Invoice #</th>
                <th>Customer</th>
                <th>Invoice Date</th>
                <th>Due Date</th>
                <th class="text-right">Total</th>
                <th class="text-right">Paid</th>
                <th class="text-right">Balance</th>
                <th class="text-right">Days Overdue</th>
                <th class="text-center">Aging</th>
            </tr>
        </thead>
        <tbody>
            @forelse($receivables as $sale)
                @php $overdue = $sale->days_overdue; @endphp
                <tr>
                    <td>INV-{{ $sale->id }}</td>
                    <td>{{ $sale->customer?->name ?? 'N/A' }}</td>
                    <td>{{ \Carbon\Carbon::parse($sale->date)->format('m/d/Y') }}</td>
                    <td class="{{ $overdue !== null ? 'overdue' : 'current-due' }}">
                        {{ \Carbon\Carbon::parse($sale->due_date)->format('m/d/Y') }}
                    </td>
                    <td class="text-right">₱{{ number_format($sale->total, 2) }}</td>
                    <td class="text-right">₱{{ number_format($sale->amount_paid, 2) }}</td>
                    <td class="text-right overdue">₱{{ number_format($sale->balance, 2) }}</td>
                    <td class="text-right {{ $overdue !== null ? 'overdue' : 'current-due' }}">
                        {{ $overdue !== null ? $overdue.' days' : 'Current' }}
                    </td>
                    <td class="text-center">
                        @php
                            $bucket = $sale->aging_bucket;
                            $cls = match($bucket) {
                                'Current'      => 'badge-current',
                                '1-30 Days'    => 'badge-1-30',
                                '31-60 Days'   => 'badge-31-60',
                                '61-90 Days'   => 'badge-61-90',
                                'Over 90 Days' => 'badge-over-90',
                                default        => '',
                            };
                        @endphp
                        <span class="badge {{ $cls }}">{{ $bucket }}</span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center" style="color:#9ca3af;">No outstanding receivables.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- ── PAYABLES TABLE ──────────────────────────────────────── --}}
    <div class="section-title page-break">ACCOUNTS PAYABLE — Supplier Bills</div>
    <table>
        <thead>
            <tr>
                <th>PO #</th>
                <th>Supplier</th>
                <th>Purchase Date</th>
                <th>Due Date</th>
                <th class="text-right">Total</th>
                <th class="text-right">Paid</th>
                <th class="text-right">Balance</th>
                <th class="text-right">Days Overdue</th>
                <th class="text-center">Aging</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payables as $purchase)
                @php $overdue = $purchase->days_overdue; @endphp
                <tr>
                    <td>PO-{{ $purchase->id }}</td>
                    <td>{{ $purchase->supplier?->name ?? 'N/A' }}</td>
                    <td>{{ \Carbon\Carbon::parse($purchase->date)->format('m/d/Y') }}</td>
                    <td class="{{ $overdue !== null ? 'overdue' : 'current-due' }}">
                        {{ \Carbon\Carbon::parse($purchase->due_date)->format('m/d/Y') }}
                    </td>
                    <td class="text-right">₱{{ number_format($purchase->total, 2) }}</td>
                    <td class="text-right">₱{{ number_format($purchase->amount_paid, 2) }}</td>
                    <td class="text-right overdue">₱{{ number_format($purchase->balance, 2) }}</td>
                    <td class="text-right {{ $overdue !== null ? 'overdue' : 'current-due' }}">
                        {{ $overdue !== null ? $overdue.' days' : 'Current' }}
                    </td>
                    <td class="text-center">
                        @php
                            $bucket = $purchase->aging_bucket;
                            $cls = match($bucket) {
                                'Current'      => 'badge-current',
                                '1-30 Days'    => 'badge-1-30',
                                '31-60 Days'   => 'badge-31-60',
                                '61-90 Days'   => 'badge-61-90',
                                'Over 90 Days' => 'badge-over-90',
                                default        => '',
                            };
                        @endphp
                        <span class="badge {{ $cls }}">{{ $bucket }}</span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center" style="color:#9ca3af;">No outstanding payables.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>This report was automatically generated. Please verify all figures before making business decisions.</p>
    </div>
</body>
</html>
