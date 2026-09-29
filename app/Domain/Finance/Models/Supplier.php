<?php

namespace App\Domain\Finance\Models;

use App\Domain\Organization\Models\Farm;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'name',
        'contact_person',
        'phone',
        'email',
        'category',
        'address',
        'notes',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class);
    }
}
