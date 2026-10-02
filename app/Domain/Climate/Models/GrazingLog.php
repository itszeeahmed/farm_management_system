<?php

namespace App\Domain\Climate\Models;

use App\Domain\Animals\Models\AnimalGroup;
use App\Domain\Organization\Models\Farm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrazingLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'pasture_plot_id',
        'animal_group_id',
        'entry_date',
        'exit_date',
        'stocking_density_heads',
        'livestock_units_per_ha',
        'pre_graze_height_cm',
        'post_graze_residual_height_cm',
        'dry_matter_utilized_kg_ha',
        'notes',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'exit_date' => 'date',
        'stocking_density_heads' => 'integer',
        'livestock_units_per_ha' => 'decimal:2',
        'pre_graze_height_cm' => 'decimal:1',
        'post_graze_residual_height_cm' => 'decimal:1',
        'dry_matter_utilized_kg_ha' => 'decimal:2',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function pasturePlot(): BelongsTo
    {
        return $this->belongsTo(PasturePlot::class);
    }

    public function animalGroup(): BelongsTo
    {
        return $this->belongsTo(AnimalGroup::class);
    }
}
