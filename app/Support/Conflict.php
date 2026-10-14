<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\CakismaTuru;

/**
 * A single rule violation detected by ConflictValidationService.
 */
final readonly class Conflict
{
    public function __construct(
        public CakismaTuru $tur,
        public string $mesaj,
        public ?TimeSlot $slot = null,
        public ?int $cakisanKayitId = null,
    ) {}
}
