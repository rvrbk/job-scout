<?php

namespace App\Http\Controllers;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Jobs\GenerateDocument;
use App\Models\DocumentClaim;
use App\Models\GeneratedDocument;
use App\Models\JobPosting;
use App\Models\LedgerFact;
use App\Services\Cv\DocumentRenderer;
use App\Services\Cv\LedgerSnapshot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class GeneratedDocumentController extends Controller
{
    /**
     * Start drafting a CV or cover letter for a job posting.
     */
    public function store(Request $request, JobPosting $jobPosting): RedirectResponse
    {
        Gate::authorize('update', $jobPosting);

        $validated = $request->validate([
            'type' => ['required', Rule::enum(DocumentType::class)],
        ]);

        if (! LedgerSnapshot::forUser($request->user())->hasFacts()) {
            throw ValidationException::withMessages([
                'type' => __('Add and confirm at least one fact in your ledger first.'),
            ]);
        }

        $document = $request->user()->generatedDocuments()->create([
            'job_posting_id' => $jobPosting->id,
            'type' => $validated['type'],
            'status' => DocumentStatus::Generating,
        ]);

        GenerateDocument::dispatch($document);

        return to_route('documents.show', $document);
    }

    public function show(GeneratedDocument $generatedDocument, DocumentRenderer $renderer): Response
    {
        Gate::authorize('view', $generatedDocument);

        $generatedDocument->load(['jobPosting', 'claims.facts', 'claims.entry']);

        return Inertia::render('documents/Show', [
            'document' => [
                'id' => $generatedDocument->id,
                'type' => $generatedDocument->type->value,
                'type_label' => $generatedDocument->type->label(),
                'status' => $generatedDocument->status->value,
                'is_processing' => $generatedDocument->status->isProcessing(),
                'can_be_approved' => $generatedDocument->canBeApproved(),
                'approved_at' => $generatedDocument->approved_at?->toDateTimeString(),
                'error' => $generatedDocument->error,
                'gaps' => $generatedDocument->gaps ?? [],
                'job' => $generatedDocument->jobPosting->only(['id', 'title', 'company']),
                'claims' => $generatedDocument->claims->map(fn (DocumentClaim $claim) => [
                    'id' => $claim->id,
                    'section' => $claim->section,
                    'group' => $claim->group,
                    'kind' => $claim->kind->value,
                    'text' => $claim->text,
                    'status' => $claim->status->value,
                    'verifier_note' => $claim->verifier_note,
                    'entry_heading' => $claim->entry === null ? null : implode(' - ', array_filter([
                        $claim->entry->title,
                        $claim->entry->organization,
                        $claim->entry->period(),
                    ])),
                    'facts' => $claim->facts->map(fn (LedgerFact $fact) => [
                        'id' => $fact->id,
                        'statement' => $fact->statement,
                    ]),
                ]),
                'markdown' => $generatedDocument->status->isProcessing() ? null : $renderer->toMarkdown($generatedDocument),
            ],
        ]);
    }

    /**
     * Draft the document again from scratch after a failure.
     */
    public function retry(GeneratedDocument $generatedDocument): RedirectResponse
    {
        Gate::authorize('update', $generatedDocument);

        $generatedDocument->update(['status' => DocumentStatus::Generating, 'error' => null, 'approved_at' => null]);

        GenerateDocument::dispatch($generatedDocument);

        return to_route('documents.show', $generatedDocument);
    }

    /**
     * The review gate: only a document whose every claim is verified can be approved.
     */
    public function approve(GeneratedDocument $generatedDocument): RedirectResponse
    {
        Gate::authorize('update', $generatedDocument);

        if (! $generatedDocument->canBeApproved()) {
            throw ValidationException::withMessages([
                'approve' => __('Resolve every flagged sentence before approving.'),
            ]);
        }

        $generatedDocument->update(['status' => DocumentStatus::Approved, 'approved_at' => now()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Approved. You can export it now.')]);

        return to_route('documents.show', $generatedDocument);
    }

    public function export(GeneratedDocument $generatedDocument, DocumentRenderer $renderer): \Illuminate\Http\Response
    {
        Gate::authorize('view', $generatedDocument);

        abort_unless($generatedDocument->status === DocumentStatus::Approved, 403, __('Approve the document before exporting it.'));

        $filename = str($generatedDocument->type->value.'-'.$generatedDocument->jobPosting->title)->slug().'.md';

        return response($renderer->toMarkdown($generatedDocument), 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function exportPdf(GeneratedDocument $generatedDocument, DocumentRenderer $renderer): \Illuminate\Http\Response
    {
        Gate::authorize('view', $generatedDocument);

        abort_unless($generatedDocument->status === DocumentStatus::Approved, 403, __('Approve the document before exporting it.'));

        $filename = str($generatedDocument->type->value.'-'.$generatedDocument->jobPosting->title)->slug().'.pdf';

        return response($renderer->toPdf($generatedDocument), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function destroy(GeneratedDocument $generatedDocument): RedirectResponse
    {
        Gate::authorize('delete', $generatedDocument);

        $jobPosting = $generatedDocument->jobPosting;
        $generatedDocument->delete();

        return to_route('jobs.show', $jobPosting);
    }
}
