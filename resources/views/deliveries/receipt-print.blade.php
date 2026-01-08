<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Receipt - #{{ $delivery->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #333;
            background: #fff;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #059669;
        }
        
        .company-info h1 {
            font-size: 28px;
            color: #047857;
            margin-bottom: 5px;
        }
        
        .company-info p {
            color: #666;
            font-size: 12px;
        }
        
        .receipt-title {
            text-align: right;
        }
        
        .receipt-title h2 {
            font-size: 24px;
            color: #047857;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .receipt-title .number {
            font-size: 16px;
            color: #666;
            margin-top: 5px;
        }
        
        .receipt-title .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 8px;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-assigned {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .status-in_transit {
            background: #e0e7ff;
            color: #3730a3;
        }
        
        .status-delivered {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }
        
        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .info-box {
            padding: 15px;
            background: #f0fdf4;
            border-radius: 8px;
            border-left: 4px solid #059669;
        }
        
        .info-box h3 {
            font-size: 12px;
            color: #047857;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        
        .info-box p {
            margin-bottom: 5px;
        }
        
        .info-box .label {
            color: #64748b;
            font-size: 12px;
        }
        
        .info-box .value {
            color: #1e293b;
            font-weight: 500;
        }
        
        /* Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .items-table thead {
            background: #047857;
            color: white;
        }
        
        .items-table th {
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .items-table th:last-child,
        .items-table td:last-child {
            text-align: right;
        }
        
        .items-table th:nth-child(2),
        .items-table td:nth-child(2) {
            text-align: center;
        }
        
        .items-table th:nth-child(3),
        .items-table td:nth-child(3) {
            text-align: right;
        }
        
        .items-table tbody tr {
            border-bottom: 1px solid #e2e8f0;
        }
        
        .items-table tbody tr:nth-child(even) {
            background: #f0fdf4;
        }
        
        .items-table td {
            padding: 12px 15px;
        }
        
        .items-table .product-name {
            font-weight: 500;
            color: #1e293b;
        }
        
        /* Summary */
        .summary {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 30px;
        }
        
        .summary-box {
            width: 300px;
            background: #f0fdf4;
            border-radius: 8px;
            padding: 15px;
            border: 2px solid #059669;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #d1fae5;
        }
        
        .summary-row:last-child {
            border-bottom: none;
            padding-top: 12px;
            margin-top: 8px;
            border-top: 2px solid #059669;
        }
        
        .summary-row .label {
            color: #64748b;
        }
        
        .summary-row .value {
            font-weight: 600;
            color: #1e293b;
        }
        
        .summary-row.total .label,
        .summary-row.total .value {
            font-size: 16px;
            color: #047857;
        }
        
        /* Delivery Info */
        .delivery-info {
            background: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .delivery-info h3 {
            font-size: 14px;
            color: #047857;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #059669;
        }
        
        .delivery-details {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
        }
        
        .delivery-detail {
            padding: 10px;
            background: white;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }
        
        .delivery-detail .label {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        
        .delivery-detail .value {
            font-weight: 600;
            color: #1e293b;
        }
        
        /* Notes */
        .notes {
            background: #f8fafc;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 30px;
        }
        
        .notes h3 {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        
        .notes p {
            color: #475569;
            font-style: italic;
        }
        
        /* Signature Section */
        .signature-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            margin-top: 50px;
            padding-top: 20px;
        }
        
        .signature-box {
            text-align: center;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 50px;
            padding-top: 10px;
        }
        
        .signature-label {
            font-size: 12px;
            color: #64748b;
        }
        
        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            color: #64748b;
            font-size: 12px;
        }
        
        .footer p {
            margin-bottom: 5px;
        }
        
        /* Print Styles */
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
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
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        
        .btn-print {
            background: #047857;
            color: white;
        }
        
        .btn-print:hover {
            background: #065f46;
        }
        
        .btn-back {
            background: #64748b;
            color: white;
        }
        
        .btn-back:hover {
            background: #475569;
        }
    </style>
</head>
<body>
    <!-- Print Actions -->
    <div class="print-actions no-print">
        <button class="btn btn-back" onclick="history.back()">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
            </svg>
            Back
        </button>
        <button class="btn btn-print" onclick="window.print()">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
            </svg>
            Print Receipt
        </button>
    </div>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                <h1>{{ config('app.name', 'TOS') }}</h1>
                <p>Delivery Receipt</p>
            </div>
            <div class="receipt-title">
                <h2>Delivery Receipt</h2>
                <div class="number">DR-{{ str_pad($delivery->id, 6, '0', STR_PAD_LEFT) }}</div>
                <div class="status status-{{ $delivery->status->value }}">
                    {{ $delivery->status->getLabel() }}
                </div>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="info-grid">
            <div class="info-box">
                <h3>Customer Information</h3>
                <p>
                    <span class="label">Name:</span>
                    <span class="value">{{ $delivery->sale?->customer?->name ?? 'Walk-in Customer' }}</span>
                </p>
                @if($delivery->sale?->customer?->phone)
                <p>
                    <span class="label">Phone:</span>
                    <span class="value">{{ $delivery->sale->customer->phone }}</span>
                </p>
                @endif
                @if($delivery->sale?->customer?->email)
                <p>
                    <span class="label">Email:</span>
                    <span class="value">{{ $delivery->sale->customer->email }}</span>
                </p>
                @endif
                <p>
                    <span class="label">Delivery Address:</span>
                    <span class="value">{{ $delivery->delivery_address ?? $delivery->sale?->customer?->address ?? 'N/A' }}</span>
                </p>
            </div>
            <div class="info-box">
                <h3>Delivery Details</h3>
                <p>
                    <span class="label">Order #:</span>
                    <span class="value">{{ $delivery->sale_id }}</span>
                </p>
                <p>
                    <span class="label">Driver:</span>
                    <span class="value">{{ $delivery->driver?->name ?? 'Unassigned' }}</span>
                </p>
                <p>
                    <span class="label">Date Created:</span>
                    <span class="value">{{ $delivery->created_at->format('M d, Y h:i A') }}</span>
                </p>
                @if($delivery->distance_km)
                <p>
                    <span class="label">Distance:</span>
                    <span class="value">{{ number_format($delivery->distance_km, 2) }} km</span>
                </p>
                @endif
            </div>
        </div>

        <!-- Delivery Timeline -->
        <div class="delivery-info">
            <h3>Delivery Timeline</h3>
            <div class="delivery-details">
                <div class="delivery-detail">
                    <div class="label">Assigned At</div>
                    <div class="value">{{ $delivery->assigned_at ? $delivery->assigned_at->format('M d, Y h:i A') : 'Not yet assigned' }}</div>
                </div>
                <div class="delivery-detail">
                    <div class="label">Picked Up At</div>
                    <div class="value">{{ $delivery->picked_up_at ? $delivery->picked_up_at->format('M d, Y h:i A') : 'Not yet picked up' }}</div>
                </div>
                <div class="delivery-detail">
                    <div class="label">Delivered At</div>
                    <div class="value">{{ $delivery->delivered_at ? $delivery->delivered_at->format('M d, Y h:i A') : 'Not yet delivered' }}</div>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>Item Description</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($delivery->sale?->sale_items ?? [] as $item)
                <tr>
                    <td class="product-name">{{ $item->product?->name ?? 'Unknown Product' }}</td>
                    <td>{{ number_format($item->quantity) }}</td>
                    <td>₱{{ number_format($item->price / max($item->quantity, 1), 2) }}</td>
                    <td>₱{{ number_format($item->price, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align: center; color: #64748b;">No items found</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Summary -->
        <div class="summary">
            <div class="summary-box">
                <div class="summary-row">
                    <span class="label">Subtotal</span>
                    <span class="value">₱{{ number_format($delivery->sale?->total ?? 0, 2) }}</span>
                </div>
                <div class="summary-row total">
                    <span class="label">Total Amount</span>
                    <span class="value">₱{{ number_format($delivery->sale?->total ?? 0, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Notes -->
        @if($delivery->notes)
        <div class="notes">
            <h3>Delivery Notes</h3>
            <p>{{ $delivery->notes }}</p>
        </div>
        @endif

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line">
                    <span class="signature-label">Received By (Customer)</span>
                </div>
            </div>
            <div class="signature-box">
                <div class="signature-line">
                    <span class="signature-label">Delivered By (Driver)</span>
                </div>
            </div>
        </div>

        <!-- Customer Feedback (if available) -->
        @if($delivery->rating || $delivery->customer_feedback)
        <div class="notes" style="margin-top: 30px;">
            <h3>Customer Feedback</h3>
            @if($delivery->rating)
            <p style="font-style: normal; margin-bottom: 5px;">
                <strong>Rating:</strong> {{ str_repeat('⭐', $delivery->rating) }}
            </p>
            @endif
            @if($delivery->customer_feedback)
            <p>{{ $delivery->customer_feedback }}</p>
            @endif
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>Thank you for choosing {{ config('app.name', 'TOS') }}!</p>
            <p>This is a computer-generated document. Printed on {{ now()->format('F d, Y h:i A') }}</p>
        </div>
    </div>
</body>
</html>
