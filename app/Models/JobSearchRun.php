<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One pass over the job sources: either the scheduled cron run or a search the user started by hand.
 *
 * @property int $id
 * @property int $user_id
 * @property string $trigger
 * @property Carbon $started_at
 * @property Carbon|null $finished_at
 * @property int|null $fetched
 * @property int|null $scored
 * @property int|null $matched
 * @property list<string>|null $failed_sources
 */
#[Fillable(['trigger', 'started_at', 'finished_at', 'fetched', 'scored', 'matched', 'failed_sources'])]
class JobSearchRun extends Model
{
    public const TRIGGER_SCHEDULED = 'scheduled';

    public const TRIGGER_MANUAL = 'manual';

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<JobPosting, $this>
     */
    public function jobPostings(): HasMany
    {
        return $this->hasMany(JobPosting::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'failed_sources' => 'array',
        ];
    }
}
