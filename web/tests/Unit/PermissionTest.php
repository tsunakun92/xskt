<?php

namespace Tests\Unit;

use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PermissionTest extends TestCase {
    private $authManager;

    protected function setUp(): void {
        parent::setUp();
        $this->authManager = Mockery::mock(\Illuminate\Auth\AuthManager::class);
        app()->instance('auth', $this->authManager);
    }

    #[Test]
    public function test_can_access_unauthenticated() {
        $this->authManager->shouldReceive('check')
            ->once()
            ->andReturn(false);

        $this->assertFalse(can_access('users.index'));
    }

    #[Test]
    public function test_can_access_super_admin() {
        $superAdmin = Mockery::mock();
        $superAdmin->shouldReceive('isSuperAdmin')->once()->andReturn(true);
        $superAdmin->shouldReceive('canAccess')->never();

        $this->authManager->shouldReceive('check')
            ->once()
            ->andReturn(true);
        $this->authManager->shouldReceive('user')
            ->once()
            ->andReturn($superAdmin);

        $this->assertTrue(can_access('any.permission'));
    }

    #[Test]
    public function test_can_access_regular_user() {
        $regularUser = Mockery::mock();
        $regularUser->shouldReceive('isSuperAdmin')->once()->andReturn(false);
        $regularUser->shouldReceive('canAccess')
            ->with('users.index')
            ->once()
            ->andReturn(true);

        $this->authManager->shouldReceive('check')
            ->once()
            ->andReturn(true);
        $this->authManager->shouldReceive('user')
            ->once()
            ->andReturn($regularUser);

        $this->assertTrue(can_access('users.index'));
    }

    #[Test]
    public function test_can_access_denied() {
        $deniedUser = Mockery::mock();
        $deniedUser->shouldReceive('isSuperAdmin')->once()->andReturn(false);
        $deniedUser->shouldReceive('canAccess')
            ->with('admin.access')
            ->once()
            ->andReturn(false);

        $this->authManager->shouldReceive('check')
            ->once()
            ->andReturn(true);
        $this->authManager->shouldReceive('user')
            ->once()
            ->andReturn($deniedUser);

        $this->assertFalse(can_access('admin.access'));
    }

    protected function tearDown(): void {
        Mockery::close();
        parent::tearDown();
    }
}
