<?php

namespace Tests\Unit\Database;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class ModelTest extends TestCase
{
    public function testModelUsesConfiguredTable(): void
    {
        $user = new User();

        $this->assertSame('users', $user->getTable());
    }

    public function testFillAssignsAllowedAttributes(): void
    {
        $user = new User([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'active' => true,
        ]);

        $this->assertSame('John Doe', $user->name);
        $this->assertSame('john@example.com', $user->email);
    }

    public function testAttributesAreCastWhenRead(): void
    {
        $user = new User(['active' => '1']);

        $this->assertTrue($user->active);
    }

    public function testModelConvertsVisibleAttributesToArray(): void
    {
        $user = new User([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $this->assertSame([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ], $user->toArray());
    }

    public function testAttributesOutsideFillableListAreIgnored(): void
    {
        $user = new User([
            'name' => 'Jane Doe',
            'admin' => true,
        ]);

        $this->assertNull($user->admin);
        $this->assertSame(['name' => 'Jane Doe'], $user->toArray());
    }
}
