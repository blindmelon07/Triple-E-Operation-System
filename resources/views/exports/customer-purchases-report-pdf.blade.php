<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Customer Purchases Report</title>
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
        .header img {
            max-height: 50px;
            margin-bottom: 8px;
        }
        .header .company-name {
            font-size: 14px;
            font-weight: bold;
            color: #1e40af;
            margin: 0 0 6px;
        }
        .header h1 {
            color: #1e40af;
            margin: 0;
            font-size: 20px;
        }
        .header p {
            margin: 4px 0;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 5px 7px;
            text-align: left;
            word-break: break-word;
        }
        th {
            background-color: #1e40af;
            color: #fff;
        }
        .text-right {
            text-align: right;
        }
        .date-row td {
            font-weight: bold;
            background-color: #e0e7ff;
        }
        .subtotal-row td {
            font-weight: bold;
            background-color: #fafafa;
        }
        .total-row td {
            font-weight: bold;
            background-color: #f7f7f7;
        }
        .signature-block {
            margin-top: 36px;
        }
        .signature-block td {
            border: none;
            padding: 0 30px;
            vertical-align: top;
        }
        .signature-role {
            font-size: 9px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 10px;
        }
        .signature-line {
            border-bottom: 1px solid #1e293b;
            height: 28px;
            text-align: center;
            vertical-align: bottom;
            line-height: 40px;
            font-size: 10px;
        }
        .signature-label {
            font-size: 8.5px;
            color: #64748b;
            text-align: center;
            margin-top: 4px;
            text-transform: uppercase;
        }
        .footer-note {
            margin-top: 15px;
            font-size: 9px;
            color: #999;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        @if($logoDataUri ?? null)
            <img src="{{ $logoDataUri }}" alt="Company Logo">
        @endif
        <p class="company-name">Tri-e Enterprises OPC</p>
        <h1>Customer Purchases Report</h1>
        <p><strong>Customer: {{ $customerName }}</strong></p>
        <p>
            @if($dateFrom || $dateTo)
                Period: {{ $dateFrom ?: 'earliest' }} to {{ $dateTo ?: 'latest' }}
            @else
                All activity
            @endif
        </p>
        <p>Generated: {{ $generatedAt }}</p>
    </div>

    @if(empty($groups))
        <p style="text-align:center; color:#999; margin-top: 20px;">No purchases found for this customer in that period.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Item</th>
                    <th>Unit</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Discount</th>
                    <th class="text-right">Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($groups as $group)
                    <tr class="date-row">
                        <td colspan="8">{{ $group['date'] }} <span style="font-weight:normal; color:#666;">({{ $group['sales_count'] }} {{ \Illuminate\Support\Str::plural('transaction', $group['sales_count']) }})</span></td>
                    </tr>
                    @foreach($group['items'] as $item)
                        <tr>
                            <td>{{ $item['reference_number'] }}</td>
                            <td>{{ $item['product'] }}</td>
                            <td>{{ $item['unit'] }}</td>
                            <td class="text-right">{{ rtrim(rtrim(number_format($item['quantity'], 2), '0'), '.') }}</td>
                            <td class="text-right">{{ number_format($item['unit_price'], 2) }}</td>
                            <td class="text-right">{{ $item['discount'] > 0 ? number_format($item['discount'], 2) : '—' }}</td>
                            <td class="text-right">{{ number_format($item['amount'], 2) }}</td>
                            <td>{{ $item['payment_status'] }}</td>
                        </tr>
                    @endforeach
                    <tr class="subtotal-row">
                        <td colspan="6" class="text-right">Subtotal — {{ $group['date'] }}</td>
                        <td class="text-right">{{ number_format($group['subtotal'], 2) }}</td>
                        <td></td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="6">Grand Total ({{ $totals['sales'] }} {{ \Illuminate\Support\Str::plural('transaction', $totals['sales']) }}, {{ $totals['items'] }} {{ \Illuminate\Support\Str::plural('item', $totals['items']) }})</td>
                    <td class="text-right">{{ number_format($totals['amount'], 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    @endif

    <table class="signature-block">
        <tr>
            <td style="width:35%">
                <div class="signature-role">Prepared By</div>
                <div class="signature-line">{{ $preparedBy ?? '' }}</div>
                <div class="signature-label">Printed Name / Signature / Date</div>
            </td>
            <td style="width:65%"></td>
        </tr>
    </table>

    <p class="footer-note">Customer Purchases Report — generated by TOS Reports.</p>
</body>
</html>
