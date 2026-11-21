<?php

namespace Tests\Feature;

use App\Mcp\Servers\LedgerServer;
use App\Mcp\Tools\AddLedgerEntryTool;
use App\Mcp\Tools\AddLedgerFactTool;
use App\Mcp\Tools\ListLedgerTool;
use App\Models\LedgerEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerMcpTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_server_rejects_requests_without_a_token()
    {
        $this->postJson('/mcp/ledger', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])
            ->assertUnauthorized();
    }

    public function test_a_token_gives_access_to_the_server()
    {
        $token = User::factory()->create()->createToken('test', ['ledger'])->plainTextToken;

        $this->withToken($token)
            ->postJson('/mcp/ledger', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])
            ->assertOk()
            ->assertJsonPath('result.tools.0.name', 'list-ledger-tool');
    }

    public function test_an_added_entry_is_unconfirmed_and_belongs_to_the_user()
    {
        $user = User::factory()->create();

        LedgerServer::actingAs($user)->tool(AddLedgerEntryTool::class, [
            'type' => 'project',
            'title' => 'Job Scout',
            'started_on' => '2026-03',
            'skills' => ['Laravel', 'Vue'],
            'evidence_url' => 'https://example.com/repo',
        ])->assertOk();

        $entry = $user->ledgerEntries()->sole();
        $this->assertSame('Job Scout', $entry->title);
        $this->assertSame('2026-03', $entry->started_on->format('Y-m'));
        $this->assertNull($entry->confirmed_at);
    }

    public function test_an_invalid_entry_is_rejected()
    {
        $user = User::factory()->create();

        LedgerServer::actingAs($user)->tool(AddLedgerEntryTool::class, [
            'type' => 'hobby',
            'title' => 'Nope',
        ])->assertHasErrors();

        $this->assertSame(0, $user->ledgerEntries()->count());
    }

    public function test_an_added_fact_is_unconfirmed()
    {
        $user = User::factory()->create();
        $entry = LedgerEntry::factory()->for($user)->create();

        LedgerServer::actingAs($user)->tool(AddLedgerFactTool::class, [
            'ledger_entry_id' => $entry->id,
            'statement' => 'Built a queue-based import pipeline.',
        ])->assertOk();

        $fact = $entry->facts()->sole();
        $this->assertSame('Built a queue-based import pipeline.', $fact->statement);
        $this->assertNull($fact->confirmed_at);
    }

    public function test_facts_cannot_be_added_to_another_users_entry()
    {
        $user = User::factory()->create();
        $foreign = LedgerEntry::factory()->create();

        LedgerServer::actingAs($user)->tool(AddLedgerFactTool::class, [
            'ledger_entry_id' => $foreign->id,
            'statement' => 'Not mine.',
        ])->assertHasErrors();

        $this->assertSame(0, $foreign->facts()->count());
    }

    public function test_listing_only_shows_the_users_own_entries()
    {
        $user = User::factory()->create();
        LedgerEntry::factory()->for($user)->create(['title' => 'Mine']);
        LedgerEntry::factory()->create(['title' => 'Theirs']);

        LedgerServer::actingAs($user)->tool(ListLedgerTool::class)
            ->assertOk()
            ->assertSee('Mine')
            ->assertDontSee('Theirs');
    }

    public function test_a_user_can_create_and_revoke_a_token()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('api-tokens.store'), ['name' => 'Laptop'])
            ->assertRedirect(route('api-tokens.index'))
            ->assertSessionHas('newToken');

        $token = $user->tokens()->sole();

        $this->actingAs($user)->delete(route('api-tokens.destroy', $token->id))->assertRedirect();

        $this->assertSame(0, $user->tokens()->count());
    }
}
