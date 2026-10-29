<?php

namespace App\Http\Controllers;

use App\Enums\DocumentType;
use App\Http\Requests\JobPostingRequest;
use App\Models\GeneratedDocument;
use App\Models\JobPosting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class JobPostingController extends Controller
{
    public function index(Request $request): Response
    {
        $postings = $request->user()->jobPostings()
            ->withCount('documents')
            ->latest()
            ->get();

        return Inertia::render('jobs/Index', [
            'postings' => $postings->map(fn (JobPosting $posting) => [
                'id' => $posting->id,
                'title' => $posting->title,
                'company' => $posting->company,
                'documents_count' => $posting->documents_count,
                'created_at' => $posting->created_at?->toDateString(),
            ]),
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
