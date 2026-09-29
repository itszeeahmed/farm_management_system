<?php

namespace App\Domain\Milk\Models;

use App\Domain\Animals\Models\Animal;
use App\Domain\Organization\Models\Farm;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MilkRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'animal_id',
        'milk_session_id',
        'recorded_date',
        'shift',
        'yield_liters',
        'fat_percentage',
        'snf_percentage',
        'protein_percentage',
        'scc',
        'temperature_c',
        'quality_status',
        'is_colostrum',
        'operator_notes',
        'recorded_by',
    ];

    protected $casts = [
        'recorded_date' => 'date',
        'yield_liters' => 'decimal:2',
        'fat_percentage' => 'decimal:2',
        'snf_percentage' => 'decimal:2',
        'protein_percentage' => 'decimal:2',
        'temperature_c' => 'decimal:1',
        'scc' => 'integer',
        'is_colostrum' => 'boolean',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(MilkSession::class, 'milk_session_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
