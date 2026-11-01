<?php

declare(strict_types=1);

namespace tests\unit\Espo\Modules\LoginAsUser\Classes\Impersonation;

use Espo\Entities\User;
use Espo\Modules\LoginAsUser\Classes\Impersonation\TargetPolicy;
use PHPUnit\Framework\TestCase;

class TargetPolicyTest extends TestCase
{
    /**
     * @param array<string, bool> $flags
     */
    private function user(string $id, array $flags = []): User
    {
        $flags += ['admin' => false, 'active' => true, 'system' => false, 'api' => false, 'super' => false];

        return new class ($id, $flags) extends User {
            /**
             * @param array<string, bool> $flags
             */
            public function __construct(private string $fakeId, private array $flags)
            {}

            public function getId(): string
            {
                return $this->fakeId;
            }

            public function isAdmin(): bool
            {
                return $this->flags['admin'] || $this->flags['super'];
            }

            public function isActive(): bool
            {
                return $this->flags['active'];
            }

            public function isSystem(): bool
            {
                return $this->flags['system'];
            }

            public function isApi(): bool
            {
                return $this->flags['api'];
            }

            public function isSuperAdmin(): bool
            {
                return $this->flags['super'];
            }
        };
    }

    public function testAdminMayActAsRegularUser(): void
    {
        $this->assertTrue((new TargetPolicy())->isAllowed($this->user('a', ['admin' => true]), $this->user('u')));
    }

    public function testNonAdminMayNot(): void
    {
        $this->assertFalse((new TargetPolicy())->isAllowed($this->user('a'), $this->user('u')));
    }

    public function testInactiveAdminMayNot(): void
    {
        $actor = $this->user('a', ['admin' => true, 'active' => false]);

        $this->assertFalse((new TargetPolicy())->isAllowed($actor, $this->user('u')));
    }

    public function testNotAsSelf(): void
    {
        $this->assertFalse((new TargetPolicy())->isAllowed($this->user('a', ['admin' => true]), $this->user('a')));
    }

    public function testNotAsInactiveSystemOrApiUsers(): void
    {
        $actor = $this->user('a', ['admin' => true]);
        $policy = new TargetPolicy();

        $this->assertFalse($policy->isAllowed($actor, $this->user('u', ['active' => false])));
        $this->assertFalse($policy->isAllowed($actor, $this->user('s', ['system' => true])));
        $this->assertFalse($policy->isAllowed($actor, $this->user('k', ['api' => true])));
    }

    public function testSuperAdminOnlyForSuperAdmin(): void
    {
        $policy = new TargetPolicy();
        $super = $this->user('s', ['super' => true]);

        $this->assertFalse($policy->isAllowed($this->user('a', ['admin' => true]), $super));
        $this->assertTrue($policy->isAllowed($this->user('b', ['super' => true]), $this->user('c', ['super' => true])));
    }
}
