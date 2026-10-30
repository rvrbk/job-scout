<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Enums\LedgerEntryType;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private const int DAYS = 14;

    private const int SCORE_BUCKET = 10;

    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $minScore = $user->jobSearchPreference?->min_score ?? 60;

        return Inertia::render('Dashboard', [
            'ledger' => $this->ledger($user),
            'jobs' => $this->jobs($user, $minScore),
            'documents' => $this->documents($user),
            'lastSearchedAt' => $user->jobSearchPreference?->last_searched_at?->toDateTimeString(),
        ]);
    }

    /**
     * Facts are "usable" when both they and their entry are confirmed, which is
     * what generated documents are allowed to draw from.
     *
     * @return array<string, mixed>
     */
    private function ledger(User $user): array
    {
        $entries = $user->ledgerEntries()->with('facts:id,ledger_entry_id,confirmed_at')->get();

        $byType = collect(LedgerEntryType::cases())
            ->reject(fn (LedgerEntryType $type) => $type === LedgerEntryType::Skill)
            ->map(function (LedgerEntryType $type) use ($entries) {
                $facts = $entries->where('type', $type)->flatMap->facts;
                $usable = $entries->where('type', $type)->filter->confirmed_at->flatMap->facts->filter->confirmed_at->count();

                return ['label' => $type->label(), 'usable' => $usable, 'other' => $facts->count() - $usable];
            })
            ->filter(fn (array $row) => $row['usable'] + $row['other'] > 0)
            ->values();

        return [
            'usable_facts' => $byType->sum('usable'),
            'total_facts' => $byType->sum('usable') + $byType->sum('other'),
            'skills' => $entries->where('type', LedgerEntryType::Skill)->filter->confirmed_at->count(),
            'by_type' => $byType->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function jobs(User $user, int $minScore): array
    {
        $found = $user->jobPostings()->whereNotNull('source')->whereNotNull('match_score')
            ->get(['id', 'title', 'company', 'match_score', 'created_at', 'dismissed_at']);

        $matches = $found->filter(fn (JobPosting $job) => $job->match_score >= $minScore && $job->dismissed_at === null);
        $best = $matches->sortByDesc('match_score')->first();

        $buckets = collect(range(0, 100 - self::SCORE_BUCKET, self::SCORE_BUCKET))
            ->map(function (int $start) use ($found, $minScore) {
                $end = $start + self::SCORE_BUCKET;
                $inBucket = $found->filter(fn (JobPosting $job) => $job->match_score >= $start && ($job->match_score < $end || ($end === 100 && $job->match_score === 100)));

                return [
                    'label' => $end === 100 ? "{$start}-100" : "{$start}-".($end - 1),
                    'matches' => $inBucket->where('match_score', '>=', $minScore)->count(),
                    'below' => $inBucket->where('match_score', '<', $minScore)->count(),
                ];
            });

        $today = Carbon::today();
        $days = collect(range(self::DAYS - 1, 0))->map(function (int $ago) use ($found, $minScore, $today) {
            $day = $today->copy()->subDays($ago);
            $onDay = $found->filter(fn (JobPosting $job) => $job->created_at->isSameDay($day));

            return [
                'label' => $day->format('M j'),
                'matches' => $onDay->where('match_score', '>=', $minScore)->count(),
                'below' => $onDay->where('match_score', '<', $minScore)->count(),
            ];
        });

        return [
            'min_score' => $minScore,
            'scored' => $found->count(),
            'matches' => $matches->count(),
            'best' => $best ? ['score' => $best->match_score, 'title' => $best->title, 'company' => $best->company] : null,
            'by_score' => $buckets->values()->all(),
            'by_day' => $days->values()->all(),
            'days' => self::DAYS,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function documents(User $user): array
    {
        $statuses = $user->generatedDocuments()->pluck('status')->countBy(fn ($status) => $status->value);

        return [
            'total' => $statuses->sum(),
            'approved' => $statuses->get(DocumentStatus::Approved->value, 0),
            'to_review' => $statuses->get(DocumentStatus::NeedsReview->value, 0) + $statuses->get(DocumentStatus::Verified->value, 0),
        ];
    }
}
