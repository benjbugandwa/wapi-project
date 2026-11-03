<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Paiement;
use App\Services\FacturePdfService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PaiementReceiptController extends Controller
{
    public function __invoke(Paiement $paiement, FacturePdfService $pdfService): Response
    {
        Gate::authorize('downloadReceipt', $paiement);

        return $pdfService->receipt($paiement);
    }
}
