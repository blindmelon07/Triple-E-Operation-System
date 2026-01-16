<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Receipt - #{{ $delivery->id }}</title>
    <style>
        /* Receipt-style CSS to match scanned design */
        * { box-sizing: border-box; margin:0; padding:0 }
        body { font-family: 'Arial', sans-serif; font-size:11px; color:#111; background:#fff }

        .container {
            width: 760px;
            margin: 8px auto;
            padding: 12px;
            background: #f7e1e1; /* paper pink tone */
            border: 1px solid #d3a6a6;
        }

        /* Header */
        .header { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:1px solid #b88; padding-bottom:6px; }
        .company-info { font-size:12px }
        .company-info h1 { font-size:16px; font-weight:700; margin-bottom:4px }
        .company-info p { font-size:10.5px; color:#222 }

        .receipt-meta { text-align:right; font-size:11px }
        .receipt-meta .title { font-weight:700; letter-spacing:1px; font-size:13px }
        .receipt-meta .meta-row { margin-top:3px }

        /* Info grid */
        .info-grid { display:flex; gap:10px; margin-top:8px }
        .info-box { flex:1; padding:6px; font-size:10.5px }
        .info-box .label { display:inline-block; width:92px; color:#333 }

        /* Items table */
        .items-table { width:100%; border-collapse:collapse; margin-top:8px; font-size:11px }
        .items-table thead th { text-align:left; padding:6px 8px; border-bottom:2px solid #000 }
        .items-table tbody td { padding:6px 8px; border-bottom:1px dashed #b88 }
        .items-table th.qty, .items-table td.qty { width:8% }
        .items-table th.desc, .items-table td.desc { width:38% }
        .items-table th.chinese, .items-table td.chinese { width:18% }
        .items-table th.price, .items-table td.price { width:12%; text-align:right }
        .items-table th.disc, .items-table td.disc { width:8%; text-align:right }
        .items-table th.amount, .items-table td.amount { width:14%; text-align:right }

        /* Summary on right */
        .summary { display:flex; justify-content:flex-end; margin-top:6px }
        .summary-box { width:300px; font-size:11px }
        .summary-row { display:flex; justify-content:space-between; padding:4px 0 }
        .summary-row .label { color:#222 }
        .summary-row.net { font-weight:700; border-top:1px solid #b88; padding-top:6px; margin-top:6px }

        /* Note above signatures */
        .received-note { margin-top:14px; font-style:italic; font-size:11px }

        /* Signatures */
        .signature-section { margin-top:18px }
        .signature-rows { display:flex; gap:8px }
        .signature { flex:1; text-align:center; font-size:11px }
        .signature .line { border-top:1px solid #111; margin-top:36px; padding-top:6px }

        .footer { text-align:center; margin-top:10px; font-size:10px; color:#444 }

        @media print { .print-actions{display:none!important} }
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
            <div class="company-info" style="display:flex;align-items:center;gap:8px;">
                <img src="{{ asset('images/logo.png') }}" alt="Tri-E Enterprises Logo" style="height:48px;object-fit:contain;">
                <div>
                        <h1>Tri-E Enterprises</h1>
                        <p>Your Trusted Business Partner</p>
                        <p style="margin-top: 10px;">Maharlika Highway,Cabidan Sorsogon City</p>
                        <p>Phone: (+639) 993-052-2540</p>
                </div>
            </div>
            <div class="receipt-meta">
                <div class="title">DELIVERY RECEIPT</div>
                <div class="meta-row">DR-{{ str_pad($delivery->id, 6, '0', STR_PAD_LEFT) }}</div>
                <div class="meta-row">Date: {{ $delivery->created_at->format('Y-m-d') }}</div>
                <div class="meta-row">Page: 1</div>
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
                    <th class="qty">Qty</th>
                    <th class="desc">Description</th>
                    <th class="chinese">ChineseName</th>
                    <th class="price">Price</th>
                    <th class="disc">Disc</th>
                    <th class="amount">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($delivery->sale?->sale_items ?? [] as $item)
                <tr>
                    <td class="qty">{{ number_format($item->quantity) }}</td>
                    <td class="desc product-name">{{ $item->product?->name ?? 'Unknown Product' }}</td>
                    <td class="chinese">{{ $item->product?->chinese_name ?? '' }}</td>
                    <td class="price">₱{{ number_format($item->price / max($item->quantity, 1), 2) }}</td>
                    <td class="disc">{{ isset($item->discount) ? '₱'.number_format($item->discount, 2) : '-' }}</td>
                    <td class="amount">₱{{ number_format($item->price, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: #64748b;">No items found</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Summary -->
        <div class="summary">
            <div class="summary-box">
                <div class="summary-row">
                    <span class="label">SUB TOTAL</span>
                    <span class="value">₱{{ number_format($delivery->sale?->subtotal ?? $delivery->sale?->total ?? 0, 2) }}</span>
                </div>
                <div class="summary-row">
                    <span class="label">DISCOUNT</span>
                    <span class="value">₱{{ number_format($delivery->sale?->discount_total ?? 0, 2) }}</span>
                </div>
                <div class="summary-row">
                    <span class="label">ADJUST</span>
                    <span class="value">₱{{ number_format($delivery->sale?->adjustment ?? 0, 2) }}</span>
                </div>
                <div class="summary-row net">
                    <span class="label">NET TOTAL</span>
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

        <div class="received-note">Received the above articles in good order and condition</div>

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
                <div class="signature">
                    <div class="line"></div>
                    <div>Driver/Truck #</div>
                </div>
                <div class="signature">
                    <div class="line"></div>
                    <div>Received By</div>
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

        
