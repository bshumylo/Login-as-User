<?php

declare(strict_types=1);

namespace Espo\Modules\LoginAsUser\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Utils\Log;
use Espo\Entities\User;
use Espo\Modules\LoginAsUser\Classes\Authentication\Hook\Hooks\LoginAsUser;
use Espo\Modules\LoginAsUser\Classes\Impersonation\TicketCookie;

/**
 * Ends the "login as user" state: the next requests are served as the administrator.
 * POST /api/v1/LoginAsUser/stop
 */
class PostStop implements Action
{
    public function __construct(
        private User $user,
        private TicketCookie $cookie,
        private Log $log,
    ) {}

    public function process(Request $request): Response
    {
        if ($request->getHeader('X-Requested-With') !== 'XMLHttpRequest') {
            throw new Forbidden('Missing required header.');
        }

        $actorId = $this->user->get(LoginAsUser::ACTOR_ATTRIBUTE);

        if ($actorId) {
            $this->log->notice('Login as User: stopped.', [
                'userId' => $actorId,
                'loginAsUserId' => $this->user->getId(),
                'ipAddress' => $this->user->get('ipAddress'),
            ]);
        }

        return ResponseComposer::json(['success' => true])
            ->addHeader('Set-Cookie', $this->cookie->expire());
    }
}
