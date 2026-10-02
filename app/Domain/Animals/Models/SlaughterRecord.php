<?php

namespace App\Domain\Animals\Models;

use App\Domain\Organization\Models\Farm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlaughterRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'animal_id',
        'slaughterhouse_name',
        'slaughter_date',
        'live_weight_kg',
        'hot_carcass_weight_kg',
        'cold_carcass_weight_kg',
        'dressing_percentage',
        'conformation_grade',
        'fat_score',
        'meat_withdrawal_cleared',
        'carcass_bar_code',
        'technician_name',
        'notes',
    ];

    protected $casts = [
        'slaughter_date' => 'date',
        'live_weight_kg' => 'decimal:2',
        'hot_carcass_weight_kg' => 'decimal:2',
        'cold_carcass_weight_kg' => 'decimal:2',
        'dressing_percentage' => 'decimal:2',
        'fat_score' => 'integer',
        'meat_withdrawal_cleared' => 'boolean',
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
