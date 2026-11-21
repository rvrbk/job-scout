<?php

namespace App\Mcp\Tools;

use App\Enums\LedgerEntryType;
use App\Http\Requests\LedgerEntryRequest;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Add an entry to the ledger, such as a project, job or certification. It is saved unconfirmed; the user confirms it in Job Scout. Add facts to it afterwards with add_ledger_fact.')]
class AddLedgerEntryTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate((new LedgerEntryRequest)->rules());

        foreach (['started_on', 'ended_on'] as $field) {
            $validated[$field] = filled($validated[$field] ?? null)
                ? CarbonImmutable::createFromFormat('!Y-m', $validated[$field])->startOfMonth()
                : null;
        }

        $validated['skills'] ??= [];

        $entry = $request->user()->ledgerEntries()->create($validated);

        return Response::json([
            'id' => $entry->id,
            'message' => 'Entry added, unconfirmed. Ask the user to review and confirm it in Job Scout.',
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()
                ->enum(array_column(LedgerEntryType::cases(), 'value'))
                ->description('Use "project" for the project being worked on.')
                ->required(),
            'title' => $schema->string()->description('Name of the project, role or certification.')->required(),
            'organization' => $schema->string()->description('Company, client or institution, if any.'),
            'location' => $schema->string(),
            'started_on' => $schema->string()->description('Start month as YYYY-MM.'),
            'ended_on' => $schema->string()->description('End month as YYYY-MM. Omit if ongoing.'),
            'skills' => $schema->array()->items($schema->string())->description('Technologies and skills actually used.'),
            'evidence_url' => $schema->string()->description('Public URL proving it, such as the repository or live site.'),
        ];
    }
}
