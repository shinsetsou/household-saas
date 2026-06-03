<h1>Expense Report - {{ $organization->name }}</h1>
<table width="100%" border="1" cellspacing="0" cellpadding="6">
    <thead><tr><th>Description</th><th>Amount</th><th>Date</th></tr></thead>
    <tbody>
    @foreach($expenses as $expense)
        <tr><td>{{ $expense->description }}</td><td>{{ number_format($expense->amount, 2) }}</td><td>{{ $expense->created_at->format('Y-m-d') }}</td></tr>
    @endforeach
    </tbody>
</table>
