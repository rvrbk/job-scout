<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Models\GeneratedDocument;
use App\Models\LedgerEntry;
use App\Models\LedgerFact;
use App\Models\User;
use App\Services\Llm\StructuredLlm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\FakeStructuredLlm;
use Tests\TestCase;

class PostingImportAndGeneralDocumentTest extends TestCase
{
    use RefreshDatabase;

    private const string VACANCY = 'We are hiring a Senior Laravel Developer at Acme to build our checkout platform. You will own APIs, mentor colleagues and work with PostgreSQL, Vue and queues. Requirements: five years of PHP, strong testing habits, and clear written communication. We offer remote work and a learning budget.';

    private function fakeLlm(array $responses): FakeStructuredLlm
    {
        $llm = new FakeStructuredLlm($responses);
        $this->app->instance(StructuredLlm::class, $llm);

        return $llm;
    }

    private function extracted(string $title = 'Senior Laravel Developer', ?string $company = 'Acme'): array
    {
        return ['title' => $title, 'company' => $company, 'description' => self::VACANCY];
    }

    public function test_pasted_text_without_a_title_has_title_and_company_read_from_it()
    {
        $user = User::factory()->create();
        $llm = $this->fakeLlm([$this->extracted()]);

        $this->actingAs($user)
            ->post(route('jobs.store'), ['description' => self::VACANCY])
            ->assertRedirect();

        $posting = $user->jobPostings()->sole();
        $this->assertSame('Senior Laravel Developer', $posting->title);
        $this->assertSame('Acme', $posting->company);
        // The pasted text is kept as written, not replaced by the model's copy.
        $this->assertSame(self::VACANCY, $posting->description);
        $this->assertCount(1, $llm->calls);
    }

    public function test_a_title_the_user_typed_is_kept_and_the_model_is_not_called()
    {
        $user = User::factory()->create();
        $llm = $this->fakeLlm([]);

        $this->actingAs($user)->post(route('jobs.store'), ['title' => 'My title', 'description' => self::VACANCY])->assertRedirect();

        $this->assertSame('My title', $user->jobPostings()->sole()->title);
        $this->assertSame([], $llm->calls);
    }

    public function test_a_link_is_fetched_and_the_vacancy_read_from_the_page()
    {
        $user = User::factory()->create();
        Http::fake(['93.184.216.34/*' => Http::response('<html><nav>Menu</nav><body><h1>Senior Laravel Developer</h1><p>'.self::VACANCY.'</p><script>track()</script></body></html>')]);
        $llm = $this->fakeLlm([$this->extracted()]);

        $this->actingAs($user)
            ->post(route('jobs.store'), ['source_url' => 'http://93.184.216.34/jobs/1'])
            ->assertRedirect();

        $posting = $user->jobPostings()->sole();
        $this->assertSame('Senior Laravel Developer', $posting->title);
        $this->assertSame('http://93.184.216.34/jobs/1', $posting->source_url);
        $this->assertStringContainsString('checkout platform', $llm->calls[0]['prompt']);
        $this->assertStringNotContainsString('track()', $llm->calls[0]['prompt']);
    }

    public function test_a_link_to_a_private_address_is_refused_without_a_request()
    {
        Http::fake();
        $this->fakeLlm([]);
        $user = User::factory()->create();

        foreach (['http://127.0.0.1/admin', 'http://10.0.0.5/x', 'http://169.254.169.254/latest/meta-data', 'file:///etc/passwd'] as $url) {
            $this->actingAs($user)->post(route('jobs.store'), ['source_url' => $url])->assertSessionHasErrors('source_url');
        }

        Http::assertNothingSent();
        $this->assertSame(0, $user->jobPostings()->count());
    }

    public function test_a_redirect_to_a_private_address_is_not_followed()
    {
        Http::fake(['93.184.216.34/*' => Http::response('', 302, ['Location' => 'http://127.0.0.1/secret'])]);
        $this->fakeLlm([]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('jobs.store'), ['source_url' => 'http://93.184.216.34/jobs/1'])->assertSessionHasErrors('source_url');

        Http::assertSentCount(1);
    }

    public function test_a_page_that_is_not_a_vacancy_is_reported_and_nothing_is_saved()
    {
        Http::fake(['93.184.216.34/*' => Http::response('<html><body>'.str_repeat('Cookie settings and other things. ', 20).'</body></html>')]);
        $this->fakeLlm([['title' => '', 'company' => null, 'description' => '']]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('jobs.store'), ['source_url' => 'http://93.184.216.34/'])->assertSessionHasErrors('source_url');

        $this->assertSame(0, $user->jobPostings()->count());
    }

    public function test_a_general_document_is_written_from_the_ledger_without_a_job()
    {
        $user = User::factory()->create(['name' => 'Sam Doe']);
        $entry = LedgerEntry::factory()->for($user)->create(['title' => 'Backend Developer', 'organization' => 'Acme', 'started_on' => '2021-03-01']);
        $fact = LedgerFact::factory()->for($entry, 'entry')->create(['statement' => 'Cut checkout API response time by 40% by adding query caching.']);

        $llm = $this->fakeLlm([
            [
                'summary' => [['text' => 'Backend developer focused on fast APIs.', 'fact_ids' => [$fact->id]]],
                'experience' => [['entry_id' => $entry->id, 'bullets' => [['text' => 'Cut checkout API response times by 40%.', 'fact_ids' => [$fact->id]]]]],
                'skills' => [],
                'gaps' => [],
            ],
            fn (string $prompt) => ['results' => array_map(
                fn (array $sentence) => ['claim_id' => $sentence['claim_id'], 'supported' => true, 'reason' => 'Supported.'],
                FakeStructuredLlm::payload($prompt, 'sentences'),
            )],
        ]);

        $response = $this->actingAs($user)->post(route('documents.store-general'), ['type' => 'cv']);

        $document = GeneratedDocument::sole();
        $response->assertRedirect(route('documents.show', $document));
        $this->assertNull($document->job_posting_id);
        $this->assertSame(DocumentStatus::Verified, $document->status);
        $this->assertStringNotContainsString('<job_posting>', $llm->calls[0]['prompt']);
        $this->assertStringContainsString('general-purpose CV', $llm->calls[0]['prompt']);

        $this->post(route('documents.approve', $document));
        $this->get(route('documents.show', $document))->assertOk();
        $this->get(route('documents.export', $document))->assertOk()->assertHeader('Content-Disposition', 'attachment; filename="cv-general.md"');
        $this->get(route('documents.export-pdf', $document))->assertOk();

        $this->delete(route('documents.destroy', $document))->assertRedirect(route('jobs.documents'));
    }

    public function test_a_general_document_needs_confirmed_facts_and_belongs_to_its_owner()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('documents.store-general'), ['type' => 'cv'])->assertSessionHasErrors('type');
        $this->assertSame(0, GeneratedDocument::count());

        $document = GeneratedDocument::factory()->for($user)->general()->create();
        $this->actingAs(User::factory()->create())->get(route('documents.show', $document))->assertForbidden();
    }

    public function test_general_documents_are_listed_on_their_own_jobs_page()
    {
        $user = User::factory()->create();
        GeneratedDocument::factory()->for($user)->general()->create();

        $this->actingAs($user)->get(route('jobs.documents'))
            ->assertInertia(fn ($page) => $page->has('generalDocuments', 1)->has('documentTypes')->has('documentStyles'));
    }
}
