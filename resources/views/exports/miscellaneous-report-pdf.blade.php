<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Miscellaneous Report</title>
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
        <h1>Miscellaneous Report</h1>
        @if($vehicleName ?? null)
            <p>Vehicle: {{ $vehicleName }}</p>
        @endif
        <p>
            @if($dateFrom || $dateTo)
                Period: {{ $dateFrom ?: 'earliest' }} to {{ $dateTo ?: 'latest' }}
            @else
                All activity
            @endif
        </p>
        <p>Generated: {{ $generatedAt }}</p>
    </div>

    @if(empty($rows))
        <p style="text-align:center; color:#999; margin-top: 20px;">No miscellaneous expenses found for those filters.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Reference</th>
                    <th>Description</th>
                    <th>Paid To</th>
                    <th>SI/DR / OR #</th>
                    <th>Truck/Unit</th>
                    <th class="text-right">Amount</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $r)
                    <tr>
                        <td>{{ $r['date'] }}</td>
                        <td>{{ $r['reference_number'] }}</td>
                        <td>{{ $r['description'] }}</td>
                        <td>{{ $r['payee'] }}</td>
                        <td>{{ $r['si_dr_number'] }}</td>
                        <td>{{ $r['truck'] ?: '—' }}@if($r['plate_number'])<br><span style="color:#888;">{{ $r['plate_number'] }}</span>@endif</td>
                        <td class="text-right">{{ number_format($r['amount'], 2) }}</td>
                        <td class="text-right">{{ number_format($r['running_total'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="6">Grand Total ({{ $totals['count'] }} {{ \Illuminate\Support\Str::plural('entry', $totals['count']) }})</td>
                    <td class="text-right">{{ number_format($totals['amount'], 2) }}</td>
                    <td class="text-right">{{ number_format($totals['amount'], 2) }}</td>
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

    <p class="footer-note">Miscellaneous Report — generated by TOS Reports.</p>
</body>
</html>
