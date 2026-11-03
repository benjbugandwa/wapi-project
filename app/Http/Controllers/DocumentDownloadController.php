<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentDownloadController extends Controller
{
    public function __invoke(
        Request $request,
        Document $document,
        DocumentService $documentService,
    ): StreamedResponse {
        Gate::authorize('download', $document);

        return $documentService->download($document, $request->user());
    }
}
