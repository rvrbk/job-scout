<?php

namespace App\Services\JobSearch;

use App\Services\Cv\LedgerSnapshot;
use App\Services\Llm\StructuredLlm;

/**
 * Scores how well found jobs fit the user's confirmed ledger. The score only
 * ranks jobs for the user to look at; nothing the model writes here ends up
 * in an application document.
 */
class JobMatcher
{
    private const int BATCH_SIZE = 8;

    private const int DESCRIPTION_LIMIT = 2500;

    private const string SYSTEM_PROMPT = <<<'PROMPT'
        You help a job seeker decide which job postings are worth their time. You get their facts ledger (confirmed statements about their experience, projects, education, certifications and skills) and a list of postings.

        Score each posting 0-100 for how well the candidate fits it, judged only on the ledger:
        - 80-100: strong fit. The core requirements and seniority are visibly covered by the ledger.
        - 60-79: good fit with minor gaps.
        - 40-59: partial fit; a significant requirement is missing or the focus differs.
        - 0-39: poor fit or a different field.
        Be strict. Matching one keyword isn't fit; the role, seniority, and main stack have to line up. Don't give credit for anything the ledger doesn't state.

        If the candidate gives a salary expectation, lower the score of a posting that states a pay range clearly below it. A posting that states no salary isn't penalised, and the expectation never raises a score.

        reason: one or two plain sentences naming the strongest match and the biggest gap. Mention only things that appear in the ledger or the posting.
        PROMPT;

    public function __construct(private StructuredLlm $llm) {}

    /**
     * @param  list<FoundJob>  $jobs
     * @return array<int, array{score: int, reason: string}> keyed by the job's position in $jobs
     */
    public function score(array $jobs, LedgerSnapshot $snapshot, ?string $salaryExpectation = null): array
    {
        $ledger = json_encode($snapshot->toPromptArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $expectation = $salaryExpectation === null ? '' : "<salary_expectation>\n{$salaryExpectation} gross per year\n</salary_expectation>\n\n";
        $results = [];

        foreach (array_chunk($jobs, self::BATCH_SIZE, preserve_keys: true) as $batch) {
            $output = $this->llm->generate(
                self::SYSTEM_PROMPT,
                "<ledger>\n{$ledger}\n</ledger>\n\n{$expectation}".$this->postings($batch),
                $this->schema(),
            );

            foreach ($output['matches'] ?? [] as $match) {
                $index = $match['index'] ?? null;

                if (is_int($index) && array_key_exists($index, $batch) && is_numeric($match['score'] ?? null)) {
                    $results[$index] = [
                        'score' => max(0, min(100, (int) round($match['score']))),
                        'reason' => trim((string) ($match['reason'] ?? '')),
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * @param  array<int, FoundJob>  $batch
     */
    private function postings(array $batch): string
    {
        $blocks = [];

        foreach ($batch as $index => $job) {
            $description = mb_substr($job->description, 0, self::DESCRIPTION_LIMIT);

            $blocks[] = "<posting index=\"{$index}\">\nTitle: {$job->title}\nCompany: {$job->company}\nLocation: {$job->location}\n\n{$description}\n</posting>";
        }

        return "Score every posting below.\n\n".implode("\n\n", $blocks);
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'matches' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'index' => ['type' => 'integer'],
                            'score' => ['type' => 'integer'],
                            'reason' => ['type' => 'string'],
                        ],
                        'required' => ['index', 'score', 'reason'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['matches'],
            'additionalProperties' => false,
        ];
    }
}
