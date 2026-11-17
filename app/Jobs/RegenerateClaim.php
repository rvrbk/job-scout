<?php

namespace App\Jobs;

use App\Enums\ClaimStatus;
use App\Models\DocumentClaim;
use App\Services\Cv\ClaimVerifier;
use App\Services\Cv\DocumentGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class RegenerateClaim implements ShouldQueue
{
    use Queueable;

    /**
     * Model calls cost money, so a failure is reported instead of retried.
     */
    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(public DocumentClaim $claim) {}

    public function handle(DocumentGenerator $generator, ClaimVerifier $verifier): void
    {
        if (! $generator->regenerateClaim($this->claim)) {
            $this->restore(__('Nothing in your ledger supports a replacement. Edit the sentence yourself or remove it.'));

            return;
        }

        $verifier->verify($this->claim->document);
    }

    public function failed(?Throwable $exception): void
    {
        $this->restore(__('Rewriting failed: :message', ['message' => $exception?->getMessage()]));
    }

    private function restore(string $note): void
    {
        $this->claim->update(['status' => ClaimStatus::Unsupported, 'verifier_note' => $note]);
        $this->claim->document->refreshStatusFromClaims();
    }
}
