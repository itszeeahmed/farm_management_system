<?php

namespace App\Domain\Milk\Models;

use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FarmerSupplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'collection_center_id',
        'supplier_code',
        'name',
        'phone',
        'cnic_or_national_id',
        'village_address',
        'cattle_count',
        'buffalo_count',
        'goat_count',
        'payout_channel',
        'payout_account_number',
        'payout_account_title',
        'is_active',
    ];

    protected $casts = [
        'cattle_count' => 'integer',
        'buffalo_count' => 'integer',
        'goat_count' => 'integer',
        'is_active' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function collectionCenter(): BelongsTo
    {
        return $this->belongsTo(MilkCollectionCenter::class, 'collection_center_id');
    }

    public function intakes(): HasMany
    {
        return $this->hasMany(MilkCollectionIntake::class);
    }

    public function getTotalLivestockCountAttribute(): int
    {
        return $this->cattle_count + $this->buffalo_count + $this->goat_count;
    }
}
