<?php

namespace App\Services\JobSearch\Sources;

use App\Models\JobSearchPreference;
use App\Services\JobSearch\FoundJob;
use App\Services\JobSearch\JobSource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

/**
 * One company's public job board on Greenhouse, Lever, Ashby or Workable. Needs no key.
 */
class CompanyBoardSource implements JobSource
{
    public const array PROVIDERS = ['greenhouse', 'lever', 'ashby', 'workable'];

    public function __construct(private string $provider, private string $slug)
    {
        if (! in_array($provider, self::PROVIDERS, true)) {
            throw new InvalidArgumentException("Unknown job board provider {$provider}.");
        }
    }

    /**
     * Parses "greenhouse:stripe"; null when it isn't a supported board.
     */
    public static function fromSpec(string $spec): ?self
    {
        [$provider, $slug] = array_pad(explode(':', trim($spec), 2), 2, '');

        return in_array(mb_strtolower($provider), self::PROVIDERS, true) && preg_match('/^[\w-]+$/', $slug)
            ? new self(mb_strtolower($provider), $slug)
            : null;
    }

    public function available(): bool
    {
        return true;
    }

    public function fetch(JobSearchPreference $preferences): array
    {
        return match ($this->provider) {
            'greenhouse' => $this->greenhouse(),
            'lever' => $this->lever(),
            'ashby' => $this->ashby(),
            'workable' => $this->workable(),
        };
    }

    /**
     * @return list<FoundJob>
     */
    private function greenhouse(): array
    {
        $rows = Http::timeout(20)
            ->get("https://boards-api.greenhouse.io/v1/boards/{$this->slug}/jobs", ['content' => 'true'])
            ->throw()
            ->json('jobs', []);

        return $this->build($rows, fn (array $row) => filled($row['title'] ?? null) && filled($row['absolute_url'] ?? null), fn (array $row) => new FoundJob(
            source: $this->label(),
            externalId: (string) $row['id'],
            title: (string) $row['title'],
            company: $row['company_name'] ?? $this->slug,
            location: $row['location']['name'] ?? null,
            remote: null,
            url: (string) $row['absolute_url'],
            // Greenhouse returns the description as escaped HTML.
            description: FoundJob::plainText(html_entity_decode((string) ($row['content'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
            postedAt: filled($row['first_published'] ?? null) ? Carbon::parse($row['first_published']) : null,
        ));
    }

    /**
     * @return list<FoundJob>
     */
    private function lever(): array
    {
        $rows = Http::timeout(20)
            ->get("https://api.lever.co/v0/postings/{$this->slug}", ['mode' => 'json'])
            ->throw()
            ->json() ?? [];

        return $this->build($rows, fn (array $row) => filled($row['text'] ?? null) && filled($row['hostedUrl'] ?? null), fn (array $row) => new FoundJob(
            source: $this->label(),
            externalId: (string) $row['id'],
            title: (string) $row['text'],
            company: $this->slug,
            location: $row['categories']['location'] ?? null,
            remote: isset($row['workplaceType']) ? $row['workplaceType'] === 'remote' : null,
            url: (string) $row['hostedUrl'],
            description: FoundJob::plainText($row['descriptionPlain'] ?? $row['description'] ?? ''),
            postedAt: isset($row['createdAt']) ? Carbon::createFromTimestampMs((int) $row['createdAt']) : null,
        ));
    }

    /**
     * @return list<FoundJob>
     */
    private function ashby(): array
    {
        $rows = Http::timeout(20)
            ->get("https://api.ashbyhq.com/posting-api/job-board/{$this->slug}")
            ->throw()
            ->json('jobs', []);

        return $this->build($rows, fn (array $row) => filled($row['title'] ?? null) && filled($row['jobUrl'] ?? null) && ($row['isListed'] ?? true), fn (array $row) => new FoundJob(
            source: $this->label(),
            externalId: (string) $row['id'],
            title: (string) $row['title'],
            company: $this->slug,
            location: $row['location'] ?? null,
            remote: isset($row['isRemote']) ? (bool) $row['isRemote'] : null,
            url: (string) $row['jobUrl'],
            description: FoundJob::plainText($row['descriptionPlain'] ?? $row['descriptionHtml'] ?? ''),
            postedAt: filled($row['publishedAt'] ?? null) ? Carbon::parse($row['publishedAt']) : null,
        ));
    }

    /**
     * @return list<FoundJob>
     */
    private function workable(): array
    {
        $response = Http::timeout(20)
            ->get("https://apply.workable.com/api/v1/widget/accounts/{$this->slug}", ['details' => 'true'])
            ->throw();

        return $this->build($response->json('jobs', []), fn (array $row) => filled($row['title'] ?? null) && filled($row['url'] ?? null), fn (array $row) => new FoundJob(
            source: $this->label(),
            externalId: (string) ($row['shortcode'] ?? $row['url']),
            title: (string) $row['title'],
            company: $response->json('name') ?: $this->slug,
            location: collect([$row['city'] ?? null, $row['state'] ?? null, $row['country'] ?? null])->filter()->implode(', ') ?: null,
            remote: isset($row['telecommuting']) ? (bool) $row['telecommuting'] : null,
            url: (string) $row['url'],
            description: FoundJob::plainText($row['description'] ?? ''),
            postedAt: filled($row['published_on'] ?? null) ? Carbon::parse($row['published_on']) : null,
        ));
    }

    /**
     * @param  array<mixed>  $rows
     * @param  callable(array<string, mixed>): bool  $valid
     * @param  callable(array<string, mixed>): FoundJob  $map
     * @return list<FoundJob>
     */
    private function build(array $rows, callable $valid, callable $map): array
    {
        return collect($rows)
            ->filter(fn (mixed $row) => is_array($row) && $valid($row))
            ->map($map)
            ->values()
            ->all();
    }

    private function label(): string
    {
        return "{$this->provider}:{$this->slug}";
    }
}
