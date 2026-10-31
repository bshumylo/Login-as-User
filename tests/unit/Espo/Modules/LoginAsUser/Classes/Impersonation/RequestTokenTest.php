<?php

declare(strict_types=1);

namespace tests\unit\Espo\Modules\LoginAsUser\Classes\Impersonation;

use Espo\Modules\LoginAsUser\Classes\Impersonation\RequestToken;
use PHPUnit\Framework\TestCase;

class RequestTokenTest extends TestCase
{
    public function testReadsTheTokenPart(): void
    {
        $this->assertSame('abc123', RequestToken::fromHeader(base64_encode('admin:abc123')));
        $this->assertSame('a:b', RequestToken::fromHeader(base64_encode('admin:a:b')));
    }

    public function testRejectsEverythingElse(): void
    {
        $this->assertNull(RequestToken::fromHeader(null));
        $this->assertNull(RequestToken::fromHeader(''));
        $this->assertNull(RequestToken::fromHeader('***'));
        $this->assertNull(RequestToken::fromHeader(base64_encode('no-colon')));
        $this->assertNull(RequestToken::fromHeader(base64_encode('admin:')));
    }
}
