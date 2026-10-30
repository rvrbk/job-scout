<?php

namespace App\Http\Controllers;

use App\Http\Requests\JobSearchPreferenceRequest;
use App\Models\JobPosting;
use App\Services\Cv\LedgerSnapshot;
use App\Services\JobSearch\JobSearcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class JobSearchController extends Controller
{
    public function update(JobSearchPreferenceRequest $request): RedirectResponse
    {
        $request->user()->jobSearchPreference()->updateOrCreate([], [
            'locations' => [],
            'keywords' => [],
            'exclusions' => [],
            'feeds' => [],
            ...array_filter($request->validated(), fn (mixed $value) => $value !== null),
            'salary_min' => $request->validated('salary_min'),
            'salary_max' => $request->validated('salary_max'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Search preferences saved.')]);

        return back();
    }

    public function search(Request $request, JobSearcher $searcher): RedirectResponse
    {
        if (! LedgerSnapshot::forUser($request->user())->hasFacts()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Confirm some facts in your ledger first.')]);

            return back();
        }

        // Scoring calls the model once per batch, so this can outlast the default limit.
        set_time_limit(0);

        $result = $searcher->search($request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => trans_choice(
            '{0} No new matches found.|{1} Found 1 new match.|[2,*] Found :count new matches.',
            $result['matched'],
        )]);

        return back();
    }

    public function dismiss(JobPosting $jobPosting): RedirectResponse
    {
        Gate::authorize('update', $jobPosting);

        $jobPosting->update(['dismissed_at' => now()]);

        return back();
    }
}
