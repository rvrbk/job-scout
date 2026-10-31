<?php

namespace App\Enums;

/**
 * How a CV or cover letter looks when exported as PDF. Only the typesetting differs, never the content.
 */
enum DocumentStyle: string
{
    case Classic = 'classic';
    case Modern = 'modern';
    case Minimal = 'minimal';
    case Bold = 'bold';

    public function label(): string
    {
        return match ($this) {
            self::Classic => __('Classic'),
            self::Modern => __('Modern'),
            self::Minimal => __('Minimal'),
            self::Bold => __('Bold'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Classic => __('Teal accents, ruled sections.'),
            self::Modern => __('Indigo accents, airy sections, solid skill tags.'),
            self::Minimal => __('Plain black and grey, no decoration.'),
            self::Bold => __('Dark header band with an amber accent.'),
        };
    }
}
