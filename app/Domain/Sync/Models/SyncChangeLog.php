<?php

namespace App\Domain\Sync\Models;

use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncChangeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'entity_type',
        'entity_id',
        'action',
        'idempotency_key',
        'delta_payload_json',
        'timestamp',
    ];

    protected $casts = [
        'delta_payload_json' => 'array',
        'timestamp' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
