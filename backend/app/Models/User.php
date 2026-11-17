<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * `role` dan `plan` sengaja TIDAK mass-assignable: register hanya boleh mengisi
 * name/email/password, supaya tidak ada jalur eskalasi hak akses dari input user.
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** Kuota konsultasi per hari (UC-07: gratis 5/hari; UC-12: premium +100). */
    public const DAILY_QUOTA = ['free' => 5, 'premium' => 100];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'premium_until' => 'datetime',
            'quota_date' => 'date',
            'two_factor_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Premium aktif bila paketnya premium dan masa aktif belum lewat.
     * `premium_until` null dianggap aktif tanpa batas (akun lama/seed admin).
     */
    public function hasActivePremium(): bool
    {
        return $this->plan === 'premium'
            && ($this->premium_until === null || $this->premium_until->isFuture());
    }

    public function effectivePlan(): string
    {
        return $this->hasActivePremium() ? 'premium' : 'free';
    }

    /** RF-07: perpanjang dari sisa masa aktif bila masih ada, bukan dari hari ini. */
    public function activatePremium(int $days): void
    {
        $base = $this->premium_until?->isFuture() ? $this->premium_until : now();

        $this->forceFill([
            'plan' => 'premium',
            'premium_until' => $base->copy()->addDays($days),
        ])->save();
    }

    public function dailyQuota(): int
    {
        return self::DAILY_QUOTA[$this->effectivePlan()] ?? self::DAILY_QUOTA['free'];
    }

    /** Sisa kuota hari ini; otomatis ter-reset saat tanggal berganti. */
    public function quotaRemaining(): int
    {
        $used = $this->quota_date?->isToday() ? $this->quota_used : 0;

        return max(0, $this->dailyQuota() - $used);
    }

    /** Dipanggil server-side saja (NFR: kuota tidak boleh dihitung dari data klien). */
    public function consumeQuota(): void
    {
        if (! $this->quota_date?->isToday()) {
            $this->quota_date = now();
            $this->quota_used = 0;
        }

        $this->quota_used++;
        $this->save();
    }

    public function businessProfile(): HasOne
    {
        return $this->hasOne(BusinessProfile::class);
    }

    public function chatSessions(): HasMany
    {
        return $this->hasMany(ChatSession::class);
    }

    public function contactMessages(): HasMany
    {
        return $this->hasMany(ContactMessage::class);
    }

    public function bugReports(): HasMany
    {
        return $this->hasMany(BugReport::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function otpCodes(): HasMany
    {
        return $this->hasMany(OtpCode::class);
    }
}
