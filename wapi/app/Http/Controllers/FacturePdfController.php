<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Facture;
use App\Services\FacturePdfService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class FacturePdfController extends Controller
{
    public function __invoke(Facture $facture, FacturePdfService $pdfService): Response
    {
        Gate::authorize('downloadPdf', $facture);

        return $pdfService->facture($facture);
    }
}
