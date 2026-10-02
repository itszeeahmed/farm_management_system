<?php

namespace App\Domain\Finance\Models;

use App\Domain\Animals\Models\Animal;
use App\Domain\Organization\Models\Farm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiologicalAssetValuation extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'animal_id',
        'valuation_date',
        'fair_value_amount',
        'estimated_cost_to_sell',
        'net_carrying_value',
        'valuation_method',
        'maturity_stage',
        'valuer_name',
        'notes',
    ];

    protected $casts = [
        'valuation_date' => 'date',
        'fair_value_amount' => 'decimal:2',
        'estimated_cost_to_sell' => 'decimal:2',
        'net_carrying_value' => 'decimal:2',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }
}
