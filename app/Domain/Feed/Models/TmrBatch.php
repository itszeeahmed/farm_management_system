<?php

namespace App\Domain\Feed\Models;

use App\Domain\Organization\Models\Farm;
use App\Domain\Organization\Models\Pen;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TmrBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'feed_formulation_id',
        'pen_id',
        'batch_number',
        'mixer_wagon_id',
        'planned_weight_kg',
        'actual_weight_kg',
        'deviation_percent',
        'mixing_duration_minutes',
        'status',
        'operator_id',
        'batch_timestamp',
    ];

    protected $casts = [
        'planned_weight_kg' => 'decimal:2',
        'actual_weight_kg' => 'decimal:2',
        'deviation_percent' => 'decimal:2',
        'mixing_duration_minutes' => 'integer',
        'batch_timestamp' => 'datetime',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function formulation(): BelongsTo
    {
        return $this->belongsTo(FeedFormulation::class, 'feed_formulation_id');
    }

    public function pen(): BelongsTo
    {
        return $this->belongsTo(Pen::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }
}
