<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Closure Report – {{ $session->closed_at?->format('F d, Y') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            color: #1e293b;
            background: #fff;
            padding: 20px 24px;
        }

        /* ── Header ── */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #1e40af;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .company-name { font-size: 15px; font-weight: 700; color: #1e40af; }
        .company-sub  { font-size: 8px; color: #64748b; margin-top: 2px; }
        .report-title { text-align: right; }
        .report-title h2 { font-size: 13px; color: #1e40af; font-weight: 700; }
        .report-title p  { font-size: 8px; color: #64748b; margin-top: 2px; }

        /* ── Section title ── */
        .section-title {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #475569;
            background: #f1f5f9;
            padding: 4px 6px;
            margin-bottom: 6px;
            border-left: 3px solid #1e40af;
        }

        /* ── Summary grid ── */
        .summary-grid {
            width: 100%;
            margin-bottom: 14px;
        }
        .summary-grid td {
            width: 25%;
            padding: 6px 8px;
            vertical-align: top;
        }
        .summary-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 6px 8px;
        }
        .summary-card .label { font-size: 7.5px; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px; }
        .summary-card .value { font-size: 12px; font-weight: 700; color: #1e293b; margin-top: 2px; }
        .summary-card.highlight .value { color: #1e40af; }
        .summary-card.danger .value    { color: #dc2626; }
        .summary-card.success .value   { color: #16a34a; }

        /* ── Sales table ── */
        .sales-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .sales-table thead tr {
            background: #1e40af;
            color: #fff;
        }
        .sales-table th {
            padding: 5px 6px;
            text-align: left;
            font-size: 8px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }
        .sales-table th:last-child,
        .sales-table td:last-child { text-align: right; }
        .sales-table td {
            padding: 4px 6px;
            font-size: 9px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .sales-table tbody tr:nth-child(even) { background: #f8fafc; }
        .sales-table .total-row td {
            border-top: 2px solid #1e40af;
            font-weight: 700;
            font-size: 9.5px;
            color: #1e40af;
            padding-top: 5px;
        }

        /* ── Footer ── */
        .footer {
            text-align: center;
            font-size: 7.5px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            margin-top: 8px;
        }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="header">
        <div>
            <div class="company-name">Tri-E Enterprises</div>
            <div class="company-sub">Maharlika Highway, Cabidan, Sorsogon City &nbsp;|&nbsp; (+639) 993-052-2540</div>
        </div>
        <div class="report-title">
            <h2>Register Closure Report</h2>
            <p>Date: {{ $session->closed_at?->format('F d, Y') }}</p>
            <p>Cashier: {{ $session->user?->name ?? 'N/A' }}</p>
            <p>Session #{{ $session->id }} &nbsp;|&nbsp; Opened: {{ $session->opened_at?->format('h:i A') }} &nbsp;|&nbsp; Closed: {{ $session->closed_at?->format('h:i A') }}</p>
        </div>
    </div>

    {{-- Session Summary --}}
    <div class="section-title">Session Summary</div>
    <table class="summary-grid">
        <tr>
            <td>
                <div class="summary-card">
                    <div class="label">Opening Amount</div>
                    <div class="value">₱{{ number_format($session->opening_amount, 2) }}</div>
                </div>
            </td>
            <td>
                <div class="summary-card highlight">
                    <div class="label">Total Sales</div>
                    <div class="value">₱{{ number_format($session->total_sales, 2) }}</div>
                </div>
            </td>
            <td>
                <div class="summary-card">
                    <div class="label">Cash Sales</div>
                    <div class="value">₱{{ number_format($session->total_cash_sales, 2) }}</div>
                </div>
            </td>
            <td>
                <div class="summary-card">
                    <div class="label">Transactions</div>
                    <div class="value">{{ $session->total_transactions }}</div>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="summary-card">
                    <div class="label">Expected Cash</div>
                    <div class="value">₱{{ number_format($session->expected_amount, 2) }}</div>
                </div>
            </td>
            <td>
                <div class="summary-card">
                    <div class="label">Closing Amount</div>
                    <div class="value">₱{{ number_format($session->closing_amount, 2) }}</div>
                </div>
            </td>
            <td colspan="2">
                @php $disc = (float) $session->discrepancy; @endphp
                <div class="summary-card {{ $disc < 0 ? 'danger' : ($disc > 0 ? 'success' : '') }}">
                    <div class="label">Discrepancy</div>
                    <div class="value">{{ $disc >= 0 ? '+' : '' }}₱{{ number_format($disc, 2) }}</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- Sales List --}}
    <div class="section-title">Sales Transactions ({{ $sales->count() }} records)</div>
    <table class="sales-table">
        <thead>
            <tr>
                <th style="width:4%">#</th>
                <th style="width:8%">Time</th>
                <th style="width:22%">Customer</th>
                <th style="width:10%">Items</th>
                <th style="width:18%">Payment</th>
                <th style="width:15%">Status</th>
                <th style="width:13%">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $index => $sale)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $sale->created_at?->setTimezone('Asia/Manila')->format('h:i A') }}</td>
                    <td>{{ $sale->customer?->name ?? 'Walk-in' }}</td>
                    <td>{{ $sale->sale_items_count ?? $sale->sale_items->count() }}</td>
                    <td>{{ match($sale->payment_method) {
                        'cash'   => 'Cash',
                        'cod'    => 'Cash on Delivery',
                        'card'   => 'Card',
                        'gcash'  => 'GCash',
                        'paymaya'=> 'PayMaya',
                        default  => ucfirst($sale->payment_method ?? 'N/A')
                    } }}</td>
                    <td>{{ ucfirst($sale->payment_status ?? 'N/A') }}</td>
                    <td>
                        ₱{{ number_format($sale->total, 2) }}
                        @if($sale->payment_term_days)
                            <br><span style="font-size:7px;color:#94a3b8;">({{ $sale->payment_term_days }}-day terms)</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align:center; color:#94a3b8; padding: 12px;">No sales recorded in this session.</td>
                </tr>
            @endforelse
        </tbody>
        @if($sales->count())
            <tfoot>
                <tr class="total-row">
                    <td colspan="6">TOTAL</td>
                    <td>₱{{ number_format($sales->where('payment_status', '!=', 'unpaid')->sum('total'), 2) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    @if($session->notes)
        <div class="section-title">Notes</div>
        <p style="font-size:9px; color:#475569; padding: 4px 6px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:3px; margin-bottom:12px;">
            {{ $session->notes }}
        </p>
    @endif

    <div class="footer">
        <p>Generated on {{ now()->format('F d, Y h:i A') }} &nbsp;|&nbsp; Tri-E Enterprises &mdash; Register Closure Report</p>
    </div>

</body>
</html>
