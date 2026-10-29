<?php

namespace App\Enums;

enum DocumentType: string
{
    case Cv = 'cv';
    case CoverLetter = 'cover_letter';

    public function label(): string
    {
        return match ($this) {
            self::Cv => __('CV'),
            self::CoverLetter => __('Cover letter'),
        };
    }
}
