<?php

namespace App\Enums;

/**
 * How a CV or cover letter is laid out on the page when exported as PDF. Combined with a DocumentStyle (the colours
 * and type) this gives each user a document that looks like theirs. Only the typesetting differs, never the content.
 */
enum DocumentLayout: string
{
    case Classic = 'classic';
    case Sidebar = 'sidebar';
    case Compact = 'compact';

    public function label(): string
    {
        return match ($this) {
            self::Classic => __('Classic'),
            self::Sidebar => __('Sidebar'),
            self::Compact => __('Compact'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Classic => __('One column, summary first.'),
            self::Sidebar => __('Contact, skills and credentials in a side column.'),
            self::Compact => __('Tighter spacing, skills right under the summary.'),
        };
    }

    /**
     * The layout a user gets until they choose one: stable per user, spread evenly over all layouts.
     */
    public static function defaultFor(int $userId): self
    {
        $cases = self::cases();

        return $cases[crc32("layout:{$userId}") % count($cases)];
    }
}
