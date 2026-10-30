<?php

namespace Tests\Feature;

use App\Enums\DocumentType;
use App\Enums\LedgerEntryType;
use App\Models\GeneratedDocument;
use App\Models\JobPosting;
use App\Models\LedgerEntry;
use App\Models\LedgerFact;
use App\Models\User;
use App\Services\JobSearch\JobSearcher;
use App\Services\Llm\StructuredLlm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class JobSearchTest extends TestCase
{
    use RefreshDatabase;

    private function userWithLedger(): User
    {
        $user = User::factory()->create();
        $entry = LedgerEntry::factory()->for($user)->create(['type' => LedgerEntryType::Experience, 'confirmed_at' => now()]);
        LedgerFact::factory()->for($entry, 'entry')->create(['confirmed_at' => now()]);

        return $user;
    }

    /**
     * Fake model that scores every posting by the digit it finds in the title.
     */
    private function fakeLlm(): void
    {
        $this->app->instance(StructuredLlm::class, new class implements StructuredLlm
        {
            public int $calls = 0;

            public function generate(string $system, string $prompt, array $schema): array
            {
                $this->calls++;
                preg_match_all('/<posting index="(\d+)">\nTitle: (.*)\n/', $prompt, $postings, PREG_SET_ORDER);

                return ['matches' => array_map(fn (array $posting) => [
                    'index' => (int) $posting[1],
                    'score' => str_contains($posting[2], 'Laravel') ? 90 : 20,
                    'reason' => 'Because.',
                ], $postings)];
            }
        });
    }

    private function fakeSources(): void
    {
        Http::fake([
            'arbeitnow.com/*' => Http::response(['data' => [
                ['slug' => 'a-1', 'title' => 'Laravel Developer', 'company_name' => 'Acme', 'location' => 'Amsterdam', 'remote' => false, 'url' => 'https://example.com/a-1', 'description' => '<p>Build things</p>', 'created_at' => now()->timestamp],
                ['slug' => 'a-2', 'title' => 'Cobol Developer', 'company_name' => 'Old Bank', 'location' => 'Amsterdam', 'remote' => false, 'url' => 'https://example.com/a-2', 'description' => 'Legacy', 'created_at' => now()->timestamp],
                ['slug' => 'a-3', 'title' => 'Laravel Developer Junior', 'company_name' => 'Acme', 'location' => 'Amsterdam', 'remote' => false, 'url' => 'https://example.com/a-3', 'description' => 'Junior role', 'created_at' => now()->timestamp],
            ]]),
            'remotive.com/*' => Http::response(['jobs' => []]),
            'weworkremotely.com/*' => Http::response('<rss><channel><item><title>Globex: Remote Laravel Engineer</title><link>https://example.com/w-1</link><guid>w-1</guid><description>Remote work</description></item></channel></rss>'),
        ]);
    }

    public function test_a_search_stores_scored_jobs_and_skips_ruled_out_ones()
    {
        $this->fakeSources();
        $this->fakeLlm();
        $user = $this->userWithLedger();
        $user->jobSearchPreference()->create(['exclusions' => ['junior'], 'min_score' => 60]);

        $result = app(JobSearcher::class)->search($user->refresh());

        $this->assertSame(4, $result['fetched']);
        $this->assertSame(['laravel developer', 'cobol developer', 'remote laravel engineer'], $user->jobPostings()->orderBy('id')->pluck('title')->map(fn ($t) => mb_strtolower($t))->all());
        $this->assertSame(2, $result['matched']);
        $this->assertNotNull($user->jobSearchPreference->last_searched_at);

        $feedJob = $user->jobPostings()->where('source_url', 'https://example.com/w-1')->sole();
        $this->assertSame('Globex', $feedJob->company);
        $this->assertSame(90, $feedJob->match_score);
    }

    public function test_jobs_already_scored_are_not_scored_again()
    {
        $this->fakeSources();
        $this->fakeLlm();
        $user = $this->userWithLedger();

        app(JobSearcher::class)->search($user);
        $count = $user->jobPostings()->count();

        $second = app(JobSearcher::class)->search($user->refresh());

        $this->assertSame(0, $second['scored']);
        $this->assertSame($count, $user->jobPostings()->count());
    }

    public function test_remote_only_drops_on_site_jobs_before_scoring()
    {
        $this->fakeSources();
        $this->fakeLlm();
        $user = $this->userWithLedger();
        $user->jobSearchPreference()->create(['remote_preference' => 'remote_only']);

        app(JobSearcher::class)->search($user->refresh());

        $this->assertSame(['Remote Laravel Engineer'], $user->jobPostings()->pluck('title')->all());
    }

    public function test_hybrid_keeps_remote_and_hybrid_jobs_only()
    {
        Http::fake([
            'arbeitnow.com/*' => Http::response(['data' => [
                ['slug' => 'h-1', 'title' => 'Laravel Developer (hybrid)', 'company_name' => 'A', 'location' => 'Amsterdam', 'remote' => false, 'url' => 'https://example.com/h-1', 'description' => 'Hybrid, 2 days at the office', 'created_at' => now()->timestamp],
                ['slug' => 'h-2', 'title' => 'Laravel Developer', 'company_name' => 'B', 'location' => 'Amsterdam', 'remote' => false, 'url' => 'https://example.com/h-2', 'description' => 'Five days on site', 'created_at' => now()->timestamp],
            ]]),
            'remotive.com/*' => Http::response(['jobs' => []]),
            'weworkremotely.com/*' => Http::response('<rss><channel></channel></rss>'),
        ]);
        $this->fakeLlm();
        $user = $this->userWithLedger();
        $user->jobSearchPreference()->create(['remote_preference' => 'hybrid']);

        app(JobSearcher::class)->search($user->refresh());

        $this->assertSame(['Laravel Developer (hybrid)'], $user->jobPostings()->pluck('title')->all());
    }

    public function test_a_failing_source_does_not_stop_the_search()
    {
        Http::fake([
            'arbeitnow.com/*' => Http::response('nope', 500),
            'remotive.com/*' => Http::response(['jobs' => []]),
            'weworkremotely.com/*' => Http::response('<rss><channel><item><title>Globex: Laravel Engineer</title><link>https://example.com/w-1</link></item></channel></rss>'),
        ]);
        $this->fakeLlm();
        $user = $this->userWithLedger();

        $result = app(JobSearcher::class)->search($user);

        $this->assertSame(['arbeitnow'], $result['failed_sources']);
        $this->assertSame(1, $user->jobPostings()->count());
    }

    public function test_search_needs_confirmed_facts()
    {
        Http::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('jobs.search'))->assertRedirect();

        Http::assertNothingSent();
        $this->assertSame(0, $user->jobPostings()->count());
    }

    public function test_search_now_runs_the_search_immediately()
    {
        Http::fake([
            'arbeitnow.com/*' => Http::response(['data' => [[
                'slug' => 'a-1', 'title' => 'Laravel Developer', 'company_name' => 'Acme', 'location' => 'Amsterdam',
                'remote' => true, 'description' => 'Build things', 'url' => 'https://example.com/a-1', 'created_at' => now()->timestamp,
            ]]]),
            'remotive.com/*' => Http::response(['jobs' => []]),
            'weworkremotely.com/*' => Http::response('<rss><channel></channel></rss>'),
        ]);
        $this->fakeLlm();
        $user = $this->userWithLedger();

        $this->actingAs($user)->post(route('jobs.search'))->assertRedirect();

        $this->assertSame(['Laravel Developer'], $user->jobPostings()->pluck('title')->all());
    }

    public function test_preferences_can_be_saved()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('jobs.preferences.update'), [
            'locations' => "Heerenveen\nNetherlands",
            'remote_preference' => 'remote_only',
            'keywords' => 'laravel, vue, Laravel',
            'exclusions' => '',
            'feeds' => "https://example.com/jobs.rss\n",
            'min_score' => 70,
        ])->assertSessionHasNoErrors();

        $preferences = $user->jobSearchPreference;

        $this->assertSame(['Heerenveen', 'Netherlands'], $preferences->locations);
        $this->assertSame(['laravel', 'vue'], $preferences->keywords);
        $this->assertSame([], $preferences->exclusions);
        $this->assertSame(['https://example.com/jobs.rss'], $preferences->feeds);
        $this->assertSame(70, $preferences->min_score);
    }

    public function test_a_salary_expectation_can_be_saved_and_cleared()
    {
        $user = User::factory()->create();
        $payload = ['remote_preference' => 'any', 'min_score' => 60];

        $this->actingAs($user)->put(route('jobs.preferences.update'), [...$payload, 'salary_min' => '60000', 'salary_max' => '80000'])
            ->assertSessionHasNoErrors();

        $this->assertSame(60000, $user->jobSearchPreference->salary_min);
        $this->assertSame('60,000 to 80,000', $user->jobSearchPreference->salaryExpectation());

        $this->actingAs($user)->put(route('jobs.preferences.update'), [...$payload, 'salary_min' => '', 'salary_max' => ''])
            ->assertSessionHasNoErrors();

        $this->assertNull($user->jobSearchPreference->refresh()->salary_min);
        $this->assertNull($user->jobSearchPreference->salaryExpectation());
    }

    public function test_the_salary_maximum_cannot_be_below_the_minimum()
    {
        $this->actingAs(User::factory()->create())->put(route('jobs.preferences.update'), [
            'remote_preference' => 'any', 'min_score' => 60, 'salary_min' => 80000, 'salary_max' => 60000,
        ])->assertSessionHasErrors('salary_max');
    }

    public function test_only_matches_at_or_above_the_threshold_are_listed()
    {
        $user = User::factory()->create();
        $user->jobSearchPreference()->create(['min_score' => 60]);
        JobPosting::factory()->for($user)->create(['title' => 'Strong', 'source' => 'x', 'external_id' => '1', 'match_score' => 85]);
        JobPosting::factory()->for($user)->create(['title' => 'Weak', 'source' => 'x', 'external_id' => '2', 'match_score' => 30]);
        JobPosting::factory()->for($user)->create(['title' => 'Dismissed', 'source' => 'x', 'external_id' => '3', 'match_score' => 95, 'dismissed_at' => now()]);
        JobPosting::factory()->for($user)->create(['title' => 'Pasted']);

        $this->actingAs($user)->get(route('jobs.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->has('postings', 2)
            ->where('postings.0.title', 'Strong')
            ->where('postings.1.title', 'Pasted'));
    }

    public function test_the_overview_shows_which_documents_exist_for_each_job()
    {
        $user = User::factory()->create();
        $posting = JobPosting::factory()->for($user)->create();
        JobPosting::factory()->for($user)->create();
        GeneratedDocument::factory()->for($user)->for($posting)->create(['type' => DocumentType::Cv]);

        $this->actingAs($user)->get(route('jobs.index'))->assertInertia(fn (AssertableInertia $page) => $page
            ->where('postings', fn ($postings) => $postings->contains(fn ($p) => $p['id'] === $posting->id
                && $p['documents'] === [['type' => 'cv', 'type_label' => 'CV', 'status' => $p['documents'][0]['status']]])
                && $postings->where('documents', [])->count() === 1));
    }

    public function test_a_posting_can_be_dismissed_only_by_its_owner()
    {
        $posting = JobPosting::factory()->create();

        $this->actingAs(User::factory()->create())->post(route('jobs.dismiss', $posting))->assertForbidden();
        $this->actingAs($posting->user)->post(route('jobs.dismiss', $posting))->assertRedirect();

        $this->assertNotNull($posting->refresh()->dismissed_at);
    }
}
