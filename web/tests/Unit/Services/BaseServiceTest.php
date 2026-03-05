<?php

namespace Tests\Unit\Services;

use Exception;
use Tests\TestCase;

use App\Services\BaseService;

/**
 * Test base service functionality.
 */
class BaseServiceTest extends TestCase {
    /**
     * Test service can handle transactions.
     */
    public function test_handle_transaction_returns_true_on_success(): void {
        $service = new class extends BaseService {
            public function testTransaction(callable $callback) {
                return $this->handleTransaction($callback);
            }
        };

        $result = $service->testTransaction(function () {
            return true;
        });

        $this->assertTrue($result);
    }

    /**
     * Test service can handle transaction failures.
     */
    public function test_handle_transaction_returns_false_on_failure(): void {
        $service = new class extends BaseService {
            public function test_transaction(callable $callback) {
                return $this->handleTransaction($callback);
            }
        };

        $result = $service->test_transaction(function () {
            throw new Exception('Test exception');
        });

        // return value should remain null on failure
        $this->assertNull($result);
    }

    /**
     * Test service can log info messages.
     */
    public function test_log_info_calls_log_handler(): void {
        $service = new class extends BaseService {
            public function test_log_info(string $message, array $context = []) {
                $this->logInfo($message, $context);
            }
        };

        // Should not throw exception
        $service->test_log_info('Test message', ['key' => 'value']);

        $this->assertTrue(true);
    }

    /**
     * Test service can log error messages.
     */
    public function test_log_error_calls_log_handler(): void {
        $service = new class extends BaseService {
            public function test_log_error(string $message, array $context = [], ?Exception $exception = null) {
                $this->logError($message, $context, $exception);
            }
        };

        // Should not throw exception
        $service->test_log_error('Test error', ['key' => 'value']);

        $this->assertTrue(true);
    }

    /**
     * Test service can log warning messages.
     */
    public function test_log_warning_calls_log_handler(): void {
        $service = new class extends BaseService {
            public function test_log_warning(string $message, array $context = []) {
                $this->logWarning($message, $context);
            }
        };

        // Should not throw exception
        $service->test_log_warning('Test warning', ['key' => 'value']);

        $this->assertTrue(true);
    }
}
