<?php

declare(strict_types=1);

namespace tests\unit\Espo\Modules\LoginAsUser\Classes\Impersonation;

use Espo\Core\Utils\Config;
use Espo\Modules\LoginAsUser\Classes\Impersonation\TicketCookie;
use PHPUnit\Framework\TestCase;

class TicketCookieTest extends TestCase
{
    private function cookie(string $siteUrl): TicketCookie
    {
        $config = new class ($siteUrl) extends Config {
            public function __construct(private string $siteUrl)
            {}

            public function get(string $name, $default = null): mixed
            {
                return $name === 'siteUrl' ? $this->siteUrl : $default;
            }
        };

        return new TicketCookie($config);
    }

    public function testCookieIsHttpOnlyAndLax(): void
    {
        $header = $this->cookie('http://crm.local')->create('value', 3600);

        $this->assertStringStartsWith('login-as-user-ticket=value;', $header);
        $this->assertStringContainsString('HttpOnly', $header);
        $this->assertStringContainsString('SameSite=Lax', $header);
        $this->assertStringContainsString('Max-Age=3600', $header);
        $this->assertStringNotContainsString('Secure', $header);
    }

    public function testSecureOverHttps(): void
    {
        $this->assertStringContainsString('Secure', $this->cookie('https://crm.example.com')->create('v', 60));
    }

    public function testExpire(): void
    {
        $header = $this->cookie('https://crm.example.com')->expire();

        $this->assertStringStartsWith('login-as-user-ticket=;', $header);
        $this->assertStringContainsString('Max-Age=0', $header);
    }
}
