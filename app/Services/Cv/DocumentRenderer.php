<?php

namespace App\Services\Cv;

use App\Enums\DocumentType;
use App\Enums\LedgerEntryType;
use App\Models\DocumentClaim;
use App\Models\GeneratedDocument;
use App\Models\LedgerEntry;
use Illuminate\Support\Collection;

/**
 * Renders a document as Markdown. Sentences come from the verified claims;
 * headings (titles, employers, periods) come straight from the ledger.
 */
class DocumentRenderer
{
    public function toMarkdown(GeneratedDocument $document): string
    {
        $document->loadMissing(['claims.entry', 'user']);

        return $document->type === DocumentType::Cv
            ? $this->cv($document)
            : $this->coverLetter($document);
    }

    private function cv(GeneratedDocument $document): string
    {
        $claims = $document->claims->groupBy('section');
        $lines = ["# {$document->user->name}", ''];

        $summary = $claims->get(DocumentClaim::SECTION_SUMMARY, collect());

        if ($summary->isNotEmpty()) {
            $lines[] = '## '.__('Summary');
            $lines[] = '';
            $lines[] = $summary->pluck('text')->implode(' ');
            $lines[] = '';
        }

        $experience = $claims->get(DocumentClaim::SECTION_EXPERIENCE, collect())->groupBy('group');

        if ($experience->isNotEmpty()) {
            $lines[] = '## '.__('Experience');
            $lines[] = '';

            foreach ($experience as $bullets) {
                $entry = $bullets->first()->entry;

                if ($entry !== null) {
                    $lines[] = '### '.$this->heading($entry);
                    $lines[] = '';
                }

                foreach ($bullets as $bullet) {
                    $lines[] = "- {$bullet->text}";
                }

                $lines[] = '';
            }
        }

        $credentials = LedgerSnapshot::forUser($document->user)
            ->entriesOfType(LedgerEntryType::Education->value, LedgerEntryType::Certification->value);

        if ($credentials->isNotEmpty()) {
            $lines[] = '## '.__('Education & certifications');
            $lines[] = '';
            $lines = [...$lines, ...$credentials->map(fn (LedgerEntry $entry) => '- '.$this->heading($entry))->all(), ''];
        }

        $skills = $claims->get(DocumentClaim::SECTION_SKILLS, collect());

        if ($skills->isNotEmpty()) {
            $lines[] = '## '.__('Skills');
            $lines[] = '';
            $lines[] = $skills->pluck('text')->implode(', ');
            $lines[] = '';
        }

        return rtrim(implode("\n", $lines))."\n";
    }

    private function coverLetter(GeneratedDocument $document): string
    {
        /** @var Collection<int, Collection<int, DocumentClaim>> $paragraphs */
        $paragraphs = $document->claims->groupBy('group');

        return $paragraphs
            ->map(fn (Collection $sentences) => $sentences->pluck('text')->implode(' '))
            ->implode("\n\n")."\n";
    }

    private function heading(LedgerEntry $entry): string
    {
        $parts = array_filter([$entry->title, $entry->organization]);
        $heading = implode(' - ', $parts);

        return $entry->period() !== null ? "{$heading} ({$entry->period()})" : $heading;
    }
}
