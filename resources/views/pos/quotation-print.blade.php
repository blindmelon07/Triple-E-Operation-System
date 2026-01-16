<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation - {{ $quotation->quotation_number }}</title>
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

        .quotation-title {
            text-align: right;
        }

        .quotation-title h2 {
            font-size: 13px;
            color: #1e40af;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
        }

        .quotation-title .number {
            font-size: 9px;
            color: #666;
            margin-top: 2px;
        }

        .quotation-title .status {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 7px;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 4px;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-accepted {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
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

        .items-table th:nth-child(3),
        .items-table td:nth-child(3),
        .items-table th:nth-child(4),
        .items-table td:nth-child(4) {
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
        
        /* Notes */
        .notes {
            background: #fffbeb;
            border-left: 2px solid #f59e0b;
            padding: 6px;
            margin-bottom: 10px;
            border-radius: 0 4px 4px 0;
        }

        .notes h3 {
            font-size: 7px;
            color: #92400e;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .notes p {
            color: #78350f;
            font-size: 7px;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 6px;
        }

        .footer p {
            margin-bottom: 2px;
        }

        /* Validity Notice */
        .validity-notice {
            background: #dbeafe;
            border-radius: 4px;
            padding: 6px;
            text-align: center;
            margin-bottom: 10px;
        }

        .validity-notice p {
            color: #1e40af;
            font-weight: 500;
            font-size: 7px;
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
            <div class="quotation-title" style="text-align: right;">
                <h2 style="margin: 0;">Quotation</h2>
                <p class="number">{{ $quotation->quotation_number }}</p>
                <span class="status status-{{ $quotation->status }}">{{ ucfirst($quotation->status) }}</span>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="info-grid">
            <div class="info-box">
                <h3>Customer Information</h3>
                @if($quotation->customer)
                    <p><span class="label">Name:</span> <span class="value">{{ $quotation->customer->name }}</span></p>
                    <p><span class="label">Phone:</span> <span class="value">{{ $quotation->customer->phone ?? 'N/A' }}</span></p>
                    <p><span class="label">Email:</span> <span class="value">{{ $quotation->customer->email ?? 'N/A' }}</span></p>
                    <p><span class="label">Address:</span> <span class="value">{{ $quotation->customer->address ?? 'N/A' }}</span></p>
                    <p><span class="label">Company:</span> <span class="value">{{ $quotation->customer->company ?? 'N/A' }}</span></p>
                @else
                    <p><span class="value">Walk-in Customer</span></p>
                @endif
            </div>
            <div class="info-box">
                <h3>Quotation Details</h3>
                <p><span class="label">Date:</span> <span class="value">{{ $quotation->date->format('F d, Y') }}</span></p>
                @if($quotation->valid_until)
                    <p><span class="label">Valid Until:</span> <span class="value">{{ $quotation->valid_until->format('F d, Y') }}</span></p>
                @endif
                <p><span class="label">Status:</span> <span class="value">{{ ucfirst($quotation->status) }}</span></p>
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%">#</th>
                    <th style="width: 35%">Product</th>
                    <th style="width: 15%">Quantity</th>
                    <th style="width: 20%">Unit Price</th>
                    <th style="width: 25%">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quotation->quotation_items as $index => $item)
                    <tr>
                        <td style="text-align: center;">{{ $index + 1 }}</td>
                        <td class="product-name">{{ $item->product_description ?? $item->product?->name }}</td>
                        <td>{{ number_format($item->quantity, $item->unit === 'piece' ? 0 : 2) }} {{ $item->unit }}</td>
                        <td>₱{{ number_format($item->unit_price, 2) }}</td>
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
                    <span class="value">₱{{ number_format($quotation->total, 2) }}</span>
                </div>
                <div class="summary-row">
                    <span class="label">Tax (0%)</span>
                    <span class="value">₱0.00</span>
                </div>
                <div class="summary-row total">
                    <span class="label">Total</span>
                    <span class="value">₱{{ number_format($quotation->total, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Notes -->
        @if($quotation->notes)
            <div class="notes">
                <h3>Notes</h3>
                <p>{{ $quotation->notes }}</p>
            </div>
        @endif

        <!-- Signatures -->
        <div style="margin-top: 20px; display: table; width: 100%;">
            <div style="display: table-cell; width: 33.33%; text-align: center; padding: 0 5px;">
                <div style="border-top: 1px solid #333; padding-top: 2px; margin-top: 20px; font-weight: bold; font-size: 7px;">
                    Prepared By
                </div>
            </div>
            <div style="display: table-cell; width: 33.33%; text-align: center; padding: 0 5px;">
                <div style="border-top: 1px solid #333; padding-top: 2px; margin-top: 20px; font-weight: bold; font-size: 7px;">
                    Approved By
                </div>
            </div>
            <div style="display: table-cell; width: 33.33%; text-align: center; padding: 0 5px;">
                <div style="border-top: 1px solid #333; padding-top: 2px; margin-top: 20px; font-weight: bold; font-size: 7px;">
                    Received By
                </div>
            </div>
        </div>

        <!-- Validity Notice -->
        @if($quotation->valid_until)
            <div style="text-align: center; margin-top: 10px; padding: 4px; background: #dbeafe; border-radius: 3px;">
                <p style="color: #1e40af; font-weight: 500; font-size: 7px;">
                    This quotation is valid until <strong>{{ $quotation->valid_until->format('F d, Y') }}</strong>
                </p>
            </div>
        @endif

        <!-- Footer -->
        <div class="footer" style="margin-top: 8px;">
            <p><strong>Prices are subject to change without prior notice.</strong></p>
            <p style="margin-top: 3px;">Thank you for considering our quotation!</p>
            <p>For any questions, please contact us at (+639) 993-052-2540</p>
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
