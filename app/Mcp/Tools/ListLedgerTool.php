<?php

namespace App\Mcp\Tools;

use App\Models\LedgerEntry;
use App\Models\LedgerFact;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List the ledger entries of the user, with their facts and ids. Call this before adding anything, to reuse an existing entry and avoid duplicates.')]
#[IsReadOnly]
class ListLedgerTool extends Tool
{
    public function handle(Request $request): Response
    {
        $entries = $request->user()->ledgerEntries()
            ->with(['facts' => fn ($query) => $query->orderBy('id')])
            ->orderByDesc('id')
            ->get()
            ->map(fn (LedgerEntry $entry) => [
                'id' => $entry->id,
                'type' => $entry->type->value,
                'title' => $entry->title,
                'organization' => $entry->organization,
                'period' => $entry->period(),
                'skills' => $entry->skills ?? [],
                'confirmed' => $entry->confirmed_at !== null,
                'facts' => $entry->facts->map(fn (LedgerFact $fact) => [
                    'id' => $fact->id,
                    'statement' => $fact->statement,
                    'confirmed' => $fact->confirmed_at !== null,
                ])->all(),
            ])
            ->all();

        return Response::json(['entries' => $entries]);
    }
}
