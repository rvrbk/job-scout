<?php

namespace App\Enums;

/**
 * The typeface of a CV or cover letter exported as PDF. Combined with a DocumentStyle (the colours) this gives each
 * user a document that feels like theirs. Only the typesetting differs, never the content.
 */
enum DocumentFont: string
{
    case Standard = 'standard';
    case Clean = 'clean';
    case Friendly = 'friendly';
    case Serif = 'serif';
    case Editorial = 'editorial';

    public function label(): string
    {
        return match ($this) {
            self::Standard => __('Standard'),
            self::Clean => __('Clean'),
            self::Friendly => __('Friendly'),
            self::Serif => __('Serif'),
            self::Editorial => __('Editorial'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Standard => __('Ubuntu headings over Noto Sans text.'),
            self::Clean => __('Noto Sans throughout.'),
            self::Friendly => __('Ubuntu throughout, rounder and warmer.'),
            self::Serif => __('Noto Serif throughout, traditional.'),
            self::Editorial => __('Serif headings over sans-serif text.'),
        };
    }

    /**
     * The font a user gets until they choose one: stable per user, spread evenly over all fonts.
     */
    public static function defaultFor(int $userId): self
    {
        $cases = self::cases();

        return $cases[crc32("font:{$userId}") % count($cases)];
    }
}
