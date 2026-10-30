<?php

namespace App\Services\JobSearch\Sources;

use App\Models\JobSearchPreference;
use App\Services\JobSearch\FoundJob;
use App\Services\JobSearch\JobSource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Remotive's public API for remote software jobs: no key needed.
 */
class RemotiveSource implements JobSource
{
    public function available(): bool
    {
        return true;
    }

    public function fetch(JobSearchPreference $preferences): array
    {
        $rows = Http::timeout(20)
            ->get('https://remotive.com/api/remote-jobs', ['category' => 'software-dev', 'limit' => 100])
            ->throw()
            ->json('jobs', []);

        return collect($rows)
            ->filter(fn (mixed $row) => is_array($row) && filled($row['title'] ?? null) && filled($row['url'] ?? null))
            ->map(fn (array $row) => new FoundJob(
                source: 'remotive',
                externalId: (string) ($row['id'] ?? $row['url']),
                title: (string) $row['title'],
                company: $row['company_name'] ?? null,
                location: $row['candidate_required_location'] ?? null,
                remote: true,
                url: (string) $row['url'],
                description: FoundJob::plainText($row['description'] ?? ''),
                postedAt: filled($row['publication_date'] ?? null) ? Carbon::parse($row['publication_date']) : null,
            ))
            ->values()
            ->all();
    }
}
