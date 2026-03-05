<?php

namespace Modules\Logging\Tests\Unit;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Mockery;
use ReflectionClass;
use Tests\TestCase;

use Modules\Logging\Utils\LogHandler;

class LogHandlerTest extends TestCase {
    protected function tearDown(): void {
        Mockery::close();
        parent::tearDown();
    }

    protected function mockRootLogger(string $level, string $message, ?callable $contextMatcher = null): void {
        // Mock Auth::user() to return null to avoid user context issues
        Auth::shouldReceive('user')->andReturn(null);

        // Create a logger mock that implements the log method
        $logger = Mockery::mock();
        $logger->shouldReceive('log')
            ->once()
            ->with($level, $message, $contextMatcher ? Mockery::on($contextMatcher) : Mockery::type('array'));

        // Mock Log::getFacadeRoot() to return our logger mock
        Log::shouldReceive('getFacadeRoot')
            ->once()
            ->andReturn($logger);
    }

    public function test_debug_logging() {
        Auth::shouldReceive('user')->andReturn(null);

        // Mock LogManager instance that getFacadeRoot returns
        $logManager = Mockery::mock(\Illuminate\Log\LogManager::class);
        $logManager->shouldReceive('log')
            ->once()
            ->with('debug', 'Test debug message', Mockery::type('array'));

        Log::swap($logManager);

        LogHandler::debug('Test debug message');

        $this->assertTrue(true); // Assert that no exception was thrown
    }

    public function test_info_logging() {
        Auth::shouldReceive('user')->andReturn(null);

        $logManager = Mockery::mock(\Illuminate\Log\LogManager::class);
        $logManager->shouldReceive('log')
            ->once()
            ->with('info', 'Test info message', Mockery::type('array'));

        Log::swap($logManager);

        LogHandler::info('Test info message');

        $this->assertTrue(true);
    }

    public function test_warning_logging() {
        Auth::shouldReceive('user')->andReturn(null);

        $logManager = Mockery::mock(\Illuminate\Log\LogManager::class);
        $logManager->shouldReceive('log')
            ->once()
            ->with('warning', 'Test warning message', Mockery::type('array'));

        Log::swap($logManager);

        LogHandler::warning('Test warning message');

        $this->assertTrue(true);
    }

    public function test_error_logging() {
        Auth::shouldReceive('user')->andReturn(null);

        $logManager = Mockery::mock(\Illuminate\Log\LogManager::class);
        $logManager->shouldReceive('log')
            ->once()
            ->with('error', 'Test error message', Mockery::type('array'));

        Log::swap($logManager);

        LogHandler::error('Test error message');

        $this->assertTrue(true);
    }

    public function test_critical_logging() {
        Auth::shouldReceive('user')->andReturn(null);

        $logManager = Mockery::mock(\Illuminate\Log\LogManager::class);
        $logManager->shouldReceive('log')
            ->once()
            ->with('critical', 'Test critical message', Mockery::type('array'));

        Log::swap($logManager);

        LogHandler::critical('Test critical message');

        $this->assertTrue(true);
    }

    public function test_cache_logging() {
        Auth::shouldReceive('user')->andReturn(null);

        $logChannel = Mockery::mock();
        $logChannel->shouldReceive('log')
            ->once()
            ->with('info', 'Cache cleared', Mockery::type('array'));

        $logManager = Mockery::mock(\Illuminate\Log\LogManager::class);
        $logManager->shouldReceive('channel')
            ->once()
            ->with('cache')
            ->andReturn($logChannel);
        Log::swap($logManager);

        LogHandler::cache('Cache cleared');

        $this->assertTrue(true);
    }

    public function test_database_error_logging() {
        Auth::shouldReceive('user')->andReturn(null);

        $exception = new Exception('Database connection failed');

        $logChannel = Mockery::mock();
        $logChannel->shouldReceive('log')
            ->once()
            ->with('error', 'Database error occurred', Mockery::on(function ($context) {
                return isset($context['exception']) && is_array($context['exception']);
            }));

        $logManager = Mockery::mock(\Illuminate\Log\LogManager::class);
        $logManager->shouldReceive('channel')
            ->once()
            ->with('database')
            ->andReturn($logChannel);
        Log::swap($logManager);

        LogHandler::databaseError('Database error occurred', $exception);

        $this->assertTrue(true);
    }

    public function test_logging_includes_user_context_when_authenticated() {
        $user = new class {
            public int $id = 1;

            public string $username = 'testuser';
        };

        $logManager = Mockery::mock(\Illuminate\Log\LogManager::class);
        $logManager->shouldReceive('log')
            ->once()
            ->with('info', 'Test message', Mockery::on(function ($context) {
                return isset($context['user']['id']) && $context['user']['id'] === 1;
            }));

        Auth::shouldReceive('user')->andReturn($user);

        Log::swap($logManager);

        LogHandler::info('Test message');

        $this->assertTrue(true);
    }

    public function test_logging_with_custom_channel() {
        Auth::shouldReceive('user')->andReturn(null);

        $logChannel = Mockery::mock();
        $logChannel->shouldReceive('log')
            ->once()
            ->with('info', 'Custom channel message', Mockery::type('array'));

        $logManager = Mockery::mock(\Illuminate\Log\LogManager::class);
        $logManager->shouldReceive('channel')
            ->once()
            ->with('custom')
            ->andReturn($logChannel);
        Log::swap($logManager);

        LogHandler::info('Custom channel message', [], 'custom');

        $this->assertTrue(true);
    }

    public function test_logging_includes_request_context_when_available() {
        Auth::shouldReceive('user')->andReturn(null);

        // Mock app to return false for runningInConsole
        $app = Mockery::mock(\Illuminate\Contracts\Foundation\Application::class);
        $app->shouldReceive('runningInConsole')->andReturn(false);
        $this->app->instance('app', $app);

        // Mock request
        $request = Mockery::mock(\Illuminate\Http\Request::class);
        $request->shouldReceive('method')->andReturn('GET');
        $request->shouldReceive('path')->andReturn('/test');
        $request->shouldReceive('ip')->andReturn('127.0.0.1');
        $request->shouldReceive('setUserResolver')->andReturnSelf();

        // Mock request() helper
        $this->app->instance('request', $request);

        $logManager = Mockery::mock(\Illuminate\Log\LogManager::class);
        $logManager->shouldReceive('log')
            ->once()
            ->with('info', 'Test with request', Mockery::type('array'));

        Log::swap($logManager);

        LogHandler::info('Test with request');

        $this->assertTrue(true);
    }

    public function test_logging_includes_console_context() {
        Auth::shouldReceive('user')->andReturn(null);

        // Mock app to return true for runningInConsole
        $app = Mockery::mock(\Illuminate\Contracts\Foundation\Application::class);
        $app->shouldReceive('runningInConsole')->andReturn(true);
        $this->app->instance('app', $app);

        $logManager = Mockery::mock(\Illuminate\Log\LogManager::class);
        $logManager->shouldReceive('log')
            ->once()
            ->with('info', 'Test console', Mockery::on(function ($context) {
                return isset($context['environment']) && $context['environment'] === 'console';
            }));

        Log::swap($logManager);

        LogHandler::info('Test console');

        $this->assertTrue(true);
    }

    public function test_logging_handles_null_request() {
        Auth::shouldReceive('user')->andReturn(null);

        // Mock app to return false for runningInConsole
        $app = Mockery::mock(\Illuminate\Contracts\Foundation\Application::class);
        $app->shouldReceive('runningInConsole')->andReturn(false);
        $this->app->instance('app', $app);

        // Don't bind request, so request() returns null
        $this->app->forgetInstance('request');

        $logManager = Mockery::mock(\Illuminate\Log\LogManager::class);
        $logManager->shouldReceive('log')
            ->once()
            ->with('info', 'Test null request', Mockery::type('array'));

        Log::swap($logManager);

        LogHandler::info('Test null request');

        $this->assertTrue(true);
    }

    public function test_database_error_with_null_exception() {
        Auth::shouldReceive('user')->andReturn(null);

        // Mock app to return true for runningInConsole
        $app = Mockery::mock(\Illuminate\Contracts\Foundation\Application::class);
        $app->shouldReceive('runningInConsole')->andReturn(true);
        $this->app->instance('app', $app);

        $logChannel = Mockery::mock();
        $logChannel->shouldReceive('log')
            ->once()
            ->with('error', 'Database error without exception', Mockery::type('array'));

        $logManager = Mockery::mock(\Illuminate\Log\LogManager::class);
        $logManager->shouldReceive('channel')
            ->once()
            ->with('database')
            ->andReturn($logChannel);
        Log::swap($logManager);

        LogHandler::databaseError('Database error without exception', null);

        $this->assertTrue(true);
    }

    public function test_sanitize_exception_message_removes_sql() {
        $reflection = new ReflectionClass(LogHandler::class);
        $method     = $reflection->getMethod('sanitizeExceptionMessage');
        $method->setAccessible(true);

        $messageWithSql = 'Some error message (SQL: SELECT * FROM users WHERE id = 1)';
        $sanitized      = $method->invoke(null, $messageWithSql);

        $this->assertEquals('Some error message', $sanitized);
        $this->assertStringNotContainsString('SQL:', $sanitized);
    }

    public function test_sanitize_exception_message_handles_message_without_sql() {
        $reflection = new ReflectionClass(LogHandler::class);
        $method     = $reflection->getMethod('sanitizeExceptionMessage');
        $method->setAccessible(true);

        $messageWithoutSql = 'Simple error message';
        $sanitized         = $method->invoke(null, $messageWithoutSql);

        $this->assertEquals('Simple error message', $sanitized);
    }

    public function test_database_error_sanitizes_exception_message() {
        Auth::shouldReceive('user')->andReturn(null);

        $exception = new Exception('Database error (SQL: SELECT * FROM users)');

        $logChannel = Mockery::mock();
        $logChannel->shouldReceive('log')
            ->once()
            ->with('error', 'Database error occurred', Mockery::on(function ($context) {
                return isset($context['exception']['message']) &&
                       $context['exception']['message'] === 'Database error' &&
                       !str_contains($context['exception']['message'], 'SQL:');
            }));

        $logManager = Mockery::mock(\Illuminate\Log\LogManager::class);
        $logManager->shouldReceive('channel')
            ->once()
            ->with('database')
            ->andReturn($logChannel);
        Log::swap($logManager);

        LogHandler::databaseError('Database error occurred', $exception);

        $this->assertTrue(true);
    }
}
