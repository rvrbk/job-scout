<?php

namespace App\Services\JobSearch;

use App\Models\JobSearchPreference;
use App\Models\User;
use App\Services\Cv\LedgerSnapshot;
use App\Services\JobSearch\Sources\ArbeitnowSource;
use App\Services\JobSearch\Sources\CareerjetSource;
use App\Services\JobSearch\Sources\CompanyBoardSource;
use App\Services\JobSearch\Sources\HimalayasSource;
use App\Services\JobSearch\Sources\JobicySource;
use App\Services\JobSearch\Sources\JoobleSource;
use App\Services\JobSearch\Sources\MuseSource;
use App\Services\JobSearch\Sources\RemoteOkSource;
use App\Services\JobSearch\Sources\RemotiveSource;
use App\Services\JobSearch\Sources\RssSource;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Fetches postings from every configured source, drops what the user's
 * preferences rule out, scores the rest against their ledger, and stores the
 * results. Every scored posting is stored, even a poor match, so it is never
 * paid for twice.
 */
class JobSearcher
{
    /** Cap on model-scored postings per run, to keep cost predictable. */
    public const int MAX_NEW_PER_RUN = 100;

    private const int MAX_AGE_DAYS = 30;

    private const string DEFAULT_FEED = 'https://weworkremotely.com/categories/remote-programming-jobs.rss';

    public function __construct(private JobMatcher $matcher) {}

    /**
     * @return array{fetched: int, scored: int, matched: int, failed_sources: list<string>}
     */
    public function search(User $user): array
    {
        $preferences = $user->jobSearchPreference ?? new JobSearchPreference([
            'remote_preference' => JobSearchPreference::REMOTE_ANY,
            'min_score' => 60,
        ]);

        $snapshot = LedgerSnapshot::forUser($user);

        if (! $snapshot->hasFacts()) {
            throw new RuntimeException(__('Your ledger has no confirmed facts yet.'));
        }

        [$found, $failed] = $this->fetch($preferences);

        $existing = $user->jobPostings()->whereNotNull('source')->get(['source', 'external_id'])
            ->map(fn ($posting) => "{$posting->source}|{$posting->external_id}")
            ->flip();

        $candidates = collect($found)
            ->unique(fn (FoundJob $job) => "{$job->source}|{$job->externalId}")
            ->reject(fn (FoundJob $job) => $existing->has("{$job->source}|{$job->externalId}"))
            ->filter(fn (FoundJob $job) => $this->passes($job, $preferences))
            ->sortByDesc(fn (FoundJob $job) => $job->postedAt?->getTimestamp() ?? 0)
            ->take(self::MAX_NEW_PER_RUN)
            ->values()
            ->all();

        $scores = $candidates === [] ? [] : $this->matcher->score($candidates, $snapshot, $preferences->salaryExpectation());
        $matched = 0;

        foreach ($scores as $index => $match) {
            $job = $candidates[$index];

            $user->jobPostings()->create([
                'title' => $job->title,
                'company' => $job->company,
                'location' => $job->location,
                'remote' => $job->remote,
                'source_url' => $job->url,
                'source' => $job->source,
                'external_id' => $job->externalId,
                'description' => $job->description,
                'posted_at' => $job->postedAt,
                'match_score' => $match['score'],
                'match_reason' => $match['reason'],
                'matched_at' => now(),
            ]);

            if ($match['score'] >= $preferences->min_score) {
                $matched++;
            }
        }

        if ($preferences->exists) {
            $preferences->update(['last_searched_at' => now()]);
        }

        return [
            'fetched' => count($found),
            'scored' => count($scores),
            'matched' => $matched,
            'failed_sources' => $failed,
        ];
    }

    /**
     * @return array{0: list<FoundJob>, 1: list<string>}
     */
    private function fetch(JobSearchPreference $preferences): array
    {
        $found = [];
        $failed = [];

        foreach ($this->sources($preferences) as $label => $source) {
            if (! $source->available()) {
                continue;
            }

            try {
                array_push($found, ...$source->fetch($preferences));
            } catch (Throwable $exception) {
                Log::warning("Job source {$label} failed: {$exception->getMessage()}");
                $failed[] = $label;
            }
        }

        return [$found, $failed];
    }

    /**
     * @return array<string, JobSource>
     */
    protected function sources(JobSearchPreference $preferences): array
    {
        $sources = [
            'arbeitnow' => new ArbeitnowSource,
            'remotive' => new RemotiveSource,
            'jobicy' => new JobicySource,
            'remoteok' => new RemoteOkSource,
            'himalayas' => new HimalayasSource,
            'themuse' => new MuseSource,
            'jooble' => new JoobleSource,
            'careerjet' => new CareerjetSource,
        ];

        foreach ($preferences->boards ?? [] as $spec) {
            if ($board = CompanyBoardSource::fromSpec($spec)) {
                $sources[$spec] = $board;
            }
        }

        foreach (array_unique([self::DEFAULT_FEED, ...($preferences->feeds ?? [])]) as $url) {
            $sources[$url] = new RssSource($url, 'rss:'.(parse_url($url, PHP_URL_HOST) ?: 'feed'));
        }

        return $sources;
    }

    /**
     * Cheap checks that run before any model call.
     */
    private function passes(FoundJob $job, JobSearchPreference $preferences): bool
    {
        if ($job->postedAt !== null && $job->postedAt->lt(now()->subDays(self::MAX_AGE_DAYS))) {
            return false;
        }

        $haystack = $job->haystack();

        foreach ($preferences->exclusions ?? [] as $term) {
            if (str_contains($haystack, mb_strtolower($term))) {
                return false;
            }
        }

        $keywords = $preferences->keywords ?? [];

        if ($keywords !== [] && ! collect($keywords)->contains(fn (string $term) => str_contains($haystack, mb_strtolower($term)))) {
            return false;
        }

        $isRemote = $job->remote === true || str_contains($haystack, 'remote');

        // Remote jobs are fine anywhere.
        if ($isRemote) {
            return true;
        }

        $mode = $this->modeFor($job, $preferences);

        return str_contains($haystack, 'hybrid')
            ? JobSearchPreference::acceptsHybrid($mode)
            : JobSearchPreference::acceptsOnSite($mode);
    }

    /**
     * What the user accepts for a job that is not remote, going by where it is: the most generous setting of the
     * listed places it falls in, else the "everywhere else" setting. A job with no stated location could be in any
     * of them, so it gets the most generous setting overall.
     */
    private function modeFor(FoundJob $job, JobSearchPreference $preferences): string
    {
        $rank = [JobSearchPreference::REMOTE_ONLY => 0, JobSearchPreference::REMOTE_HYBRID => 1, JobSearchPreference::REMOTE_ANY => 2];
        $places = $preferences->places();

        $modes = blank($job->location)
            ? array_column($places, 'mode')
            : collect($places)
                ->filter(fn (array $place) => LocationMatcher::matches($job->location, [$place['place']]))
                ->pluck('mode')
                ->all();

        if ($modes === [] || blank($job->location)) {
            $modes[] = $preferences->elsewhere();
        }

        return collect($modes)->sortByDesc(fn (string $mode) => $rank[$mode] ?? 0)->first();
    }
}
