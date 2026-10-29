<?php

namespace Tests\Feature;

use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class JobPostingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_job_posting_can_be_saved()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('jobs.store'), [
            'title' => 'Laravel Developer',
            'company' => 'Acme',
            'description' => 'We need someone with Laravel and Vue.',
        ]);

        $posting = $user->jobPostings()->sole();
        $response->assertRedirect(route('jobs.show', $posting));
    }

    public function test_the_owner_can_view_a_posting()
    {
        $posting = JobPosting::factory()->create();

        $this->actingAs($posting->user)
            ->get(route('jobs.show', $posting))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('jobs/Show')
                ->where('posting.id', $posting->id));
    }

    public function test_users_cannot_view_or_delete_someone_elses_posting()
    {
        $posting = JobPosting::factory()->create();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get(route('jobs.show', $posting))->assertForbidden();
        $this->actingAs($intruder)->delete(route('jobs.destroy', $posting))->assertForbidden();
    }
}
