<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case Generating = 'generating';
    case Verifying = 'verifying';
    case NeedsReview = 'needs_review';
    case Verified = 'verified';
    case Approved = 'approved';
    case Failed = 'failed';

    public function isProcessing(): bool
    {
        return in_array($this, [self::Generating, self::Verifying], true);
    }
}
