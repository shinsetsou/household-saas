<h1>Contribution Report - {{ $organization->name }}</h1>
<table width="100%" border="1" cellspacing="0" cellpadding="6">
    <thead><tr><th>Household</th><th>Amount</th><th>Status</th><th>Verification</th><th>Date</th></tr></thead>
    <tbody>
    @foreach($contributions as $contribution)
        <tr><td>{{ $contribution->household->name }}</td><td>{{ number_format($contribution->amount, 2) }}</td><td>{{ $contribution->status }}</td><td>{{ $contribution->verification_status }}</td><td>{{ $contribution->created_at->format('Y-m-d') }}</td></tr>
    @endforeach
    </tbody>
</table>
