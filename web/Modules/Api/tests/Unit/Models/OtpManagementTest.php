<?php

namespace Modules\Api\Tests\Unit\Models;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use Modules\Admin\Models\User;
use Modules\Api\Models\OtpManagement;

class OtpManagementTest extends TestCase {
    use RefreshDatabase;

    protected OtpManagement $otp;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = User::factory()->create([
            'name'     => 'Test User',
            'username' => 'testuser',
            'email'    => 'test@example.com',
        ]);

        $this->otp = OtpManagement::create([
            'type'       => OtpManagement::TYPE_REGISTER,
            'email'      => 'test@example.com',
            'user_id'    => $this->user->id,
            'otp_code'   => '123456',
            'expires_at' => now()->addMinutes(10),
            'platform'   => 1,
            'version'    => '1.0.0',
        ]);
    }

    #[Test]
    public function it_uses_correct_table_name(): void {
        $this->assertEquals('otp_managements', $this->otp->getTable());
    }

    #[Test]
    public function it_has_correct_fillable_attributes(): void {
        $this->assertSame([
            'type',
            'user_id',
            'email',
            'otp_code',
            'expires_at',
            'verified_at',
            'used_at',
            'platform',
            'version',
        ], $this->otp->getFillable());
    }

    #[Test]
    public function it_has_correct_type_constants(): void {
        $this->assertEquals(1, OtpManagement::TYPE_REGISTER);
        $this->assertEquals(2, OtpManagement::TYPE_FORGOT_PASSWORD);
    }

    #[Test]
    public function it_belongs_to_user(): void {
        $this->assertInstanceOf(User::class, $this->otp->rUser);
        $this->assertEquals($this->user->id, $this->otp->user_id);
        $this->assertEquals($this->user->email, $this->otp->rUser->email);
    }

    #[Test]
    public function it_casts_datetime_fields(): void {
        $this->assertInstanceOf(Carbon::class, $this->otp->expires_at);
        $this->assertNull($this->otp->verified_at);
        $this->assertNull($this->otp->used_at);
    }

    #[Test]
    public function it_can_check_if_otp_is_expired(): void {
        // Not expired OTP
        $this->assertFalse($this->otp->isExpired());

        // Expired OTP
        $expiredOtp = OtpManagement::create([
            'type'       => OtpManagement::TYPE_REGISTER,
            'email'      => 'expired@example.com',
            'otp_code'   => '999999',
            'expires_at' => now()->subMinutes(10),
        ]);

        $this->assertTrue($expiredOtp->isExpired());
    }

    #[Test]
    public function it_can_check_if_otp_is_used(): void {
        // Not used OTP
        $this->assertFalse($this->otp->isUsed());

        // Used OTP
        $usedOtp = OtpManagement::create([
            'type'       => OtpManagement::TYPE_REGISTER,
            'email'      => 'used@example.com',
            'otp_code'   => '888888',
            'expires_at' => now()->addMinutes(10),
            'used_at'    => now(),
        ]);

        $this->assertTrue($usedOtp->isUsed());
    }

    #[Test]
    public function it_can_check_if_otp_is_valid(): void {
        // Valid OTP (not expired and not used)
        $this->assertTrue($this->otp->isValid());

        // Invalid - expired
        $expiredOtp = OtpManagement::create([
            'type'       => OtpManagement::TYPE_REGISTER,
            'email'      => 'expired@example.com',
            'otp_code'   => '777777',
            'expires_at' => now()->subMinutes(10),
        ]);
        $this->assertFalse($expiredOtp->isValid());

        // Invalid - used
        $usedOtp = OtpManagement::create([
            'type'       => OtpManagement::TYPE_REGISTER,
            'email'      => 'used@example.com',
            'otp_code'   => '666666',
            'expires_at' => now()->addMinutes(10),
            'used_at'    => now(),
        ]);
        $this->assertFalse($usedOtp->isValid());

        // Invalid - both expired and used
        $invalidOtp = OtpManagement::create([
            'type'       => OtpManagement::TYPE_REGISTER,
            'email'      => 'invalid@example.com',
            'otp_code'   => '555555',
            'expires_at' => now()->subMinutes(10),
            'used_at'    => now(),
        ]);
        $this->assertFalse($invalidOtp->isValid());
    }

    #[Test]
    public function it_can_find_valid_otp_for_email(): void {
        $validOtp = OtpManagement::findValidOtpForEmail(
            'test@example.com',
            OtpManagement::TYPE_REGISTER,
            '123456'
        );

        $this->assertInstanceOf(OtpManagement::class, $validOtp);
        $this->assertEquals($this->otp->id, $validOtp->id);
    }

    #[Test]
    public function it_returns_null_when_finding_invalid_otp(): void {
        // Wrong email
        $result = OtpManagement::findValidOtpForEmail(
            'wrong@example.com',
            OtpManagement::TYPE_REGISTER,
            '123456'
        );
        $this->assertNull($result);

        // Wrong type
        $result = OtpManagement::findValidOtpForEmail(
            'test@example.com',
            OtpManagement::TYPE_FORGOT_PASSWORD,
            '123456'
        );
        $this->assertNull($result);

        // Wrong OTP code
        $result = OtpManagement::findValidOtpForEmail(
            'test@example.com',
            OtpManagement::TYPE_REGISTER,
            '999999'
        );
        $this->assertNull($result);
    }

    #[Test]
    public function it_returns_null_for_expired_otp(): void {
        $expiredOtp = OtpManagement::create([
            'type'       => OtpManagement::TYPE_REGISTER,
            'email'      => 'expired@example.com',
            'otp_code'   => '111111',
            'expires_at' => now()->subMinutes(10),
        ]);

        $result = OtpManagement::findValidOtpForEmail(
            'expired@example.com',
            OtpManagement::TYPE_REGISTER,
            '111111'
        );

        $this->assertNull($result);
    }

    #[Test]
    public function it_returns_null_for_used_otp(): void {
        $usedOtp = OtpManagement::create([
            'type'       => OtpManagement::TYPE_REGISTER,
            'email'      => 'used@example.com',
            'otp_code'   => '222222',
            'expires_at' => now()->addMinutes(10),
            'used_at'    => now(),
        ]);

        $result = OtpManagement::findValidOtpForEmail(
            'used@example.com',
            OtpManagement::TYPE_REGISTER,
            '222222'
        );

        $this->assertNull($result);
    }

    #[Test]
    public function it_can_invalidate_old_otps_for_email(): void {
        // Create multiple valid OTPs for same email
        OtpManagement::create([
            'type'       => OtpManagement::TYPE_REGISTER,
            'email'      => 'invalidate@example.com',
            'otp_code'   => '333333',
            'expires_at' => now()->addMinutes(10),
        ]);

        OtpManagement::create([
            'type'       => OtpManagement::TYPE_REGISTER,
            'email'      => 'invalidate@example.com',
            'otp_code'   => '444444',
            'expires_at' => now()->addMinutes(10),
        ]);

        // Invalidate old OTPs
        $count = OtpManagement::invalidateOldOtpsForEmail(
            'invalidate@example.com',
            OtpManagement::TYPE_REGISTER
        );

        $this->assertEquals(2, $count);

        // Verify they are marked as used
        $otps = OtpManagement::where('email', 'invalidate@example.com')
            ->where('type', OtpManagement::TYPE_REGISTER)
            ->get();

        foreach ($otps as $otp) {
            $this->assertNotNull($otp->used_at);
        }
    }

    #[Test]
    public function it_only_invalidates_unused_otps(): void {
        // Create one used and one unused OTP
        OtpManagement::create([
            'type'       => OtpManagement::TYPE_REGISTER,
            'email'      => 'mixed@example.com',
            'otp_code'   => '555555',
            'expires_at' => now()->addMinutes(10),
            'used_at'    => now()->subMinutes(5), // Already used
        ]);

        OtpManagement::create([
            'type'       => OtpManagement::TYPE_REGISTER,
            'email'      => 'mixed@example.com',
            'otp_code'   => '666666',
            'expires_at' => now()->addMinutes(10),
        ]);

        // Invalidate - should only invalidate the unused one
        $count = OtpManagement::invalidateOldOtpsForEmail(
            'mixed@example.com',
            OtpManagement::TYPE_REGISTER
        );

        $this->assertEquals(1, $count);
    }

    #[Test]
    public function it_only_invalidates_valid_otps(): void {
        // Create one expired and one valid OTP
        OtpManagement::create([
            'type'       => OtpManagement::TYPE_REGISTER,
            'email'      => 'expired@example.com',
            'otp_code'   => '777777',
            'expires_at' => now()->subMinutes(10), // Already expired
        ]);

        OtpManagement::create([
            'type'       => OtpManagement::TYPE_REGISTER,
            'email'      => 'expired@example.com',
            'otp_code'   => '888888',
            'expires_at' => now()->addMinutes(10),
        ]);

        // Invalidate - should only invalidate the valid (not expired) one
        $count = OtpManagement::invalidateOldOtpsForEmail(
            'expired@example.com',
            OtpManagement::TYPE_REGISTER
        );

        $this->assertEquals(1, $count);
    }

    #[Test]
    public function it_can_create_otp_with_all_fields(): void {
        $otp = OtpManagement::create([
            'type'        => OtpManagement::TYPE_FORGOT_PASSWORD,
            'email'       => 'forgot@example.com',
            'user_id'     => $this->user->id,
            'otp_code'    => '999999',
            'expires_at'  => now()->addMinutes(15),
            'verified_at' => now(),
            'used_at'     => now(),
            'platform'    => 2,
            'version'     => '2.0.0',
        ]);

        $this->assertInstanceOf(OtpManagement::class, $otp);
        $this->assertEquals(OtpManagement::TYPE_FORGOT_PASSWORD, $otp->type);
        $this->assertEquals('forgot@example.com', $otp->email);
        $this->assertEquals('999999', $otp->otp_code);
        $this->assertNotNull($otp->verified_at);
        $this->assertNotNull($otp->used_at);
        $this->assertEquals(2, $otp->platform);
        $this->assertEquals('2.0.0', $otp->version);
    }
}
