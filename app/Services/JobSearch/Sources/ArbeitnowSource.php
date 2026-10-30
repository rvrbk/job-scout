<?php

namespace App\Services\JobSearch\Sources;

use App\Models\JobSearchPreference;
use App\Services\JobSearch\FoundJob;
use App\Services\JobSearch\JobSource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Arbeitnow's public job board API: Europe-focused, no key needed.
 */
class ArbeitnowSource implements JobSource
{
    public function available(): bool
    {
        return true;
    }

    public function fetch(JobSearchPreference $preferences): array
    {
        $rows = Http::timeout(20)->get('https://www.arbeitnow.com/api/job-board-api')->throw()->json('data', []);

        return collect($rows)
            ->filter(fn (mixed $row) => is_array($row) && filled($row['title'] ?? null) && filled($row['url'] ?? null))
            ->map(fn (array $row) => new FoundJob(
                source: 'arbeitnow',
                externalId: (string) ($row['slug'] ?? $row['url']),
                title: (string) $row['title'],
                company: $row['company_name'] ?? null,
                location: $row['location'] ?? null,
                remote: isset($row['remote']) ? (bool) $row['remote'] : null,
                url: (string) $row['url'],
                description: FoundJob::plainText($row['description'] ?? ''),
                postedAt: isset($row['created_at']) ? Carbon::createFromTimestamp((int) $row['created_at']) : null,
            ))
            ->values()
            ->all();
    }
}
