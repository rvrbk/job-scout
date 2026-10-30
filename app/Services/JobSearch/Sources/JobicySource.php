<?php

namespace App\Services\JobSearch\Sources;

use App\Models\JobSearchPreference;
use App\Services\JobSearch\FoundJob;
use App\Services\JobSearch\JobSource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Jobicy's public remote jobs API: no key needed. Its terms ask for a credit and a link to the original posting.
 */
class JobicySource implements JobSource
{
    public function available(): bool
    {
        return true;
    }

    public function fetch(JobSearchPreference $preferences): array
    {
        $rows = Http::timeout(20)
            ->get('https://jobicy.com/api/v2/remote-jobs', ['count' => 100, 'industry' => 'dev'])
            ->throw()
            ->json('jobs', []);

        return collect($rows)
            ->filter(fn (mixed $row) => is_array($row) && filled($row['jobTitle'] ?? null) && filled($row['url'] ?? null))
            ->map(fn (array $row) => new FoundJob(
                source: 'jobicy',
                externalId: (string) ($row['id'] ?? $row['url']),
                title: FoundJob::plainText($row['jobTitle']),
                company: $row['companyName'] ?? null,
                location: $row['jobGeo'] ?? null,
                remote: true,
                url: (string) $row['url'],
                description: FoundJob::plainText($row['jobDescription'] ?? $row['jobExcerpt'] ?? ''),
                postedAt: filled($row['pubDate'] ?? null) ? Carbon::parse($row['pubDate']) : null,
            ))
            ->values()
            ->all();
    }
}
