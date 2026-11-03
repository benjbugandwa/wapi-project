<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ __('Recu de paiement') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 12px; }
        h1 { font-size: 22px; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        th, td { border: 1px solid #d1d5db; padding: 8px; text-align: left; }
        th { background: #f3f4f6; width: 35%; }
        .right { text-align: right; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    <h1>{{ __('Recu de paiement') }}</h1>
    <p class="muted">{{ $paiement->cabinet->denomination }}</p>

    <table>
        <tr><th>{{ __('Facture') }}</th><td>{{ $paiement->facture->numero_facture }}</td></tr>
        <tr><th>{{ __('Client') }}</th><td>{{ $paiement->facture->client->nom }} {{ $paiement->facture->client->prenom }}</td></tr>
        <tr><th>{{ __('Date paiement') }}</th><td>{{ $paiement->date_paiement->format('Y-m-d') }}</td></tr>
        <tr><th>{{ __('Methode') }}</th><td>{{ $paiement->methode_paiement->label() }}</td></tr>
        <tr><th>{{ __('Reference') }}</th><td>{{ $paiement->reference_transaction ?? '-' }}</td></tr>
        <tr><th>{{ __('Montant paye') }}</th><td class="right">{{ number_format((float) $paiement->montant_paye, 2, ',', ' ') }}</td></tr>
    </table>
</body>
</html>
