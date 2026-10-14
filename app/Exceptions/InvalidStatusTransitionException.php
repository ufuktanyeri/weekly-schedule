<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\UserStatus;
use DomainException;

final class InvalidStatusTransitionException extends DomainException
{
    public function __construct(UserStatus $from, UserStatus $to)
    {
        parent::__construct("Kullanıcı durumu [{$from->label()}] → [{$to->label()}] olarak değiştirilemez.");
    }
}
