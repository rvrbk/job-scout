<?php

namespace App\Services\JobSearch\Sources;

use App\Models\JobSearchPreference;
use App\Services\JobSearch\FoundJob;
use App\Services\JobSearch\JobSource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Himalayas' public remote jobs API: no key needed, newest 20 postings per request.
 */
class HimalayasSource implements JobSource
{
    public function available(): bool
    {
        return true;
    }

    public function fetch(JobSearchPreference $preferences): array
    {
        $rows = Http::timeout(20)
            ->withUserAgent('job-scout')
            ->get('https://himalayas.app/jobs/api', ['limit' => 20])
            ->throw()
            ->json('jobs', []);

        return collect($rows)
            ->filter(fn (mixed $row) => is_array($row) && filled($row['title'] ?? null) && filled($row['applicationLink'] ?? null))
            ->map(fn (array $row) => new FoundJob(
                source: 'himalayas',
                externalId: (string) ($row['guid'] ?? $row['applicationLink']),
                title: (string) $row['title'],
                company: $row['companyName'] ?? null,
                location: implode(', ', (array) ($row['locationRestrictions'] ?? [])) ?: null,
                remote: true,
                url: (string) $row['applicationLink'],
                description: FoundJob::plainText($row['description'] ?? $row['excerpt'] ?? ''),
                postedAt: isset($row['pubDate']) ? Carbon::createFromTimestamp((int) $row['pubDate']) : null,
            ))
            ->values()
            ->all();
    }
}
