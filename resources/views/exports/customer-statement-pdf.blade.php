<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Statement of Account – {{ $customer->name }}</title>
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
        .header-brand { display: flex; align-items: center; gap: 8px; }
        .header-brand img { max-height: 40px; }
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

        /* ── Customer info block ── */
        .customer-block {
            width: 100%;
            margin-bottom: 14px;
        }
        .customer-block td { vertical-align: top; padding: 6px 8px; }
        .customer-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 8px 10px;
        }
        .customer-box .name { font-size: 11px; font-weight: 700; color: #1e293b; margin-bottom: 3px; }
        .customer-box .line { font-size: 8.5px; color: #64748b; margin-top: 1px; }

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

        /* ── Ledger table ── */
        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .ledger-table thead tr {
            background: #1e40af;
            color: #fff;
        }
        .ledger-table th {
            padding: 5px 6px;
            text-align: left;
            font-size: 8px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }
        .ledger-table th:nth-child(4),
        .ledger-table th:nth-child(5),
        .ledger-table th:nth-child(6),
        .ledger-table td:nth-child(4),
        .ledger-table td:nth-child(5),
        .ledger-table td:nth-child(6) { text-align: right; }
        .ledger-table td {
            padding: 4px 6px;
            font-size: 9px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .ledger-table tbody tr:nth-child(even) { background: #f8fafc; }
        .ledger-table .opening-row td {
            font-style: italic;
            color: #64748b;
            border-bottom: 1px solid #cbd5e1;
        }
        .ledger-table .total-row td {
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
        <div class="header-brand">
            @if($logoDataUri ?? null)
                <img src="{{ $logoDataUri }}" alt="Company Logo">
            @endif
            <div>
                <div class="company-name">Tri-E Enterprises</div>
                <div class="company-sub">Maharlika Highway, Cabidan, Sorsogon City &nbsp;|&nbsp; (+639) 993-052-2540</div>
            </div>
        </div>
        <div class="report-title">
            <h2>Statement of Account</h2>
            <p>
                @if($dateFrom && $dateTo)
                    Period: {{ $dateFrom->format('M d, Y') }} – {{ $dateTo->format('M d, Y') }}
                @elseif($dateTo)
                    As of {{ $dateTo->format('M d, Y') }}
                @else
                    Full Account History
                @endif
            </p>
            <p>Generated: {{ $generatedAt }}</p>
        </div>
    </div>

    {{-- Customer Info --}}
    <table class="customer-block">
        <tr>
            <td style="width:60%">
                <div class="customer-box">
                    <div class="name">{{ $customer->name }}</div>
                    @if($customer->company)
                        <div class="line">{{ $customer->company }}</div>
                    @endif
                    @if($customer->address)
                        <div class="line">{{ $customer->address }}</div>
                    @endif
                    @if($customer->phone)
                        <div class="line">Tel: {{ $customer->phone }}</div>
                    @endif
                    @if($customer->email)
                        <div class="line">Email: {{ $customer->email }}</div>
                    @endif
                </div>
            </td>
            <td style="width:40%">
                <div class="customer-box">
                    <div class="line"><strong>Payment Terms:</strong> {{ $customer->payment_term_days === 0 ? 'COD' : 'Net '.$customer->payment_term_days.' days' }}</div>
                    @if($customer->contact_person)
                        <div class="line"><strong>Contact Person:</strong> {{ $customer->contact_person }}</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- Summary --}}
    <div class="section-title">Account Summary</div>
    <table class="summary-grid">
        <tr>
            <td>
                <div class="summary-card">
                    <div class="label">Opening Balance</div>
                    <div class="value">₱{{ number_format($openingBalance, 2) }}</div>
                </div>
            </td>
            <td>
                <div class="summary-card highlight">
                    <div class="label">Total Invoiced</div>
                    <div class="value">₱{{ number_format($totalInvoiced, 2) }}</div>
                </div>
            </td>
            <td>
                <div class="summary-card success">
                    <div class="label">Total Paid</div>
                    <div class="value">₱{{ number_format($totalPaid, 2) }}</div>
                </div>
            </td>
            <td>
                <div class="summary-card {{ $closingBalance > 0 ? 'danger' : '' }}">
                    <div class="label">Closing Balance</div>
                    <div class="value">₱{{ number_format($closingBalance, 2) }}</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- Ledger --}}
    <div class="section-title">Transaction Ledger ({{ $entries->count() }} entries)</div>
    <table class="ledger-table">
        <thead>
            <tr>
                <th style="width:10%">Date</th>
                <th style="width:20%">Transaction</th>
                <th style="width:24%">Details</th>
                <th style="width:15%">Debit</th>
                <th style="width:15%">Credit</th>
                <th style="width:16%">Balance</th>
            </tr>
        </thead>
        <tbody>
            @if($dateFrom)
                <tr class="opening-row">
                    <td>{{ $dateFrom->format('M d, Y') }}</td>
                    <td colspan="4">Opening Balance (carried forward)</td>
                    <td>₱{{ number_format($openingBalance, 2) }}</td>
                </tr>
            @endif
            @forelse($entries as $entry)
                <tr>
                    <td>{{ $entry['date']->format('M d, Y') }}</td>
                    <td>{{ $entry['label'] }}</td>
                    <td>{{ $entry['detail'] }}</td>
                    <td>{{ $entry['debit'] > 0 ? '₱'.number_format($entry['debit'], 2) : '—' }}</td>
                    <td>{{ $entry['credit'] > 0 ? '₱'.number_format($entry['credit'], 2) : '—' }}</td>
                    <td>₱{{ number_format($entry['running_balance'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center; color:#94a3b8; padding: 12px;">No transactions recorded for this period.</td>
                </tr>
            @endforelse
        </tbody>
        @if($entries->count())
            <tfoot>
                <tr class="total-row">
                    <td colspan="3">TOTALS</td>
                    <td>₱{{ number_format($totalInvoiced, 2) }}</td>
                    <td>₱{{ number_format($totalPaid, 2) }}</td>
                    <td>₱{{ number_format($closingBalance, 2) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="footer">
        <p>Generated on {{ $generatedAt }} &nbsp;|&nbsp; Tri-E Enterprises &mdash; Statement of Account</p>
    </div>

</body>
</html>
