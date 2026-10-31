<?php

declare(strict_types=1);

namespace Espo\Modules\LoginAsUser\Classes\Impersonation;

/**
 * A signed permission of an administrator to act as another user.
 */
final readonly class Ticket
{
    public function __construct(
        public string $actorId,
        public string $targetId,
        public string $tokenHash,
        public int $expiresAt,
    ) {}

    public function isExpired(int $now): bool
    {
        return $this->expiresAt <= $now;
    }
}
