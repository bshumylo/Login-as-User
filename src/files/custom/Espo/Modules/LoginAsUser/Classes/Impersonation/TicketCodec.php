<?php

declare(strict_types=1);

namespace Espo\Modules\LoginAsUser\Classes\Impersonation;

use Espo\Core\Utils\Config;
use RuntimeException;

/**
 * Encodes and verifies tickets: `base64url(json).base64url(hmac-sha256)`.
 * The key is derived from the instance `cryptKey`, so a ticket cannot be forged or moved to another instance.
 */
class TicketCodec
{
    private const KEY_CONTEXT = 'login-as-user:ticket:v1';

    public function __construct(
        private Config $config,
    ) {}

    public function encode(Ticket $ticket): string
    {
        $json = json_encode([
            'v' => 1,
            'a' => $ticket->actorId,
            'u' => $ticket->targetId,
            't' => $ticket->tokenHash,
            'e' => $ticket->expiresAt,
        ], JSON_THROW_ON_ERROR);

        return self::base64UrlEncode($json) . '.' . self::base64UrlEncode($this->sign($json));
    }

    /**
     * @return ?Ticket Null if the value is malformed or the signature does not match.
     */
    public function decode(string $value): ?Ticket
    {
        if (strlen($value) > 1024 || substr_count($value, '.') !== 1) {
            return null;
        }

        [$payload, $signature] = explode('.', $value);

        $json = self::base64UrlDecode($payload);
        $signatureRaw = self::base64UrlDecode($signature);

        if ($json === null || $signatureRaw === null) {
            return null;
        }

        if (!hash_equals($this->sign($json), $signatureRaw)) {
            return null;
        }

        $data = json_decode($json, true);

        if (
            !is_array($data) ||
            ($data['v'] ?? null) !== 1 ||
            !is_string($data['a'] ?? null) ||
            !is_string($data['u'] ?? null) ||
            !is_string($data['t'] ?? null) ||
            !is_int($data['e'] ?? null)
        ) {
            return null;
        }

        return new Ticket($data['a'], $data['u'], $data['t'], $data['e']);
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    private function sign(string $json): string
    {
        $cryptKey = (string) $this->config->get('cryptKey');

        if ($cryptKey === '') {
            throw new RuntimeException('The cryptKey config parameter is not set.');
        }

        $key = hash_hmac('sha256', self::KEY_CONTEXT, $cryptKey, true);

        return hash_hmac('sha256', $json, $key, true);
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): ?string
    {
        $decoded = base64_decode(strtr($data, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }
}
