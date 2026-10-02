<?php

namespace App\Domain\Feed\Models;

use App\Domain\Organization\Models\Farm;
use App\Domain\Organization\Models\Pen;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedBunkScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'pen_id',
        'assessed_at',
        'score',
        'refusal_estimated_kg',
        'adjustment_action',
        'assessed_by',
        'notes',
    ];

    protected $casts = [
        'assessed_at' => 'datetime',
        'score' => 'integer',
        'refusal_estimated_kg' => 'decimal:2',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function pen(): BelongsTo
    {
        return $this->belongsTo(Pen::class);
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }
}
