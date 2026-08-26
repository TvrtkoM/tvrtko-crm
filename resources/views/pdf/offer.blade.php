<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $offer->offer_number }}</title>
</head>
<body>
    <h1>Tvrtko CRM</h1>
    <p>Offer: {{ $offer->offer_number }}</p>
    <p>Total: {{ number_format($offer->total, 2) }} €</p>
</body>
</html>
