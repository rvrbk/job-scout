<?php

namespace App\Http\Controllers;

use App\Enums\DocumentType;
use App\Http\Requests\JobPostingRequest;
use App\Models\GeneratedDocument;
use App\Models\JobPosting;
use App\Models\JobSearchPreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class JobPostingController extends Controller
{
    public function index(Request $request): Response
    {
        $preferences = $request->user()->jobSearchPreference;

        $postings = $request->user()->jobPostings()
            ->visible($preferences->min_score ?? 60)
            ->withCount('documents')
            ->with('documents:id,job_posting_id,type,status')
            ->orderByDesc('match_score')
            ->latest()
            ->get();

        return Inertia::render('jobs/Index', [
            'postings' => $postings->map(fn (JobPosting $posting) => [
                'id' => $posting->id,
                'title' => $posting->title,
                'company' => $posting->company,
                'location' => $posting->location,
                'remote' => $posting->remote,
                'source_url' => $posting->source_url,
                'found' => $posting->source !== null,
                'is_new' => $posting->seen_at === null,
                'match_score' => $posting->match_score,
                'match_reason' => $posting->match_reason,
                'documents_count' => $posting->documents_count,
                'documents' => $posting->documents
                    ->map(fn (GeneratedDocument $document) => [
                        'type' => $document->type->value,
                        'type_label' => $document->type->label(),
                        'status' => $document->status->value,
                    ])
                    ->values(),
                'created_at' => $posting->created_at?->toDateString(),
            ]),
            'preferences' => [
                'locations' => $preferences?->places() ?? [],
                'remote_preference' => $preferences?->elsewhere() ?? JobSearchPreference::REMOTE_ONLY,
                'keywords' => $preferences?->keywords ?? [],
                'exclusions' => $preferences?->exclusions ?? [],
                'feeds' => $preferences?->feeds ?? [],
                'boards' => $preferences?->boards ?? [],
                'min_score' => $preferences?->min_score ?? 60,
                'salary_min' => $preferences?->salary_min,
                'salary_max' => $preferences?->salary_max,
                'last_searched_at' => $preferences?->last_searched_at?->toDateTimeString(),
            ],
        ]);
    }

    public function store(JobPostingRequest $request): RedirectResponse
    {
        $posting = $request->user()->jobPostings()->create($request->validated());

        return to_route('jobs.show', $posting);
    }

    public function show(JobPosting $jobPosting): Response
    {
        Gate::authorize('view', $jobPosting);

        if ($jobPosting->seen_at === null) {
            $jobPosting->forceFill(['seen_at' => now()])->save();
        }

        return Inertia::render('jobs/Show', [
            'posting' => $jobPosting->only(['id', 'title', 'company', 'source_url', 'description']),
            'documents' => $jobPosting->documents()->latest()->get()->map(fn (GeneratedDocument $document) => [
                'id' => $document->id,
                'type' => $document->type->value,
                'type_label' => $document->type->label(),
                'status' => $document->status->value,
                'created_at' => $document->created_at?->toDateTimeString(),
            ]),
            'documentTypes' => collect(DocumentType::cases())->map(fn (DocumentType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ]),
        ]);
    }

    public function destroy(JobPosting $jobPosting): RedirectResponse
    {
        Gate::authorize('delete', $jobPosting);

        $jobPosting->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Job removed.')]);

        return to_route('jobs.index');
    }
}
