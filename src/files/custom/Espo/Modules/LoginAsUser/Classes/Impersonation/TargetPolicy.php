<?php

declare(strict_types=1);

namespace Espo\Modules\LoginAsUser\Classes\Impersonation;

use Espo\Entities\User;

/**
 * Who an administrator may act as. Checked when the ticket is issued and again on every request,
 * because the users can change while a ticket is valid.
 */
class TargetPolicy
{
    public function isAllowed(User $actor, User $target): bool
    {
        if (!$actor->isAdmin() || !$actor->isActive()) {
            return false;
        }

        if ($actor->getId() === $target->getId()) {
            return false;
        }

        if (!$target->isActive() || $target->isSystem() || $target->isApi()) {
            return false;
        }

        // Only a super-admin may act as a super-admin.
        return !$target->isSuperAdmin() || $actor->isSuperAdmin();
    }
}
