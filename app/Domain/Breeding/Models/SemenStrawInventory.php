<?php

namespace App\Domain\Breeding\Models;

use App\Domain\Organization\Models\Farm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SemenStrawInventory extends Model
{
    use HasFactory;

    protected $table = 'semen_straw_inventories';

    protected $fillable = [
        'farm_id',
        'straw_code',
        'sire_name',
        'sire_breed',
        'naab_code',
        'semen_type',
        'canister_location',
        'cane_number',
        'straws_in_stock',
        'unit_cost_pkr',
    ];

    protected $casts = [
        'straws_in_stock' => 'integer',
        'unit_cost_pkr' => 'decimal:2',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function breedingEvents(): HasMany
    {
        return $this->hasMany(BreedingEvent::class);
    }
}
