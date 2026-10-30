<?php

namespace App\Services\Cv;

use App\Enums\DocumentType;
use App\Enums\LedgerEntryType;
use App\Models\DocumentClaim;
use App\Models\GeneratedDocument;
use App\Models\LedgerEntry;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Collection;

/**
 * Renders a document as Markdown. Sentences come from the verified claims;
 * headings (titles, employers, periods) come straight from the ledger.
 */
class DocumentRenderer
{
    public function toMarkdown(GeneratedDocument $document): string
    {
        $document->loadMissing(['claims.entry', 'claims.facts', 'user']);

        return $document->type === DocumentType::Cv
            ? $this->cv($document)
            : $this->coverLetter($document);
    }

    /**
     * The same content as the Markdown export, typeset on A4.
     */
    public function toPdf(GeneratedDocument $document): string
    {
        $document->loadMissing(['claims.entry', 'claims.facts', 'user']);

        $html = $document->type === DocumentType::Cv
            ? view('pdf.cv', [...$this->cvViewData($document), 'fonts' => resource_path('fonts')])->render()
            : view('pdf.cover-letter', [...$this->coverLetterViewData($document), 'fonts' => resource_path('fonts')])->render();

        $options = new Options;
        $options->set('defaultFont', 'Noto Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('fontDir', $this->fontCache());
        $options->set('fontCache', $this->fontCache());
        $options->set('chroot', [resource_path('fonts'), base_path()]);

        $pdf = new Dompdf($options);
        $pdf->loadHtml($html);
        $pdf->setPaper('A4');
        $pdf->render();

        return $pdf->output();
    }

    /**
     * Where dompdf keeps the font metrics it builds from resources/fonts.
     */
    private function fontCache(): string
    {
        $path = storage_path('app/dompdf');

        if (! is_dir($path)) {
            mkdir($path, 0775, true);
        }

        return $path;
    }

    /**
     * @return array<string, mixed>
     */
    private function cvViewData(GeneratedDocument $document): array
    {
        $claims = $document->claims->groupBy('section');
        $text = fn (Collection $claims): array => $claims->pluck('text')->map(fn (string $text) => $this->pdfText($text))->all();

        $credentials = LedgerSnapshot::forUser($document->user)
            ->entriesOfType(LedgerEntryType::Education->value, LedgerEntryType::Certification->value);

        return [
            ...$this->letterhead($document),
            'summary' => implode(' ', $text($claims->get(DocumentClaim::SECTION_SUMMARY, collect()))),
            'experience' => $claims->get(DocumentClaim::SECTION_EXPERIENCE, collect())
                ->groupBy('group')
                ->map(fn (Collection $bullets) => [
                    ...$this->entryHeading($bullets->first()->entry),
                    'bullets' => $bullets->map(fn (DocumentClaim $claim) => [
                        'text' => $this->pdfText($claim->text),
                        'url' => $this->claimUrl($claim),
                    ])->all(),
                ])
                ->values()
                ->all(),
            'skills' => $text($claims->get(DocumentClaim::SECTION_SKILLS, collect())),
            'credentials' => $credentials->map(fn (LedgerEntry $entry) => $this->entryHeading($entry))->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function coverLetterViewData(GeneratedDocument $document): array
    {
        return [
            ...$this->letterhead($document),
            'paragraphs' => $document->claims->groupBy('group')
                ->map(fn (Collection $sentences) => $this->pdfText($sentences->pluck('text')->implode(' ')))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{name: string, contact: list<string>, links: list<array{label: string, url: string}>}
     */
    private function letterhead(GeneratedDocument $document): array
    {
        $user = $document->user;

        $links = collect([$user->github_url, $user->linkedin_url])
            ->filter()
            ->map(fn (string $url) => [
                'label' => preg_replace('#^https?://(www\.)?#', '', rtrim($url, '/')),
                'url' => $url,
            ])
            ->values()
            ->all();

        return [
            'name' => $this->pdfText($user->name),
            'contact' => array_values(array_filter([$user->email, $user->phone, $user->location ? $this->pdfText($user->location) : null])),
            'links' => $links,
        ];
    }

    /**
     * @return array{title: string, organization: ?string, period: ?string, url: ?string}
     */
    private function entryHeading(?LedgerEntry $entry): array
    {
        return [
            'title' => $this->pdfText($entry?->title ?? ''),
            'organization' => $entry?->organization ? $this->pdfText($entry->organization) : null,
            'period' => $entry?->period(),
            'url' => $entry?->evidence_url,
        ];
    }

    /**
     * The evidence link of a fact the claim cites, unless the entry heading
     * above it already links to the same place.
     */
    private function claimUrl(DocumentClaim $claim): ?string
    {
        return $claim->facts
            ->pluck('evidence_url')
            ->filter(fn (?string $url) => filled($url) && $url !== $claim->entry?->evidence_url)
            ->first();
    }

    /**
     * The built-in PDF fonts have no emoji; drop them rather than print "?".
     */
    private function pdfText(string $text): string
    {
        $emoji = '[\x{1F000}-\x{1FFFF}\x{2600}-\x{27BF}\x{FE0F}\x{200D}]+';

        // A run like "👍/👎" goes entirely, so no stray slash is left behind.
        $text = preg_replace("/{$emoji}(?:\\s*\\/\\s*{$emoji})*\\s*/u", '', $text);

        return trim(preg_replace('/\\s{2,}/', ' ', (string) $text));
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
                    $url = $this->claimUrl($bullet);
                    $lines[] = $url !== null ? "- {$bullet->text} ([".__('source')."]({$url}))" : "- {$bullet->text}";
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
        $title = $entry->evidence_url ? "[{$entry->title}]({$entry->evidence_url})" : $entry->title;
        $heading = implode(' - ', array_filter([$title, $entry->organization]));

        return $entry->period() !== null ? "{$heading} ({$entry->period()})" : $heading;
    }
}
