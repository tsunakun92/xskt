<?php

namespace Tests\Unit\Utils;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use Modules\Logging\Utils\LogHandler;

class LogHandleTest extends TestCase {
    #[Test]
    public function it_logs_error_with_exception() {
        // Mock Auth to return null (no user)
        Auth::shouldReceive('user')->andReturn(null);

        // Mock LogManager directly - LogHandler uses Log::getFacadeRoot()
        $logManager = Mockery::mock('Illuminate\Log\LogManager');
        $logManager->shouldReceive('log')
            ->once()
            ->with('error', 'Test error message', Mockery::type('array'));

        // Replace the Log facade instance
        $this->app->instance('log', $logManager);
        Log::swap($logManager);

        LogHandler::error('Test error message');
    }

    #[Test]
    public function it_logs_error_with_additional_context() {
        // Mock Auth to return null (no user)
        Auth::shouldReceive('user')->andReturn(null);

        $context = ['additional' => 'info'];

        // Mock LogManager directly
        $logManager = Mockery::mock('Illuminate\Log\LogManager');
        $logManager->shouldReceive('log')
            ->once()
            ->with('error', 'Test error message', Mockery::on(function ($arg) {
                return isset($arg['additional']) && $arg['additional'] === 'info';
            }));

        $this->app->instance('log', $logManager);
        Log::swap($logManager);

        LogHandler::error('Test error message', $context);
    }

    #[Test]
    public function it_logs_info_message() {
        // Mock Auth to return null (no user)
        Auth::shouldReceive('user')->andReturn(null);

        // Mock LogManager directly
        $logManager = Mockery::mock('Illuminate\Log\LogManager');
        $logManager->shouldReceive('log')
            ->once()
            ->with('info', 'Test info message', Mockery::type('array'));

        $this->app->instance('log', $logManager);
        Log::swap($logManager);

        LogHandler::info('Test info message');
    }

    #[Test]
    public function it_logs_warning_message() {
        // Mock Auth to return null (no user)
        Auth::shouldReceive('user')->andReturn(null);

        // Mock LogManager directly
        $logManager = Mockery::mock('Illuminate\Log\LogManager');
        $logManager->shouldReceive('log')
            ->once()
            ->with('warning', 'Test warning message', Mockery::type('array'));

        $this->app->instance('log', $logManager);
        Log::swap($logManager);

        LogHandler::warning('Test warning message');
    }

    #[Test]
    public function it_logs_debug_message_in_local_environment() {
        // Set environment to local
        $this->app['env'] = 'local';

        // Mock Auth to return null (no user)
        Auth::shouldReceive('user')->andReturn(null);

        // Mock LogManager directly
        $logManager = Mockery::mock('Illuminate\Log\LogManager');
        $logManager->shouldReceive('log')
            ->once()
            ->with('debug', 'Test debug message', Mockery::type('array'));

        $this->app->instance('log', $logManager);
        Log::swap($logManager);

        LogHandler::debug('Test debug message');
    }

    #[Test]
    public function it_logs_debug_message_in_production_environment() {
        // Set environment to production
        $this->app['env'] = 'production';

        // Mock Auth to return null (no user)
        Auth::shouldReceive('user')->andReturn(null);

        // LogHandler still logs debug in production (no environment check)
        $logManager = Mockery::mock('Illuminate\Log\LogManager');
        $logManager->shouldReceive('log')
            ->once()
            ->with('debug', 'Test debug message', Mockery::type('array'));

        $this->app->instance('log', $logManager);
        Log::swap($logManager);

        LogHandler::debug('Test debug message');
    }
}
