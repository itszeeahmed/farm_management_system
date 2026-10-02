<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Domain\Organization\Models\AuditLog;
use App\Domain\Organization\Models\Farm;
use App\Domain\Organization\Models\Permission;
use App\Domain\Organization\Models\UserFarmAccess;
use Carbon\Carbon;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
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
        ];
    }

    public function preference(): HasOne
    {
        return $this->hasOne(UserPreference::class);
    }

    public function getOrCreatePreference(): UserPreference
    {
        return $this->preference ?? $this->preference()->create([
            'locale' => 'en',
            'timezone' => 'Asia/Karachi',
            'date_format' => 'Y-m-d',
            'time_format' => '24h',
            'currency' => 'PKR',
            'currency_symbol_position' => 'before',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
        ]);
    }

    public function farmAccesses(): HasMany
    {
        return $this->hasMany(UserFarmAccess::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function canAccessFarm(Farm $farm, ?string $permission = null): bool
    {
        $access = $this->farmAccesses()
            ->where('farm_id', $farm->id)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now());
            })
            ->with('role.permissions')
            ->first();

        if (! $access) {
            return false;
        }

        if ($permission === null) {
            return true;
        }

        if ($access->access_level === 'full' || $access->role?->slug === 'farm_owner') {
            return true;
        }

        if (! $access->role) {
            return false;
        }

        return $access->role->permissions->contains('name', $permission);
    }

    /**
     * Get list of permission slugs for the specified farm.
     *
     * @return array<string>
     */
    public function getFarmPermissions(Farm $farm): array
    {
        $access = $this->farmAccesses()
            ->where('farm_id', $farm->id)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now());
            })
            ->with('role.permissions')
            ->first();

        if (! $access || ! $access->role) {
            return [];
        }

        if ($access->access_level === 'full' || $access->role->slug === 'farm_owner') {
            return Permission::pluck('name')->toArray();
        }

        return $access->role->permissions->pluck('name')->toArray();
    }
}
