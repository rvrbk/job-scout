<?php

namespace App\Services\JobSearch\Sources;

use App\Models\JobSearchPreference;
use App\Services\JobSearch\FoundJob;
use App\Services\JobSearch\JobSource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * The Muse's public API: no key needed, software engineering category only.
 */
class MuseSource implements JobSource
{
    private const int PAGES = 3;

    public function available(): bool
    {
        return true;
    }

    public function fetch(JobSearchPreference $preferences): array
    {
        $rows = [];

        for ($page = 1; $page <= self::PAGES; $page++) {
            $rows = [...$rows, ...Http::timeout(20)
                ->withUserAgent('job-scout')
                ->get('https://www.themuse.com/api/public/jobs', ['category' => 'Software Engineering', 'page' => $page])
                ->throw()
                ->json('results', [])];
        }

        return collect($rows)
            ->filter(fn (mixed $row) => is_array($row) && filled($row['name'] ?? null) && filled($row['refs']['landing_page'] ?? null))
            ->map(function (array $row) {
                $locations = collect($row['locations'] ?? [])->pluck('name')->filter();

                return new FoundJob(
                    source: 'themuse',
                    externalId: (string) ($row['id'] ?? $row['refs']['landing_page']),
                    title: (string) $row['name'],
                    company: $row['company']['name'] ?? null,
                    location: $locations->implode(', ') ?: null,
                    remote: $locations->contains(fn (string $name) => str_contains(mb_strtolower($name), 'remote')) ?: null,
                    url: (string) $row['refs']['landing_page'],
                    description: FoundJob::plainText($row['contents'] ?? ''),
                    postedAt: filled($row['publication_date'] ?? null) ? Carbon::parse($row['publication_date']) : null,
                );
            })
            ->values()
            ->all();
    }
}
