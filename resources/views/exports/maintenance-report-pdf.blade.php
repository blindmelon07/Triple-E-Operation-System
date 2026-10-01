<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Maintenance Report</title>
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
        .total-row td {
            font-weight: bold;
            background-color: #f7f7f7;
        }
        .section-title {
            color: #1e40af;
            font-size: 12px;
            margin: 20px 0 0;
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
        <h1>Maintenance Report{{ ($perSupplier ?? false) ? ' — Per Supplier' : '' }}</h1>
        <p>
            @if($dateFrom || $dateTo)
                Period: {{ $dateFrom ?: 'earliest' }} to {{ $dateTo ?: 'latest' }}
            @else
                All activity
            @endif
        </p>
        <p>Generated: {{ $generatedAt }}</p>
    </div>

    @if($perSupplier ?? false)
        @if(empty($groups))
            <p style="text-align:center; color:#999; margin-top: 20px;">No maintenance activity found for those filters.</p>
        @else
            <h3 class="section-title">Summary per Supplier</h3>
            <table>
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th class="text-right">No. of Records</th>
                        <th class="text-right">Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groups as $group)
                        <tr>
                            <td>{{ $group['supplier'] }}</td>
                            <td class="text-right">{{ number_format($group['count']) }}</td>
                            <td class="text-right">{{ number_format($group['subtotal'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td>Grand Total</td>
                        <td class="text-right">{{ number_format(array_sum(array_column($groups, 'count'))) }}</td>
                        <td class="text-right">{{ number_format($totals['amount'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>

            @foreach($groups as $group)
                <h3 class="section-title">{{ $group['supplier'] }}</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Vehicle</th>
                            <th>SI /DR #</th>
                            <th>PO#</th>
                            <th class="text-right">Amount</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($group['rows'] as $r)
                            <tr>
                                <td>{{ $r['date'] }}</td>
                                <td>{{ $r['vehicle'] }}</td>
                                <td>{{ $r['si_number'] }}</td>
                                <td>{{ $r['po_number'] }}</td>
                                <td class="text-right">{{ number_format($r['amount'], 2) }}</td>
                                <td class="text-right">{{ number_format($r['running_total'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="4">Subtotal — {{ $group['supplier'] }}</td>
                            <td class="text-right">{{ number_format($group['subtotal'], 2) }}</td>
                            <td class="text-right">{{ number_format($group['subtotal'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            @endforeach
        @endif
    @elseif(empty($rows))
        <p style="text-align:center; color:#999; margin-top: 20px;">No maintenance activity found for those filters.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Supplier</th>
                    <th>SI /DR #</th>
                    <th>PO#</th>
                    <th class="text-right">Amount</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $r)
                    <tr>
                        <td>{{ $r['date'] }}</td>
                        <td>{{ $r['supplier'] }}</td>
                        <td>{{ $r['si_number'] }}</td>
                        <td>{{ $r['po_number'] }}</td>
                        <td class="text-right">{{ number_format($r['amount'], 2) }}</td>
                        <td class="text-right">{{ number_format($r['running_total'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="4">Grand Total</td>
                    <td class="text-right">{{ number_format($totals['amount'], 2) }}</td>
                    <td class="text-right">{{ number_format($totals['amount'], 2) }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    <table class="signature-block">
        <tr>
            <td style="width:50%">
                <div class="signature-role">Prepared By</div>
                <div class="signature-line">{{ $preparedBy ?? '' }}</div>
                <div class="signature-label">Printed Name / Signature / Date</div>
            </td>
            <td style="width:50%"></td>
        </tr>
    </table>

    <p class="footer-note">Maintenance Report — generated by TOS Reports.</p>
</body>
</html>
