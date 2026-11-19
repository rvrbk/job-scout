<?php

namespace Tests\Feature;

use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoreNewMatchesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_counts_unseen_matches_for_the_user_behind_a_valid_token(): void
    {
        config(['services.lore.token' => 'secret']);
        $user = User::factory()->create(['email' => 'me@example.com']);
        JobPosting::factory()->for($user)->count(2)->create(['source' => 'feed', 'match_score' => 80, 'seen_at' => null, 'saved_at' => null, 'dismissed_at' => null]);
        JobPosting::factory()->for($user)->create(['source' => 'feed', 'match_score' => 80, 'seen_at' => now()]);
        JobPosting::factory()->for($user)->create(['source' => 'feed', 'match_score' => 10, 'seen_at' => null]);
        JobPosting::factory()->for(User::factory()->create())->create(['source' => 'feed', 'match_score' => 90, 'seen_at' => null]);

        $this->withToken('secret')->getJson(route('integrations.lore.new-matches', ['email' => 'ME@example.com']))
            ->assertOk()
            ->assertJson(['new' => 2, 'url' => route('jobs.index')]);
    }

    public function test_it_rejects_a_wrong_token_and_is_off_without_one(): void
    {
        $user = User::factory()->create();
        $url = route('integrations.lore.new-matches', ['email' => $user->email]);

        $this->withToken('anything')->getJson($url)->assertNotFound();

        config(['services.lore.token' => 'secret']);
        $this->getJson($url)->assertUnauthorized();
        $this->withToken('wrong')->getJson($url)->assertUnauthorized();
        $this->withToken('secret')->getJson(route('integrations.lore.new-matches', ['email' => 'nobody@example.com']))->assertNotFound();
    }
}
