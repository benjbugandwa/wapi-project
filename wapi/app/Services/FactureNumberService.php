<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Facture;

class FactureNumberService
{
    public function nextForCabinet(string $cabinetId): string
    {
        $year = now()->format('Y');
        $prefix = 'FAC-'.$year.'-';

        $lastNumber = Facture::query()
            ->where('cabinet_id', $cabinetId)
            ->where('numero_facture', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('numero_facture')
            ->value('numero_facture');

        $sequence = $lastNumber === null ? 1 : ((int) substr((string) $lastNumber, -4)) + 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
