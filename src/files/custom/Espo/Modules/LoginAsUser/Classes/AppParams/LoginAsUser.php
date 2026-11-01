<?php

declare(strict_types=1);

namespace Espo\Modules\LoginAsUser\Classes\AppParams;

use Espo\Entities\User;
use Espo\Modules\LoginAsUser\Classes\Authentication\Hook\Hooks\LoginAsUser as Hook;
use Espo\ORM\EntityManager;
use Espo\Tools\App\AppParam;

/**
 * Tells the frontend that the current session acts as another user, and who the real user is.
 */
class LoginAsUser implements AppParam
{
    public function __construct(
        private User $user,
        private EntityManager $entityManager,
    ) {}

    /**
     * @return ?array{actorId: string, actorName: string}
     */
    public function get(): ?array
    {
        $actorId = $this->user->get(Hook::ACTOR_ATTRIBUTE);

        if (!is_string($actorId) || $actorId === '') {
            return null;
        }

        $actor = $this->entityManager->getEntityById(User::ENTITY_TYPE, $actorId);

        return [
            'actorId' => $actorId,
            'actorName' => $actor ? (string) $actor->get('name') : '',
        ];
    }
}
