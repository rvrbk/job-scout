<?php

namespace App\Http\Controllers;

use App\Http\Requests\JobSearchPreferenceRequest;
use App\Jobs\RunJobSearch;
use App\Models\JobPosting;
use App\Services\Cv\LedgerSnapshot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
            'boards' => [],
            ...array_filter($request->validated(), fn (mixed $value) => $value !== null),
            'salary_min' => $request->validated('salary_min'),
            'salary_max' => $request->validated('salary_max'),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Search preferences saved.')]);

        return back();
    }

    public function search(Request $request): RedirectResponse
    {
        if (! LedgerSnapshot::forUser($request->user())->hasFacts()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Confirm some facts in your ledger first.')]);

            return back();
        }

        // Scoring calls the model once per batch: too slow for a web request, so it runs after the response.
        $lock = Cache::lock(RunJobSearch::lockKey($request->user()), 1800);

        if (! $lock->get()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('A search is already running.')]);

            return back();
        }

        RunJobSearch::dispatchAfterResponse($request->user(), $lock->owner());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Search started. It can take a few minutes; reload the page to see new matches.')]);

        return back();
    }

    public function dismiss(JobPosting $jobPosting): RedirectResponse
    {
        Gate::authorize('update', $jobPosting);

        $jobPosting->update(['dismissed_at' => now()]);

        return back();
    }
}
