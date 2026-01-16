<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $type === 'delivery' ? 'Delivery' : 'Pick Up' }} Receipt - #{{ $sale->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: 8.5in 5.5in;
            margin: 0.3in;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 9px;
            line-height: 1.3;
            color: #333;
            background: #fff;
        }

        .container {
            max-width: 100%;
            margin: 0;
            padding: 10px;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #3b82f6;
        }

        .company-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .company-info h1 {
            font-size: 14px;
            color: #1e40af;
            margin-bottom: 2px;
        }

        .company-info p {
            color: #666;
            font-size: 7px;
            line-height: 1.2;
        }

        .receipt-title {
            text-align: right;
        }

        .receipt-title h2 {
            font-size: 13px;
            color: #1e40af;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
        }

        .receipt-title .type-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 7px;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 4px;
        }

        .type-delivery {
            background: #dbeafe;
            color: #1e40af;
        }

        .type-pickup {
            background: #d1fae5;
            color: #065f46;
        }

        .receipt-title .number {
            font-size: 9px;
            color: #666;
            margin-top: 2px;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 10px;
        }

        .info-box {
            padding: 6px;
            background: #f8fafc;
            border-radius: 4px;
        }

        .info-box h3 {
            font-size: 7px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .info-box p {
            margin-bottom: 2px;
            font-size: 8px;
        }

        .info-box .label {
            color: #64748b;
            font-size: 7px;
        }

        .info-box .value {
            color: #1e293b;
            font-weight: 500;
            font-size: 8px;
        }

        /* Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .items-table thead {
            background: #1e40af;
            color: white;
        }

        .items-table th {
            padding: 4px 6px;
            text-align: left;
            font-weight: 600;
            font-size: 7px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .items-table th:last-child,
        .items-table td:last-child {
            text-align: right;
        }

        .items-table th:nth-child(2),
        .items-table td:nth-child(2),
        .items-table th:nth-child(3),
        .items-table td:nth-child(3) {
            text-align: right;
        }

        .items-table tbody tr {
            border-bottom: 1px solid #e2e8f0;
        }

        .items-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .items-table td {
            padding: 4px 6px;
            font-size: 8px;
        }

        .items-table .product-name {
            font-weight: 500;
            color: #1e293b;
        }

        /* Summary */
        .summary {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 10px;
        }

        .summary-box {
            width: 140px;
            background: #f8fafc;
            border-radius: 4px;
            padding: 6px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            border-bottom: 1px solid #e2e8f0;
            font-size: 8px;
        }

        .summary-row:last-child {
            border-bottom: none;
            padding-top: 4px;
            margin-top: 2px;
            border-top: 1px solid #1e40af;
        }

        .summary-row .label {
            color: #64748b;
        }

        .summary-row .value {
            font-weight: 500;
            color: #1e293b;
        }

        .summary-row.total .label,
        .summary-row.total .value {
            font-size: 10px;
            font-weight: 700;
            color: #1e40af;
        }

        /* Received note */
        .received-note {
            margin-top: 10px;
            font-style: italic;
            font-size: 7px;
            text-align: center;
            color: #666;
        }

        /* Signatures */
        .signature-section {
            margin-top: 12px;
        }

        .signature-rows {
            display: flex;
            gap: 8px;
        }

        .signature {
            flex: 1;
            text-align: center;
            font-size: 7px;
        }

        .signature .line {
            border-top: 1px solid #333;
            margin-top: 20px;
            padding-top: 2px;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 6px;
            margin-top: 10px;
        }

        .footer p {
            margin-bottom: 2px;
        }

        /* Print Styles */
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

        /* Print Button */
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

    <div class="container">
        <!-- Header -->
        <div class="header" style="display: flex; justify-content: space-between; align-items: center;">
            <div class="company-info" style="display: flex; align-items: center; gap: 8px;">
                <img src="{{ asset('images/logo.png') }}" alt="Company Logo" style="max-height: 30px;">
                <div>
                    <h1 style="margin: 0;">Tri-E Enterprises</h1>
                    <p>Your Trusted Business Partner</p>
                    <p style="margin-top: 3px;">Maharlika Highway,Cabidan Sorsogon City</p>
                    <p>Phone: (+639) 993-052-2540</p>
                </div>
            </div>
            <div class="receipt-title" style="text-align: right;">
                <h2 style="margin: 0;">{{ $type === 'delivery' ? 'Delivery' : 'Pick Up' }} Receipt</h2>
                <p class="number">RECEIPT-{{ str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}</p>
                <span class="type-badge {{ $type === 'delivery' ? 'type-delivery' : 'type-pickup' }}">
                    {{ $type === 'delivery' ? 'For Delivery' : 'For Pick Up' }}
                </span>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="info-grid">
            <div class="info-box">
                <h3>Customer Information</h3>
                @if($sale->customer)
                    <p><span class="label">Name:</span> <span class="value">{{ $sale->customer->name }}</span></p>
                    <p><span class="label">Phone:</span> <span class="value">{{ $sale->customer->phone ?? 'N/A' }}</span></p>
                    <p><span class="label">Email:</span> <span class="value">{{ $sale->customer->email ?? 'N/A' }}</span></p>
                    <p><span class="label">Address:</span> <span class="value">{{ $sale->customer->address ?? 'N/A' }}</span></p>
                    <p><span class="label">Company:</span> <span class="value">{{ $sale->customer->company ?? 'N/A' }}</span></p>
                @else
                    <p><span class="value">Walk-in Customer</span></p>
                @endif
            </div>
            <div class="info-box">
                <h3>Receipt Details</h3>
                <p><span class="label">Date:</span> <span class="value">{{ $sale->date->format('F d, Y') }}</span></p>
                <p><span class="label">Time:</span> <span class="value">{{ $sale->date->format('h:i A') }}</span></p>
                <p><span class="label">Type:</span> <span class="value">{{ $type === 'delivery' ? 'For Delivery' : 'For Pick Up' }}</span></p>
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%">#</th>
                    <th style="width: 50%">Product</th>
                    <th style="width: 20%">Quantity</th>
                    <th style="width: 25%">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->sale_items as $index => $item)
                    <tr>
                        <td style="text-align: center;">{{ $index + 1 }}</td>
                        <td class="product-name">{{ $item->product?->name ?? 'Unknown Product' }}</td>
                        <td>{{ number_format($item->quantity, 2) }}</td>
                        <td>₱{{ number_format($item->price, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Summary -->
        <div class="summary">
            <div class="summary-box">
                <div class="summary-row">
                    <span class="label">Subtotal</span>
                    <span class="value">₱{{ number_format($sale->total, 2) }}</span>
                </div>
                <div class="summary-row">
                    <span class="label">Tax (0%)</span>
                    <span class="value">₱0.00</span>
                </div>
                <div class="summary-row total">
                    <span class="label">Total</span>
                    <span class="value">₱{{ number_format($sale->total, 2) }}</span>
                </div>
            </div>
        </div>

        <div class="received-note">
            @if($type === 'delivery')
                Received the above articles in good order and condition for delivery
            @else
                Customer will pick up the above articles
            @endif
        </div>

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-rows">
                <div class="signature">
                    <div class="line"></div>
                    <div>Prepared By</div>
                </div>
                <div class="signature">
                    <div class="line"></div>
                    <div>Checker</div>
                </div>
                <div class="signature">
                    <div class="line"></div>
                    <div>Approved By</div>
                </div>
                @if($type === 'delivery')
                <div class="signature">
                    <div class="line"></div>
                    <div>Driver/Truck #</div>
                </div>
                <div class="signature">
                    <div class="line"></div>
                    <div>Received By</div>
                </div>
                @else
                <div class="signature">
                    <div class="line"></div>
                    <div>Released By</div>
                </div>
                <div class="signature">
                    <div class="line"></div>
                    <div>Picked Up By</div>
                </div>
                @endif
            </div>
        </div>

        <!-- Footer -->
        <div class="footer" style="margin-top: 8px;">
            <p><strong>Thank you for your business!</strong></p>
            <p style="margin-top: 3px;">For any questions, please contact us at (+639) 993-052-2540</p>
            <p style="margin-top: 3px; font-size: 6px; color: #94a3b8;">
                Generated on {{ now()->format('F d, Y h:i A') }}
            </p>
        </div>
    </div>

    <script>
        // Auto-print when page loads (optional - uncomment if desired)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
