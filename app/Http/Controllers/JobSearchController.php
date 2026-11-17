<?php

namespace App\Http\Controllers;

use App\Http\Requests\JobSearchPreferenceRequest;
use App\Jobs\RunJobSearch;
use App\Models\JobPosting;
use App\Services\Cv\LedgerSnapshot;
use App\Services\JobSearch\BoardFinder;
use App\Services\Llm\LlmException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use RuntimeException;

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

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Search started. It can take a few minutes; new matches appear on this page by themselves.')]);

        return back();
    }

    /**
     * Company boards that suit the user's ledger and saved preferences, with how many of their open jobs fit.
     */
    public function suggestBoards(Request $request, BoardFinder $finder): JsonResponse
    {
        set_time_limit(120);

        try {
            return response()->json(['boards' => $finder->suggest($request->user())]);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (LlmException) {
            return response()->json(['message' => __('Could not look for boards right now. Try again in a moment.')], 503);
        }
    }

    public function save(JobPosting $jobPosting): RedirectResponse
    {
        Gate::authorize('update', $jobPosting);

        $jobPosting->update(['saved_at' => $jobPosting->saved_at === null ? now() : null]);

        return back();
    }

    public function dismiss(JobPosting $jobPosting): RedirectResponse
    {
        Gate::authorize('update', $jobPosting);

        $jobPosting->update(['dismissed_at' => now()]);

        return back();
    }
}
