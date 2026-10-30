<?php

namespace Tests\Feature;

use App\Enums\DocumentType;
use App\Enums\LedgerEntryType;
use App\Jobs\RunJobSearch;
use App\Models\GeneratedDocument;
use App\Models\JobPosting;
use App\Models\LedgerEntry;
use App\Models\LedgerFact;
use App\Models\User;
use App\Services\JobSearch\JobSearcher;
use App\Services\Llm\StructuredLlm;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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
            '*' => Http::response([]),
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
            '*' => Http::response([]),
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
            '*' => Http::response([]),
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
            '*' => Http::response([]),
        ]);
        $this->fakeLlm();
        $user = $this->userWithLedger();

        $this->actingAs($user)->post(route('jobs.search'))->assertRedirect();

        $this->assertSame(['Laravel Developer'], $user->jobPostings()->pluck('title')->all());
    }

    public function test_a_second_search_is_refused_while_one_is_running()
    {
        Http::fake();
        $this->fakeLlm();
        $user = $this->userWithLedger();
        $lock = Cache::lock(RunJobSearch::lockKey($user), 60);
        $lock->get();

        $this->actingAs($user)->post(route('jobs.search'))->assertRedirect();

        Http::assertNothingSent();
        $lock->release();
    }

    public function test_the_scheduled_search_runs_for_users_with_preferences_and_skips_running_ones()
    {
        Http::fake(['remotive.com/*' => Http::response(['jobs' => []])]);
        $this->fakeLlm();
        $searched = $this->userWithLedger();
        $searched->jobSearchPreference()->create(['min_score' => 60]);
        $busy = $this->userWithLedger();
        $busy->jobSearchPreference()->create(['min_score' => 60]);
        $noPreferences = $this->userWithLedger();
        $lock = Cache::lock(RunJobSearch::lockKey($busy), 60);
        $lock->get();

        $this->artisan('jobs:search')->assertSuccessful();

        $this->assertNotNull($searched->jobSearchPreference->refresh()->last_searched_at);
        $this->assertNull($busy->jobSearchPreference->refresh()->last_searched_at);
        $this->assertNull($noPreferences->jobSearchPreference);
        $lock->release();
    }

    public function test_the_search_is_scheduled_every_two_hours()
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->command, 'jobs:search'));

        $this->assertNotNull($event);
        $this->assertSame('0 */2 * * *', $event->expression);
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
            'boards' => "greenhouse:stripe\nLever:palantir, greenhouse:stripe\n",
            'min_score' => 70,
        ])->assertSessionHasNoErrors();

        $preferences = $user->jobSearchPreference;

        $this->assertSame(['Heerenveen', 'Netherlands'], $preferences->locations);
        $this->assertSame(['laravel', 'vue'], $preferences->keywords);
        $this->assertSame([], $preferences->exclusions);
        $this->assertSame(['https://example.com/jobs.rss'], $preferences->feeds);
        $this->assertSame(['greenhouse:stripe', 'Lever:palantir'], $preferences->boards);
        $this->assertSame(70, $preferences->min_score);
    }

    public function test_unsupported_company_boards_are_rejected()
    {
        $this->actingAs(User::factory()->create())->put(route('jobs.preferences.update'), [
            'remote_preference' => 'any', 'min_score' => 60, 'boards' => 'indeed:acme',
        ])->assertSessionHasErrors('boards.0');
    }

    public function test_company_boards_and_open_apis_are_searched()
    {
        Http::fake([
            'boards-api.greenhouse.io/*' => Http::response(['jobs' => [
                ['id' => 7, 'title' => 'Laravel Engineer', 'company_name' => 'Stripe', 'location' => ['name' => 'Remote'], 'absolute_url' => 'https://example.com/gh-7', 'content' => '&lt;p&gt;Build things&lt;/p&gt;', 'first_published' => '2026-10-01T10:00:00-04:00'],
            ]]),
            'api.lever.co/*' => Http::response([
                ['id' => 'abc', 'text' => 'Vue Engineer', 'hostedUrl' => 'https://example.com/lv-1', 'categories' => ['location' => 'Berlin'], 'workplaceType' => 'remote', 'descriptionPlain' => 'Frontend', 'createdAt' => now()->getTimestampMs()],
            ]),
            'jobicy.com/*' => Http::response(['jobs' => [
                ['id' => 1, 'jobTitle' => 'PHP Developer', 'companyName' => 'Jobi', 'jobGeo' => 'Europe', 'url' => 'https://example.com/jc-1', 'jobDescription' => '<p>PHP</p>', 'pubDate' => '2026-10-30T03:30:29+00:00'],
            ]]),
            'remoteok.com/*' => Http::response([['legal' => 'notice'], ['id' => '9', 'position' => 'Laravel Dev', 'company' => 'ROK', 'location' => '', 'url' => 'https://example.com/rok-9', 'description' => 'x', 'date' => '2026-10-28T21:53:15+00:00']]),
            'himalayas.app/*' => Http::response(['jobs' => [
                ['title' => 'Laravel Lead', 'companyName' => 'Him', 'guid' => 'h-1', 'applicationLink' => 'https://example.com/h-1', 'locationRestrictions' => ['Netherlands'], 'description' => 'x', 'pubDate' => 1793346517],
            ]]),
            'themuse.com/*' => Http::response(['results' => [
                ['id' => 5, 'name' => 'Laravel Dev', 'company' => ['name' => 'Muse Co'], 'locations' => [['name' => 'Flexible / Remote']], 'refs' => ['landing_page' => 'https://example.com/m-5'], 'contents' => '<p>x</p>', 'publication_date' => '2026-10-03T18:56:56Z'],
            ]]),
            'weworkremotely.com/*' => Http::response('<rss><channel></channel></rss>'),
            '*' => Http::response([]),
        ]);
        $user = $this->userWithLedger();
        $user->jobSearchPreference()->create(['boards' => ['greenhouse:stripe', 'lever:palantir', 'bogus:thing'], 'min_score' => 0, 'remote_preference' => 'any']);
        $this->fakeLlm();

        $result = app(JobSearcher::class)->search($user);

        $this->assertSame([], $result['failed_sources']);
        $this->assertEqualsCanonicalizing(
            ['greenhouse:stripe', 'lever:palantir', 'jobicy', 'remoteok', 'himalayas', 'themuse'],
            $user->jobPostings()->pluck('source')->all(),
        );
        $this->assertSame('Build things', $user->jobPostings()->where('source', 'greenhouse:stripe')->value('description'));
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
