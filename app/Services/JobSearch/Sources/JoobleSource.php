<?php

namespace App\Services\JobSearch\Sources;

use App\Models\JobSearchPreference;
use App\Services\JobSearch\FoundJob;
use App\Services\JobSearch\JobSource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Jooble's search API. Needs JOOBLE_API_KEY (free at jooble.org/api/about); skipped without it.
 */
class JoobleSource implements JobSource
{
    private const int MAX_KEYWORDS = 5;

    public function available(): bool
    {
        return filled(config('services.jooble.key'));
    }

    public function fetch(JobSearchPreference $preferences): array
    {
        $location = collect($preferences->locations)->first(fn (string $item) => mb_strtolower($item) !== 'remote');
        $jobs = [];

        foreach (array_slice($preferences->keywords ?: ['developer'], 0, self::MAX_KEYWORDS) as $keyword) {
            $rows = Http::timeout(20)
                ->post('https://jooble.org/api/'.config('services.jooble.key'), array_filter(['keywords' => $keyword, 'location' => $location]))
                ->throw()
                ->json('jobs', []);

            foreach ($rows as $row) {
                if (! is_array($row) || blank($row['title'] ?? null) || blank($row['link'] ?? null)) {
                    continue;
                }

                $jobs[] = new FoundJob(
                    source: 'jooble',
                    externalId: (string) ($row['id'] ?? $row['link']),
                    title: FoundJob::plainText($row['title']),
                    company: filled($row['company'] ?? null) ? $row['company'] : null,
                    location: filled($row['location'] ?? null) ? $row['location'] : null,
                    remote: null,
                    url: (string) $row['link'],
                    description: FoundJob::plainText($row['snippet'] ?? ''),
                    postedAt: filled($row['updated'] ?? null) ? Carbon::parse($row['updated']) : null,
                );
            }
        }

        return $jobs;
    }
}
