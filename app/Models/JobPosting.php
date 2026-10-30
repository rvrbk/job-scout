<?php

namespace App\Models;

use Database\Factories\JobPostingFactory;
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
 * @property string $title
 * @property string|null $company
 * @property string|null $source_url
 * @property string $description
 * @property string|null $location
 * @property bool|null $remote
 * @property string|null $source
 * @property string|null $external_id
 * @property Carbon|null $posted_at
 * @property int|null $match_score
 * @property string|null $match_reason
 * @property Carbon|null $matched_at
 * @property Carbon|null $dismissed_at
 * @property Carbon|null $seen_at
 */
#[Fillable(['title', 'company', 'location', 'remote', 'source_url', 'source', 'external_id', 'description', 'posted_at', 'match_score', 'match_reason', 'matched_at', 'dismissed_at', 'seen_at'])]
class JobPosting extends Model
{
    /** @use HasFactory<JobPostingFactory> */
    use HasFactory;

    /**
     * Jobs worth showing: pasted by hand, or found by the search and scored at or above the threshold.
     *
     * @param  Builder<JobPosting>  $query
     */
    public function scopeVisible(Builder $query, int $minScore): void
    {
        $query->whereNull('dismissed_at')
            ->where(fn (Builder $query) => $query->whereNull('source')->orWhere('match_score', '>=', $minScore));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<GeneratedDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'remote' => 'boolean',
            'posted_at' => 'datetime',
            'matched_at' => 'datetime',
            'dismissed_at' => 'datetime',
            'seen_at' => 'datetime',
            'match_score' => 'integer',
        ];
    }
}
