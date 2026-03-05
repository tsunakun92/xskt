<?php

namespace Tests\Unit\Entities;

use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use App\Entities\Sanctum\PersonalAccessToken;
use App\Models\BaseModel;

class PersonalAccessTokenTest extends TestCase {
    use RefreshDatabase;

    #[Test]
    public function it_reports_platform_name_and_active_state_and_can_logout(): void {
        $token = new PersonalAccessToken([
            'platform' => PersonalAccessToken::PLATFORM_ANDROID,
            'status'   => BaseModel::STATUS_ACTIVE,
        ]);

        $this->assertSame('Android', $token->getPlatformName());
        $this->assertTrue($token->isActive());

        // Persist and test logout updates status
        $token->tokenable_id   = 1;
        $token->tokenable_type = 'App\\Models\\User';
        $token->name           = 'Device 1';
        $token->token          = 'dummy-token';
        $token->abilities      = ['*'];
        $token->save();

        $token->logout();
        $this->assertFalse($token->fresh()->isActive());
    }

    #[Test]
    public function it_validates_and_converts_platform_strings_and_integers(): void {
        $this->assertTrue(PersonalAccessToken::isValidPlatform('1'));
        $this->assertTrue(PersonalAccessToken::isValidPlatform('web'));
        $this->assertFalse(PersonalAccessToken::isValidPlatform('unknown'));

        $this->assertSame(PersonalAccessToken::PLATFORM_WEB, PersonalAccessToken::convertStringToInt('web'));
        $this->assertSame(PersonalAccessToken::PLATFORM_IOS, PersonalAccessToken::convertStringToInt('3'));

        $this->assertSame('android', PersonalAccessToken::convertIntToString(PersonalAccessToken::PLATFORM_ANDROID));
        $this->assertSame('web', PersonalAccessToken::convertIntToString(999));
    }

    #[Test]
    public function it_throws_exception_for_invalid_platform_string(): void {
        $this->expectException(InvalidArgumentException::class);
        PersonalAccessToken::convertStringToInt('invalid-platform');
    }

    #[Test]
    public function it_provides_platform_validation_rules_and_mobile_tokens_query(): void {
        $rules = PersonalAccessToken::getPlatformValidationRules();
        $this->assertIsArray($rules);
        $this->assertNotEmpty($rules);

        // No tokens yet
        $tokens = PersonalAccessToken::getMobileTokensByUserId(1);
        $this->assertCount(0, $tokens);

        // Create a couple of mobile tokens
        PersonalAccessToken::forceCreate([
            'tokenable_id'   => 1,
            'tokenable_type' => 'App\\Models\\User',
            'name'           => 'Android device',
            'token'          => 'token-1',
            'abilities'      => ['*'],
            'platform'       => PersonalAccessToken::PLATFORM_ANDROID,
            'status'         => BaseModel::STATUS_ACTIVE,
        ]);

        PersonalAccessToken::forceCreate([
            'tokenable_id'   => 1,
            'tokenable_type' => 'App\\Models\\User',
            'name'           => 'iOS device',
            'token'          => 'token-2',
            'abilities'      => ['*'],
            'platform'       => PersonalAccessToken::PLATFORM_IOS,
            'status'         => BaseModel::STATUS_ACTIVE,
        ]);

        $tokens = PersonalAccessToken::getMobileTokensByUserId(1);
        $this->assertCount(2, $tokens);
    }
}
