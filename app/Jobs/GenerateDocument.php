<?php

namespace App\Jobs;

use App\Enums\DocumentStatus;
use App\Models\GeneratedDocument;
use App\Services\Cv\ClaimVerifier;
use App\Services\Cv\DocumentGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateDocument implements ShouldQueue
{
    use Queueable;

    /**
     * Model calls cost money, so a failure is reported instead of retried.
     */
    public int $tries = 1;

    public int $timeout = 900;

    public function __construct(public GeneratedDocument $document) {}

    public function handle(DocumentGenerator $generator, ClaimVerifier $verifier): void
    {
        $generator->generate($this->document);

        $this->document->update(['status' => DocumentStatus::Verifying]);

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
