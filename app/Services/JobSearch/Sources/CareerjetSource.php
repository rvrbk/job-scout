<?php

namespace App\Services\JobSearch\Sources;

use App\Models\JobSearchPreference;
use App\Services\JobSearch\FoundJob;
use App\Services\JobSearch\JobSource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Careerjet's partner search API (v4). Needs CAREERJET_API_KEY; skipped without it.
 */
class CareerjetSource implements JobSource
{
    private const int MAX_KEYWORDS = 5;

    public function available(): bool
    {
        return filled(config('services.careerjet.key'));
    }

    public function fetch(JobSearchPreference $preferences): array
    {
        $location = collect($preferences->placeNames())->first(fn (string $item) => mb_strtolower($item) !== 'remote');
        $jobs = [];

        foreach (array_slice($preferences->keywords ?: ['developer'], 0, self::MAX_KEYWORDS) as $keyword) {
            $rows = Http::timeout(20)
                ->withBasicAuth((string) config('services.careerjet.key'), '')
                ->withHeaders(['Referer' => config('app.url')])
                ->get('https://search.api.careerjet.net/v4/query', array_filter([
                    'locale_code' => config('services.careerjet.locale', 'nl_NL'),
                    'keywords' => $keyword,
                    'location' => $location,
                    'sort' => 'date',
                    'page_size' => 50,
                    'user_ip' => request()->ip() ?? '127.0.0.1',
                    'user_agent' => 'job-scout',
                ]))
                ->throw()
                ->json('jobs', []);

            foreach ($rows as $row) {
                if (! is_array($row) || blank($row['title'] ?? null) || blank($row['url'] ?? null)) {
                    continue;
                }

                $jobs[] = new FoundJob(
                    source: 'careerjet',
                    externalId: (string) $row['url'],
                    title: FoundJob::plainText($row['title']),
                    company: filled($row['company'] ?? null) ? $row['company'] : null,
                    location: filled($row['locations'] ?? null) ? $row['locations'] : null,
                    remote: null,
                    url: (string) $row['url'],
                    description: FoundJob::plainText($row['description'] ?? ''),
                    postedAt: filled($row['date'] ?? null) ? Carbon::parse($row['date']) : null,
                );
            }
        }

        return $jobs;
    }
}
