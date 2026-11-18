<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_see_the_tour_until_they_finish_it()
    {
        $user = User::factory()->create(['onboarded_at' => null]);

        $this->actingAs($user)->get('/dashboard')->assertInertia(fn ($page) => $page->where('showTour', true));

        $this->actingAs($user)->post(route('onboarding.complete'))->assertRedirect();
        $this->assertNotNull($user->fresh()->onboarded_at);

        $this->actingAs($user)->get('/dashboard')->assertInertia(fn ($page) => $page->where('showTour', false));
    }

    public function test_completing_again_keeps_the_original_time()
    {
        $user = User::factory()->create(['onboarded_at' => now()->subDay()]);
        $original = $user->onboarded_at;

        $this->actingAs($user)->post(route('onboarding.complete'));

        $this->assertTrue($user->fresh()->onboarded_at->equalTo($original));
    }
}
