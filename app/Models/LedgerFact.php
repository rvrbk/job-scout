<?php

namespace App\Models;

use Database\Factories\LedgerFactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $ledger_entry_id
 * @property string $statement
 * @property string|null $evidence_url
 * @property Carbon|null $confirmed_at
 */
#[Fillable(['statement', 'evidence_url', 'confirmed_at'])]
class LedgerFact extends Model
{
    /** @use HasFactory<LedgerFactFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<LedgerEntry, $this>
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(LedgerEntry::class, 'ledger_entry_id');
    }

    /**
     * Only facts the user has confirmed as accurate.
     *
     * @param  Builder<LedgerFact>  $query
     */
    public function scopeConfirmed(Builder $query): void
    {
        $query->whereNotNull('confirmed_at');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
        ];
    }
}
