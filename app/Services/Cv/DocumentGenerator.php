<?php

namespace App\Services\Cv;

use App\Enums\ClaimKind;
use App\Enums\ClaimStatus;
use App\Enums\DocumentType;
use App\Models\DocumentClaim;
use App\Models\GeneratedDocument;
use App\Services\Llm\StructuredLlm;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Drafts a CV or cover letter from the user's confirmed ledger. The model only
 * writes sentences; each one is stored as a claim with the fact IDs it cites,
 * so the verifier can check it. Titles, employers and dates never pass through
 * the model: the renderer takes them from the ledger.
 */
class DocumentGenerator
{
    private const string SYSTEM_PROMPT = <<<'PROMPT'
        You draft application documents for a job seeker from their facts ledger: statements they have confirmed are true, each with an ID. The documents have to stay truthful to the ledger, because the candidate will be held to anything they send.

        - Every sentence that says something about the candidate (what they did, know, achieved, or are like) is a claim and must list the IDs of the facts that support it. A claim may rephrase, condense, combine or reorder facts, and choose what to emphasise for this job, but it can't add anything the cited facts don't say: no extra technologies, numbers, durations, titles, outcomes, or broader scope ("led" when the fact says "contributed").
        - Job titles, employers and dates are added from the ledger separately. Don't write them into sentences unless they are part of a cited fact.
        - When the job asks for something the ledger doesn't show, add it to gaps with a short, honest note, such as the closest related fact or simply that it isn't in the ledger. Don't stretch a fact to cover it.
        - A shorter document with only strong claims is better than a longer one with weak ones.
        PROMPT;

    public function __construct(private StructuredLlm $llm) {}

    public function generate(GeneratedDocument $document): void
    {
        $snapshot = LedgerSnapshot::forUser($document->user);

        if (! $snapshot->hasFacts()) {
            throw new RuntimeException(__('Your ledger has no confirmed facts yet.'));
        }

        $isCv = $document->type === DocumentType::Cv;

        $output = $this->llm->generate(
            self::SYSTEM_PROMPT,
            $this->prompt($document, $snapshot, $isCv),
            $isCv ? $this->cvSchema() : $this->coverLetterSchema(),
        );

        DB::transaction(function () use ($document, $snapshot, $output, $isCv) {
            $document->claims()->delete();

            if ($isCv) {
                $this->storeCvClaims($document, $snapshot, $output);
            } else {
                $this->storeCoverLetterClaims($document, $snapshot, $output);
            }

            $gaps = is_array($output['gaps'] ?? null) ? $output['gaps'] : [];

            $document->gaps = array_values(collect($gaps)
                ->filter(fn (mixed $gap) => is_array($gap) && is_string($gap['requirement'] ?? null) && is_string($gap['note'] ?? null))
                ->map(fn (array $gap) => ['requirement' => $gap['requirement'], 'note' => $gap['note']])
                ->all());
            $document->save();
        });
    }

    private function prompt(GeneratedDocument $document, LedgerSnapshot $snapshot, bool $isCv): string
    {
        $posting = $document->jobPosting;

        $task = $isCv
            ? 'Write a CV tailored to this job. summary: two or three sentences. experience: the most relevant ledger entries of any type, most relevant first, each with two to five bullets that cite facts from that same entry only. skills: the relevant skills from the ledger skills list, using the names exactly as written there.'
            : 'Write a cover letter for this job: three or four paragraphs, under about 350 words. Each sentence is either a claim (cites fact IDs) or framing: a greeting, what draws the candidate to this role based on what the posting says, or a closing. Framing must not assert any experience, skill, achievement or trait of the candidate, and must not invent personal history with the company.';

        return implode("\n\n", [
            $task,
            "<job_posting>\nTitle: {$posting->title}\nCompany: {$posting->company}\n\n{$posting->description}\n</job_posting>",
            "<ledger>\n".json_encode($snapshot->toPromptArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n</ledger>",
        ]);
    }

    /**
     * @param  array<string, mixed>  $output
     */
    private function storeCvClaims(GeneratedDocument $document, LedgerSnapshot $snapshot, array $output): void
    {
        foreach (array_values($output['summary'] ?? []) as $position => $sentence) {
            $this->storeClaim($document, $snapshot, DocumentClaim::SECTION_SUMMARY, 0, $position, ClaimKind::Claim, $sentence['text'], $sentence['fact_ids'] ?? []);
        }

        foreach (array_values($output['experience'] ?? []) as $group => $experience) {
            $entry = $snapshot->entry((int) $experience['entry_id']);

            foreach (array_values($experience['bullets'] ?? []) as $position => $bullet) {
                $this->storeClaim($document, $snapshot, DocumentClaim::SECTION_EXPERIENCE, $group, $position, ClaimKind::Claim, $bullet['text'], $bullet['fact_ids'] ?? [], $entry?->id);
            }
        }

        foreach (array_values($output['skills'] ?? []) as $position => $skill) {
            $this->storeClaim($document, $snapshot, DocumentClaim::SECTION_SKILLS, 0, $position, ClaimKind::Skill, $skill, []);
        }
    }

    /**
     * @param  array<string, mixed>  $output
     */
    private function storeCoverLetterClaims(GeneratedDocument $document, LedgerSnapshot $snapshot, array $output): void
    {
        foreach (array_values($output['paragraphs'] ?? []) as $group => $paragraph) {
            foreach (array_values($paragraph['sentences'] ?? []) as $position => $sentence) {
                $kind = ClaimKind::tryFrom($sentence['kind'] ?? '') === ClaimKind::Framing ? ClaimKind::Framing : ClaimKind::Claim;

                $this->storeClaim($document, $snapshot, DocumentClaim::SECTION_LETTER, $group, $position, $kind, $sentence['text'], $sentence['fact_ids'] ?? []);
            }
        }
    }

    /**
     * Store a claim, keeping only cited IDs that exist in the snapshot. Unknown IDs
     * are dropped rather than trusted, so a claim citing only invented IDs ends up
     * with no sources and fails verification.
     *
     * @param  array<int|string>  $factIds
     */
    private function storeClaim(
        GeneratedDocument $document,
        LedgerSnapshot $snapshot,
        string $section,
        int $group,
        int $position,
        ClaimKind $kind,
        string $text,
        array $factIds,
        ?int $ledgerEntryId = null,
    ): void {
        $claim = $document->claims()->create([
            'ledger_entry_id' => $ledgerEntryId,
            'section' => $section,
            'group' => $group,
            'position' => $position,
            'kind' => $kind,
            'text' => trim($text),
            'status' => ClaimStatus::Pending,
        ]);

        $validFactIds = collect($factIds)
            ->map(fn (int|string $id) => (int) $id)
            ->filter(fn (int $id) => $snapshot->fact($id) !== null)
            ->unique()
            ->values()
            ->all();

        $claim->facts()->sync($validFactIds);
    }

    /**
     * @return array<string, mixed>
     */
    private function cvSchema(): array
    {
        return $this->objectSchema([
            'summary' => ['type' => 'array', 'items' => $this->citedSentenceSchema()],
            'experience' => [
                'type' => 'array',
                'items' => $this->objectSchema([
                    'entry_id' => ['type' => 'integer'],
                    'bullets' => ['type' => 'array', 'items' => $this->citedSentenceSchema()],
                ]),
            ],
            'skills' => ['type' => 'array', 'items' => ['type' => 'string']],
            'gaps' => $this->gapsSchema(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function coverLetterSchema(): array
    {
        return $this->objectSchema([
            'paragraphs' => [
                'type' => 'array',
                'items' => $this->objectSchema([
                    'sentences' => [
                        'type' => 'array',
                        'items' => $this->objectSchema([
                            'text' => ['type' => 'string'],
                            'kind' => ['type' => 'string', 'enum' => [ClaimKind::Claim->value, ClaimKind::Framing->value]],
                            'fact_ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
                        ]),
                    ],
                ]),
            ],
            'gaps' => $this->gapsSchema(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function citedSentenceSchema(): array
    {
        return $this->objectSchema([
            'text' => ['type' => 'string'],
            'fact_ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function gapsSchema(): array
    {
        return [
            'type' => 'array',
            'items' => $this->objectSchema([
                'requirement' => ['type' => 'string'],
                'note' => ['type' => 'string'],
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    private function objectSchema(array $properties): array
    {
        return [
            'type' => 'object',
            'properties' => $properties,
            'required' => array_keys($properties),
            'additionalProperties' => false,
        ];
    }
}
