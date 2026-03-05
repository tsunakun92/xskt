<?php

namespace Tests\Unit\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use App\Http\Requests\Auth\LoginRequest;
use Modules\Admin\Models\User;

class LoginRequestTest extends TestCase {
    protected LoginRequest $request;

    protected function setUp(): void {
        parent::setUp();
        $this->request = new LoginRequest;
    }

    #[Test]
    public function it_is_always_authorized() {
        $this->assertTrue($this->request->authorize());
    }

    #[Test]
    public function it_has_required_validation_rules() {
        $rules = $this->request->rules();

        $this->assertArrayHasKey('username', $rules);
        $this->assertArrayHasKey('password', $rules);
        $this->assertContains('required', $rules['username']);
        $this->assertContains('string', $rules['username']);
        $this->assertContains('required', $rules['password']);
        $this->assertContains('string', $rules['password']);
    }

    #[Test]
    public function it_throws_validation_exception_when_credentials_are_invalid() {
        $this->expectException(ValidationException::class);

        $request = $this->createRequest(['username' => 'test', 'password' => 'wrong']);

        RateLimiter::shouldReceive('tooManyAttempts')
            ->once()
            ->andReturn(false);

        Auth::shouldReceive('attempt')
            ->once()
            ->with(['username' => 'test', 'password' => 'wrong', 'status' => User::STATUS_ACTIVE], false)
            ->andReturn(false);

        RateLimiter::shouldReceive('hit')
            ->once()
            ->with($request->throttleKey());

        $request->authenticate();
    }

    #[Test]
    public function it_clears_rate_limiter_on_successful_authentication() {
        $request = $this->createRequest(['username' => 'test', 'password' => 'correct']);

        RateLimiter::shouldReceive('tooManyAttempts')
            ->once()
            ->andReturn(false);

        Auth::shouldReceive('attempt')
            ->once()
            ->with(['username' => 'test', 'password' => 'correct', 'status' => User::STATUS_ACTIVE], false)
            ->andReturn(true);

        RateLimiter::shouldReceive('clear')
            ->once()
            ->with($request->throttleKey());

        $request->authenticate();
    }

    #[Test]
    public function it_throws_validation_exception_when_rate_limited() {
        Event::fake();

        $this->expectException(ValidationException::class);

        RateLimiter::shouldReceive('tooManyAttempts')
            ->once()
            ->andReturn(true);

        RateLimiter::shouldReceive('availableIn')
            ->once()
            ->andReturn(60);

        $this->request->ensureIsNotRateLimited();

        Event::assertDispatched(Lockout::class);
    }

    #[Test]
    public function it_generates_correct_throttle_key() {
        $request = $this->createRequest(['username' => 'TEST_USER']);
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $expectedKey = 'test_user|127.0.0.1';
        $this->assertEquals($expectedKey, $request->throttleKey());
    }

    protected function createRequest(array $data = []): LoginRequest {
        return new LoginRequest($data);
    }
}
