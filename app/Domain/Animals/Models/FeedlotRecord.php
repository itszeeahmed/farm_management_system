<?php

namespace App\Domain\Animals\Models;

use App\Domain\Organization\Models\Farm;
use App\Domain\Organization\Models\Pen;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedlotRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'animal_id',
        'pen_id',
        'intake_date',
        'intake_weight_kg',
        'current_weight_kg',
        'target_slaughter_weight_kg',
        'days_on_feed',
        'average_daily_gain_kg',
        'total_gain_kg',
        'total_feed_consumed_kg_dm',
        'feed_conversion_ratio',
        'daily_ration_cost',
        'cost_per_kg_gain',
        'status',
        'notes',
    ];

    protected $casts = [
        'intake_date' => 'date',
        'intake_weight_kg' => 'decimal:2',
        'current_weight_kg' => 'decimal:2',
        'target_slaughter_weight_kg' => 'decimal:2',
        'days_on_feed' => 'integer',
        'average_daily_gain_kg' => 'decimal:3',
        'total_gain_kg' => 'decimal:2',
        'total_feed_consumed_kg_dm' => 'decimal:2',
        'feed_conversion_ratio' => 'decimal:2',
        'daily_ration_cost' => 'decimal:2',
        'cost_per_kg_gain' => 'decimal:2',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function pen(): BelongsTo
    {
        return $this->belongsTo(Pen::class);
    }
}
