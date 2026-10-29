<?php

namespace App\Jobs;

use App\Enums\DocumentStatus;
use App\Models\GeneratedDocument;
use App\Services\Cv\ClaimVerifier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class VerifyDocument implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(public GeneratedDocument $document) {}

    public function handle(ClaimVerifier $verifier): void
    {
        $verifier->verify($this->document);
    }

    public function failed(?Throwable $exception): void
    {
        $this->document->update([
            'status' => DocumentStatus::Failed,
            'error' => $exception?->getMessage(),
        ]);
    }
}
