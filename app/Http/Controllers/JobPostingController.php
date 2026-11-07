<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStyle;
use App\Enums\DocumentTone;
use App\Enums\DocumentType;
use App\Http\Requests\JobPostingRequest;
use App\Models\GeneratedDocument;
use App\Models\JobPosting;
use App\Models\JobSearchPreference;
use App\Services\JobSearch\PostingImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class JobPostingController extends Controller
{
    public function index(Request $request): Response
    {
        $preferences = $request->user()->jobSearchPreference;

        // Matches the search found and scored well; they stay one list, best score first.
        $found = $request->user()->jobPostings()
            ->visible($preferences->min_score ?? 60)
            ->whereNotNull('source')
            ->whereNull('saved_at')
            ->withCount('documents')
            ->with('documents:id,job_posting_id,type,status', 'searchRun:id,trigger,started_at')
            ->orderByDesc('match_score')
            ->latest()
            ->get();

        return Inertia::render('jobs/Index', [
            'postings' => $found->map(fn (JobPosting $posting) => $this->summary($posting)),
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
                'salary_currency' => $preferences?->salary_currency ?? 'EUR',
                'currencies' => JobSearchPreference::CURRENCIES,
                'last_searched_at' => $preferences?->last_searched_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Saved and hand-added jobs: searchable, sorted by date added, paged.
     */
    public function saved(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'in:newest,oldest'],
        ]);
        $term = trim($filters['q'] ?? '');
        $sort = $filters['sort'] ?? 'newest';

        $saved = $request->user()->jobPostings()
            ->visible($request->user()->jobSearchPreference->min_score ?? 60)
            ->where(fn ($query) => $query->whereNull('source')->orWhereNotNull('saved_at'))
            ->when($term !== '', function ($query) use ($term) {
                $like = '%'.addcslashes($term, '%_\\').'%';

                $query->where(fn ($query) => $query
                    ->whereLike('title', $like)
                    ->orWhereLike('company', $like)
                    ->orWhereLike('location', $like));
            })
            ->withCount('documents')
            ->with('documents:id,job_posting_id,type,status', 'searchRun:id,trigger,started_at')
            ->orderBy('created_at', $sort === 'oldest' ? 'asc' : 'desc')
            ->orderBy('id', $sort === 'oldest' ? 'asc' : 'desc')
            ->paginate(10, pageName: 'saved_page')
            ->withQueryString()
            ->through(fn (JobPosting $posting) => $this->summary($posting));

        return Inertia::render('jobs/Saved', [
            'saved' => $saved,
            'filters' => ['q' => $term, 'sort' => $sort],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('jobs/Add');
    }

    /**
     * General CVs and cover letters, written from the ledger without a job.
     */
    public function documents(Request $request): Response
    {
        return Inertia::render('jobs/Documents', [
            'generalDocuments' => $request->user()->generatedDocuments()->whereNull('job_posting_id')->latest()->get()->map(fn (GeneratedDocument $document) => [
                'id' => $document->id,
                'type' => $document->type->value,
                'type_label' => $document->type->label(),
                'status' => $document->status->value,
                'created_at' => $document->created_at?->toDateTimeString(),
            ]),
            ...$this->documentOptions($request),
        ]);
    }

    /**
     * Add a vacancy by pasting its text, linking to it, or both. Whatever the
     * user left blank (title, company, or the whole description) is read from
     * the text or the page.
     */
    public function store(JobPostingRequest $request, PostingImporter $importer): RedirectResponse
    {
        $data = $request->validated();
        $field = filled($data['description'] ?? null) ? 'description' : 'source_url';

        try {
            if (blank($data['description'] ?? null)) {
                $data = [...$data, ...$importer->fromUrl($data['source_url'])];
            } elseif (blank($data['title'] ?? null)) {
                $data = [...$data, ...array_filter($importer->fromText($data['description']), fn ($value, $key) => $key !== 'description' && filled($value), ARRAY_FILTER_USE_BOTH)];
            }
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([$field => $exception->getMessage()]);
        }

        $posting = $request->user()->jobPostings()->create($data);

        return to_route('jobs.show', $posting);
    }

    public function show(JobPosting $jobPosting): Response
    {
        Gate::authorize('view', $jobPosting);

        if ($jobPosting->seen_at === null) {
            $jobPosting->forceFill(['seen_at' => now()])->save();
        }

        return Inertia::render('jobs/Show', [
            'posting' => [
                ...$jobPosting->only(['id', 'title', 'company', 'source_url', 'description']),
                'saved' => $jobPosting->saved_at !== null,
            ],
            'documents' => $jobPosting->documents()->latest()->get()->map(fn (GeneratedDocument $document) => [
                'id' => $document->id,
                'type' => $document->type->value,
                'type_label' => $document->type->label(),
                'status' => $document->status->value,
                'created_at' => $document->created_at?->toDateTimeString(),
            ]),
            ...$this->documentOptions(request()),
        ]);
    }

    public function destroy(JobPosting $jobPosting): RedirectResponse
    {
        Gate::authorize('delete', $jobPosting);

        $jobPosting->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Job removed.')]);

        return to_route('jobs.index');
    }

    /**
     * The choices offered when drafting a document.
     *
     * @return array<string, mixed>
     */
    private function documentOptions(Request $request): array
    {
        return [
            'documentStyles' => collect(DocumentStyle::cases())->map(fn (DocumentStyle $style) => [
                'value' => $style->value,
                'label' => $style->label(),
                'description' => $style->description(),
            ]),
            'documentTones' => collect(DocumentTone::cases())->map(fn (DocumentTone $tone) => [
                'value' => $tone->value,
                'label' => $tone->label(),
                'description' => $tone->description(),
            ]),
            'preferredStyle' => $request->user()->preferred_document_style?->value,
            'preferredTone' => $request->user()->preferred_document_tone?->value,
            'documentTypes' => collect(DocumentType::cases())->map(fn (DocumentType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(JobPosting $posting): array
    {
        return [
            'id' => $posting->id,
            'title' => $posting->title,
            'company' => $posting->company,
            'location' => $posting->location,
            'remote' => $posting->remote,
            'source_url' => $posting->source_url,
            'saved' => $posting->saved_at !== null,
            'found' => $posting->source !== null && $posting->saved_at === null,
            'is_new' => $posting->seen_at === null,
            'source' => $posting->source,
            'found_by' => $posting->searchRun === null ? null : [
                'trigger' => $posting->searchRun->trigger,
                'at' => $posting->searchRun->started_at->toIso8601String(),
            ],
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
        ];
    }
}
