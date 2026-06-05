<!DOCTYPE html>
<html>
<head>
    <title>New Contribution Added</title>
</head>
<body>
    <h1>New Contribution Added</h1>
    <p>A new contribution has been added for the household ID: {{ $contributionData['household_id'] }}</p>
    <p>Contribution Amount: ${{ number_format($contributionData['amount'], 2) }}</p>
    <p>Reference Number: {{ $contributionData['reference_number'] }}</p>
    <p>Date: {{ now()->format('F j, Y') }}</p>
    @if (isset($contributionData['photo']))
    <p>Proof of Payment:</p>
    <img src="{{ \Illuminate\Support\Facades\Storage::disk('s3')->url($contributionData['photo']) }}" alt="Proof of Payment">
    @endif
</body>
</html>
