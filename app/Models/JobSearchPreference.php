<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property list<string>|null $locations
 * @property string $remote_preference
 * @property list<string>|null $keywords
 * @property list<string>|null $exclusions
 * @property list<string>|null $feeds
 * @property int $min_score
 * @property int|null $salary_min
 * @property int|null $salary_max
 * @property Carbon|null $last_searched_at
 */
#[Fillable(['locations', 'remote_preference', 'keywords', 'exclusions', 'feeds', 'min_score', 'salary_min', 'salary_max', 'last_searched_at'])]
class JobSearchPreference extends Model
{
    public const string REMOTE_ANY = 'any';

    public const string REMOTE_ONLY = 'remote_only';

    public const string REMOTE_HYBRID = 'hybrid';

    /**
     * The expectation as prompt text, or null when none is set.
     */
    public function salaryExpectation(): ?string
    {
        return match (true) {
            $this->salary_min !== null && $this->salary_max !== null => number_format($this->salary_min).' to '.number_format($this->salary_max),
            $this->salary_min !== null => 'at least '.number_format($this->salary_min),
            $this->salary_max !== null => 'up to '.number_format($this->salary_max),
            default => null,
        };
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'locations' => 'array',
            'keywords' => 'array',
            'exclusions' => 'array',
            'feeds' => 'array',
            'min_score' => 'integer',
            'salary_min' => 'integer',
            'salary_max' => 'integer',
            'last_searched_at' => 'datetime',
        ];
    }
}
