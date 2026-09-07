<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Supplier Price Comparison</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
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
        .header h1 {
            color: #1e40af;
            margin: 0 0 5px 0;
            font-size: 20px;
        }
        .header p {
            margin: 3px 0;
            color: #666;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background-color: #f3f4f6;
            font-weight: bold;
            color: #374151;
            padding: 7px 8px;
            text-align: left;
            border-bottom: 1px solid #d1d5db;
            font-size: 9px;
        }
        td {
            padding: 6px 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .empty-cell { color: #d1d5db; }
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            font-size: 9px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        @if($logoDataUri ?? null)
            <img src="{{ $logoDataUri }}" alt="Company Logo">
        @endif
        <h1>Supplier Price Comparison</h1>
        <p>Generated: {{ $generatedAt }}</p>
        @if($categoryName)
            <p>Filtered by Category: <strong>{{ $categoryName }}</strong></p>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Category</th>
                @foreach($suppliers as $supplier)
                    <th class="text-right">{{ $supplier->name }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['Product'] }}</td>
                    <td>{{ $row['Category'] }}</td>
                    @foreach($suppliers as $supplier)
                        @php $price = $row[$supplier->name] ?? ''; @endphp
                        <td class="text-right {{ $price === '' ? 'empty-cell' : '' }}">
                            {{ $price === '' ? '—' : '₱'.$price }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 2 + count($suppliers) }}" class="text-center" style="color:#9ca3af;">
                        No supplier base prices recorded yet.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Only products with at least one recorded supplier base price are listed. A dash (—) means that supplier hasn't quoted a price for that product.</p>
    </div>
</body>
</html>
