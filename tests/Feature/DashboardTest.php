<?php

namespace Tests\Feature;

use App\Enums\LedgerEntryType;
use App\Models\JobPosting;
use App\Models\LedgerEntry;
use App\Models\LedgerFact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_the_dashboard_summarises_the_ledger_and_job_matches()
    {
        $user = User::factory()->create();
        $user->jobSearchPreference()->create(['min_score' => 60]);

        $entry = LedgerEntry::factory()->for($user)->create(['type' => LedgerEntryType::Experience, 'confirmed_at' => now()]);
        LedgerFact::factory()->for($entry, 'entry')->count(2)->create(['confirmed_at' => now()]);
        LedgerFact::factory()->for($entry, 'entry')->create(['confirmed_at' => null]);
        LedgerEntry::factory()->for($user)->create(['type' => LedgerEntryType::Skill, 'title' => 'PHP', 'skills' => [], 'confirmed_at' => now()]);

        foreach ([85 => 'Great', 72 => 'Good', 30 => 'Poor'] as $score => $title) {
            JobPosting::factory()->for($user)->create(['title' => $title, 'source' => 'x', 'external_id' => $title, 'match_score' => $score]);
        }
        JobPosting::factory()->for($user)->create(['title' => 'Dismissed', 'source' => 'x', 'external_id' => 'd', 'match_score' => 95, 'dismissed_at' => now()]);

        $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('ledger.usable_facts', 2)
            ->where('ledger.total_facts', 3)
            ->where('ledger.skills', 1)
            ->where('ledger.by_type.0.label', 'Work experience')
            ->where('jobs.scored', 4)
            ->where('jobs.matches', 2)
            ->where('jobs.best.title', 'Great')
            ->where('jobs.by_score.7.matches', 1)
            ->where('jobs.by_score.3.below', 1)
            ->has('jobs.by_day', 14)
            ->where('jobs.by_day.13.matches', 3));
    }
}
