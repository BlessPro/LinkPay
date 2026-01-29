<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Products report</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #0f172a; font-size: 12px; }
        .header { margin-bottom: 24px; }
        .title { font-size: 20px; font-weight: bold; }
        .muted { color: #64748b; }
        .kpis { display: table; width: 100%; margin: 16px 0 24px; }
        .kpi { display: table-cell; width: 25%; border: 1px solid #e2e8f0; padding: 10px; }
        .kpi-label { font-size: 10px; text-transform: uppercase; letter-spacing: 2px; color: #94a3b8; }
        .kpi-value { font-size: 16px; font-weight: bold; margin-top: 6px; }
        .section-title { font-size: 14px; font-weight: bold; margin: 18px 0 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e2e8f0; padding: 8px; text-align: left; }
        th { background: #f8fafc; font-weight: bold; }
        .chart { margin: 16px 0 24px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; background: #ecfeff; color: #0f766e; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Products report</div>
        <div class="muted">
            Seller: {{ $seller->sellerProfile?->business_name ?? $seller->name }}
            &middot; Range: {{ $rangeLabel }}
            &middot; Type: {{ str_replace('_', ' ', $type) }}
        </div>
    </div>

    <div class="kpis">
        <div class="kpi">
            <div class="kpi-label">Revenue</div>
            <div class="kpi-value">{{ \App\Support\Money::format($totalRevenue, $currency) }}</div>
        </div>
        <div class="kpi">
            <div class="kpi-label">Payments</div>
            <div class="kpi-value">{{ $totalOrders }}</div>
        </div>
        <div class="kpi">
            <div class="kpi-label">Views</div>
            <div class="kpi-value">{{ $totalViews }}</div>
        </div>
        <div class="kpi">
            <div class="kpi-label">Conversion</div>
            <div class="kpi-value">{{ $conversion }}%</div>
        </div>
    </div>

    @if($chartImage)
        <div class="section-title">Performance chart</div>
        <div class="chart">
            <img src="{{ $chartImage }}" alt="Performance chart" style="width: 100%; max-width: 700px;">
        </div>
    @endif

    <div class="section-title">Inventory</div>
    <table>
        <thead>
            <tr>
                @foreach($rows[0] ?? [] as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach(array_slice($rows, 1) as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
