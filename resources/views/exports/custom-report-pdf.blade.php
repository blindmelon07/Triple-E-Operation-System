<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
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
        .header h1 {
            color: #1e40af;
            margin: 0;
            font-size: 20px;
        }
        .header p {
            margin: 4px 0;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
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
        tr:nth-child(even) {
            background-color: #f7f7f7;
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
    <div class="header">
        <h1>{{ $title }}</h1>
        <p>
            @if($dateFrom || $dateTo)
                Date range: {{ $dateFrom ?: 'earliest' }} to {{ $dateTo ?: 'latest' }}
            @else
                All dates
            @endif
        </p>
        <p>Generated: {{ $generatedAt }}</p>
    </div>

    <table>
        <thead>
            <tr>
                @foreach($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($row as $value)
                        <td>{{ $value }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" style="text-align:center; color:#999;">No matching records.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer-note">
        Showing {{ count($rows) }} of {{ number_format($totalMatchCount) }} matching row(s)
        @if($totalMatchCount > count($rows))
            — PDF exports are capped to keep the file safe to render; use "Export CSV" instead to get all {{ number_format($totalMatchCount) }} rows
        @endif
    </p>
</body>
</html>
