<?php

namespace App\Services\Cv;

use App\Enums\ClaimKind;
use App\Enums\ClaimStatus;
use App\Models\DocumentClaim;
use App\Models\GeneratedDocument;
use App\Models\LedgerFact;
use App\Services\Llm\StructuredLlm;
use Illuminate\Support\Collection;

/**
 * Checks every pending claim of a document against the user's current ledger.
 * Deterministic checks run first; claims that pass them go to a separate model
 * call that sees only each sentence and the facts it cites. Verification fails
 * closed: a claim without a clear "supported" verdict is marked unsupported.
 */
class ClaimVerifier
{
    private const string SYSTEM_PROMPT = <<<'PROMPT'
        You check a draft CV or cover letter for statements the candidate can't back up. For each sentence you get its kind and the ledger facts it cites; the facts are the only evidence.

        - kind "claim": supported only if everything it asserts about the candidate is stated in, or directly implied by, the cited facts. Paraphrasing and summarising are fine. It is unsupported if it adds specifics (technologies, numbers, durations, team size, outcomes), inflates scope or seniority, or turns a single instance into a general pattern.
        - kind "framing": supported only if it asserts nothing about the candidate's experience, skills, achievements or traits. Statements about the job or the company are fine.

        Give a one-sentence reason for each verdict that names the unsupported part when there is one. Return a result for every claim_id.
        PROMPT;

    public function __construct(private StructuredLlm $llm) {}

    public function verify(GeneratedDocument $document): void
    {
        $snapshot = LedgerSnapshot::forUser($document->user);

        $pending = $document->claims()
            ->where('status', ClaimStatus::Pending)
            ->with('facts')
            ->get();

        $needsModel = $pending->filter(fn (DocumentClaim $claim) => $this->runDeterministicChecks($claim, $snapshot));

        if ($needsModel->isNotEmpty()) {
            $this->runModelCheck($needsModel, $snapshot);
        }

        $document->refreshStatusFromClaims();
    }

    /**
     * Resolve what can be decided without the model. Returns true when the claim
     * still needs the model's judgement.
     */
    private function runDeterministicChecks(DocumentClaim $claim, LedgerSnapshot $snapshot): bool
    {
        if ($claim->kind === ClaimKind::Skill) {
            $canonical = $snapshot->canonicalSkill($claim->text);

            if ($canonical === null) {
                $this->mark($claim, ClaimStatus::Unsupported, __('":skill" is not a skill in your confirmed ledger.', ['skill' => $claim->text]));
            } else {
                $claim->text = $canonical;
                $this->mark($claim, ClaimStatus::Supported, __('Listed in your ledger.'));
            }

            return false;
        }

        if ($claim->kind === ClaimKind::Framing) {
            return true;
        }

        $facts = $this->confirmedFacts($claim, $snapshot);

        if ($facts->isEmpty()) {
            $this->mark($claim, ClaimStatus::Unsupported, __('Cites no confirmed facts from your ledger.'));

            return false;
        }

        if ($claim->section === DocumentClaim::SECTION_EXPERIENCE) {
            if ($claim->ledger_entry_id === null || $snapshot->entry($claim->ledger_entry_id) === null) {
                $this->mark($claim, ClaimStatus::Unsupported, __('Belongs to an entry that is not in your confirmed ledger.'));

                return false;
            }

            if ($facts->contains(fn (LedgerFact $fact) => $fact->ledger_entry_id !== $claim->ledger_entry_id)) {
                $this->mark($claim, ClaimStatus::Unsupported, __('Cites facts from a different entry than the one it is listed under.'));

                return false;
            }
        }

        $missingNumbers = $this->numbersMissingFromFacts($claim->text, $facts);

        if ($missingNumbers !== []) {
            $this->mark($claim, ClaimStatus::Unsupported, __('Mentions :numbers, which the cited facts do not contain.', ['numbers' => implode(', ', $missingNumbers)]));

            return false;
        }

        return true;
    }

    /**
     * @param  Collection<int, DocumentClaim>  $claims
     */
    private function runModelCheck(Collection $claims, LedgerSnapshot $snapshot): void
    {
        $payload = $claims->map(fn (DocumentClaim $claim) => [
            'claim_id' => $claim->id,
            'kind' => $claim->kind->value,
            'text' => $claim->text,
            'cited_facts' => $this->confirmedFacts($claim, $snapshot)->pluck('statement')->all(),
        ])->values()->all();

        $output = $this->llm->generate(
            self::SYSTEM_PROMPT,
            "<sentences>\n".json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n</sentences>",
            [
                'type' => 'object',
                'properties' => [
                    'results' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'claim_id' => ['type' => 'integer'],
                                'supported' => ['type' => 'boolean'],
                                'reason' => ['type' => 'string'],
                            ],
                            'required' => ['claim_id', 'supported', 'reason'],
                            'additionalProperties' => false,
                        ],
                    ],
                ],
                'required' => ['results'],
                'additionalProperties' => false,
            ],
        );

        $results = is_array($output['results'] ?? null) ? $output['results'] : [];
        $verdicts = collect($results)
            ->filter(fn (mixed $result) => is_array($result) && is_int($result['claim_id'] ?? null))
            ->keyBy('claim_id');

        foreach ($claims as $claim) {
            $verdict = $verdicts->get($claim->id);

            if ($verdict === null) {
                $this->mark($claim, ClaimStatus::Unsupported, __('The verifier returned no verdict for this sentence.'));

                continue;
            }

            $this->mark(
                $claim,
                $verdict['supported'] === true ? ClaimStatus::Supported : ClaimStatus::Unsupported,
                (string) $verdict['reason'],
            );
        }
    }

    /**
     * The claim's cited facts that are still confirmed in the ledger right now.
     *
     * @return Collection<int, LedgerFact>
     */
    private function confirmedFacts(DocumentClaim $claim, LedgerSnapshot $snapshot): Collection
    {
        return $claim->facts
            ->map(fn (LedgerFact $fact) => $snapshot->fact($fact->id))
            ->filter()
            ->values()
            ->toBase();
    }

    /**
     * Numbers in the claim ("40%", "12", "3.5") must appear in at least one cited fact.
     *
     * @param  Collection<int, LedgerFact>  $facts
     * @return list<string>
     */
    private function numbersMissingFromFacts(string $text, Collection $facts): array
    {
        $factNumbers = $this->extractNumbers($facts->pluck('statement')->implode(' '));

        return array_values(array_diff($this->extractNumbers($text), $factNumbers));
    }

    /**
     * @return list<string>
     */
    private function extractNumbers(string $text): array
    {
        preg_match_all('/\d+(?:[.,]\d+)*/', $text, $matches);

        return array_values(array_unique(array_map(
            fn (string $number) => str_replace(',', '', $number),
            $matches[0],
        )));
    }

    private function mark(DocumentClaim $claim, ClaimStatus $status, string $note): void
    {
        $claim->status = $status;
        $claim->verifier_note = $note;
        $claim->save();
    }
}
