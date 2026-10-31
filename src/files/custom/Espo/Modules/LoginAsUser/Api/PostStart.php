<?php

declare(strict_types=1);

namespace Espo\Modules\LoginAsUser\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Log;
use Espo\Entities\User;
use Espo\Modules\LoginAsUser\Classes\Authentication\Hook\Hooks\LoginAsUser;
use Espo\Modules\LoginAsUser\Classes\Impersonation\RequestToken;
use Espo\Modules\LoginAsUser\Classes\Impersonation\TargetPolicy;
use Espo\Modules\LoginAsUser\Classes\Impersonation\Ticket;
use Espo\Modules\LoginAsUser\Classes\Impersonation\TicketCodec;
use Espo\Modules\LoginAsUser\Classes\Impersonation\TicketCookie;
use Espo\ORM\EntityManager;

/**
 * Lets an administrator start acting as another user.
 * POST /api/v1/LoginAsUser/start {userId}
 */
class PostStart implements Action
{
    private const DEFAULT_TTL = 3600;
    private const MIN_TTL = 60;
    private const MAX_TTL = 28800;

    public function __construct(
        private User $user,
        private EntityManager $entityManager,
        private Config $config,
        private TicketCodec $codec,
        private TicketCookie $cookie,
        private TargetPolicy $policy,
        private Log $log,
    ) {}

    public function process(Request $request): Response
    {
        if ($request->getHeader('X-Requested-With') !== 'XMLHttpRequest') {
            throw new Forbidden('Missing required header.');
        }

        if (!$this->user->isAdmin() || $this->user->get(LoginAsUser::ACTOR_ATTRIBUTE)) {
            throw new Forbidden('Only administrators can log in as other users.');
        }

        $targetId = $request->getParsedBody()->userId ?? null;

        if (!is_string($targetId) || $targetId === '') {
            throw new BadRequest('No userId.');
        }

        $target = $this->entityManager->getEntityById(User::ENTITY_TYPE, $targetId);

        if (!$target instanceof User) {
            throw new Forbidden('User does not exist.');
        }

        if (!$this->policy->isAllowed($this->user, $target)) {
            throw new Forbidden('It is not allowed to log in as this user.');
        }

        $token = RequestToken::get($request);

        if ($token === null) {
            throw new Forbidden('Only a session authenticated by an auth token can log in as another user.');
        }

        $ttl = $this->getTtl();

        $ticket = new Ticket(
            $this->user->getId(),
            $target->getId(),
            TicketCodec::hashToken($token),
            time() + $ttl
        );

        $this->log->notice('Login as User: started.', [
            'userId' => $this->user->getId(),
            'userName' => $this->user->getUserName(),
            'loginAsUserId' => $target->getId(),
            'loginAsUserName' => $target->getUserName(),
            'ipAddress' => $this->user->get('ipAddress'),
            'ttl' => $ttl,
        ]);

        return ResponseComposer::json(['success' => true, 'ttl' => $ttl])
            ->addHeader('Set-Cookie', $this->cookie->create($this->codec->encode($ticket), $ttl));
    }

    private function getTtl(): int
    {
        $ttl = $this->config->get('loginAsUserTtl');

        if (!is_int($ttl)) {
            return self::DEFAULT_TTL;
        }

        return min(max($ttl, self::MIN_TTL), self::MAX_TTL);
    }
}
