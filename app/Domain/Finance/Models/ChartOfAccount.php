<?php

namespace App\Domain\Finance\Models;

use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    use HasFactory;

    protected $table = 'chart_of_accounts';

    protected $fillable = [
        'organization_id',
        'account_code',
        'name',
        'account_type',
        'parent_account_id',
        'currency',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_account_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_account_id');
    }

    public function debitEntries(): HasMany
    {
        return $this->hasMany(GeneralLedgerEntry::class, 'debit_account_id');
    }

    public function creditEntries(): HasMany
    {
        return $this->hasMany(GeneralLedgerEntry::class, 'credit_account_id');
    }
}
