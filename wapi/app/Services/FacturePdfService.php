<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Facture;
use App\Models\Paiement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class FacturePdfService
{
    public function facture(Facture $facture): Response
    {
        $facture->load(['cabinet', 'client', 'dossier', 'lignes', 'paiements']);

        return Pdf::loadView('pdf.facture', ['facture' => $facture])
            ->download($facture->numero_facture.'.pdf');
    }

    public function receipt(Paiement $paiement): Response
    {
        $paiement->load(['cabinet', 'facture.client']);

        return Pdf::loadView('pdf.recu-paiement', ['paiement' => $paiement])
            ->download('recu-'.$paiement->id.'.pdf');
    }
}
