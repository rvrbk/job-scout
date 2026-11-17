<?php

namespace App\Http\Controllers;

use App\Enums\ClaimKind;
use App\Enums\ClaimStatus;
use App\Http\Requests\DocumentClaimRequest;
use App\Jobs\RegenerateClaim;
use App\Jobs\VerifyDocument;
use App\Models\DocumentClaim;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class DocumentClaimController extends Controller
{
    /**
     * Rewrite a sentence. The edit is verified again before the document can be approved.
     */
    public function update(DocumentClaimRequest $request, DocumentClaim $documentClaim): RedirectResponse
    {
        $document = $documentClaim->document;

        Gate::authorize('update', $document);
        abort_if($document->status->isProcessing(), 409, __('Wait for the current check to finish.'));

        $documentClaim->update([
            'text' => $request->validated('text'),
            'status' => ClaimStatus::Pending,
            'verifier_note' => null,
        ]);

        $document->refreshStatusFromClaims();

        VerifyDocument::dispatch($document);

        return to_route('documents.show', $document);
    }

    /**
     * Let the model rewrite a sentence that failed verification, then check it again.
     */
    public function regenerate(DocumentClaim $documentClaim): RedirectResponse
    {
        $document = $documentClaim->document;

        Gate::authorize('update', $document);
        abort_if($document->status->isProcessing(), 409, __('Wait for the current check to finish.'));
        abort_unless($documentClaim->status === ClaimStatus::Unsupported && $documentClaim->kind !== ClaimKind::Skill, 422, __('Only flagged sentences can be rewritten.'));

        $documentClaim->update(['status' => ClaimStatus::Pending]);

        $document->refreshStatusFromClaims();

        RegenerateClaim::dispatch($documentClaim);

        return to_route('documents.show', $document);
    }

    public function destroy(DocumentClaim $documentClaim): RedirectResponse
    {
        $document = $documentClaim->document;

        Gate::authorize('update', $document);
        abort_if($document->status->isProcessing(), 409, __('Wait for the current check to finish.'));

        $documentClaim->delete();

        $document->refreshStatusFromClaims();

        return to_route('documents.show', $document);
    }
}
