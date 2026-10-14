<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Provider-agnostic snapshot of the identity returned by Google OAuth.
 */
final readonly class GoogleUserData
{
    public function __construct(
        public string $id,
        public string $email,
        public string $name,
        public bool $emailVerified,
        public ?string $avatarUrl = null,
    ) {}
}
