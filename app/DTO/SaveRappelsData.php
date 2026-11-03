<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class SaveRappelsData
{
    /**
     * @param  array<int, string>  $channels
     */
    public function __construct(
        public int $delaiMinutes,
        public array $channels,
    ) {}
}
