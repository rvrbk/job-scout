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

    public function test_a_posting_is_new_until_it_has_been_opened()
    {
        $posting = JobPosting::factory()->create(['source' => 'remotive', 'external_id' => '1', 'match_score' => 90]);

        $this->actingAs($posting->user)
            ->get(route('jobs.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('postings.0.is_new', true));

        $this->get(route('jobs.show', $posting))->assertOk();

        $this->get(route('jobs.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('postings.0.is_new', false));
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

    public function test_saved_jobs_are_paged_ten_at_a_time_newest_first()
    {
        $user = User::factory()->create();
        foreach (range(1, 12) as $day) {
            JobPosting::factory()->for($user)->create(['title' => "Job {$day}", 'created_at' => now()->subDays(20 - $day)]);
        }

        $this->actingAs($user)->get(route('jobs.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('saved.data', 10)
                ->where('saved.total', 12)
                ->where('saved.data.0.title', 'Job 12'));

        $this->get(route('jobs.index', ['saved_page' => 2]))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('saved.data', 2)->where('saved.data.1.title', 'Job 1'));
    }

    public function test_saved_jobs_can_be_sorted_oldest_first()
    {
        $user = User::factory()->create();
        JobPosting::factory()->for($user)->create(['title' => 'Old', 'created_at' => now()->subDays(5)]);
        JobPosting::factory()->for($user)->create(['title' => 'New', 'created_at' => now()]);

        $this->actingAs($user)->get(route('jobs.index', ['sort' => 'oldest']))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('saved.data.0.title', 'Old')->where('filters.sort', 'oldest'));

        $this->get(route('jobs.index', ['sort' => 'sideways']))->assertSessionHasErrors('sort');
    }

    public function test_saved_jobs_can_be_searched_by_title_company_or_location_across_pages()
    {
        $user = User::factory()->create();
        JobPosting::factory()->count(11)->for($user)->create(['title' => 'Plumber', 'company' => 'Pipes Inc']);
        JobPosting::factory()->for($user)->create(['title' => 'Laravel Developer', 'company' => 'Acme', 'location' => 'Utrecht']);
        JobPosting::factory()->create(['title' => 'Laravel Someone Else']);

        $search = fn (string $q) => $this->actingAs($user)->get(route('jobs.index', ['q' => $q]));

        $search('laravel')->assertInertia(fn (AssertableInertia $page) => $page->has('saved.data', 1)->where('saved.data.0.company', 'Acme'));
        $search('ACME')->assertInertia(fn (AssertableInertia $page) => $page->has('saved.data', 1));
        $search('utrecht')->assertInertia(fn (AssertableInertia $page) => $page->has('saved.data', 1));
        $search('%')->assertInertia(fn (AssertableInertia $page) => $page->has('saved.data', 0));
        $search('')->assertInertia(fn (AssertableInertia $page) => $page->where('saved.total', 12));
    }

    public function test_found_matches_are_not_mixed_into_saved_jobs_until_saved()
    {
        $user = User::factory()->create();
        $match = JobPosting::factory()->for($user)->create(['source' => 'remotive', 'external_id' => '1', 'match_score' => 90]);

        $this->actingAs($user)->get(route('jobs.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('postings', 1)->has('saved.data', 0));

        $match->update(['saved_at' => now()]);

        $this->get(route('jobs.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('postings', 0)->has('saved.data', 1));
    }
}
