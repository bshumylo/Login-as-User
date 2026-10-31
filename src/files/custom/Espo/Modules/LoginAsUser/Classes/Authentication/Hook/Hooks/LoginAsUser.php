<?php

declare(strict_types=1);

namespace Espo\Modules\LoginAsUser\Classes\Authentication\Hook\Hooks;

use Espo\Core\Api\Request;
use Espo\Core\Authentication\AuthenticationData;
use Espo\Core\Authentication\Hook\OnResult;
use Espo\Core\Authentication\Result;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Utils\Log;
use Espo\Entities\User;
use Espo\ORM\EntityManager;

/**
 * Replaces the authenticated administrator with the user whose id is stored in
 * the `login-as-user-id` cookie. Only administrators may do this.
 */
class LoginAsUser implements OnResult
{
    public const COOKIE_NAME = 'login-as-user-id';

    public function __construct(
        private User $user,
        private EntityManager $entityManager,
        private Log $log,
    ) {}

    /**
     * @throws Forbidden
     */
    public function process(Result $result, AuthenticationData $data, Request $request): void
    {
        $user = $result->getUser();
        $loginAsUserId = $request->getCookieParam(self::COOKIE_NAME);

        if ($loginAsUserId === null || $user === null) {
            return;
        }

        if (!$user->isAdmin()) {
            $this->log->warning('Trying to login as user by non-admin user.', [
                'userId' => $user->getId(),
                'loginAsUserId' => $loginAsUserId,
            ]);

            throw new Forbidden('Insufficient permissions for logging in as other users.');
        }

        $loginAsUser = $this->entityManager->getEntityById(User::ENTITY_TYPE, $loginAsUserId);

        if (!$loginAsUser instanceof User) {
            $this->log->warning('Trying to login as user that does not exist.', [
                'userId' => $user->getId(),
                'loginAsUserId' => $loginAsUserId,
            ]);

            throw new Forbidden('User does not exist.');
        }

        // The same preparation the core does for a logged user: team-based ACL needs the teams.
        if (!$loginAsUser->isPortal()) {
            $loginAsUser->loadLinkMultipleField('teams');
        }

        $ipAddress = $user->get('ipAddress');

        $this->user->reset();
        $this->user->set($loginAsUser->getValueMap());
        $this->user->set('ipAddress', $ipAddress);
    }
}
