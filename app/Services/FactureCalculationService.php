<?php

declare(strict_types=1);

namespace App\Services;

class FactureCalculationService
{
    /**
     * @param  array<int, array{description: string, quantite: numeric-string, prix_unitaire: numeric-string}>  $lignes
     * @return array{montant_ht: string, montant_tva: string, montant_ttc: string, lignes: array<int, array{description: string, quantite: string, prix_unitaire: string, total_ligne_ht: string}>}
     */
    public function calculate(array $lignes, string $tauxTva): array
    {
        $normalizedLines = [];
        $montantHt = 0.0;

        foreach ($lignes as $ligne) {
            $quantite = round((float) $ligne['quantite'], 2);
            $prixUnitaire = round((float) $ligne['prix_unitaire'], 2);
            $total = round($quantite * $prixUnitaire, 2);
            $montantHt += $total;

            $normalizedLines[] = [
                'description' => $ligne['description'],
                'quantite' => number_format($quantite, 2, '.', ''),
                'prix_unitaire' => number_format($prixUnitaire, 2, '.', ''),
                'total_ligne_ht' => number_format($total, 2, '.', ''),
            ];
        }

        $montantHt = round($montantHt, 2);
        $montantTva = round($montantHt * ((float) $tauxTva / 100), 2);
        $montantTtc = round($montantHt + $montantTva, 2);

        return [
            'montant_ht' => number_format($montantHt, 2, '.', ''),
            'montant_tva' => number_format($montantTva, 2, '.', ''),
            'montant_ttc' => number_format($montantTtc, 2, '.', ''),
            'lignes' => $normalizedLines,
        ];
    }
}
