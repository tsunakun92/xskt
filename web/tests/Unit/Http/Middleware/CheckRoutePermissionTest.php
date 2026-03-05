<?php

namespace Tests\Unit\Http\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route as RouteFacade;
use Mockery;
use Tests\TestCase;

use App\Http\Middleware\CheckRoutePermission;

class CheckRoutePermissionTest extends TestCase {
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        // Register fake routes for testing
        RouteFacade::middleware('web')->get('/test', function () {
            return 'ok';
        })->name('test.route');
        RouteFacade::middleware('web')->get('/test-ajax', function () {
            return 'ok';
        })->name('test.ajax');
    }

    protected function tearDown(): void {
        // Close Mockery after each test
        Mockery::close();
        parent::tearDown();
    }

    public function test_handle_without_route_name_allows_request() {
        $middleware = new CheckRoutePermission;
        $request    = Request::create('/no-route', 'GET');
        $request->setRouteResolver(function () {
            return (new Route(['GET'], '/no-route', []))->name(null);
        });
        $next = function ($req) {
            return response('next');
        };
        $response = $middleware->handle($request, $next);
        $this->assertEquals('next', $response->getContent());
    }

    public function test_handle_with_ajax_route_allows_request() {
        $middleware = new CheckRoutePermission;
        $request    = Request::create('/test-ajax', 'GET');
        $request->setRouteResolver(function () {
            return (new Route(['GET'], '/test-ajax', []))->name('test.ajax');
        });
        $next = function ($req) {
            return response('next');
        };
        $response = $middleware->handle($request, $next);
        $this->assertEquals('next', $response->getContent());
    }

    public function test_handle_without_permission_aborts_403() {
        $middleware = new CheckRoutePermission;
        $request    = Request::create('/test', 'GET');
        $request->setRouteResolver(function () {
            return (new Route(['GET'], '/test', []))->name('test.route');
        });
        $next = function ($req) {
            return response('next');
        };
        // Mock a user without permission using Mockery
        $mockUser     = Mockery::mock();
        $mockUser->id = 1; // Add id property for LogHandler
        $mockUser->shouldReceive('isSuperAdmin')->andReturn(false);
        $mockUser->shouldReceive('canAccess')->andReturn(false);
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($mockUser);
        // Expect a 403 HttpException
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $middleware->handle($request, $next);
    }

    public function test_handle_with_permission_allows_request() {
        $middleware = new CheckRoutePermission;
        $request    = Request::create('/test', 'GET');
        $request->setRouteResolver(function () {
            return (new Route(['GET'], '/test', []))->name('test.route');
        });
        $next = function ($req) {
            return response('next');
        };
        // Mock a user with permission using Mockery
        $mockUser = Mockery::mock();
        $mockUser->shouldReceive('isSuperAdmin')->andReturn(true);
        $mockUser->shouldReceive('canAccess')->andReturn(true);
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($mockUser);
        $response = $middleware->handle($request, $next);
        $this->assertEquals('next', $response->getContent());
    }

    public function test_handle_with_store_route_maps_to_create_permission() {
        $middleware = new CheckRoutePermission;
        $request    = Request::create('/test', 'POST');
        $request->setRouteResolver(function () {
            return (new Route(['POST'], '/test', []))->name('test.store');
        });
        $next = function ($req) {
            return response('next');
        };
        // Mock a user with create permission
        $mockUser     = Mockery::mock();
        $mockUser->id = 1;
        $mockUser->shouldReceive('isSuperAdmin')->andReturn(false);
        $mockUser->shouldReceive('canAccess')->with('test.create')->andReturn(true);
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($mockUser);
        $response = $middleware->handle($request, $next);
        $this->assertEquals('next', $response->getContent());
    }

    public function test_handle_with_update_route_maps_to_edit_permission() {
        $middleware = new CheckRoutePermission;
        $request    = Request::create('/test/1', 'PUT');
        $request->setRouteResolver(function () {
            return (new Route(['PUT'], '/test/{id}', []))->name('test.update');
        });
        $next = function ($req) {
            return response('next');
        };
        // Mock a user with edit permission
        $mockUser     = Mockery::mock();
        $mockUser->id = 1;
        $mockUser->shouldReceive('isSuperAdmin')->andReturn(false);
        $mockUser->shouldReceive('canAccess')->with('test.edit')->andReturn(true);
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($mockUser);
        $response = $middleware->handle($request, $next);
        $this->assertEquals('next', $response->getContent());
    }

    public function test_handle_with_destroy_route_maps_to_delete_permission() {
        $middleware = new CheckRoutePermission;
        $request    = Request::create('/test/1', 'DELETE');
        $request->setRouteResolver(function () {
            return (new Route(['DELETE'], '/test/{id}', []))->name('test.destroy');
        });
        $next = function ($req) {
            return response('next');
        };
        // Mock a user with delete permission
        $mockUser     = Mockery::mock();
        $mockUser->id = 1;
        $mockUser->shouldReceive('isSuperAdmin')->andReturn(false);
        $mockUser->shouldReceive('canAccess')->with('test.delete')->andReturn(true);
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($mockUser);
        $response = $middleware->handle($request, $next);
        $this->assertEquals('next', $response->getContent());
    }
}
