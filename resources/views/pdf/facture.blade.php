<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $facture->numero_facture }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 12px; }
        h1 { font-size: 22px; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        th, td { border: 1px solid #d1d5db; padding: 8px; text-align: left; }
        th { background: #f3f4f6; }
        .right { text-align: right; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    <h1>{{ __('Facture') }} {{ $facture->numero_facture }}</h1>
    <p class="muted">{{ $facture->cabinet->denomination }} - {{ $facture->cabinet->adresse_cabinet }}</p>

    <p>
        <strong>{{ __('Client') }}:</strong> {{ $facture->client->nom }} {{ $facture->client->prenom }}<br>
        <strong>{{ __('Date emission') }}:</strong> {{ $facture->date_emission->format('Y-m-d') }}<br>
        <strong>{{ __('Echeance') }}:</strong> {{ $facture->date_echeance->format('Y-m-d') }}<br>
        <strong>{{ __('Statut') }}:</strong> {{ $facture->statut->label() }}
    </p>

    <table>
        <thead>
            <tr>
                <th>{{ __('Description') }}</th>
                <th class="right">{{ __('Quantite') }}</th>
                <th class="right">{{ __('Prix unitaire') }}</th>
                <th class="right">{{ __('Total HT') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($facture->lignes as $ligne)
                <tr>
                    <td>{{ $ligne->description }}</td>
                    <td class="right">{{ $ligne->quantite }}</td>
                    <td class="right">{{ number_format((float) $ligne->prix_unitaire, 2, ',', ' ') }}</td>
                    <td class="right">{{ number_format((float) $ligne->total_ligne_ht, 2, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table>
        <tr><th>{{ __('Montant HT') }}</th><td class="right">{{ number_format((float) $facture->montant_ht, 2, ',', ' ') }}</td></tr>
        <tr><th>{{ __('TVA') }} {{ $facture->taux_tva }}%</th><td class="right">{{ number_format((float) $facture->montant_tva, 2, ',', ' ') }}</td></tr>
        <tr><th>{{ __('Montant TTC') }}</th><td class="right">{{ number_format((float) $facture->montant_ttc, 2, ',', ' ') }}</td></tr>
        <tr><th>{{ __('Montant paye') }}</th><td class="right">{{ number_format((float) $facture->paidAmount(), 2, ',', ' ') }}</td></tr>
        <tr><th>{{ __('Reste du') }}</th><td class="right">{{ number_format((float) $facture->remainingAmount(), 2, ',', ' ') }}</td></tr>
    </table>

    @if ($facture->notes_client)
        <p><strong>{{ __('Notes') }}:</strong><br>{{ $facture->notes_client }}</p>
    @endif
</body>
</html>
