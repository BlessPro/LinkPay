<p><strong>New interested customer</strong></p>
<p>Product: {{ $lead->product?->name ?? 'N/A' }}</p>
<p>Name: {{ $lead->name ?? 'Not provided' }}</p>
<p>Contact(s): {{ $lead->contact_raw }}</p>
@if($lead->note)
    <p>Note: {{ $lead->note }}</p>
@endif
