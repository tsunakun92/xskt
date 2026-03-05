<?php

namespace Modules\Api\Tests\Feature;

use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;

use Modules\Admin\Database\Seeders\RoleSeeder;
use Modules\Admin\Models\Role;
use Modules\Admin\Models\User;
use Modules\Crm\Models\CrmCustomer;
use Modules\Hr\Database\Seeders\HrCompanySeeder;
use Modules\Hr\Models\HrCompany;
use Modules\Hr\Models\HrProfile;

/**
 * Feature tests for profile_update GraphQL API (P0117).
 */
class ProfileUpdateApiTest extends BaseApiTest {
    /**
     * Set up the test environment before each test.
     */
    protected function setUp(): void {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(HrCompanySeeder::class);
    }

    /**
     * Build GraphQL mutation string for profile_update.
     *
     * @param  array<string, mixed>  $args
     * @return string
     */
    protected function getMutation(array $args): string {
        $defaultArgs = [
            'version'  => '1.0',
            'platform' => 'android',
        ];

        $args = array_merge($defaultArgs, $args);

        $pairs = [];
        foreach ($args as $key => $value) {
            if ($value === null) {
                $pairs[] = "{$key}: null";

                continue;
            }

            if (is_bool($value)) {
                $pairs[] = "{$key}: " . ($value ? 'true' : 'false');

                continue;
            }

            if (is_int($value) || is_float($value)) {
                $pairs[] = "{$key}: {$value}";

                continue;
            }

            $pairs[] = sprintf('%s: "%s"', $key, addslashes((string) $value));
        }

        $argString = implode("\n                    ", $pairs);

        return sprintf(
            'mutation {
                profile_update(
                    %s
                ) {
                    status
                    message
                    data {
                        id
                        user_id
                        first_name
                        last_name
                        kana_first_name
                        kana_last_name
                        fullname
                        phone_number
                        postal_code
                        ward
                        birthday
                        address
                        gender
                        company_id
                        status
                    }
                }
            }',
            $argString
        );
    }

    public function test_profile_update_unauthenticated(): void {
        $response = $this->postGraphQL($this->getMutation([
            'first_name' => 'Test',
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'errors' => [
                ['message'],
            ],
        ]);

        $this->assertStringContainsString('Unauthenticated', (string) $response->json('errors.0.message'));
    }

    public function test_profile_update_customer_success_updates_crm_customer_only(): void {
        $customerRole = Role::where('code', Role::ROLE_CUSTOMER_CODE)->first();
        $this->assertNotNull($customerRole);

        $user = User::factory()->create([
            'role_id' => $customerRole->id,
            'status'  => User::STATUS_ACTIVE,
        ]);

        $customer = CrmCustomer::create([
            'user_id'    => $user->id,
            'first_name' => 'OldFirst',
            'last_name'  => 'OldLast',
            'code'       => 'CUST01-000001',
            'address'    => 'Old Address',
            'birthday'   => '1990-01-01',
            'gender'     => 1,
            'type'       => 1,
            'status'     => CrmCustomer::STATUS_ACTIVE,
        ]);

        Sanctum::actingAs($user);

        $company = HrCompany::first();
        $this->assertNotNull($company);

        $response = $this->postGraphQL($this->getMutation([
            'first_name' => 'NewFirst',
            'last_name'  => 'NewLast',
            'chome'      => '1',
            'company_id' => $company->id, // should be ignored for customer
        ]));

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('data.profile_update.status'));
        $this->assertEquals('Profile updated successfully', $response->json('data.profile_update.message'));

        $this->assertEquals('NewFirst', $response->json('data.profile_update.data.first_name'));
        $this->assertEquals('NewLast', $response->json('data.profile_update.data.last_name'));
        $this->assertNull($response->json('data.profile_update.data.company_id'));

        $customer->refresh();
        $this->assertEquals('NewFirst', $customer->first_name);
        $this->assertEquals('NewLast', $customer->last_name);
        $this->assertEquals('1', $customer->chome);
    }

    public function test_profile_update_staff_success_updates_hr_profile_only(): void {
        $staffRole = Role::where('code', Role::ROLE_STAFF_CODE)->first();
        $this->assertNotNull($staffRole);

        $user = User::factory()->create([
            'role_id' => $staffRole->id,
            'status'  => User::STATUS_ACTIVE,
        ]);

        $company1 = HrCompany::query()->first();
        $company2 = HrCompany::query()->skip(1)->first() ?? $company1;
        $this->assertNotNull($company1);
        $this->assertNotNull($company2);

        $profile = HrProfile::create([
            'user_id'    => $user->id,
            'first_name' => 'OldFirst',
            'last_name'  => 'OldLast',
            'birthday'   => '1990-01-01',
            'code'       => 'HR0001-000001',
            'address'    => 'Old Address',
            'gender'     => 1,
            'company_id' => $company1->id,
            'status'     => HrProfile::STATUS_ACTIVE,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postGraphQL($this->getMutation([
            'first_name' => 'NewFirst',
            'last_name'  => 'NewLast',
            'company_id' => $company2->id,
            'chome'      => '99', // should be ignored for staff
        ]));

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('data.profile_update.status'));
        $this->assertEquals('Profile updated successfully', $response->json('data.profile_update.message'));

        $this->assertEquals('NewFirst', $response->json('data.profile_update.data.first_name'));
        $this->assertEquals('NewLast', $response->json('data.profile_update.data.last_name'));
        $this->assertEquals($company2->id, $response->json('data.profile_update.data.company_id'));

        $profile->refresh();
        $this->assertEquals('NewFirst', $profile->first_name);
        $this->assertEquals('NewLast', $profile->last_name);
        $this->assertEquals($company2->id, $profile->company_id);
    }

    public function test_profile_update_profile_not_found(): void {
        $customerRole = Role::where('code', Role::ROLE_CUSTOMER_CODE)->first();
        $this->assertNotNull($customerRole);

        $user = User::factory()->create([
            'role_id' => $customerRole->id,
            'status'  => User::STATUS_ACTIVE,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postGraphQL($this->getMutation([
            'first_name' => 'NewFirst',
        ]));

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('data.profile_update.status'));
        $this->assertEquals('Profile not found', $response->json('data.profile_update.message'));
        $this->assertNull($response->json('data.profile_update.data'));
    }

    public function test_profile_update_validation_error_invalid_postal_code(): void {
        $staffRole = Role::where('code', Role::ROLE_STAFF_CODE)->first();
        $this->assertNotNull($staffRole);

        $user = User::factory()->create([
            'role_id' => $staffRole->id,
            'status'  => User::STATUS_ACTIVE,
        ]);

        $company = HrCompany::query()->first();
        $this->assertNotNull($company);

        HrProfile::create([
            'user_id'    => $user->id,
            'first_name' => 'OldFirst',
            'last_name'  => 'OldLast',
            'birthday'   => '1990-01-01',
            'code'       => 'HR0002-000001',
            'address'    => 'Old Address',
            'gender'     => 1,
            'company_id' => $company->id,
            'status'     => HrProfile::STATUS_ACTIVE,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postGraphQL($this->getMutation([
            'postal_code' => 'abc',
        ]));

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('data.profile_update.status'));

        $message = $response->json('data.profile_update.message');
        $this->assertIsString($message);
        $this->assertStringStartsWith('Invalid input data:', $message);
    }

    public function test_profile_update_rate_limit(): void {
        config()->set('api.rate_limit.default.max_attempts', 1);
        config()->set('api.rate_limit.default.decay_minutes', 1);

        $customerRole = Role::where('code', Role::ROLE_CUSTOMER_CODE)->first();
        $this->assertNotNull($customerRole);

        $user = User::factory()->create([
            'role_id' => $customerRole->id,
            'status'  => User::STATUS_ACTIVE,
        ]);

        CrmCustomer::create([
            'user_id'    => $user->id,
            'first_name' => 'OldFirst',
            'last_name'  => 'OldLast',
            'code'       => 'CUST01-000002',
            'address'    => 'Old Address',
            'birthday'   => '1990-01-01',
            'gender'     => 1,
            'type'       => 1,
            'status'     => CrmCustomer::STATUS_ACTIVE,
        ]);

        Sanctum::actingAs($user);

        RateLimiter::clear('profile_update:127.0.0.1');

        $first = $this->postGraphQL($this->getMutation(['first_name' => 'A']));
        $first->assertStatus(200);

        $second = $this->postGraphQL($this->getMutation(['first_name' => 'B']));
        $second->assertStatus(200);

        $this->assertEquals(0, $second->json('data.profile_update.status'));
        $this->assertStringStartsWith(
            'Too many requests. Please try again in',
            (string) $second->json('data.profile_update.message')
        );
    }
}
