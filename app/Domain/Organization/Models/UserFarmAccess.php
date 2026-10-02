<?php

namespace App\Domain\Organization\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserFarmAccess extends Model
{
    use HasFactory;

    protected $table = 'user_farm_access';

    protected $fillable = [
        'user_id',
        'farm_id',
        'role_id',
        'access_level',
        'granted_by',
        'granted_at',
        'expires_at',
        'is_active',
        'reason',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function grantor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', Carbon::now());
            });
    }

    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return Carbon::now()->greaterThan($this->expires_at);
    }
}
