<?php

declare(strict_types=1);

namespace Espo\Modules\LoginAsUser\Classes\Impersonation;

use Espo\Core\Api\Request;
use Espo\Core\Authentication\HeaderKey;

/**
 * Reads the auth token the frontend sends in the `Espo-Authorization` header (`base64(username:token)`).
 */
class RequestToken
{
    public static function get(Request $request): ?string
    {
        return self::fromHeader($request->getHeader(HeaderKey::AUTHORIZATION));
    }

    public static function fromHeader(?string $header): ?string
    {
        if ($header === null || $header === '') {
            return null;
        }

        $decoded = base64_decode($header, true);

        if ($decoded === false || !str_contains($decoded, ':')) {
            return null;
        }

        $token = trim(explode(':', $decoded, 2)[1]);

        return $token !== '' ? $token : null;
    }
}
