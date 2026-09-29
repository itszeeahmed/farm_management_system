<?php

namespace App\Domain\Feed\Models;

use App\Domain\Animals\Models\Animal;
use App\Domain\Organization\Models\Farm;
use App\Domain\Organization\Models\Pen;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedConsumption extends Model
{
    use HasFactory;

    protected $fillable = [
        'farm_id',
        'feed_item_id',
        'pen_id',
        'animal_id',
        'consumption_date',
        'quantity_consumed',
        'unit_cost',
        'total_cost',
        'recorded_by',
        'notes',
    ];

    protected $casts = [
        'consumption_date' => 'date',
        'quantity_consumed' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function feedItem(): BelongsTo
    {
        return $this->belongsTo(FeedItem::class);
    }

    public function pen(): BelongsTo
    {
        return $this->belongsTo(Pen::class);
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
