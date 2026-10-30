<?php

namespace App\Services\JobSearch\Sources;

use App\Models\JobSearchPreference;
use App\Services\JobSearch\FoundJob;
use App\Services\JobSearch\JobSource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Adzuna's search API. Needs ADZUNA_APP_ID and ADZUNA_APP_KEY; skipped without them.
 */
class AdzunaSource implements JobSource
{
    public function available(): bool
    {
        return filled(config('services.adzuna.app_id')) && filled(config('services.adzuna.app_key'));
    }

    public function fetch(JobSearchPreference $preferences): array
    {
        $query = array_filter([
            'app_id' => config('services.adzuna.app_id'),
            'app_key' => config('services.adzuna.app_key'),
            'results_per_page' => 50,
            'max_days_old' => 30,
            'sort_by' => 'date',
            'what_or' => implode(' ', $preferences->keywords ?: ['developer']),
            'where' => $this->place($preferences),
            'content-type' => 'application/json',
        ]);

        $country = config('services.adzuna.country', 'nl');

        $rows = Http::timeout(20)
            ->get("https://api.adzuna.com/v1/api/jobs/{$country}/search/1", $query)
            ->throw()
            ->json('results', []);

        return collect($rows)
            ->filter(fn (mixed $row) => is_array($row) && filled($row['title'] ?? null) && filled($row['redirect_url'] ?? null))
            ->map(fn (array $row) => new FoundJob(
                source: 'adzuna',
                externalId: (string) ($row['id'] ?? $row['redirect_url']),
                title: FoundJob::plainText($row['title']),
                company: $row['company']['display_name'] ?? null,
                location: $row['location']['display_name'] ?? null,
                remote: null,
                url: (string) $row['redirect_url'],
                description: FoundJob::plainText($row['description'] ?? ''),
                postedAt: filled($row['created'] ?? null) ? Carbon::parse($row['created']) : null,
            ))
            ->values()
            ->all();
    }

    /**
     * Adzuna takes a single place; "remote" isn't one.
     */
    private function place(JobSearchPreference $preferences): ?string
    {
        return collect($preferences->locations)
            ->first(fn (string $location) => mb_strtolower($location) !== 'remote');
    }
}
