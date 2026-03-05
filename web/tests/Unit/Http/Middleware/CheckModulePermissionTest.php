<?php

namespace Tests\Unit\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use App\Http\Middleware\CheckModulePermission;

class CheckModulePermissionTest extends TestCase {
    /**
     * Test that request is allowed when user has module permission.
     *
     * @return void
     */
    #[Test]
    public function it_allows_request_when_user_has_module_permission(): void {
        $middleware = new CheckModulePermission;
        $request    = Request::create('/admin', 'GET');

        $mockUser     = Mockery::mock();
        $mockUser->id = 1;
        $mockUser->shouldReceive('isSuperAdmin')->andReturn(false);
        $mockUser->shouldReceive('canAccess')->with('admin.module')->andReturn(true);

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($mockUser);

        $next = function ($req) {
            return response('next');
        };

        $response = $middleware->handle($request, $next, 'admin');

        $this->assertEquals('next', $response->getContent());
    }

    /**
     * Test that request is allowed when user is super admin.
     *
     * @return void
     */
    #[Test]
    public function it_allows_request_when_user_is_super_admin(): void {
        $middleware = new CheckModulePermission;
        $request    = Request::create('/admin', 'GET');

        $mockUser     = Mockery::mock();
        $mockUser->id = 1;
        $mockUser->shouldReceive('isSuperAdmin')->andReturn(true);
        $mockUser->shouldNotReceive('canAccess');

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($mockUser);

        $next = function ($req) {
            return response('next');
        };

        $response = $middleware->handle($request, $next, 'admin');

        $this->assertEquals('next', $response->getContent());
    }

    /**
     * Test that request is aborted with 403 when user has no module permission.
     *
     * @return void
     */
    #[Test]
    public function it_aborts_with_403_when_user_has_no_module_permission(): void {
        $middleware = new CheckModulePermission;
        $request    = Request::create('/admin', 'GET');

        $mockUser     = Mockery::mock();
        $mockUser->id = 1;
        $mockUser->shouldReceive('isSuperAdmin')->andReturn(false);
        $mockUser->shouldReceive('canAccess')->with('admin.module')->andReturn(false);

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($mockUser);

        $next = function ($req) {
            return response('next');
        };

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $middleware->handle($request, $next, 'admin');
    }
}
