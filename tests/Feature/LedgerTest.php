<?php

namespace Tests\Feature;

use App\Enums\LedgerEntryType;
use App\Models\LedgerEntry;
use App\Models\LedgerFact;
use App\Models\User;
use App\Services\Cv\LedgerSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class LedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_the_ledger()
    {
        $this->get(route('ledger.index'))->assertRedirect(route('login'));
    }

    public function test_the_ledger_only_shows_the_users_own_entries()
    {
        $user = User::factory()->create();
        $entry = LedgerEntry::factory()->for($user)->create();
        LedgerFact::factory()->for($entry, 'entry')->create();
        LedgerEntry::factory()->create();

        $this->actingAs($user)
            ->get(route('ledger.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('ledger/Index')
                ->has('entries', 1)
                ->has('entries.0.facts', 1)
                ->where('entries.0.id', $entry->id));
    }

    public function test_an_entry_can_be_added()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('ledger.store'), [
                'type' => LedgerEntryType::Experience->value,
                'title' => 'Backend Developer',
                'organization' => 'Acme',
                'started_on' => '2021-03',
                'ended_on' => '',
                'skills' => 'PHP, Laravel, php, ',
                'confirmed' => true,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('ledger.index'));

        $entry = $user->ledgerEntries()->sole();

        $this->assertSame('2021-03-01', $entry->started_on->toDateString());
        $this->assertNull($entry->ended_on);
        $this->assertSame(['PHP', 'Laravel'], $entry->skills);
        $this->assertNotNull($entry->confirmed_at);
    }

    public function test_an_entry_cannot_end_before_it_starts()
    {
        $this->actingAs(User::factory()->create())
            ->post(route('ledger.store'), [
                'type' => LedgerEntryType::Project->value,
                'title' => 'Side project',
                'started_on' => '2022-05',
                'ended_on' => '2021-01',
            ])
            ->assertSessionHasErrors('ended_on');
    }

    public function test_unchecking_confirmation_unconfirms_an_entry()
    {
        $entry = LedgerEntry::factory()->create();

        $this->actingAs($entry->user)
            ->put(route('ledger.update', $entry), [
                'type' => $entry->type->value,
                'title' => $entry->title,
                'confirmed' => false,
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull($entry->fresh()->confirmed_at);
    }

    public function test_users_cannot_change_someone_elses_entry()
    {
        $entry = LedgerEntry::factory()->create();

        $this->actingAs(User::factory()->create())
            ->put(route('ledger.update', $entry), ['type' => 'project', 'title' => 'Hijacked'])
            ->assertForbidden();

        $this->actingAs(User::factory()->create())
            ->delete(route('ledger.destroy', $entry))
            ->assertForbidden();
    }

    public function test_facts_can_be_added_updated_and_deleted()
    {
        $entry = LedgerEntry::factory()->create();

        $this->actingAs($entry->user)
            ->post(route('facts.store', $entry), [
                'statement' => 'Migrated the billing service to Laravel 11.',
                'confirmed' => true,
            ])
            ->assertSessionHasNoErrors();

        $fact = $entry->facts()->sole();
        $this->assertNotNull($fact->confirmed_at);

        $this->actingAs($entry->user)
            ->put(route('facts.update', $fact), ['statement' => 'Migrated billing to Laravel 11.', 'confirmed' => true])
            ->assertSessionHasNoErrors();

        $this->assertSame('Migrated billing to Laravel 11.', $fact->fresh()->statement);

        $this->actingAs($entry->user)->delete(route('facts.destroy', $fact));

        $this->assertModelMissing($fact);
    }

    public function test_users_cannot_touch_facts_on_someone_elses_entry()
    {
        $fact = LedgerFact::factory()->create();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->post(route('facts.store', $fact->entry), ['statement' => 'Planted fact', 'confirmed' => true])
            ->assertForbidden();

        $this->actingAs($intruder)
            ->put(route('facts.update', $fact), ['statement' => 'Changed', 'confirmed' => true])
            ->assertForbidden();

        $this->actingAs($intruder)->delete(route('facts.destroy', $fact))->assertForbidden();
    }

    public function test_standalone_skill_entries_count_as_skills_but_not_as_experience()
    {
        $user = User::factory()->create();
        LedgerEntry::factory()->for($user)->create(['type' => LedgerEntryType::Skill, 'title' => 'Laravel', 'skills' => [], 'confirmed_at' => now()]);
        LedgerEntry::factory()->for($user)->create(['type' => LedgerEntryType::Skill, 'title' => 'Draft skill', 'skills' => [], 'confirmed_at' => null]);

        $snapshot = LedgerSnapshot::forUser($user);

        $this->assertSame(['Laravel'], $snapshot->skills());
        $this->assertSame('Laravel', $snapshot->canonicalSkill('laravel'));
        $this->assertSame([], $snapshot->toPromptArray()['entries']);
    }

    public function test_periods_that_only_know_years_are_shown_as_years()
    {
        $period = fn (?string $start, ?string $end) => LedgerEntry::factory()->make(['started_on' => $start, 'ended_on' => $end])->period();

        $this->assertSame('2023 - 2024', $period('2023-01-01', '2024-12-01'));
        $this->assertSame('2024 - Present', $period('2024-01-01', null));
        $this->assertSame('Mar 2021 - Present', $period('2021-03-01', null));
        $this->assertSame('Jan 2016 - Jun 2019', $period('2016-01-01', '2019-06-01'));
        $this->assertSame('Mar 2019 - Dec 2021', $period('2019-03-01', '2021-12-01'));
    }
}
