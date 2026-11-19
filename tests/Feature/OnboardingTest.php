<?php

namespace Tests\Feature;

use App\Models\LedgerEntry;
use App\Models\LedgerFact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_with_usable_facts_see_the_tour_until_they_finish_it()
    {
        $user = User::factory()->create(['onboarded_at' => null]);
        $entry = LedgerEntry::factory()->for($user)->create(['confirmed_at' => now()]);
        LedgerFact::factory()->for($entry, 'entry')->create(['confirmed_at' => now()]);

        $this->actingAs($user)->get('/dashboard')->assertInertia(fn ($page) => $page->where('showTour', true));

        $this->actingAs($user)->post(route('onboarding.complete'))->assertRedirect();
        $this->assertNotNull($user->fresh()->onboarded_at);

        $this->actingAs($user)->get('/dashboard')->assertInertia(fn ($page) => $page->where('showTour', false));
    }

    public function test_a_user_with_an_empty_ledger_gets_the_guide_not_the_tour_now_or_later()
    {
        $user = User::factory()->create(['onboarded_at' => null]);

        $this->actingAs($user)->get('/dashboard')->assertInertia(fn ($page) => $page->where('showTour', false)->where('ledger.usable_facts', 0));

        $entry = LedgerEntry::factory()->for($user)->create(['confirmed_at' => now()]);
        LedgerFact::factory()->for($entry, 'entry')->create(['confirmed_at' => now()]);

        $this->actingAs($user)->get('/dashboard')->assertInertia(fn ($page) => $page->where('showTour', false));
    }

    public function test_background_request_gets_no_content()
    {
        $user = User::factory()->create(['onboarded_at' => null]);

        $this->actingAs($user)->postJson(route('onboarding.complete'))->assertNoContent();
        $this->assertNotNull($user->fresh()->onboarded_at);
    }

    public function test_completing_again_keeps_the_original_time()
    {
        $user = User::factory()->create(['onboarded_at' => now()->subDay()]);
        $original = $user->onboarded_at;

        $this->actingAs($user)->post(route('onboarding.complete'));

        $this->assertTrue($user->fresh()->onboarded_at->equalTo($original));
    }
}
