<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - OR-{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Use A4 landscape so two A6 receipts fit side-by-side */
        @page {
            size: 297mm 210mm; /* width x height -> landscape */
            margin: 6mm 6mm; /* small margins to maximize printable area */
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 7px;
            line-height: 1.2;
            color: #333;
            background: #fff;
        }

        .container {
            width: 297mm;
            max-width: 100%;
            margin: 0 auto;
            padding: 0;
            display: flex;
            flex-direction: row;
            flex-wrap: nowrap;
            gap: 0;
            align-items: flex-start;
            justify-content: flex-start;
        }

        .receipt-copy {
            padding: 6mm;
            position: relative;
            overflow: hidden;
            width: 105mm;
            height: 148mm;
            box-sizing: border-box;
            background: #fff;
            page-break-inside: avoid;
            flex: 0 0 105mm;
        }

        .watermark {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            align-content: flex-start;
            overflow: hidden;
        }

        .watermark span {
            display: block;
            width: 160%;
            text-align: center;
            font-size: 12px;
            font-weight: 900;
            color: rgba(30, 64, 175, 0.04);
            letter-spacing: 1.5px;
            text-transform: uppercase;
            white-space: nowrap;
            transform: rotate(-30deg);
            transform-origin: center center;
            margin: 6px 0;
            user-select: none;
        }

        .receipt-copy > *:not(.watermark) {
            position: relative;
            z-index: 1;
        }

        @media print {
            .watermark span {
                color: rgba(30, 64, 175, 0.07);
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        .copy-label {
            text-align: center;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #334155;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }

        .cut-line {
            width: 8mm;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            align-self: flex-start;
            position: relative;
            flex: 0 0 8mm;
        }

        .cut-line .divider {
            width: 1px;
            height: 148mm;
            border-left: 1px dashed #94a3b8;
        }

        .cut-line .label {
            position: absolute;
            top: 50%;
            left: 10px;
            transform: translateY(-50%) rotate(90deg);
            color: #94a3b8;
            font-size: 9px;
            letter-spacing: 1px;
            white-space: nowrap;
            background: #fff;
            padding: 2px 4px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
            padding-bottom: 4px;
            border-bottom: 1px solid #3b82f6;
        }

        .company-info {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .company-info h1 {
            font-size: 10px;
            color: #1e40af;
            margin-bottom: 1px;
        }

        .company-info p {
            color: #666;
            font-size: 5.5px;
            line-height: 1.2;
        }

        .receipt-title {
            text-align: right;
        }

        .receipt-title h2 {
            font-size: 9px;
            color: #1e40af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }

        .receipt-title .type-badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 8px;
            font-size: 5.5px;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 2px;
            background: #d1fae5;
            color: #065f46;
        }

        .receipt-title .number {
            font-size: 7px;
            color: #666;
            margin-top: 1px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px;
            margin-bottom: 5px;
        }

        .info-box {
            padding: 3px 4px;
            background: #f8fafc;
            border-radius: 3px;
        }

        .info-box h3 {
            font-size: 5.5px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 2px;
        }

        .info-box p {
            margin-bottom: 1px;
            font-size: 6px;
        }

        .info-box .label {
            color: #64748b;
            font-size: 5.5px;
        }

        .info-box .value {
            color: #1e293b;
            font-weight: 500;
            font-size: 6px;
        }

        .summary {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 5px;
        }

        .summary-box {
            width: 140px;
            background: #f8fafc;
            border-radius: 3px;
            padding: 3px 4px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
            border-bottom: 1px solid #e2e8f0;
            font-size: 6px;
        }

        .summary-row:last-child {
            border-bottom: none;
            padding-top: 2px;
            margin-top: 1px;
            border-top: 1px solid #1e40af;
        }

        .summary-row .label {
            color: #64748b;
        }

        .summary-row .value {
            font-weight: 500;
            color: #1e293b;
        }

        .summary-row.paid-today .label,
        .summary-row.paid-today .value {
            font-size: 7.5px;
            font-weight: 700;
            color: #1e40af;
        }

        .summary-row.total .label,
        .summary-row.total .value {
            font-size: 7.5px;
            font-weight: 700;
        }

        .summary-row.total.settled .value { color: #059669; }
        .summary-row.total.remaining .value { color: #dc2626; }

        .received-note {
            margin-top: 5px;
            font-style: italic;
            font-size: 5.5px;
            text-align: center;
            color: #666;
        }

        .signature-section {
            margin-top: 8px;
        }

        .signature-rows {
            display: flex;
            gap: 4px;
        }

        .signature {
            flex: 1;
            text-align: center;
            font-size: 5.5px;
        }

        .signature .line {
            border-top: 1px solid #333;
            margin-top: 14px;
            padding-top: 1px;
        }

        .footer {
            text-align: center;
            padding-top: 4px;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 5px;
            margin-top: 5px;
        }

        .footer p {
            margin-bottom: 1px;
        }

        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .container {
                padding: 0;
            }

            .no-print {
                display: none !important;
            }
        }

        .print-actions {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 1000;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #475569;
        }

        .btn-secondary:hover {
            background: #cbd5e1;
        }

        .btn svg {
            width: 18px;
            height: 18px;
        }
    </style>
</head>
<body>
    <!-- Print Actions -->
    <div class="print-actions no-print">
        <button class="btn btn-primary" onclick="window.print()">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Print
        </button>
        <button class="btn btn-secondary" onclick="window.close()">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            Close
        </button>
    </div>

    @php
        $sale = $payment->sale;
        $previousBalance = (float) $sale->total - ((float) $sale->amount_paid - (float) $payment->amount);
        $isFullySettled = (float) $payment->balance_after <= 0.001;
    @endphp

    <div class="container">
        @for ($copy = 0; $copy < 2; $copy++)
            <div class="receipt-copy">
                <div class="watermark">
                    @for($i = 0; $i < 6; $i++)
                        <span>Tri-E Enterprises</span>
                    @endfor
                </div>
                <div class="copy-label">{{ $copy === 0 ? 'Office Copy' : "Customer's Copy" }}</div>

                <!-- Header -->
                <div class="header">
                    <div class="company-info">
                        <img src="{{ asset('images/logo.png') }}" alt="Company Logo" style="max-height: 22px;">
                        <div>
                            <h1 style="margin: 0;">Tri-E Enterprises</h1>
                            <p>Your Trusted Business Partner</p>
                            <p style="margin-top: 3px;">Maharlika Highway,Cabidan Sorsogon City</p>
                            <p>Phone: (+639) 993-052-2540</p>
                        </div>
                    </div>
                    <div class="receipt-title">
                        <h2 style="margin: 0;">Payment Receipt</h2>
                        <p class="number">OR-{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</p>
                        <span class="type-badge">Invoice #{{ $sale->id }}</span>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="info-grid">
                    <div class="info-box">
                        <h3>Customer Information</h3>
                        @if($sale->customer)
                            <p><span class="label">Name:</span> <span class="value">{{ $sale->customer->name }}</span></p>
                            <p><span class="label">Phone:</span> <span class="value">{{ $sale->customer->phone ?? 'N/A' }}</span></p>
                            <p><span class="label">Address:</span> <span class="value">{{ $sale->customer->address ?? 'N/A' }}</span></p>
                        @else
                            <p><span class="value">Walk-in Customer</span></p>
                        @endif
                    </div>
                    <div class="info-box">
                        <h3>Payment Details</h3>
                        <p><span class="label">Invoice Date:</span> <span class="value">{{ $sale->date?->format('F d, Y') }}</span></p>
                        <p><span class="label">Due Date:</span> <span class="value">{{ $sale->due_date?->format('F d, Y') ?? 'N/A' }}</span></p>
                        <p><span class="label">Payment Date:</span> <span class="value">{{ $payment->created_at->format('F d, Y h:i A') }}</span></p>
                        <p><span class="label">Method:</span> <span class="value">{{ match($payment->payment_method) { 'cash' => 'Cash', 'gcash' => 'GCash', 'paymaya' => 'PayMaya', 'card' => 'Card', 'bank' => 'Bank Transfer', 'check' => 'Check', default => ucfirst($payment->payment_method) } }}</span></p>
                        @if($payment->reference_number)
                            <p><span class="label">Reference #:</span> <span class="value">{{ $payment->reference_number }}</span></p>
                        @endif
                        <p><span class="label">Cashier:</span> <span class="value">{{ $payment->recordedBy?->name ?? 'N/A' }}</span></p>
                    </div>
                </div>

                <!-- Payment Summary -->
                <div class="summary">
                    <div class="summary-box">
                        <div class="summary-row">
                            <span class="label">Original Invoice Total</span>
                            <span class="value">₱{{ number_format($sale->total, 2) }}</span>
                        </div>
                        <div class="summary-row">
                            <span class="label">Previous Balance</span>
                            <span class="value">₱{{ number_format($previousBalance, 2) }}</span>
                        </div>
                        <div class="summary-row paid-today">
                            <span class="label">Amount Paid Today</span>
                            <span class="value">₱{{ number_format($payment->amount, 2) }}</span>
                        </div>
                        <div class="summary-row total {{ $isFullySettled ? 'settled' : 'remaining' }}">
                            <span class="label">Remaining Balance</span>
                            <span class="value">₱{{ number_format($payment->balance_after, 2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="received-note">
                    {{ $isFullySettled ? 'Invoice fully settled. Thank you!' : 'Partial payment received. Balance remains outstanding.' }}
                </div>

                <!-- Signature Section -->
                <div class="signature-section">
                    <div class="signature-rows">
                        <div class="signature">
                            <div class="line"></div>
                            <div>Received By (Cashier)</div>
                        </div>
                        <div class="signature">
                            <div class="line"></div>
                            <div>Customer Signature</div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="footer" style="margin-top: 4px;">
                    <p><strong>Thank you for your business!</strong></p>
                    <p style="margin-top: 2px;">For any questions, please contact us at (+639) 993-052-2540</p>
                    <p style="margin-top: 2px; font-size: 5px; color: #94a3b8;">
                        Generated on {{ now()->format('F d, Y h:i A') }}
                    </p>
                </div>
            </div>

            @if($copy === 0)
                {{-- ===== CUT LINE ===== --}}
                <div class="cut-line">
                    <div class="divider"></div>
                    <div class="label">✂ CUT HERE</div>
                </div>
            @endif
        @endfor
    </div>
</body>
</html>
