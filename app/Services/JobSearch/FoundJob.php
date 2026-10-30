<?php

namespace App\Services\JobSearch;

use Carbon\CarbonInterface;

final readonly class FoundJob
{
    public function __construct(
        public string $source,
        public string $externalId,
        public string $title,
        public ?string $company,
        public ?string $location,
        public ?bool $remote,
        public string $url,
        public string $description,
        public ?CarbonInterface $postedAt,
    ) {}

    /**
     * Job feeds ship HTML; the model and the UI want plain text.
     */
    public static function plainText(?string $html): string
    {
        $text = preg_replace(['/<\s*(br|\/p|\/li|\/h\d)\b[^>]*>/i', '/<[^>]+>/'], ["\n", ' '], (string) $html);
        $text = html_entity_decode((string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace(['/[ \t\x{00A0}]+/u', '/\s*\n\s*/'], [' ', "\n"], $text);

        return trim((string) $text);
    }

    public function haystack(): string
    {
        return mb_strtolower("{$this->title} {$this->company} {$this->location} {$this->description}");
    }
}
