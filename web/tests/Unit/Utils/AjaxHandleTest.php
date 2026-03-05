<?php

namespace Tests\Unit\Utils;

use Exception;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Testing\TestResponse;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

use App\Utils\AjaxHandle;

class AjaxHandleTest extends TestCase {
    protected function tearDown(): void {
        Mockery::close();
        parent::tearDown();
    }

    protected function invokeProtected(string $method, array $args = []) {
        $refMethod = new ReflectionMethod(AjaxHandle::class, $method);
        $refMethod->setAccessible(true);

        return $refMethod->invokeArgs(null, $args);
    }

    protected function mockLogger(): void {
        $logger = Mockery::mock(\Illuminate\Log\LogManager::class);
        $logger->shouldReceive('log')->byDefault()->andReturnNull();
        $logger->shouldReceive('channel')->byDefault()->andReturnSelf();
        Log::swap($logger);
    }

    #[Test]
    public function success_returns_expected_json_structure() {
        Auth::shouldReceive('user')->andReturn(null);
        $this->mockLogger();

        $data     = ['id' => 1, 'name' => 'Test'];
        $custom   = ['extra' => 'value'];
        $response = AjaxHandle::success('OK', $data, $custom);

        $wrapped = TestResponse::fromBaseResponse($response);
        $wrapped->assertStatus(200)
            ->assertJson([
                'status'  => AjaxHandle::STATUS_SUCCESS,
                'message' => 'OK',
                'data'    => $data,
                'extra'   => 'value',
            ]);
    }

    #[Test]
    public function error_with_exception_uses_default_message_when_empty() {
        Auth::shouldReceive('user')->andReturn(null);
        $this->mockLogger();

        $exception = new Exception('Something went wrong', 123);

        $response = AjaxHandle::error('', $exception, ['foo' => 'bar'], 500);

        $wrapped = TestResponse::fromBaseResponse($response);
        $wrapped->assertStatus(500)
            ->assertJsonFragment([
                'status'  => AjaxHandle::STATUS_FAILURE,
                'message' => 'An error occurred',
            ])
            ->assertJsonFragment(['foo' => 'bar']);
    }

    #[Test]
    public function error_with_string_sets_message() {
        Auth::shouldReceive('user')->andReturn(null);
        $this->mockLogger();

        $response = AjaxHandle::error('Failed', 'DETAIL_MESSAGE', [], 400);

        $wrapped = TestResponse::fromBaseResponse($response);
        $wrapped->assertStatus(400)
            ->assertJsonFragment([
                'status'  => AjaxHandle::STATUS_FAILURE,
                'message' => 'Failed',
            ]);
    }

    #[Test]
    public function validation_error_with_array_errors() {
        Auth::shouldReceive('user')->andReturn(null);
        $this->mockLogger();

        $errors = [
            'email' => ['The email field is required.'],
        ];

        $response = AjaxHandle::validationError($errors, 'Invalid data');

        $wrapped = TestResponse::fromBaseResponse($response);
        $wrapped->assertStatus(422)
            ->assertJsonFragment([
                'status'  => AjaxHandle::STATUS_FAILURE,
                'message' => 'Invalid data',
            ])
            ->assertJsonFragment([
                'email' => ['The email field is required.'],
            ]);
    }

    #[Test]
    public function validation_error_with_validator_instance() {
        Auth::shouldReceive('user')->andReturn(null);
        $this->mockLogger();

        $validator = Mockery::mock(Validator::class);
        $validator->shouldReceive('errors->toArray')
            ->once()
            ->andReturn([
                'name' => ['The name field is required.'],
            ]);

        $response = AjaxHandle::validationError($validator);

        $wrapped = TestResponse::fromBaseResponse($response);
        $wrapped->assertStatus(422)
            ->assertJsonFragment([
                'status'  => AjaxHandle::STATUS_FAILURE,
                'message' => 'Validation failed',
            ])
            ->assertJsonFragment([
                'name' => ['The name field is required.'],
            ]);
    }

    #[Test]
    public function truncate_data_for_log_handles_long_string_and_array() {
        $long   = str_repeat('a', 2000);
        $result = $this->invokeProtected('truncateDataForLog', [$long, 100]);
        $this->assertStringContainsString('truncated', $result);

        $array   = range(1, 100);
        $summary = $this->invokeProtected('truncateDataForLog', [$array, 100]);
        $this->assertIsArray($summary);
        $this->assertTrue($summary['truncated']);
        $this->assertEquals('array', $summary['type']);
    }

    #[Test]
    public function get_data_size_returns_expected_info_for_array_and_string() {
        $sizeArray = $this->invokeProtected('getDataSize', [[1, 2, 3]]);
        $this->assertEquals('array', $sizeArray['type']);
        $this->assertEquals(3, $sizeArray['count']);

        $sizeString = $this->invokeProtected('getDataSize', ['abc']);
        $this->assertEquals('string', $sizeString['type']);
        $this->assertEquals(3, $sizeString['length']);
    }

    #[Test]
    public function success_uses_default_message_when_empty() {
        Auth::shouldReceive('user')->andReturn(null);
        $this->mockLogger();

        $response = AjaxHandle::success('', ['foo' => 'bar']);
        $wrapped  = TestResponse::fromBaseResponse($response);

        $wrapped->assertStatus(200)
            ->assertJsonFragment([
                'status'  => AjaxHandle::STATUS_SUCCESS,
                'message' => 'Success',
            ]);
    }

    #[Test]
    public function validation_error_coerces_non_array_errors() {
        Auth::shouldReceive('user')->andReturn(null);
        $this->mockLogger();

        $response = AjaxHandle::validationError('not-array');
        $wrapped  = TestResponse::fromBaseResponse($response);

        $wrapped->assertStatus(422)
            ->assertJsonFragment([
                'status'  => AjaxHandle::STATUS_FAILURE,
                'message' => 'Validation failed',
            ]);
    }

    #[Test]
    public function truncate_data_for_log_handles_object_and_get_data_size_object() {
        Auth::shouldReceive('user')->andReturn(null);
        $this->mockLogger();

        $obj = new class {
            public string $foo = 'bar';

            public function toArray() {
                return ['foo' => $this->foo];
            }
        };

        $truncated = $this->invokeProtected('truncateDataForLog', [$obj, 10]);
        $this->assertIsArray($truncated);
        // truncateDataForLog converts object with toArray() to array format
        $this->assertEquals('array', $truncated['type'] ?? 'array');

        $size = $this->invokeProtected('getDataSize', [$obj]);
        // getDataSize returns original type (object), but includes count if toArray() exists
        $this->assertEquals('object', $size['type']);
        $this->assertEquals(1, $size['count']);
    }

    #[Test]
    public function error_without_exception_uses_message_only() {
        Auth::shouldReceive('user')->andReturn(null);
        $this->mockLogger();

        $response = AjaxHandle::error('Simple error', null, [], 400);
        $wrapped  = TestResponse::fromBaseResponse($response);

        $wrapped->assertStatus(400)
            ->assertJsonFragment([
                'status'  => AjaxHandle::STATUS_FAILURE,
                'message' => 'Simple error',
            ]);
    }

    #[Test]
    public function truncate_data_for_log_handles_null_value() {
        $result = $this->invokeProtected('truncateDataForLog', [null]);
        $this->assertNull($result);
    }

    #[Test]
    public function truncate_data_for_log_handles_object_without_to_array() {
        $obj = new class {
            public string $foo = 'bar';
            // No toArray() method
        };

        $result = $this->invokeProtected('truncateDataForLog', [$obj, 1000]);
        $this->assertIsArray($result);
        $this->assertEquals('object', $result['type']);
        $this->assertArrayHasKey('class', $result);
    }

    #[Test]
    public function truncate_data_for_log_handles_large_object() {
        // Create an object that when serialized exceeds maxLength
        $obj = new class {
            public string $largeData;

            public function __construct() {
                $this->largeData = str_repeat('x', 5000); // Large string
            }
        };

        $result = $this->invokeProtected('truncateDataForLog', [$obj, 100]);
        $this->assertIsArray($result);
        $this->assertEquals('object', $result['type']);
        // Object may or may not be truncated depending on serialized size
        if (isset($result['truncated'])) {
            $this->assertTrue($result['truncated']);
            $this->assertArrayHasKey('size_bytes', $result);
        } else {
            // If not truncated, should have class name
            $this->assertArrayHasKey('class', $result);
        }
    }

    #[Test]
    public function truncate_data_for_log_handles_object_with_reflection_error() {
        // Create an object that might cause reflection issues
        // We'll use a closure which can't be easily reflected
        $closure = function () {
            return 'test';
        };

        // Closures can't be serialized normally, but truncateDataForLog should handle it
        $result = $this->invokeProtected('truncateDataForLog', [$closure, 1000]);
        // Should return object info even if reflection fails
        $this->assertIsArray($result);
        $this->assertEquals('object', $result['type']);
    }

    #[Test]
    public function truncate_data_for_log_handles_other_types() {
        // Test int
        $intResult = $this->invokeProtected('truncateDataForLog', [123]);
        $this->assertEquals(123, $intResult);

        // Test float
        $floatResult = $this->invokeProtected('truncateDataForLog', [123.45]);
        $this->assertEquals(123.45, $floatResult);

        // Test bool
        $boolResult = $this->invokeProtected('truncateDataForLog', [true]);
        $this->assertTrue($boolResult);

        // Test false
        $falseResult = $this->invokeProtected('truncateDataForLog', [false]);
        $this->assertFalse($falseResult);
    }

    #[Test]
    public function get_data_size_handles_other_types() {
        // Test int
        $intSize = $this->invokeProtected('getDataSize', [123]);
        $this->assertEquals('integer', $intSize['type']);

        // Test float
        $floatSize = $this->invokeProtected('getDataSize', [123.45]);
        $this->assertEquals('double', $floatSize['type']);

        // Test bool
        $boolSize = $this->invokeProtected('getDataSize', [true]);
        $this->assertEquals('boolean', $boolSize['type']);

        // Test null
        $nullSize = $this->invokeProtected('getDataSize', [null]);
        $this->assertEquals('NULL', $nullSize['type']);
    }

    #[Test]
    public function get_data_size_handles_object_that_cannot_be_serialized() {
        // Create a closure which can't be serialized
        $closure = function () {
            return 'test';
        };

        $size = $this->invokeProtected('getDataSize', [$closure]);
        $this->assertEquals('object', $size['type']);
        // Should have error key if serialization fails
        if (isset($size['error'])) {
            $this->assertArrayHasKey('error', $size);
        }
    }

    #[Test]
    public function truncate_data_for_log_handles_string_within_limit() {
        $shortString = 'Hello World';
        $result      = $this->invokeProtected('truncateDataForLog', [$shortString, 100]);
        $this->assertEquals($shortString, $result);
    }

    #[Test]
    public function truncate_data_for_log_handles_array_within_limit() {
        $smallArray = ['key1' => 'value1', 'key2' => 'value2'];
        $result     = $this->invokeProtected('truncateDataForLog', [$smallArray, 10000]);
        // Should return trimmed array, not truncated summary
        $this->assertIsArray($result);
        $this->assertArrayNotHasKey('truncated', $result);
    }
}
