<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Period Transaction Report – {{ $periodLabel }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 8px;
            color: #111;
            background: #fff;
            padding: 10px 12px;
        }

        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }

        .page-header {
            text-align: center;
            border-bottom: 2px double #000;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }
        .page-header .title    { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; text-decoration: underline; }
        .page-header .date-line { font-size: 10px; font-weight: 700; margin-top: 2px; }
        .page-header .sub-line  { font-size: 8px; margin-top: 1px; }

        .layout { width: 100%; border-collapse: collapse; vertical-align: top; }
        .layout > tbody > tr > td { vertical-align: top; padding: 0 5px; border-right: 1px solid #ccc; }
        .layout > tbody > tr > td:first-child { padding-left: 0; }
        .layout > tbody > tr > td:last-child  { border-right: none; padding-right: 0; }

        .col-header { font-size: 9px; font-weight: 700; text-align: center; text-decoration: underline; font-style: italic; margin-bottom: 5px; letter-spacing: 0.3px; }

        .cust-name { font-size: 8px; font-weight: 700; border-bottom: 1px solid #999; padding-bottom: 1px; margin-bottom: 2px; }
        .cust-ref  { font-size: 7px; color: #555; margin-bottom: 1px; }

        .item-tbl { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
        .item-tbl td { font-size: 7.5px; padding: 1px 1px; vertical-align: top; }
        .item-tbl .qty   { width: 13%; text-align: right; }
        .item-tbl .unit  { width: 12%; padding-left: 2px; }
        .item-tbl .desc  { width: 47%; }
        .item-tbl .price { width: 28%; text-align: right; }
        .item-tbl .subtotal td { border-top: 1px solid #555; font-weight: 700; }

        .sec-total { border-top: 2px solid #000; margin-top: 4px; padding-top: 2px; }
        .sec-total table { width: 100%; border-collapse: collapse; }
        .sec-total td { font-size: 8px; padding: 1px 2px; }
        .sec-total .lbl { font-weight: 700; }
        .sec-total .amt { text-align: right; font-weight: 700; }
        .sec-total .unpaid { color: #cc0000; }

        .exp-row { width: 100%; border-collapse: collapse; margin-bottom: 1px; }
        .exp-row td { font-size: 7.5px; padding: 1px 1px; vertical-align: top; }
        .exp-row .e-desc { width: 68%; }
        .exp-row .e-amt  { width: 32%; text-align: right; }

        .sum-tbl { width: 100%; border-collapse: collapse; }
        .sum-tbl td { font-size: 8px; padding: 2px 2px; vertical-align: top; }
        .sum-tbl .sl  { width: 62%; }
        .sum-tbl .sv  { width: 38%; text-align: right; font-weight: 700; }
        .sum-tbl .total-row td { border-top: 2px solid #000; border-bottom: 2px solid #000; font-weight: 700; font-size: 8.5px; }
        .sum-tbl .grand-row td { border-top: 2px solid #000; border-bottom: 3px double #000; font-weight: 700; font-size: 9px; }
        .sum-tbl .spacer td   { height: 4px; }
        .sum-tbl .short td { color: #cc0000; }
        .sum-tbl .over  td { color: #006600; }

        .xcheck-tbl { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .xcheck-tbl td { font-size: 7.5px; padding: 1px 2px; }
        .xcheck-tbl .xl { width: 58%; }
        .xcheck-tbl .xr { width: 42%; text-align: right; }
        .xcheck-tbl .xh td { font-weight: 700; text-decoration: underline; font-size: 7.5px; }
        .xcheck-tbl .xt td { border-top: 1px solid #000; font-weight: 700; }

        .bd-title { font-size: 8px; font-weight: 700; text-decoration: underline; margin-top: 6px; margin-bottom: 2px; }
        .bd-tbl   { width: 100%; border-collapse: collapse; }
        .bd-tbl td { font-size: 7.5px; padding: 1px 2px; }
        .bd-tbl .d-denom { width: 28%; text-align: right; }
        .bd-tbl .d-x     { width: 8%;  text-align: center; color: #555; }
        .bd-tbl .d-cnt   { width: 18%; text-align: center; }
        .bd-tbl .d-tot   { width: 46%; text-align: right; font-weight: 700; }
        .bd-tbl .bd-sum td { border-top: 1px solid #000; font-weight: 700; }

        .sess-tbl { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .sess-tbl th { font-size: 7.5px; padding: 2px 3px; background: #222; color: #fff; text-align: left; }
        .sess-tbl td { font-size: 7.5px; padding: 2px 3px; border-bottom: 1px solid #ddd; }
        .sess-tbl tr:nth-child(even) td { background: #f5f5f5; }
        .sess-tbl .r { text-align: right; }
        .sess-tbl .sum-row td { border-top: 2px solid #000; font-weight: 700; background: #eee; }

        .div-line { border-top: 1px solid #aaa; margin: 3px 0; }

        .page-footer { border-top: 1px solid #aaa; margin-top: 8px; padding-top: 3px; text-align: center; font-size: 7px; color: #555; }
    </style>
</head>
<body>

{{-- ===== ONE PAGE PER SESSION ===== --}}
@foreach($dayReports as $day)
@php
    $dSession          = $day['session'];
    $dSales            = $day['sales'];
    $dExpenses         = $day['expenses'];
    $dTotalSales       = $day['totalSales'];
    $dTotalUnpaid      = $day['totalUnpaidSales'];
    $dTotalPaid        = $day['totalPaidSales'];
    $dNonCash          = $day['nonCashPaidSales'];
    $dTotalExpenses    = $day['totalExpenses'];
    $dPettyCash        = $day['pettyCash'];
    $dIncomeTotal      = $day['incomeTotal'];
    $dTotalDeductions  = $day['totalDeductions'];
    $dActualCash       = $day['actualCashOnHand'];
    $dDiscrepancy      = $day['discrepancy'];
    $dNamedSales       = $dSales->filter(fn($s) => $s->customer_id !== null)->values();
    $dWalkinSales      = $dSales->filter(fn($s) => $s->customer_id === null)->values();
    $dGcash   = (float)$dSales->where('payment_status','!=','unpaid')->where('payment_method','gcash')->sum('total');
    $dCard    = (float)$dSales->where('payment_status','!=','unpaid')->where('payment_method','card')->sum('total');
    $dPaymaya = (float)$dSales->where('payment_status','!=','unpaid')->where('payment_method','paymaya')->sum('total');
    $dCod     = (float)$dSales->where('payment_status','!=','unpaid')->where('payment_method','cod')->sum('total');
@endphp

<div class="page">

{{-- HEADER --}}
<div class="page-header">
    <div class="title">Tri-E Ent. OPC Daily Transaction Report</div>
    <div class="date-line">{{ $dSession->opened_at?->format('l, F d, Y') }}</div>
    <div class="sub-line">
        PETTY CASH &#8369;{{ number_format($dPettyCash, 2) }}
        &nbsp;|&nbsp; Cashier: {{ $dSession->user?->name ?? 'N/A' }}
        &nbsp;|&nbsp; Session #{{ $dSession->id }}
        &nbsp;|&nbsp; {{ $dSession->opened_at?->format('h:i A') }} – {{ $dSession->closed_at?->format('h:i A') ?? 'Open' }}
        &nbsp;|&nbsp; Period: {{ $periodLabel }}
    </div>
</div>

{{-- 4-COLUMN LAYOUT --}}
<table class="layout">
<tbody>
<tr>

    {{-- COL 1: NAMED CUSTOMER SALES (30%) --}}
    <td style="width:30%;">
        <div class="col-header">Hardware Sales</div>

        @forelse($dNamedSales as $sale)
            <div class="cust-name">
                {{ $sale->customer?->name }}
                @if($sale->customer?->address)
                    <span style="font-weight:400;font-size:6.5px;"> – {{ $sale->customer->address }}</span>
                @endif
            </div>
            @if($sale->reference_number)
                <div class="cust-ref">Ref: {{ $sale->reference_number }}</div>
            @endif
            <table class="item-tbl">
                @foreach($sale->sale_items as $item)
                    <tr>
                        <td class="qty">{{ rtrim(rtrim(number_format((float)$item->quantity,2),'0'),'.') }}</td>
                        <td class="unit">{{ $item->unit }}</td>
                        <td class="desc">{{ $item->is_manual ? $item->product_description : ($item->product?->name ?? $item->product_description) }}</td>
                        <td class="price">
                            @if((float)$item->quantity != 1){{ number_format($item->unit_price,2) }}&nbsp;@endif
                            <u>{{ number_format($item->price,2) }}</u>
                        </td>
                    </tr>
                @endforeach
                <tr class="subtotal">
                    <td colspan="3">&nbsp;</td>
                    <td class="price">
                        {{ number_format($sale->total,2) }}
                        @if($sale->payment_status === 'unpaid')
                            <br><span style="color:#cc0000;font-size:6px;">(UNPAID)</span>
                        @endif
                    </td>
                </tr>
            </table>
        @empty
            <p style="font-size:7.5px;color:#888;font-style:italic;">No named customer sales.</p>
        @endforelse

        <div class="sec-total">
            <table>
                <tr><td class="lbl">TOTAL HARDWARE SALES</td><td class="amt">{{ number_format($dTotalSales,2) }}</td></tr>
                @if($dTotalUnpaid > 0)
                <tr><td class="lbl unpaid">TOTAL UNPAID</td><td class="amt unpaid">{{ number_format($dTotalUnpaid,2) }}</td></tr>
                @endif
            </table>
        </div>
    </td>

    {{-- COL 2: WALK-IN SALES (23%) --}}
    <td style="width:23%;">
        <div class="col-header">Hardware Sales</div>

        @forelse($dWalkinSales as $sale)
            <div class="cust-name" style="font-style:italic;">
                Walk-in
                @if($sale->reference_number)
                    <span style="font-weight:400;font-size:6.5px;">&nbsp;#{{ $sale->reference_number }}</span>
                @endif
            </div>
            <table class="item-tbl">
                @foreach($sale->sale_items as $item)
                    <tr>
                        <td class="qty">{{ rtrim(rtrim(number_format((float)$item->quantity,2),'0'),'.') }}</td>
                        <td class="unit">{{ $item->unit }}</td>
                        <td class="desc">{{ $item->is_manual ? $item->product_description : ($item->product?->name ?? $item->product_description) }}</td>
                        <td class="price">
                            @if((float)$item->quantity != 1){{ number_format($item->unit_price,2) }}&nbsp;@endif
                            <u>{{ number_format($item->price,2) }}</u>
                        </td>
                    </tr>
                @endforeach
                <tr class="subtotal">
                    <td colspan="3">&nbsp;</td>
                    <td class="price">{{ number_format($sale->total,2) }}</td>
                </tr>
            </table>
        @empty
            <p style="font-size:7.5px;color:#888;font-style:italic;">No walk-in sales.</p>
        @endforelse

        <table class="xcheck-tbl" style="margin-top:8px;">
            <tr class="xh"><td colspan="2">ON EXCEL (MANUAL)</td></tr>
            <tr><td class="xl">TOTAL SALES</td><td class="xr">{{ number_format($dTotalSales,2) }}</td></tr>
            <tr><td class="xl">STARTING CASH AMOUNT</td><td class="xr">{{ number_format($dPettyCash,2) }}</td></tr>
            <tr class="xt"><td class="xl">TOTAL:</td><td class="xr">{{ number_format($dTotalSales+$dPettyCash,2) }}</td></tr>
            <tr style="height:4px;"><td colspan="2"></td></tr>
            <tr class="xh"><td colspan="2">POS SALES</td></tr>
            <tr><td class="xl">TOTAL SALES</td><td class="xr">{{ number_format($dTotalPaid,2) }}</td></tr>
            <tr><td class="xl">STARTING CASH AMOUNT</td><td class="xr">{{ number_format($dPettyCash,2) }}</td></tr>
            <tr class="xt"><td class="xl">TOTAL:</td><td class="xr">{{ number_format($dTotalPaid+$dPettyCash,2) }}</td></tr>
            <tr style="height:3px;"><td colspan="2"></td></tr>
            <tr><td class="xl" style="font-weight:700;">OVERALL – TALLY !!</td><td class="xr"></td></tr>
        </table>
    </td>

    {{-- COL 3: EXPENSES (20%) --}}
    <td style="width:20%;">
        <div class="col-header">Expenses</div>

        @forelse($dExpenses as $expense)
            <table class="exp-row">
                <tr>
                    <td class="e-desc">
                        {{ $expense->description ?: $expense->category?->name }}
                        @if($expense->payee) – {{ $expense->payee }} @endif
                    </td>
                    <td class="e-amt">{{ number_format($expense->amount,2) }}</td>
                </tr>
            </table>
        @empty
            <p style="font-size:7.5px;color:#888;font-style:italic;">No expenses.</p>
        @endforelse

        <div class="div-line"></div>
        <table style="width:100%;border-collapse:collapse;">
            <tr>
                <td style="font-weight:700;font-size:8px;">TOTAL EXPENSES:</td>
                <td style="text-align:right;font-weight:700;font-size:8px;">{{ number_format($dTotalExpenses,2) }}</td>
            </tr>
        </table>

        @if($dNonCash > 0)
        <div style="margin-top:8px;">
            <div style="font-weight:700;font-size:7.5px;text-decoration:underline;margin-bottom:2px;">C/A</div>
            @if($dGcash > 0)<table class="exp-row"><tr><td class="e-desc">GCash</td><td class="e-amt">{{ number_format($dGcash,2) }}</td></tr></table>@endif
            @if($dCard > 0)<table class="exp-row"><tr><td class="e-desc">Card</td><td class="e-amt">{{ number_format($dCard,2) }}</td></tr></table>@endif
            @if($dPaymaya > 0)<table class="exp-row"><tr><td class="e-desc">PayMaya</td><td class="e-amt">{{ number_format($dPaymaya,2) }}</td></tr></table>@endif
            @if($dCod > 0)<table class="exp-row"><tr><td class="e-desc">COD</td><td class="e-amt">{{ number_format($dCod,2) }}</td></tr></table>@endif
            <div class="div-line"></div>
            <table style="width:100%;border-collapse:collapse;">
                <tr>
                    <td style="font-weight:700;font-size:8px;">TOTAL:</td>
                    <td style="text-align:right;font-weight:700;font-size:8px;">{{ number_format($dNonCash,2) }}</td>
                </tr>
            </table>
        </div>
        @endif
    </td>

    {{-- COL 4: SUMMARY TRANSACTION (27%) --}}
    <td style="width:27%;">
        <div class="col-header">Summary Transaction</div>

        <table class="sum-tbl">
            <tr><td class="sl">PETTY CASH</td><td class="sv">{{ number_format($dPettyCash,2) }}</td></tr>
            <tr><td class="sl">H.W SALES:</td><td class="sv">{{ number_format($dTotalSales,2) }}</td></tr>
            <tr><td class="sl">ROOFING SALES:</td><td class="sv">&nbsp;</td></tr>
            <tr><td class="sl">PREVIOUS:</td><td class="sv">&nbsp;</td></tr>
            <tr><td class="sl">OTHER PAYMENT:</td><td class="sv">&nbsp;</td></tr>
            <tr class="total-row"><td class="sl">TOTAL</td><td class="sv">{{ number_format($dIncomeTotal,2) }}</td></tr>

            <tr class="spacer"><td colspan="2"></td></tr>

            <tr><td class="sl">UNPAID ROOFING SALES:</td><td class="sv">&nbsp;</td></tr>
            <tr>
                <td class="sl" style="color:#cc0000;">UNPAID H-WARE SALES:</td>
                <td class="sv" style="color:#cc0000;">{{ $dTotalUnpaid > 0 ? number_format($dTotalUnpaid,2) : '–' }}</td>
            </tr>
            <tr><td class="sl">DEDUCT PAYMENT TO BOSS</td><td class="sv">&nbsp;</td></tr>
            <tr>
                <td class="sl">CHECK / G-CASH/BANK<br>TRANSFER:</td>
                <td class="sv">{{ $dNonCash > 0 ? number_format($dNonCash,2) : '–' }}</td>
            </tr>
            <tr><td class="sl">EXPENSES:</td><td class="sv">{{ $dTotalExpenses > 0 ? number_format($dTotalExpenses,2) : '–' }}</td></tr>
            <tr><td class="sl">REMITTED/DEPOSIT:</td><td class="sv">&nbsp;</td></tr>
            <tr class="total-row"><td class="sl">TOTAL</td><td class="sv">{{ number_format($dTotalDeductions + $dTotalExpenses,2) }}</td></tr>

            <tr class="spacer"><td colspan="2"></td></tr>

            @if(abs($dDiscrepancy) > 0.005)
            <tr class="{{ $dDiscrepancy < 0 ? 'short' : 'over' }}">
                <td class="sl">{{ $dDiscrepancy < 0 ? 'SHORT' : 'OVER' }}</td>
                <td class="sv">{{ number_format(abs($dDiscrepancy),2) }}</td>
            </tr>
            @else
            <tr><td class="sl">SHORT &nbsp;&nbsp; OVER</td><td class="sv">–</td></tr>
            @endif

            <tr class="grand-row"><td class="sl">TOTAL CASH on HAND</td><td class="sv">{{ number_format($dActualCash,2) }}</td></tr>
        </table>

        <div class="bd-title">CASH BREAKDOWN</div>
        <table class="bd-tbl">
            @foreach([1000,500,200,100,50,20,10,5,1] as $denom)
            <tr>
                <td class="d-denom">{{ number_format($denom,2) }}</td>
                <td class="d-x">×</td>
                <td class="d-cnt">&nbsp;</td>
                <td class="d-tot">&nbsp;</td>
            </tr>
            @endforeach
            <tr class="bd-sum">
                <td colspan="2" style="font-weight:700;font-size:8px;">REMITTED:</td>
                <td colspan="2">&nbsp;</td>
            </tr>
            <tr class="bd-sum">
                <td colspan="2" style="font-weight:700;font-size:8px;">TOTAL CASH on HAND</td>
                <td colspan="2" style="text-align:right;font-weight:700;font-size:8px;">{{ number_format($dActualCash,2) }}</td>
            </tr>
        </table>
    </td>

</tr>
</tbody>
</table>

<div class="page-footer">
    Generated on {{ now()->setTimezone('Asia/Manila')->format('F d, Y h:i A') }}
    &nbsp;|&nbsp; Period: {{ $periodLabel }}
    &nbsp;|&nbsp; Session #{{ $dSession->id }}
</div>

</div>{{-- end .page --}}
@endforeach

{{-- ===== FINAL SUMMARY PAGE ===== --}}
@php
    $pGcash   = (float)collect($dayReports)->sum(fn($d) => $d['sales']->where('payment_status','!=','unpaid')->where('payment_method','gcash')->sum('total'));
    $pCard    = (float)collect($dayReports)->sum(fn($d) => $d['sales']->where('payment_status','!=','unpaid')->where('payment_method','card')->sum('total'));
    $pPaymaya = (float)collect($dayReports)->sum(fn($d) => $d['sales']->where('payment_status','!=','unpaid')->where('payment_method','paymaya')->sum('total'));
    $pCod     = (float)collect($dayReports)->sum(fn($d) => $d['sales']->where('payment_status','!=','unpaid')->where('payment_method','cod')->sum('total'));
@endphp

<div class="page">

<div class="page-header">
    <div class="title">Tri-E Ent. OPC Period Summary</div>
    <div class="date-line">{{ $periodLabel }}</div>
    <div class="sub-line">
        {{ $sessions->count() }} session(s)
        &nbsp;|&nbsp; Total Sales: &#8369;{{ number_format($totalSales,2) }}
        &nbsp;|&nbsp; Total Expenses: &#8369;{{ number_format($totalExpenses,2) }}
    </div>
</div>

<table class="layout">
<tbody>
<tr>

    {{-- COL 1+2: SESSIONS SUMMARY (53%) --}}
    <td style="width:53%;">
        <div class="col-header">Sessions Summary</div>
        <table class="sess-tbl">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Cashier</th>
                    <th class="r">Petty Cash</th>
                    <th class="r">Total Sales</th>
                    <th class="r">Unpaid</th>
                    <th class="r">Expenses</th>
                    <th class="r">Cash on Hand</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dayReports as $day)
                <tr>
                    <td>{{ $day['session']->opened_at?->format('M d, Y') }}</td>
                    <td>{{ $day['session']->user?->name }}</td>
                    <td class="r">{{ number_format($day['pettyCash'],2) }}</td>
                    <td class="r">{{ number_format($day['totalSales'],2) }}</td>
                    <td class="r" style="{{ $day['totalUnpaidSales'] > 0 ? 'color:#cc0000;' : '' }}">
                        {{ $day['totalUnpaidSales'] > 0 ? number_format($day['totalUnpaidSales'],2) : '–' }}
                    </td>
                    <td class="r">{{ $day['totalExpenses'] > 0 ? number_format($day['totalExpenses'],2) : '–' }}</td>
                    <td class="r">{{ number_format($day['actualCashOnHand'],2) }}</td>
                </tr>
                @endforeach
                <tr class="sum-row">
                    <td colspan="2">PERIOD TOTAL</td>
                    <td class="r">{{ number_format($pettyCash,2) }}</td>
                    <td class="r">{{ number_format($totalSales,2) }}</td>
                    <td class="r" style="{{ $totalUnpaidSales > 0 ? 'color:#cc0000;' : '' }}">
                        {{ $totalUnpaidSales > 0 ? number_format($totalUnpaidSales,2) : '–' }}
                    </td>
                    <td class="r">{{ $totalExpenses > 0 ? number_format($totalExpenses,2) : '–' }}</td>
                    <td class="r">{{ number_format($actualCashOnHand,2) }}</td>
                </tr>
            </tbody>
        </table>
    </td>

    {{-- COL 3: EXPENSES + NON-CASH (20%) --}}
    <td style="width:20%;">
        <div class="col-header">Expenses</div>

        @php $allPeriodExpenses = collect($dayReports)->flatMap(fn($d) => $d['expenses']->all())->sortBy('expense_date'); @endphp

        @forelse($allPeriodExpenses as $expense)
            <table class="exp-row">
                <tr>
                    <td style="width:55%;font-size:7.5px;">
                        {{ $expense->description ?: $expense->category?->name }}
                        @if($expense->payee) – {{ $expense->payee }} @endif
                    </td>
                    <td style="width:18%;font-size:7px;color:#555;">{{ $expense->expense_date?->format('M d') }}</td>
                    <td style="width:27%;text-align:right;font-size:7.5px;">{{ number_format($expense->amount,2) }}</td>
                </tr>
            </table>
        @empty
            <p style="font-size:7.5px;color:#888;font-style:italic;">No expenses.</p>
        @endforelse

        <div class="div-line"></div>
        <table style="width:100%;border-collapse:collapse;">
            <tr>
                <td style="font-weight:700;font-size:8px;">TOTAL EXPENSES:</td>
                <td style="text-align:right;font-weight:700;font-size:8px;">{{ number_format($totalExpenses,2) }}</td>
            </tr>
        </table>

        @if($nonCashPaidSales > 0)
        <div style="margin-top:8px;">
            <div style="font-weight:700;font-size:7.5px;text-decoration:underline;margin-bottom:2px;">C/A (Non-Cash)</div>
            @if($pGcash > 0)<table class="exp-row"><tr><td class="e-desc">GCash</td><td class="e-amt">{{ number_format($pGcash,2) }}</td></tr></table>@endif
            @if($pCard > 0)<table class="exp-row"><tr><td class="e-desc">Card</td><td class="e-amt">{{ number_format($pCard,2) }}</td></tr></table>@endif
            @if($pPaymaya > 0)<table class="exp-row"><tr><td class="e-desc">PayMaya</td><td class="e-amt">{{ number_format($pPaymaya,2) }}</td></tr></table>@endif
            @if($pCod > 0)<table class="exp-row"><tr><td class="e-desc">COD</td><td class="e-amt">{{ number_format($pCod,2) }}</td></tr></table>@endif
            <div class="div-line"></div>
            <table style="width:100%;border-collapse:collapse;">
                <tr>
                    <td style="font-weight:700;font-size:8px;">TOTAL:</td>
                    <td style="text-align:right;font-weight:700;font-size:8px;">{{ number_format($nonCashPaidSales,2) }}</td>
                </tr>
            </table>
        </div>
        @endif
    </td>

    {{-- COL 4: PERIOD SUMMARY TRANSACTION (27%) --}}
    <td style="width:27%;">
        <div class="col-header">Summary Transaction</div>

        <table class="sum-tbl">
            <tr><td class="sl">PETTY CASH</td><td class="sv">{{ number_format($pettyCash,2) }}</td></tr>
            <tr><td class="sl">H.W SALES:</td><td class="sv">{{ number_format($totalSales,2) }}</td></tr>
            <tr><td class="sl">ROOFING SALES:</td><td class="sv">&nbsp;</td></tr>
            <tr><td class="sl">PREVIOUS:</td><td class="sv">&nbsp;</td></tr>
            <tr><td class="sl">OTHER PAYMENT:</td><td class="sv">&nbsp;</td></tr>
            <tr class="total-row"><td class="sl">TOTAL</td><td class="sv">{{ number_format($incomeTotal,2) }}</td></tr>

            <tr class="spacer"><td colspan="2"></td></tr>

            <tr><td class="sl">UNPAID ROOFING SALES:</td><td class="sv">&nbsp;</td></tr>
            <tr>
                <td class="sl" style="color:#cc0000;">UNPAID H-WARE SALES:</td>
                <td class="sv" style="color:#cc0000;">{{ $totalUnpaidSales > 0 ? number_format($totalUnpaidSales,2) : '–' }}</td>
            </tr>
            <tr><td class="sl">DEDUCT PAYMENT TO BOSS</td><td class="sv">&nbsp;</td></tr>
            <tr>
                <td class="sl">CHECK / G-CASH/BANK<br>TRANSFER:</td>
                <td class="sv">{{ $nonCashPaidSales > 0 ? number_format($nonCashPaidSales,2) : '–' }}</td>
            </tr>
            <tr><td class="sl">EXPENSES:</td><td class="sv">{{ $totalExpenses > 0 ? number_format($totalExpenses,2) : '–' }}</td></tr>
            <tr><td class="sl">REMITTED/DEPOSIT:</td><td class="sv">&nbsp;</td></tr>
            <tr class="total-row"><td class="sl">TOTAL</td><td class="sv">{{ number_format($totalDeductions + $totalExpenses,2) }}</td></tr>

            <tr class="spacer"><td colspan="2"></td></tr>

            @if(abs($discrepancy) > 0.005)
            <tr class="{{ $discrepancy < 0 ? 'short' : 'over' }}">
                <td class="sl">{{ $discrepancy < 0 ? 'SHORT' : 'OVER' }}</td>
                <td class="sv">{{ number_format(abs($discrepancy),2) }}</td>
            </tr>
            @else
            <tr><td class="sl">SHORT &nbsp;&nbsp; OVER</td><td class="sv">–</td></tr>
            @endif

            <tr class="grand-row"><td class="sl">TOTAL CASH on HAND</td><td class="sv">{{ number_format($actualCashOnHand,2) }}</td></tr>
        </table>

        <div class="bd-title">CASH BREAKDOWN</div>
        <table class="bd-tbl">
            @foreach([1000,500,200,100,50,20,10,5,1] as $denom)
            <tr>
                <td class="d-denom">{{ number_format($denom,2) }}</td>
                <td class="d-x">×</td>
                <td class="d-cnt">&nbsp;</td>
                <td class="d-tot">&nbsp;</td>
            </tr>
            @endforeach
            <tr class="bd-sum">
                <td colspan="2" style="font-weight:700;font-size:8px;">REMITTED:</td>
                <td colspan="2">&nbsp;</td>
            </tr>
            <tr class="bd-sum">
                <td colspan="2" style="font-weight:700;font-size:8px;">TOTAL CASH on HAND</td>
                <td colspan="2" style="text-align:right;font-weight:700;font-size:8px;">{{ number_format($actualCashOnHand,2) }}</td>
            </tr>
        </table>
    </td>

</tr>
</tbody>
</table>

<div class="page-footer">
    Generated on {{ now()->setTimezone('Asia/Manila')->format('F d, Y h:i A') }}
    &nbsp;|&nbsp; Tri-E Enterprises OPC – Period Report: {{ $periodLabel }}
</div>

</div>{{-- end summary .page --}}

</body>
</html>
