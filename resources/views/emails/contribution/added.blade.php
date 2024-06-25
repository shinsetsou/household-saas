<!DOCTYPE html>
<html>
<head>
    <title>New Contribution Added {{ $household->name }}</title>
</head>
<body>
    <h1>New Contribution Added</h1>
    <p>A new contribution has been added for the household: {{ $household->name }}</p>
    <p>Contribution Amount: ${{ number_format($contribution->amount, 2) }}</p>
    <p>Date: {{ $contribution->created_at->format('F j, Y') }}</p>
</body>
</html>
