<?php

namespace Tests\Unit\Http\Requests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Mockery;
use ReflectionClass;
use Tests\TestCase;

use Modules\Admin\Http\Requests\BaseAdminRequest;
use Modules\Admin\Http\Requests\UserRequest;

class BaseAdminRequestTest extends TestCase {
    use RefreshDatabase;

    protected function tearDown(): void {
        Mockery::close();
        parent::tearDown();
    }

    public function test_authorize_returns_false_when_permission_base_empty() {
        $request = new class extends BaseAdminRequest {
            protected string $permissionBase = '';

            public function rules(): array {
                return [];
            }
        };

        $request->setContainer(app());
        $request->setRedirector(app('redirect'));
        $request->setRouteResolver(function () {
            return (new Route(['POST'], '/test', []))->name('users.store');
        });
        $request->initialize(['name' => 'Test'], [], [], [], [], ['REQUEST_METHOD' => 'POST']);

        $this->assertFalse($request->authorize());
    }

    public function test_authorize_checks_create_permission_for_post() {
        $mockUser = Mockery::mock();
        $mockUser->shouldReceive('isSuperAdmin')->andReturn(false);
        $mockUser->shouldReceive('canAccess')->with('users.create')->andReturn(true);

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($mockUser);

        $request = new UserRequest;
        $request->setContainer(app());
        $request->setRedirector(app('redirect'));
        $request->setRouteResolver(function () {
            return (new Route(['POST'], '/users', []))->name('users.store');
        });
        $request->initialize(['name' => 'Test'], [], [], [], [], ['REQUEST_METHOD' => 'POST']);

        $this->assertTrue($request->authorize());
    }

    public function test_authorize_checks_edit_permission_for_put() {
        $mockUser = Mockery::mock();
        $mockUser->shouldReceive('isSuperAdmin')->andReturn(false);
        $mockUser->shouldReceive('canAccess')->with('users.edit')->andReturn(true);

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($mockUser);

        $request = new UserRequest;
        $request->setContainer(app());
        $request->setRedirector(app('redirect'));
        $request->setRouteResolver(function () {
            return (new Route(['PUT'], '/users/1', []))->name('users.update');
        });
        $request->initialize(['name' => 'Test'], [], [], [], [], ['REQUEST_METHOD' => 'PUT']);

        $this->assertTrue($request->authorize());
    }

    public function test_authorize_checks_edit_permission_for_patch() {
        $mockUser = Mockery::mock();
        $mockUser->shouldReceive('isSuperAdmin')->andReturn(false);
        $mockUser->shouldReceive('canAccess')->with('users.edit')->andReturn(true);

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($mockUser);

        $request = new UserRequest;
        $request->setContainer(app());
        $request->setRedirector(app('redirect'));
        $request->setRouteResolver(function () {
            return (new Route(['PATCH'], '/users/1', []))->name('users.update');
        });
        $request->initialize(['name' => 'Test'], [], [], [], [], ['REQUEST_METHOD' => 'PATCH']);

        $this->assertTrue($request->authorize());
    }

    public function test_authorize_returns_false_for_other_methods() {
        $request = new UserRequest;
        $request->setContainer(app());
        $request->setRedirector(app('redirect'));
        $request->setRouteResolver(function () {
            return (new Route(['GET'], '/users', []))->name('users.index');
        });
        $request->initialize([], [], [], [], [], ['REQUEST_METHOD' => 'GET']);

        $this->assertFalse($request->authorize());
    }

    public function test_authorize_denies_when_no_permission() {
        $mockUser = Mockery::mock();
        $mockUser->shouldReceive('isSuperAdmin')->andReturn(false);
        $mockUser->shouldReceive('canAccess')->with('users.create')->andReturn(false);

        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($mockUser);

        $request = new UserRequest;
        $request->setContainer(app());
        $request->setRedirector(app('redirect'));
        $request->setRouteResolver(function () {
            return (new Route(['POST'], '/users', []))->name('users.store');
        });
        $request->initialize(['name' => 'Test'], [], [], [], [], ['REQUEST_METHOD' => 'POST']);

        $this->assertFalse($request->authorize());
    }

    public function test_get_route_id_from_numeric_route_parameter() {
        $request = Mockery::mock(UserRequest::class)->makePartial();
        $request->shouldReceive('route')
            ->with('user')
            ->andReturn('123');
        $request->shouldReceive('route')
            ->with('id')
            ->andReturn(null);

        $reflection = new ReflectionClass($request);
        $method     = $reflection->getMethod('getRouteId');
        $method->setAccessible(true);

        $id = $method->invoke($request, ['user']);
        $this->assertEquals(123, $id);
    }

    public function test_get_route_id_from_model_route_parameter() {
        $mockModel = new class {
            public function getKey() {
                return 456;
            }
        };

        $request = Mockery::mock(UserRequest::class)->makePartial();
        $request->shouldReceive('route')
            ->with('user')
            ->andReturn($mockModel);
        $request->shouldReceive('route')
            ->with('id')
            ->andReturn(null);

        $reflection = new ReflectionClass($request);
        $method     = $reflection->getMethod('getRouteId');
        $method->setAccessible(true);

        $id = $method->invoke($request, ['user']);
        $this->assertEquals(456, $id);
    }

    public function test_get_route_id_falls_back_to_id_parameter() {
        $request = Mockery::mock(UserRequest::class)->makePartial();
        $request->shouldReceive('route')
            ->with('non_existent')
            ->andReturn(null);
        $request->shouldReceive('route')
            ->with('id')
            ->andReturn('789');

        $reflection = new ReflectionClass($request);
        $method     = $reflection->getMethod('getRouteId');
        $method->setAccessible(true);

        $id = $method->invoke($request, ['non_existent']);
        $this->assertEquals(789, $id);
    }

    public function test_get_route_id_returns_zero_when_not_found() {
        $request = Mockery::mock(UserRequest::class)->makePartial();
        $request->shouldReceive('route')
            ->with('non_existent')
            ->andReturn(null);
        $request->shouldReceive('route')
            ->with('id')
            ->andReturn(null);

        $reflection = new ReflectionClass($request);
        $method     = $reflection->getMethod('getRouteId');
        $method->setAccessible(true);

        $id = $method->invoke($request, ['non_existent']);
        $this->assertEquals(0, $id);
    }
}
