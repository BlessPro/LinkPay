<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Weekly seller summary</title>
</head>
<body style="font-family: Arial, sans-serif; color: #0f172a; line-height: 1.5;">
    <h2 style="margin-bottom: 8px;">Weekly performance summary</h2>
    <p style="margin-top: 0;">Hi {{ $summary['seller_name'] }}, here is your last 7-day snapshot.</p>

    <ul>
        <li><strong>Revenue:</strong> {{ $summary['currency'] }} {{ number_format((float) $summary['revenue'], 2) }}</li>
        <li><strong>Paid orders:</strong> {{ $summary['paid_orders'] }}</li>
        <li><strong>New customers:</strong> {{ $summary['new_customers'] }}</li>
    </ul>

    <p><strong>Top product:</strong> {{ $summary['top_product'] ?? 'No product data yet' }}</p>
    <p style="margin-top: 20px;">Keep going - consistency compounds.</p>
</body>
</html>
