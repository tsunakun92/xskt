<?php

namespace Modules\Api\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Modules\Admin\Models\User;

/**
 * Model for Otp Management records.
 *
 * @property int $id
 * @property int $type
 * @property string $email
 * @property int $user_id
 * @property string $otp_code
 * @property \Carbon\Carbon $expires_at
 * @property \Carbon\Carbon|null $verified_at
 * @property \Carbon\Carbon|null $used_at
 * @property int|null $platform
 * @property string|null $version
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * Relationships:
 * @property User $rUser
 */
class OtpManagement extends ApiModel {
    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'otp_managements';

    /**
     * OTP Types
     */
    public const TYPE_REGISTER = 1;

    public const TYPE_FORGOT_PASSWORD = 2;

    /**
     * Mass assignable attributes.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'user_id',
        'email',
        'otp_code',
        'expires_at',
        'verified_at',
        'used_at',
        'platform',
        'version',
    ];

    /**
     * Casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array {
        return [
            'expires_at'  => 'datetime',
            'verified_at' => 'datetime',
            'used_at'     => 'datetime',
        ];
    }

    /**
     * Relationship to User.
     *
     * @return BelongsTo
     */
    public function rUser(): BelongsTo {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Check if OTP is expired.
     *
     * @return bool
     */
    public function isExpired(): bool {
        return $this->expires_at->lt(now());
    }

    /**
     * Check if OTP is used.
     *
     * @return bool
     */
    public function isUsed(): bool {
        return $this->used_at !== null;
    }

    /**
     * Check if OTP is valid (not expired and not used).
     *
     * @return bool
     */
    public function isValid(): bool {
        return !$this->isExpired() && !$this->isUsed();
    }

    /**
     * Find a valid OTP by email, type, otpcode.
     *
     * @param  string  $email
     * @param  int  $type
     * @param  string  $otpCode
     * @return OtpManagement|null
     */
    public static function findValidOtpForEmail(string $email, int $type, string $otpCode): ?self {
        return self::query()
            ->where('email', $email)
            ->where('type', $type)
            ->where('otp_code', $otpCode)
            ->where('expires_at', '>', now())
            ->whereNull('used_at')
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Invalidate old unused OTPs for a email.
     *
     * @param  string  $email
     * @param  int  $type
     * @return int Number of invalidated OTPs
     */
    public static function invalidateOldOtpsForEmail(string $email, int $type): int {
        return self::query()
            ->where('email', $email)
            ->where('type', $type)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->update(['used_at' => now()]);
    }
}
