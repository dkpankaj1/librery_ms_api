<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name',
    'email',
    'password',
    'library_id',
    'user_type',
    'phone',
    'account_status',
    'account_valid_until',
    'account_verified_at',
    'last_login_at',
    'is_active',
    'avatar',
    'notes',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'account_valid_until' => 'datetime',
            'account_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The library this user belongs to.
     */
    public function library(): BelongsTo
    {
        return $this->belongsTo(Library::class);
    }

    /**
     * Helpers for common account checks.
     */
    public function isSuperAdmin(): bool
    {
        return $this->user_type === 'super_admin';
    }

    public function isActive(): bool
    {
        return $this->account_status === 'active' && $this->is_active === true;
    }

    public function isAccountValid(): bool
    {
        return $this->account_valid_until === null || $this->account_valid_until->isFuture();
    }
}
