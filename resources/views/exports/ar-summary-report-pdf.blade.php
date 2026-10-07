<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>SOA Summary Report – Accounts Receivable</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9.5px;
            color: #111;
            margin: 0;
            padding: 16px 20px;
        }
        .brand { text-align: center; margin-bottom: 6px; }
        .brand img { max-height: 40px; }
        .brand .company-name { font-size: 12px; font-weight: bold; color: #1e40af; margin-top: 2px; }
        h1 {
            text-align: center;
            font-size: 15px;
            letter-spacing: 0.5px;
            margin: 6px 0 12px;
        }
        table.sheet { width: 100%; border-collapse: collapse; }
        table.sheet th, table.sheet td { border: 1px solid #555; padding: 3px 6px; }
        table.sheet th { font-size: 9px; text-align: center; background: #f3f4f6; }
        th.notes { color: #dc2626; }
        td.c { text-align: center; }
        td.r { text-align: right; }
        td.name { text-transform: uppercase; }
        td.note { color: #dc2626; font-weight: bold; text-transform: uppercase; }
        tr.overdue td { background: #dbeafe; color: #dc2626; }
        tr.total td { font-weight: bold; }
        tr.total td.label { background: #dbeafe; text-align: center; }
        .signatures { margin-top: 28px; }
        .signatures td { padding: 4px 0; vertical-align: bottom; }
        .line { border-bottom: 1px solid #111; display: inline-block; min-width: 280px; text-align: center; }
        .footer { margin-top: 18px; font-size: 8px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="brand">
        @if($logoDataUri)
            <img src="{{ $logoDataUri }}" alt="Logo">
        @endif
        <div class="company-name">Tri-e Enterprises OPC</div>
    </div>

    <h1>STATEMENT OF ACCOUNT SUMMARY REPORT ON HARDWARE – {{ $year }} ( ACCOUNTS RECEIVABLES)</h1>

    <table class="sheet">
        <thead>
            <tr>
                <th style="width:4%">NO.</th>
                <th style="width:22%">CUSTOMER NAME</th>
                <th style="width:15%">SOA No.</th>
                <th style="width:9%">BILLING DATE</th>
                <th style="width:12%">ACCOUNTS RECEIVABLE</th>
                <th style="width:11%">TOTAL AMOUNT</th>
                <th style="width:8%">STATUS</th>
                <th class="notes" style="width:19%">NOTES:</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr class="{{ $row['status'] === 'OVER DUE' ? 'overdue' : '' }}">
                    <td class="c">{{ $row['id'] }}</td>
                    <td class="name">{{ $row['name'] }}</td>
                    <td>{{ $row['soa_number'] }}</td>
                    <td class="c">{{ $row['billing_date'] ? \Illuminate\Support\Carbon::parse($row['billing_date'])->format('j-M-y') : '' }}</td>
                    <td class="r">{{ number_format($row['receivable'], 2) }}</td>
                    <td class="r">{{ number_format($row['running_total'], 2) }}</td>
                    <td class="c">{{ $row['status'] }}</td>
                    <td class="note">{{ $row['notes'] }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="c">No outstanding receivables.</td></tr>
            @endforelse
            <tr class="total">
                <td></td>
                <td></td>
                <td colspan="2" class="label">TOTAL AMOUNT RECEIVABLE:</td>
                <td class="r">{{ number_format($total, 2) }}</td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <table class="signatures">
        <tr>
            <td style="width:110px">PREPARED BY:</td>
            <td><span class="line">{{ $preparedBy }}</span></td>
        </tr>
        <tr><td colspan="2" style="height:14px"></td></tr>
        <tr>
            <td colspan="2">RECEIVED AND VERIFIED AS TRUE AND COMPLETE BY : <span class="line">&nbsp;</span></td>
        </tr>
    </table>

    <div class="footer">Generated {{ $generatedAt }}</div>
</body>
</html>
