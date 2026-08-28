<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            color: #1e293b;
            padding: 20px 24px;
        }

        .header {
            text-align: center;
            margin-bottom: 16px;
            border-bottom: 2px solid #1e40af;
            padding-bottom: 12px;
        }
        .header h1 {
            color: #1e40af;
            font-size: 18px;
            margin-bottom: 4px;
        }
        .header p { color: #64748b; font-size: 9px; margin-top: 2px; }

        table.sales-table {
            width: 100%;
            border-collapse: collapse;
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
        .sales-table th.num,
        .sales-table td.num { text-align: right; }
        .sales-table td {
            padding: 4px 6px;
            font-size: 8.5px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .sales-table tbody tr:nth-child(even) { background: #f8fafc; }
        .sales-table .items-cell { color: #475569; }

        .sales-table tfoot .total-row td {
            border-top: 2px solid #1e40af;
            font-weight: 700;
            font-size: 10px;
            color: #1e40af;
            padding-top: 6px;
        }

        .footer {
            text-align: center;
            font-size: 7.5px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            margin-top: 12px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>Sales Report</h1>
        <p>Tri-E Enterprises &nbsp;|&nbsp; Period: {{ $periodLabel }}</p>
        <p>{{ $sales->count() }} sale{{ $sales->count() === 1 ? '' : 's' }}</p>
    </div>

    <table class="sales-table">
        <thead>
            <tr>
                <th style="width:5%">#</th>
                <th style="width:12%">Date</th>
                <th style="width:18%">Customer</th>
                <th style="width:8%" class="num">Items</th>
                <th style="width:42%">Items Sold</th>
                <th style="width:15%" class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $index => $sale)
                @php
                    $items = $sale->sale_items->where('is_voided', false);
                    $itemsSold = $items->map(function ($item) {
                        $name = $item->is_manual
                            ? $item->product_description
                            : ($item->product?->name ?? $item->product_description);
                        $qty = rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.');
                        return "{$name} x{$qty}";
                    })->implode(', ');
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $sale->date?->format('Y-m-d') }}</td>
                    <td>{{ $sale->customer?->name ?? 'Walk-in' }}</td>
                    <td class="num">{{ $items->count() }}</td>
                    <td class="items-cell">{{ $itemsSold ?: '—' }}</td>
                    <td class="num">₱{{ number_format($sale->total, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center; color:#94a3b8; padding: 12px;">No sales in this period.</td>
                </tr>
            @endforelse
        </tbody>
        @if($sales->count())
            <tfoot>
                <tr class="total-row">
                    <td colspan="5">GRAND TOTAL</td>
                    <td class="num">₱{{ number_format($sales->sum('total'), 2) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="footer">
        <p>Generated on {{ $generatedAt }} &nbsp;|&nbsp; Tri-E Enterprises &mdash; Sales Report</p>
    </div>

</body>
</html>
