<?php

namespace App\Mcp\Tools;

use App\Http\Requests\LedgerFactRequest;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Add one fact to an existing ledger entry. A fact is a single concrete, verifiable statement, for example "Built a queue-based import pipeline in Laravel". It is saved unconfirmed; the user confirms it in Job Scout.')]
class AddLedgerFactTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'ledger_entry_id' => ['required', 'integer'],
            ...(new LedgerFactRequest)->rules(),
        ]);

        $entry = $request->user()->ledgerEntries()->find($validated['ledger_entry_id']);

        if ($entry === null) {
            return Response::error('No ledger entry with that id. Call list_ledger to see valid ids.');
        }

        $fact = $entry->facts()->create([
            'statement' => $validated['statement'],
            'evidence_url' => $validated['evidence_url'] ?? null,
        ]);

        return Response::json([
            'id' => $fact->id,
            'message' => 'Fact added, unconfirmed. Ask the user to review and confirm it in Job Scout.',
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'ledger_entry_id' => $schema->integer()->description('Id of the entry, from list_ledger or add_ledger_entry.')->required(),
            'statement' => $schema->string()->description('One factual statement, max 1000 characters. No invented numbers.')->required(),
            'evidence_url' => $schema->string()->description('Public URL backing the statement, if any.'),
        ];
    }
}
