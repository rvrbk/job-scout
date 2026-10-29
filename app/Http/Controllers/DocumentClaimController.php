<?php

namespace App\Http\Controllers;

use App\Enums\ClaimStatus;
use App\Http\Requests\DocumentClaimRequest;
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
