<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\AddLedgerEntryTool;
use App\Mcp\Tools\AddLedgerFactTool;
use App\Mcp\Tools\ListLedgerTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Job Scout Ledger')]
#[Version('1.0.0')]
#[Instructions(<<<'TEXT'
Records facts about the user's work in their Job Scout facts ledger, which later drives the CVs and cover letters Job Scout writes for them.

A ledger is a list of entries (a project, a job, a certification...), each holding facts: single, concrete, verifiable statements.

Workflow: call list_ledger first to find an existing entry for the project and avoid duplicates. Use add_ledger_entry only when none exists, then add_ledger_fact for each fact.

Only record what is true and demonstrable from the codebase or from what the user told you. Never invent metrics, scale, team size or outcomes. Everything you add is unconfirmed until the user confirms it in Job Scout, so tell the user to review it there.
TEXT)]
class LedgerServer extends Server
{
    protected array $tools = [
        ListLedgerTool::class,
        AddLedgerEntryTool::class,
        AddLedgerFactTool::class,
    ];
}
