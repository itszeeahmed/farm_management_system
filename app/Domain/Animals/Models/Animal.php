<?php

namespace App\Domain\Animals\Models;

use App\Domain\Breeding\Models\BreedingEvent;
use App\Domain\Breeding\Models\Pregnancy;
use App\Domain\Health\Models\HealthCase;
use App\Domain\Health\Models\Treatment;
use App\Domain\Health\Models\Vaccination;
use App\Domain\Milk\Models\MilkRecord;
use App\Domain\Organization\Models\Farm;
use App\Domain\Organization\Models\Pen;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Animal extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'farm_id',
        'species_id',
        'breed_id',
        'pen_id',
        'tag_number',
        'electronic_id',
        'qr_code_identifier',
        'name',
        'sex',
        'birth_date',
        'birth_weight_kg',
        'current_weight_kg',
        'weaning_date',
        'acquisition_type',
        'acquisition_date',
        'status',
        'parity',
        'sire_id',
        'dam_id',
        'photo_url',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'acquisition_date' => 'date',
        'weaning_date' => 'date',
        'birth_weight_kg' => 'decimal:2',
        'current_weight_kg' => 'decimal:2',
        'parity' => 'integer',
    ];

    protected $appends = [
        'age_in_months',
        'has_active_withdrawal',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function species(): BelongsTo
    {
        return $this->belongsTo(Species::class);
    }

    public function breed(): BelongsTo
    {
        return $this->belongsTo(Breed::class);
    }

    public function pen(): BelongsTo
    {
        return $this->belongsTo(Pen::class);
    }

    public function sire(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'sire_id');
    }

    public function dam(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'dam_id');
    }

    public function offspring(): HasMany
    {
        return $this->hasMany(Animal::class, 'dam_id');
    }

    public function weightRecords(): HasMany
    {
        return $this->hasMany(WeightRecord::class);
    }

    public function milkRecords(): HasMany
    {
        return $this->hasMany(MilkRecord::class);
    }

    public function healthCases(): HasMany
    {
        return $this->hasMany(HealthCase::class);
    }

    public function treatments(): HasMany
    {
        return $this->hasMany(Treatment::class);
    }

    public function vaccinations(): HasMany
    {
        return $this->hasMany(Vaccination::class);
    }

    public function breedingEvents(): HasMany
    {
        return $this->hasMany(BreedingEvent::class);
    }

    public function pregnancies(): HasMany
    {
        return $this->hasMany(Pregnancy::class);
    }

    public function getAgeInMonthsAttribute(): ?int
    {
        if (! $this->birth_date) {
            return null;
        }

        return Carbon::now()->diffInMonths($this->birth_date);
    }

    public function getHasActiveWithdrawalAttribute(): bool
    {
        return $this->treatments()
            ->where('milk_withdrawal_until', '>', Carbon::now())
            ->exists();
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['active', 'lactating', 'dry', 'pregnant']);
    }

    public function scopeLactating($query)
    {
        return $query->where('status', 'lactating');
    }
}
