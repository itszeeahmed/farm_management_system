<?php

namespace App\Domain\Sync\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncClientRegistry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_uuid',
        'app_version',
        'platform',
        'last_sync_rev_id',
        'last_synced_at',
    ];

    protected $casts = [
        'last_sync_rev_id' => 'integer',
        'last_synced_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
