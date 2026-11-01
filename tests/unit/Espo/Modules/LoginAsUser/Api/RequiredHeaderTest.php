<?php

declare(strict_types=1);

namespace tests\unit\Espo\Modules\LoginAsUser\Api;

use Espo\Core\Api\Request;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Log;
use Espo\Entities\User;
use Espo\Modules\LoginAsUser\Api\PostStart;
use Espo\Modules\LoginAsUser\Api\PostStop;
use Espo\Modules\LoginAsUser\Classes\Impersonation\TargetPolicy;
use Espo\Modules\LoginAsUser\Classes\Impersonation\TicketCodec;
use Espo\Modules\LoginAsUser\Classes\Impersonation\TicketCookie;
use Espo\ORM\EntityManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Both endpoints are a CSRF guard: they require "X-Requested-With: XMLHttpRequest",
 * which the client sends explicitly (Espo.Ajax does not add it).
 */
class RequiredHeaderTest extends TestCase
{
    private function request(?string $header): Request
    {
        $request = $this->createStub(Request::class);
        $request->method('getHeader')->willReturnCallback(
            fn (string $name) => $name === 'X-Requested-With' ? $header : null
        );
        $request->method('getParsedBody')->willReturn((object) []);

        return $request;
    }

    private function nonAdmin(): User
    {
        $user = $this->createStub(User::class);
        $user->method('isAdmin')->willReturn(false);

        return $user;
    }

    private function start(): PostStart
    {
        return new PostStart(
            $this->nonAdmin(),
            $this->createStub(EntityManager::class),
            $this->createStub(Config::class),
            $this->createStub(TicketCodec::class),
            $this->createStub(TicketCookie::class),
            $this->createStub(TargetPolicy::class),
            $this->createStub(Log::class),
        );
    }

    private function stop(): PostStop
    {
        return new PostStop(
            $this->nonAdmin(),
            $this->createStub(TicketCookie::class),
            $this->createStub(Log::class),
        );
    }

    /**
     * @return array<string, array{?string}>
     */
    public static function badHeaders(): array
    {
        return [
            'missing' => [null],
            'empty' => [''],
            'other value' => ['fetch'],
        ];
    }

    #[DataProvider('badHeaders')]
    public function testStartRequiresHeader(?string $header): void
    {
        $this->expectException(Forbidden::class);
        $this->expectExceptionMessage('Missing required header.');

        $this->start()->process($this->request($header));
    }

    public function testStartPassesHeaderCheck(): void
    {
        $this->expectException(Forbidden::class);
        $this->expectExceptionMessage('Only administrators can log in as other users.');

        $this->start()->process($this->request('XMLHttpRequest'));
    }

    #[DataProvider('badHeaders')]
    public function testStopRequiresHeader(?string $header): void
    {
        $this->expectException(Forbidden::class);
        $this->expectExceptionMessage('Missing required header.');

        $this->stop()->process($this->request($header));
    }

    public function testStopPassesHeaderCheck(): void
    {
        $response = $this->stop()->process($this->request('XMLHttpRequest'));

        $this->assertSame(200, $response->getStatusCode());
    }
}
