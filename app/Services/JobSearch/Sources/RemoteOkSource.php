<?php

namespace App\Services\JobSearch\Sources;

use App\Models\JobSearchPreference;
use App\Services\JobSearch\FoundJob;
use App\Services\JobSearch\JobSource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Remote OK's public feed: no key needed. Its terms require a link back to the posting on Remote OK.
 */
class RemoteOkSource implements JobSource
{
    public function available(): bool
    {
        return true;
    }

    public function fetch(JobSearchPreference $preferences): array
    {
        $rows = Http::timeout(20)->withUserAgent('job-scout')->get('https://remoteok.com/api')->throw()->json() ?? [];

        // The first element is a legal notice, not a job.
        return collect($rows)
            ->filter(fn (mixed $row) => is_array($row) && filled($row['position'] ?? null) && filled($row['url'] ?? null))
            ->map(fn (array $row) => new FoundJob(
                source: 'remoteok',
                externalId: (string) ($row['id'] ?? $row['url']),
                title: FoundJob::plainText($row['position']),
                company: $row['company'] ?? null,
                location: filled($row['location'] ?? null) ? $row['location'] : null,
                remote: true,
                url: (string) $row['url'],
                description: FoundJob::plainText($row['description'] ?? ''),
                postedAt: filled($row['date'] ?? null) ? Carbon::parse($row['date']) : null,
            ))
            ->values()
            ->all();
    }
}
