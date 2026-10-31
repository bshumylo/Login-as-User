<?php

declare(strict_types=1);

namespace Espo\Modules\LoginAsUser\Classes\Impersonation;

use Espo\Core\Utils\Config;

/**
 * Builds the Set-Cookie header values of the ticket cookie. The cookie is HttpOnly: scripts cannot read or forge it.
 */
class TicketCookie
{
    public const NAME = 'login-as-user-ticket';

    public function __construct(
        private Config $config,
    ) {}

    public function create(string $value, int $maxAge): string
    {
        return $this->compose($value, 'Max-Age=' . max(0, $maxAge));
    }

    public function expire(): string
    {
        return $this->compose('', 'Max-Age=0', 'Expires=Thu, 01 Jan 1970 00:00:01 GMT');
    }

    private function compose(string $value, string ...$attributes): string
    {
        $parts = [self::NAME . '=' . $value, ...$attributes, 'Path=/', 'HttpOnly', 'SameSite=Lax'];

        if (str_starts_with((string) $this->config->get('siteUrl'), 'https://')) {
            $parts[] = 'Secure';
        }

        return implode('; ', $parts);
    }
}
