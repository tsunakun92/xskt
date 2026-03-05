<?php

namespace Modules\Admin\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use Modules\Admin\Models\PersonalAccessToken;
use Modules\Admin\Models\Role;
use Modules\Admin\Models\User;

class PersonalAccessTokenTest extends TestCase {
    use RefreshDatabase;

    protected User $user;

    protected PersonalAccessToken $token;

    /**
     * Set up test dependencies.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();

        // Create a basic customer role to satisfy foreign key constraints
        $role = Role::create([
            'name'   => 'Customer',
            'code'   => Role::ROLE_CUSTOMER_CODE,
            'status' => Role::STATUS_ACTIVE,
        ]);

        $this->user = User::factory()->create([
            'role_id' => $role->id,
            'status'  => User::STATUS_ACTIVE,
        ]);

        $this->token = PersonalAccessToken::create([
            'tokenable_id'   => $this->user->id,
            'tokenable_type' => User::class,
            'name'           => 'Test Token',
            'token'          => 'test-token',
            'abilities'      => json_encode(['*']),
            'platform'       => 1,
            'device_token'   => 'device-token',
            'status'         => PersonalAccessToken::STATUS_ACTIVE,
        ]);
    }

    /**
     * Test that personal access token uses correct table name.
     *
     * @return void
     */
    #[Test]
    public function it_uses_correct_table_name(): void {
        $this->assertEquals('personal_access_tokens', $this->token->getTable());
    }

    /**
     * Test that personal access token has correct fillable attributes.
     *
     * @return void
     */
    #[Test]
    public function it_has_correct_fillable_attributes(): void {
        $expected = [
            'tokenable_id',
            'tokenable_type',
            'name',
            'token',
            'abilities',
            'platform',
            'device_token',
            'status',
            'expires_at',
        ];

        $this->assertEquals($expected, $this->token->getFillable());
    }

    /**
     * Test that personal access token has correct datatable columns.
     *
     * @return void
     */
    #[Test]
    public function it_has_correct_datatable_columns(): void {
        $expectedColumns = [
            'id',
            'user_name',
            'name',
            'platform_name',
            'status',
            'last_used_at',
            'expires_at',
            'created_at',
            'action',
        ];

        $this->assertEquals($expectedColumns, PersonalAccessToken::getDatatableColumns());
    }

    /**
     * Test that personal access token belongs to user and exposes user_name accessor.
     *
     * @return void
     */
    #[Test]
    public function it_belongs_to_user_and_has_user_name_accessor(): void {
        $this->assertInstanceOf(User::class, $this->token->rUser);
        $this->assertEquals($this->user->id, $this->token->tokenable_id);
        $this->assertEquals($this->user->name, $this->token->user_name);
    }

    /**
     * Test that personal access token has platform_name accessor and filter fields configuration.
     *
     * @return void
     */
    #[Test]
    public function it_has_platform_name_accessor_and_filter_fields(): void {
        $this->assertNotEmpty($this->token->platform_name);

        $fields = PersonalAccessToken::getFilterFields('personal-access-tokens');

        $this->assertIsArray($fields);
        $this->assertArrayHasKey('platform', $fields);
        $this->assertArrayHasKey('status', $fields);
        $this->assertArrayHasKey('tokenable_id', $fields);

        $this->assertEquals('select', $fields['platform']['type']);
        $this->assertEquals('select', $fields['status']['type']);
        $this->assertEquals('select', $fields['tokenable_id']['type']);
    }
}
