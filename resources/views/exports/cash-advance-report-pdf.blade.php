<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cash Advance Monitoring Sheet</title>
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
        h2 {
            font-size: 12px;
            color: #1e293b;
            margin: 18px 0 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
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
        .muted {
            font-style: italic;
            color: #666;
        }
        .total-row td {
            font-weight: bold;
            background-color: #f7f7f7;
        }
        .ledger {
            page-break-inside: avoid;
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
    @php
        $money = fn ($v) => number_format((float) $v, 2);
        $blank = fn ($v) => (float) $v != 0 ? number_format((float) $v, 2) : '';
    @endphp

    <div class="header">
        @if($logoDataUri ?? null)
            <img src="{{ $logoDataUri }}" alt="Company Logo">
        @endif
        <p class="company-name">Tri-e Enterprises OPC</p>
        <h1>Cash Advance Monitoring Sheet</h1>
        <p>{{ $mode === 'ledger' ? 'Ledger per Employee' : 'Summary' }}</p>
        <p>
            @if($dateFrom || $dateTo)
                Period: {{ $dateFrom ?: 'earliest' }} to {{ $dateTo ?: 'latest' }}
            @else
                All activity
            @endif
        </p>
        <p>Generated: {{ $generatedAt }}</p>
    </div>

    @if(empty($summary))
        <p style="text-align:center; color:#999; margin-top: 20px;">No cash advance activity found for those filters.</p>
    @elseif($mode === 'ledger')
        @foreach($ledgers as $l)
            <div class="ledger">
                <h2>{{ $l['employee'] }}</h2>
                <table>
                    <thead>
                        <tr>
                            <th style="width:12%">Date</th>
                            <th style="width:17%">Reference</th>
                            <th>Particulars</th>
                            <th class="text-right" style="width:13%">Cash Advance</th>
                            <th class="text-right" style="width:13%">Deduction</th>
                            <th class="text-right" style="width:13%">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="muted">
                            <td colspan="5">Beginning balance</td>
                            <td class="text-right">{{ $money($l['beginning']) }}</td>
                        </tr>
                        @foreach($l['rows'] as $r)
                            <tr>
                                <td>{{ $r['date'] }}</td>
                                <td>{{ $r['reference'] }}</td>
                                <td>{{ $r['particulars'] }}</td>
                                <td class="text-right">{{ $blank($r['advance']) }}</td>
                                <td class="text-right">{{ $blank($r['deduction']) }}</td>
                                <td class="text-right">{{ $money($r['balance']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="3">Total</td>
                            <td class="text-right">{{ $money($l['totals']['advances']) }}</td>
                            <td class="text-right">{{ $money($l['totals']['deductions']) }}</td>
                            <td class="text-right">{{ $money($l['totals']['ending']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endforeach
    @else
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th class="text-right">Beginning Balance</th>
                    <th class="text-right">Cash Advances</th>
                    <th class="text-right">Deductions</th>
                    <th class="text-right">Ending Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($summary as $r)
                    <tr>
                        <td>{{ $r['employee'] }}</td>
                        <td class="text-right">{{ $money($r['beginning']) }}</td>
                        <td class="text-right">{{ $money($r['advances']) }}</td>
                        <td class="text-right">{{ $money($r['deductions']) }}</td>
                        <td class="text-right">{{ $money($r['ending']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td>Grand Total</td>
                    <td class="text-right">{{ $money($summaryTotals['beginning']) }}</td>
                    <td class="text-right">{{ $money($summaryTotals['advances']) }}</td>
                    <td class="text-right">{{ $money($summaryTotals['deductions']) }}</td>
                    <td class="text-right">{{ $money($summaryTotals['ending']) }}</td>
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
            <td style="width:30%"></td>
            <td style="width:35%">
                <div class="signature-role">Checked By</div>
                <div class="signature-line"></div>
                <div class="signature-label">Printed Name / Signature / Date</div>
            </td>
        </tr>
    </table>

    <p class="footer-note">Cash Advance Monitoring Sheet — generated by TOS Reports.</p>
</body>
</html>
