<?php

namespace App\Http\Controllers;

use App\Enums\DocumentFont;
use App\Enums\DocumentLanguage;
use App\Enums\DocumentStatus;
use App\Enums\DocumentStyle;
use App\Enums\DocumentTone;
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

        return $this->draft($request, $jobPosting);
    }

    /**
     * Start drafting a general CV or cover letter from the ledger alone.
     */
    public function storeGeneral(Request $request): RedirectResponse
    {
        return $this->draft($request, null);
    }

    private function draft(Request $request, ?JobPosting $jobPosting): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::enum(DocumentType::class)],
            'style' => ['nullable', Rule::enum(DocumentStyle::class)],
            'font' => ['nullable', Rule::enum(DocumentFont::class)],
            'tone' => ['nullable', Rule::enum(DocumentTone::class)],
            'language' => ['nullable', Rule::enum(DocumentLanguage::class)],
        ]);

        if (! LedgerSnapshot::forUser($request->user())->hasFacts()) {
            throw ValidationException::withMessages([
                'type' => __('Add and confirm at least one fact in your ledger first.'),
            ]);
        }

        $user = $request->user();
        $style = DocumentStyle::tryFrom($validated['style'] ?? '') ?? $user->documentStyle();
        $font = DocumentFont::tryFrom($validated['font'] ?? '') ?? $user->documentFont();
        $tone = DocumentTone::tryFrom($validated['tone'] ?? '') ?? DocumentTone::Professional;

        $language = DocumentLanguage::tryFrom($validated['language'] ?? '') ?? $user->documentLanguage();

        // Remember the choice so the next document starts from the same look, tone and language.
        $user->forceFill([
            'preferred_document_style' => $style,
            'preferred_document_font' => $font,
            'preferred_document_tone' => $tone,
            'preferred_document_language' => $language,
        ])->save();

        $document = $request->user()->generatedDocuments()->create([
            'job_posting_id' => $jobPosting?->id,
            'type' => $validated['type'],
            'style' => $style,
            'font' => $font,
            'tone' => $tone,
            'language' => $language,
            'status' => DocumentStatus::Generating,
        ]);

        GenerateDocument::dispatch($document);

        return to_route('documents.show', $document);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function languageOptions(): array
    {
        return collect(DocumentLanguage::cases())->map(fn (DocumentLanguage $language) => [
            'value' => $language->value,
            'label' => $language->label(),
        ])->all();
    }

    /**
     * @return list<array{value: string, label: string, description: string}>
     */
    public static function styleOptions(): array
    {
        return collect(DocumentStyle::cases())->map(fn (DocumentStyle $style) => [
            'value' => $style->value,
            'label' => $style->label(),
            'description' => $style->description(),
        ])->all();
    }

    /**
     * @return list<array{value: string, label: string, description: string}>
     */
    public static function fontOptions(): array
    {
        return collect(DocumentFont::cases())->map(fn (DocumentFont $font) => [
            'value' => $font->value,
            'label' => $font->label(),
            'description' => $font->description(),
        ])->all();
    }

    public function show(GeneratedDocument $generatedDocument, DocumentRenderer $renderer): Response
    {
        Gate::authorize('view', $generatedDocument);

        $generatedDocument->load(['jobPosting', 'claims.facts', 'claims.entry']);

        return Inertia::render('documents/Show', [
            'documentStyles' => $this->styleOptions(),
            'documentFonts' => $this->fontOptions(),
            'document' => [
                'id' => $generatedDocument->id,
                'type' => $generatedDocument->type->value,
                'type_label' => $generatedDocument->type->label(),
                'style' => $generatedDocument->style->value,
                'font' => $generatedDocument->font->value,
                'style_label' => $generatedDocument->style->label(),
                'font_label' => $generatedDocument->font->label(),
                'tone_label' => $generatedDocument->tone->label(),
                'language_label' => $generatedDocument->language->label(),
                'status' => $generatedDocument->status->value,
                'is_processing' => $generatedDocument->status->isProcessing(),
                'can_be_approved' => $generatedDocument->canBeApproved(),
                'approved_at' => $generatedDocument->approved_at?->toDateTimeString(),
                'error' => $generatedDocument->error,
                'gaps' => $generatedDocument->gaps ?? [],
                'job' => $generatedDocument->jobPosting?->only(['id', 'title', 'company']),
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
     * Typeset an existing document differently. Only the PDF changes, so there is nothing to redraft or re-verify
     * and an approval stays. The new look is also what the next document starts from.
     */
    public function updateLook(Request $request, GeneratedDocument $generatedDocument): RedirectResponse
    {
        Gate::authorize('update', $generatedDocument);

        $validated = $request->validate([
            'style' => ['required', Rule::enum(DocumentStyle::class)],
            'font' => ['required', Rule::enum(DocumentFont::class)],
        ]);

        $generatedDocument->update($validated);

        $request->user()->forceFill([
            'preferred_document_style' => $validated['style'],
            'preferred_document_font' => $validated['font'],
        ])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Look updated.')]);

        return to_route('documents.show', $generatedDocument);
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

        $filename = str($generatedDocument->type->value.'-'.$generatedDocument->subject())->slug().'.md';

        return response($renderer->toMarkdown($generatedDocument), 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function exportPdf(GeneratedDocument $generatedDocument, DocumentRenderer $renderer): \Illuminate\Http\Response
    {
        Gate::authorize('view', $generatedDocument);

        abort_unless($generatedDocument->status === DocumentStatus::Approved, 403, __('Approve the document before exporting it.'));

        $filename = str($generatedDocument->type->value.'-'.$generatedDocument->subject())->slug().'.pdf';

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

        return $jobPosting === null ? to_route('jobs.documents') : to_route('jobs.show', $jobPosting);
    }
}
