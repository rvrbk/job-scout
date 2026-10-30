<?php

namespace App\Services\Cv;

use App\Enums\LedgerEntryType;
use App\Models\LedgerEntry;
use App\Models\LedgerFact;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * The part of a user's ledger that generated documents may draw from:
 * confirmed entries and their confirmed facts. Nothing outside this snapshot
 * is shown to the model or accepted as a source.
 */
final class LedgerSnapshot
{
    /** @var array<int, LedgerFact> */
    private array $factsById = [];

    /** @var array<string, string> lowercase name => name as written in the ledger */
    private array $skillsByKey = [];

    /**
     * @param  Collection<int, LedgerEntry>  $entries
     */
    public function __construct(public readonly Collection $entries)
    {
        foreach ($entries as $entry) {
            if ($entry->type === LedgerEntryType::Skill) {
                $this->skillsByKey[mb_strtolower(trim($entry->title))] ??= trim($entry->title);
            }

            foreach ($entry->facts as $fact) {
                $this->factsById[$fact->id] = $fact;
            }

            foreach ($entry->skills ?? [] as $skill) {
                $this->skillsByKey[mb_strtolower(trim($skill))] ??= trim($skill);
            }
        }
    }

    public static function forUser(User $user): self
    {
        $entries = $user->ledgerEntries()
            ->confirmed()
            ->with(['facts' => fn ($query) => $query->confirmed()->orderBy('id')])
            ->orderByDesc('started_on')
            ->get();

        return new self($entries);
    }

    public function hasFacts(): bool
    {
        return $this->factsById !== [];
    }

    public function fact(int $id): ?LedgerFact
    {
        return $this->factsById[$id] ?? null;
    }

    public function entry(int $id): ?LedgerEntry
    {
        return $this->entries->firstWhere('id', $id);
    }

    /**
     * Return the skill as written in the ledger, or null when the ledger doesn't list it.
     */
    public function canonicalSkill(string $name): ?string
    {
        return $this->skillsByKey[mb_strtolower(trim($name))] ?? null;
    }

    /**
     * @return list<string>
     */
    public function skills(): array
    {
        return array_values($this->skillsByKey);
    }

    /**
     * @return Collection<int, LedgerEntry>
     */
    public function entriesOfType(string ...$types): Collection
    {
        return $this->entries->filter(fn (LedgerEntry $entry) => in_array($entry->type->value, $types, true))->values();
    }

    /**
     * The ledger as the model sees it.
     *
     * @return array{entries: array<int, array<string, mixed>>, skills: list<string>}
     */
    public function toPromptArray(): array
    {
        return [
            'entries' => $this->entries->reject(fn (LedgerEntry $entry) => $entry->type === LedgerEntryType::Skill)->values()->map(fn (LedgerEntry $entry) => [
                'entry_id' => $entry->id,
                'type' => $entry->type->value,
                'title' => $entry->title,
                'organization' => $entry->organization,
                'period' => $entry->period(),
                'skills' => $entry->skills ?? [],
                'facts' => $entry->facts->map(fn (LedgerFact $fact) => [
                    'fact_id' => $fact->id,
                    'statement' => $fact->statement,
                ])->all(),
            ])->all(),
            'skills' => $this->skills(),
        ];
    }
}
