<?php

namespace App\Services\Cv;

use App\Enums\LedgerEntryType;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Services\Llm\StructuredLlm;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Reads an existing CV into ledger entries and facts. Nothing already in the ledger is changed or removed, and
 * everything imported is left unconfirmed: the user has to review it before a generated document may use it.
 */
class LedgerImporter
{
    public const int MAX_CHARACTERS = 60000;

    private const string SYSTEM_PROMPT = <<<'PROMPT'
        You turn a candidate's existing CV into entries for their facts ledger. The ledger is the only source later documents may be written from, so it has to say exactly what the CV says.

        - Extract only what the CV states. Never add, infer, round up or embellish: no technologies, numbers, durations, titles or outcomes that are not written there.
        - Each entry is one job, project, degree, certification, achievement or standalone skill group. Facts are single self-contained statements, usually one per bullet or sentence, kept close to the CV's own wording. Write them so they make sense without the surrounding text (start with the verb, don't use "I").
        - Give a job's responsibilities and results as facts of that job. A summary paragraph or skills list becomes facts on a skill or achievement entry, or skills on the entry they belong to.
        - Dates are YYYY-MM. When only a year is given, use January of that year to start and December to end. Leave ended_on empty for a role that is current, and leave a date empty when the CV gives none.
        - Skip entries already in the existing ledger (same role at the same organization, same degree...), and skip facts an existing entry already states. You may still return an existing entry with only its new facts, using the same title and organization.
        - Contact details, photos and references are not ledger material.
        PROMPT;

    public function __construct(private StructuredLlm $llm) {}

    /**
     * @return array{entries: int, facts: int}
     */
    public function import(User $user, string $cvText): array
    {
        $existing = $user->ledgerEntries()->with('facts')->get();

        $output = $this->llm->generate(self::SYSTEM_PROMPT, $this->prompt($cvText, $existing), $this->schema());

        $addedEntries = 0;
        $addedFacts = 0;

        DB::transaction(function () use ($user, $existing, $output, &$addedEntries, &$addedFacts) {
            foreach (is_array($output['entries'] ?? null) ? $output['entries'] : [] as $row) {
                $type = is_array($row) ? LedgerEntryType::tryFrom((string) ($row['type'] ?? '')) : null;
                $title = $type === null ? '' : $this->limit($row['title'] ?? '', 255);

                if ($title === '') {
                    continue;
                }

                $organization = $this->limit($row['organization'] ?? '', 255) ?: null;
                $entry = $existing->first(fn (LedgerEntry $candidate) => $candidate->type === $type
                    && $this->key($candidate->title) === $this->key($title)
                    && $this->key((string) $candidate->organization) === $this->key((string) $organization));

                if ($entry === null) {
                    $entry = $user->ledgerEntries()->create([
                        'type' => $type,
                        'title' => $title,
                        'organization' => $organization,
                        'location' => $this->limit($row['location'] ?? '', 255) ?: null,
                        'started_on' => $this->month($row['started_on'] ?? null),
                        'ended_on' => $this->month($row['ended_on'] ?? null),
                        'skills' => collect($row['skills'] ?? [])->filter(fn (mixed $skill) => is_string($skill))->map(fn (string $skill) => $this->limit($skill, 100))->filter()->unique(fn (string $skill) => $this->key($skill))->values()->all(),
                    ]);
                    $entry->setRelation('facts', collect());
                    $existing->push($entry);
                    $addedEntries++;
                }

                $known = $entry->facts->map(fn ($fact) => $this->key($fact->statement))->flip();

                foreach ($row['facts'] ?? [] as $statement) {
                    $statement = is_string($statement) ? trim($statement) : '';

                    if ($statement === '' || $known->has($this->key($statement))) {
                        continue;
                    }

                    $entry->facts->push($entry->facts()->create(['statement' => $statement]));
                    $known->put($this->key($statement), true);
                    $addedFacts++;
                }
            }
        });

        return ['entries' => $addedEntries, 'facts' => $addedFacts];
    }

    /**
     * @param  iterable<LedgerEntry>  $existing
     */
    private function prompt(string $cvText, iterable $existing): string
    {
        $ledger = collect($existing)->map(fn (LedgerEntry $entry) => [
            'type' => $entry->type->value,
            'title' => $entry->title,
            'organization' => $entry->organization,
            'period' => $entry->period(),
            'facts' => $entry->facts->pluck('statement')->all(),
        ])->values()->all();

        return implode("\n\n", [
            'Extract the ledger entries from the CV below.',
            "<existing_ledger>\n".json_encode($ledger, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n</existing_ledger>",
            "<cv>\n".mb_substr($cvText, 0, self::MAX_CHARACTERS)."\n</cv>",
        ]);
    }

    private function month(mixed $value): ?CarbonImmutable
    {
        return is_string($value) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value) === 1
            ? CarbonImmutable::createFromFormat('!Y-m', $value)->startOfMonth()
            : null;
    }

    private function limit(mixed $value, int $max): string
    {
        return is_string($value) ? mb_substr(trim($value), 0, $max) : '';
    }

    private function key(string $text): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/', ' ', $text)));
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(): array
    {
        $text = ['type' => 'string'];

        return [
            'type' => 'object',
            'properties' => [
                'entries' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'type' => ['type' => 'string', 'enum' => array_column(LedgerEntryType::cases(), 'value')],
                            'title' => $text,
                            'organization' => $text,
                            'location' => $text,
                            'started_on' => $text,
                            'ended_on' => $text,
                            'skills' => ['type' => 'array', 'items' => $text],
                            'facts' => ['type' => 'array', 'items' => $text],
                        ],
                        'required' => ['type', 'title', 'organization', 'location', 'started_on', 'ended_on', 'skills', 'facts'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['entries'],
            'additionalProperties' => false,
        ];
    }
}
