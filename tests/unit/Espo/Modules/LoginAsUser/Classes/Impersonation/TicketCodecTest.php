<?php

declare(strict_types=1);

namespace tests\unit\Espo\Modules\LoginAsUser\Classes\Impersonation;

use Espo\Core\Utils\Config;
use Espo\Modules\LoginAsUser\Classes\Impersonation\Ticket;
use Espo\Modules\LoginAsUser\Classes\Impersonation\TicketCodec;
use PHPUnit\Framework\TestCase;

class TicketCodecTest extends TestCase
{
    private function config(array $values): Config
    {
        return new class ($values) extends Config {
            public function __construct(private array $values)
            {}

            public function get(string $name, $default = null): mixed
            {
                return $this->values[$name] ?? $default;
            }
        };
    }

    private function codec(string $cryptKey = 'secret-key'): TicketCodec
    {
        return new TicketCodec($this->config(['cryptKey' => $cryptKey]));
    }

    private function ticket(): Ticket
    {
        return new Ticket('admin1', 'user1', TicketCodec::hashToken('token'), 2000000000);
    }

    public function testRoundTrip(): void
    {
        $codec = $this->codec();

        $decoded = $codec->decode($codec->encode($this->ticket()));

        $this->assertNotNull($decoded);
        $this->assertSame('admin1', $decoded->actorId);
        $this->assertSame('user1', $decoded->targetId);
        $this->assertSame(TicketCodec::hashToken('token'), $decoded->tokenHash);
        $this->assertSame(2000000000, $decoded->expiresAt);
    }

    public function testTamperedPayloadIsRejected(): void
    {
        $codec = $this->codec();

        [, $signature] = explode('.', $codec->encode($this->ticket()));

        $forged = json_encode(['v' => 1, 'a' => 'admin1', 'u' => 'other', 't' => TicketCodec::hashToken('token'), 'e' => 2000000000]);
        $payload = rtrim(strtr(base64_encode((string) $forged), '+/', '-_'), '=');

        $this->assertNull($codec->decode($payload . '.' . $signature));
    }

    public function testTamperedSignatureIsRejected(): void
    {
        $codec = $this->codec();

        [$payload] = explode('.', $codec->encode($this->ticket()));

        $this->assertNull($codec->decode($payload . '.' . rtrim(strtr(base64_encode(str_repeat('x', 32)), '+/', '-_'), '=')));
    }

    public function testAnotherInstanceKeyIsRejected(): void
    {
        $value = $this->codec('key-a')->encode($this->ticket());

        $this->assertNull($this->codec('key-b')->decode($value));
    }

    public function testMalformedValuesAreRejected(): void
    {
        $codec = $this->codec();

        $this->assertNull($codec->decode(''));
        $this->assertNull($codec->decode('abc'));
        $this->assertNull($codec->decode('a.b.c'));
        $this->assertNull($codec->decode('!!!.???'));
        $this->assertNull($codec->decode(str_repeat('a', 2000) . '.' . 'b'));
    }

    public function testExpiry(): void
    {
        $ticket = new Ticket('a', 'u', 'h', 1000);

        $this->assertTrue($ticket->isExpired(1000));
        $this->assertTrue($ticket->isExpired(1001));
        $this->assertFalse($ticket->isExpired(999));
    }

    public function testMissingCryptKeyRefusesToSign(): void
    {
        $thrown = false;

        try {
            $this->codec('')->encode($this->ticket());
        } catch (\RuntimeException) {
            $thrown = true;
        }

        $this->assertTrue($thrown);
    }
}
