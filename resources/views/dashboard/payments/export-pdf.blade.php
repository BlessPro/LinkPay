<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Payments report</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #0f172a; }
        .header { margin-bottom: 24px; }
        .title { font-size: 20px; font-weight: bold; }
        .muted { color: #64748b; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th, td { border: 1px solid #e2e8f0; padding: 6px; text-align: left; }
        th { background: #f8fafc; font-weight: bold; }
        .summary-row { font-size: 12px; }
        .summary-label { text-transform: uppercase; letter-spacing: 1px; font-size: 10px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Payments report</div>
        <div class="muted">Range: {{ $rangeLabel }}</div>
        <div class="muted">{{ $summary['count'] }} verified transactions</div>
    </div>

    <div class="summary-row">
        <p class="summary-label">Totals</p>
        <p>Revenue: {{ \App\Support\Money::format($summary['revenue'], $currency) }}</p>
    </div>

    <table>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['reference'] }}</td>
                    <td>{{ $row['type'] }}</td>
                    <td>{{ $row['item'] }}</td>
                    <td>{{ $row['payer'] }}</td>
                    <td>{{ $currency }} {{ number_format((float) $row['amount'], 2, '.', ',') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
