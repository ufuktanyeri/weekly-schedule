<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Support\Conflict;
use RuntimeException;

final class ScheduleConflictException extends RuntimeException
{
    /**
     * @param  non-empty-list<Conflict>  $conflicts
     */
    public function __construct(public readonly array $conflicts)
    {
        parent::__construct($conflicts[0]->mesaj);
    }

    /**
     * @return list<string>
     */
    public function messages(): array
    {
        return array_values(array_unique(array_map(
            static fn (Conflict $c): string => $c->mesaj,
            $this->conflicts,
        )));
    }
}
