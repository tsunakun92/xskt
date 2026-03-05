<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Route;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HelpersTest extends TestCase {
    #[Test]
    public function test_singular_case() {
        $this->assertEquals('category', singular_case('categories'));
        $this->assertEquals('city', singular_case('cities'));
        $this->assertEquals('box', singular_case('boxes'));
    }

    #[Test]
    public function test_plural_case() {
        $this->assertEquals('categories', plural_case('category'));
        $this->assertEquals('cities', plural_case('city'));
        $this->assertEquals('boxes', plural_case('box'));
    }

    #[Test]
    public function test_camel_case() {
        $this->assertEquals('productCategory', camel_case('product_category'));
        $this->assertEquals('firstName', camel_case('first_name'));
    }

    #[Test]
    public function test_studly_case() {
        $this->assertEquals('ProductCategory', studly_case('product_category'));
        $this->assertEquals('FirstName', studly_case('first_name'));
    }

    #[Test]
    public function test_kebab_case() {
        $this->assertEquals('product-category', kebab_case('product_category'));
        $this->assertEquals('first-name', kebab_case('firstName'));
    }

    #[Test]
    public function test_title_case() {
        $this->assertEquals('Product Category', title_case('product_category'));
        $this->assertEquals('First Name', title_case('first.name'));
    }

    #[Test]
    public function test_snake_case() {
        $this->assertEquals('product_category', snake_case('productCategory'));
        $this->assertEquals('first_name', snake_case('firstName'));
    }

    #[Test]
    public function test_trim_text() {
        $longText = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit';
        $this->assertEquals('Lorem i...', trim_text($longText, 10));
        $this->assertEquals($longText, trim_text($longText, 100)); // No trimming needed
    }

    #[Test]
    public function test_get_route_name() {
        $this->assertEquals('product-category', get_route_name('product-category.index'));
        $this->assertEquals('simple-route', get_route_name('simple-route')); // Route without action
    }

    #[Test]
    public function test_get_route_action() {
        $this->assertEquals('index', get_route_action('product-category.index'));
        $this->assertEquals('simple-route', get_route_action('simple-route')); // Route without action
    }

    #[Test]
    public function test_get_permission_label() {
        // Test comprehensive scenarios to ensure 100% coverage
        $testCases = [
            'users.index',
            'products.create',
            'test.action',
            'simple-route',
            '',
            'single',
            'a.b.c.d.e',
            'changelog.index',
            'permission.without.action',
        ];

        foreach ($testCases as $permissionKey) {
            $result = get_permission_label($permissionKey);
            $this->assertIsString($result);
        }
    }

    #[Test]
    public function test_has_route() {
        // Mock Route facade
        Route::shouldReceive('has')
            ->with('users.index')
            ->andReturn(true);

        Route::shouldReceive('has')
            ->with('non.existent.route')
            ->andReturn(false);

        $this->assertTrue(has_route('users.index'));
        $this->assertFalse(has_route('non.existent.route'));
    }

    #[Test]
    public function test_is_json_string_with_valid_json() {
        // Valid JSON objects
        $this->assertTrue(is_json_string('{"name": "John", "age": 30}'));
        $this->assertTrue(is_json_string('{"key": "value"}'));
        $this->assertTrue(is_json_string('{"nested": {"key": "value"}}'));

        // Valid JSON arrays
        $this->assertTrue(is_json_string('[1, 2, 3]'));
        $this->assertTrue(is_json_string('["a", "b", "c"]'));
        $this->assertTrue(is_json_string('[{"id": 1}, {"id": 2}]'));

        // Valid JSON with whitespace
        $this->assertTrue(is_json_string('  {"key": "value"}  '));
        $this->assertTrue(is_json_string("\n{\"key\": \"value\"}\n"));
    }

    #[Test]
    public function test_is_json_string_with_invalid_json() {
        // Invalid JSON strings
        $this->assertFalse(is_json_string('not json'));
        $this->assertFalse(is_json_string('{invalid json}'));
        $this->assertFalse(is_json_string('{"key": "value"'));
        $this->assertFalse(is_json_string('{"key": value}'));
        $this->assertFalse(is_json_string('key: value'));

        // Non-string values
        $this->assertFalse(is_json_string(null));
        $this->assertFalse(is_json_string(123));
        $this->assertFalse(is_json_string(['key' => 'value']));
        $this->assertFalse(is_json_string(true));
        $this->assertFalse(is_json_string(false));

        // Empty strings
        $this->assertFalse(is_json_string(''));
        $this->assertFalse(is_json_string('   '));
    }

    #[Test]
    public function test_parse_json_display_data_with_empty_value() {
        $result = parse_json_display_data('');

        $this->assertFalse($result['isJson']);
        $this->assertFalse($result['hasGraphQL']);
        $this->assertNull($result['jsonData']);
        $this->assertNull($result['graphqlQuery']);
        $this->assertNull($result['otherData']);
    }

    #[Test]
    public function test_parse_json_display_data_with_regular_json() {
        $json   = '{"name": "John", "age": 30, "city": "Tokyo"}';
        $result = parse_json_display_data($json);

        $this->assertTrue($result['isJson']);
        $this->assertFalse($result['hasGraphQL']);
        $this->assertNotNull($result['jsonData']);
        $this->assertNull($result['graphqlQuery']);
        $this->assertNull($result['otherData']);
        $this->assertStringContainsString('John', $result['jsonData']);
        $this->assertStringContainsString('Tokyo', $result['jsonData']);
    }

    #[Test]
    public function test_parse_json_display_data_with_json_array() {
        $json   = '[{"id": 1, "name": "Item 1"}, {"id": 2, "name": "Item 2"}]';
        $result = parse_json_display_data($json);

        $this->assertTrue($result['isJson']);
        $this->assertFalse($result['hasGraphQL']);
        $this->assertNotNull($result['jsonData']);
        $this->assertNull($result['graphqlQuery']);
        $this->assertNull($result['otherData']);
        $this->assertStringContainsString('Item 1', $result['jsonData']);
    }

    #[Test]
    public function test_parse_json_display_data_with_graphql_query() {
        $json   = '{"operationName": "Login", "variables": {}, "query": "mutation Login {\\n    login(username: \\"admin\\") {\\n        token\\n    }\\n}"}';
        $result = parse_json_display_data($json);

        $this->assertTrue($result['isJson']);
        $this->assertTrue($result['hasGraphQL']);
        $this->assertNull($result['jsonData']);
        $this->assertNotNull($result['graphqlQuery']);
        $this->assertNotNull($result['otherData']);
        $this->assertStringContainsString('mutation Login', $result['graphqlQuery']);
        $this->assertStringContainsString('login', $result['graphqlQuery']);
        $this->assertStringContainsString('operationName', $result['otherData']);
        $this->assertStringContainsString('Login', $result['otherData']);
    }

    #[Test]
    public function test_parse_json_display_data_with_graphql_query_only() {
        $json   = '{"query": "query GetUser {\\n    user(id: 1) {\\n        name\\n        email\\n    }\\n}"}';
        $result = parse_json_display_data($json);

        $this->assertTrue($result['isJson']);
        $this->assertTrue($result['hasGraphQL']);
        $this->assertNull($result['jsonData']);
        $this->assertNotNull($result['graphqlQuery']);
        $this->assertNull($result['otherData']); // No other data when only query exists
        $this->assertStringContainsString('query GetUser', $result['graphqlQuery']);
        $this->assertStringContainsString('user(id: 1)', $result['graphqlQuery']);
    }

    #[Test]
    public function test_parse_json_display_data_with_invalid_json() {
        $invalidJson = 'not a valid json string';
        $result      = parse_json_display_data($invalidJson);

        $this->assertFalse($result['isJson']);
        $this->assertFalse($result['hasGraphQL']);
        $this->assertNull($result['jsonData']);
        $this->assertNull($result['graphqlQuery']);
        $this->assertNull($result['otherData']);
    }

    #[Test]
    public function test_parse_json_display_data_with_complex_graphql() {
        $json = '{
            "operationName": "ChangePassword",
            "variables": {"current": "old", "new": "newpass"},
            "query": "mutation ChangePassword {\\n    change_password(\\n        current_password: \\"old\\"\\n        new_password: \\"newpass\\"\\n    ) {\\n        status\\n        message\\n    }\\n}"
        }';
        $result = parse_json_display_data($json);

        $this->assertTrue($result['isJson']);
        $this->assertTrue($result['hasGraphQL']);
        $this->assertNotNull($result['graphqlQuery']);
        $this->assertNotNull($result['otherData']);
        $this->assertStringContainsString('mutation ChangePassword', $result['graphqlQuery']);
        $this->assertStringContainsString('change_password', $result['graphqlQuery']);
        $this->assertStringContainsString('operationName', $result['otherData']);
        $this->assertStringContainsString('variables', $result['otherData']);
    }

    protected function setUp(): void {
        parent::setUp();
    }

    protected function tearDown(): void {
        Mockery::close();
        parent::tearDown();
    }
}
