<?php

namespace App\Models;

use App\Enums\LedgerEntryType;
use Database\Factories\LedgerEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property LedgerEntryType $type
 * @property string $title
 * @property string|null $organization
 * @property string|null $location
 * @property Carbon|null $started_on
 * @property Carbon|null $ended_on
 * @property list<string>|null $skills
 * @property string|null $evidence_url
 * @property Carbon|null $confirmed_at
 */
#[Fillable(['type', 'title', 'organization', 'location', 'started_on', 'ended_on', 'skills', 'evidence_url', 'confirmed_at'])]
class LedgerEntry extends Model
{
    /** @use HasFactory<LedgerEntryFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<LedgerFact, $this>
     */
    public function facts(): HasMany
    {
        return $this->hasMany(LedgerFact::class);
    }

    /**
     * Only entries the user has confirmed as accurate.
     *
     * @param  Builder<LedgerEntry>  $query
     */
    public function scopeConfirmed(Builder $query): void
    {
        $query->whereNotNull('confirmed_at');
    }

    /**
     * Human-readable period, e.g. "Mar 2021 - Present". A period that runs from January to December (or to the
     * present) is shown as years only, so a CV that only knows years does not gain months it never claimed.
     */
    public function period(): ?string
    {
        if ($this->started_on === null) {
            return $this->ended_on?->format('M Y');
        }

        $yearsOnly = $this->started_on->month === 1 && ($this->ended_on === null || $this->ended_on->month === 12);
        $format = $yearsOnly ? 'Y' : 'M Y';

        return $this->started_on->format($format).' - '.($this->ended_on?->format($format) ?? __('Present'));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LedgerEntryType::class,
            'started_on' => 'date',
            'ended_on' => 'date',
            'skills' => 'array',
            'confirmed_at' => 'datetime',
        ];
    }
}
