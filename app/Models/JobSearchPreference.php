<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property list<string|array{place: string, mode: string}>|null $locations
 * @property string $remote_preference
 * @property list<string>|null $keywords
 * @property list<string>|null $exclusions
 * @property list<string>|null $feeds
 * @property list<string>|null $boards
 * @property int $min_score
 * @property int|null $salary_min
 * @property int|null $salary_max
 * @property string $salary_currency
 * @property Carbon|null $last_searched_at
 */
#[Fillable(['locations', 'remote_preference', 'keywords', 'exclusions', 'feeds', 'boards', 'min_score', 'salary_min', 'salary_max', 'salary_currency', 'last_searched_at'])]
class JobSearchPreference extends Model
{
    /** Currencies a salary expectation can be given in. */
    public const array CURRENCIES = ['EUR', 'USD', 'GBP', 'CHF', 'SEK', 'DKK', 'NOK', 'PLN', 'CZK', 'CAD', 'AUD'];

    public const string REMOTE_ANY = 'any';

    public const string REMOTE_ONLY = 'remote_only';

    public const string REMOTE_HYBRID = 'hybrid';

    /**
     * The places the user listed, each with the kind of job they accept there. Rows saved before every place
     * had its own setting hold plain names, which all share the old single setting.
     *
     * @return list<array{place: string, mode: string}>
     */
    public function places(): array
    {
        return collect($this->locations ?? [])
            ->map(fn (string|array $entry) => is_string($entry)
                ? ['place' => $entry, 'mode' => $this->remote_preference]
                : ['place' => $entry['place'], 'mode' => $entry['mode']])
            ->values()
            ->all();
    }

    /**
     * The kind of job accepted anywhere that is not a listed place. Rows with plain place names only ever
     * wanted remote jobs elsewhere; for the rest `remote_preference` holds this setting.
     */
    public function elsewhere(): string
    {
        $legacy = collect($this->locations ?? [])->contains(fn (string|array $entry) => is_string($entry));

        return $legacy ? self::REMOTE_ONLY : $this->remote_preference;
    }

    /**
     * @return list<string>
     */
    public function placeNames(): array
    {
        return array_column($this->places(), 'place');
    }

    /**
     * Whether jobs that are not remote are accepted under the given setting.
     */
    public static function acceptsOnSite(string $mode): bool
    {
        return $mode === self::REMOTE_ANY;
    }

    public static function acceptsHybrid(string $mode): bool
    {
        return $mode === self::REMOTE_ANY || $mode === self::REMOTE_HYBRID;
    }

    /**
     * The expectation as prompt text, or null when none is set.
     */
    public function salaryExpectation(): ?string
    {
        $currency = $this->salary_currency ?? 'EUR';

        return match (true) {
            $this->salary_min !== null && $this->salary_max !== null => "{$currency} ".number_format($this->salary_min).' to '.number_format($this->salary_max),
            $this->salary_min !== null => "at least {$currency} ".number_format($this->salary_min),
            $this->salary_max !== null => "up to {$currency} ".number_format($this->salary_max),
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
            'boards' => 'array',
            'min_score' => 'integer',
            'salary_min' => 'integer',
            'salary_max' => 'integer',
            'last_searched_at' => 'datetime',
        ];
    }
}
