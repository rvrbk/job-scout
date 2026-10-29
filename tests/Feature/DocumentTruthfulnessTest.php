<?php

namespace Tests\Feature;

use App\Enums\ClaimKind;
use App\Enums\ClaimStatus;
use App\Enums\DocumentStatus;
use App\Enums\LedgerEntryType;
use App\Jobs\GenerateDocument;
use App\Models\DocumentClaim;
use App\Models\GeneratedDocument;
use App\Models\JobPosting;
use App\Models\LedgerEntry;
use App\Models\LedgerFact;
use App\Models\User;
use App\Services\Llm\StructuredLlm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeStructuredLlm;
use Tests\TestCase;

class DocumentTruthfulnessTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private LedgerEntry $entry;

    private LedgerFact $fact;

    private JobPosting $posting;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Sam Doe']);
        $this->entry = LedgerEntry::factory()->for($this->user)->create([
            'title' => 'Backend Developer',
            'organization' => 'Acme',
            'started_on' => '2021-03-01',
            'skills' => ['Laravel', 'PostgreSQL'],
        ]);
        $this->fact = LedgerFact::factory()->for($this->entry, 'entry')->create([
            'statement' => 'Cut checkout API response time by 40% by adding query caching.',
        ]);
        $this->posting = JobPosting::factory()->for($this->user)->create();
    }

    public function test_only_confirmed_facts_and_entries_are_shown_to_the_model()
    {
        LedgerFact::factory()->for($this->entry, 'entry')->unconfirmed()->create(['statement' => 'Unconfirmed fact']);
        $hiddenEntry = LedgerEntry::factory()->for($this->user)->unconfirmed()->create();
        LedgerFact::factory()->for($hiddenEntry, 'entry')->create(['statement' => 'Fact on unconfirmed entry']);
        LedgerFact::factory()->create(['statement' => 'Someone else\'s fact']);

        $llm = $this->fakeLlm([$this->cvOutput(), $this->allSupported()]);

        $this->runGeneration();

        $ledger = FakeStructuredLlm::payload($llm->calls[0]['prompt'], 'ledger');

        $this->assertCount(1, $ledger['entries']);
        $this->assertSame([$this->fact->id], array_column($ledger['entries'][0]['facts'], 'fact_id'));
        $this->assertStringNotContainsString('Unconfirmed fact', $llm->calls[0]['prompt']);
        $this->assertStringNotContainsString('Fact on unconfirmed entry', $llm->calls[0]['prompt']);
        $this->assertStringNotContainsString('Someone else', $llm->calls[0]['prompt']);
    }

    public function test_a_fully_supported_cv_is_verified_and_can_be_approved_and_exported()
    {
        $this->fakeLlm([$this->cvOutput(), $this->allSupported()]);

        $document = $this->runGeneration();

        $this->assertSame(DocumentStatus::Verified, $document->status);
        $this->assertSame([['requirement' => 'Kubernetes', 'note' => 'Not in your ledger.']], $document->gaps);

        $this->actingAs($this->user)
            ->post(route('documents.approve', $document))
            ->assertSessionHasNoErrors();

        $markdown = $this->actingAs($this->user)
            ->get(route('documents.export', $document))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->getContent();

        $this->assertStringContainsString('# Sam Doe', $markdown);
        $this->assertStringContainsString('### Backend Developer - Acme (Mar 2021 - Present)', $markdown);
        $this->assertStringContainsString('- Cut checkout API response times by 40% through query caching.', $markdown);
        $this->assertStringContainsString('Laravel', $markdown);
    }

    public function test_claims_citing_invented_fact_ids_are_flagged_without_asking_the_model()
    {
        $llm = $this->fakeLlm([
            $this->cvOutput(summary: [['text' => 'Led a team of 12 engineers.', 'fact_ids' => [999999]]]),
            $this->allSupported(),
        ]);

        $document = $this->runGeneration();
        $claim = $document->claims()->where('section', DocumentClaim::SECTION_SUMMARY)->sole();

        $this->assertSame(ClaimStatus::Unsupported, $claim->status);
        $this->assertSame('Cites no confirmed facts from your ledger.', $claim->verifier_note);
        $this->assertSame(0, $claim->facts()->count());
        $this->assertNotContains($claim->id, array_column(FakeStructuredLlm::payload($llm->calls[1]['prompt'], 'sentences'), 'claim_id'));
        $this->assertSame(DocumentStatus::NeedsReview, $document->status);
    }

    public function test_numbers_that_are_not_in_the_cited_facts_are_flagged()
    {
        $this->fakeLlm([
            $this->cvOutput(summary: [['text' => 'Cut response time by 60%.', 'fact_ids' => [$this->fact->id]]]),
            $this->allSupported(),
        ]);

        $claim = $this->runGeneration()->claims()->where('section', DocumentClaim::SECTION_SUMMARY)->sole();

        $this->assertSame(ClaimStatus::Unsupported, $claim->status);
        $this->assertStringContainsString('60', $claim->verifier_note);
    }

    public function test_skills_must_exist_in_the_ledger()
    {
        $this->fakeLlm([$this->cvOutput(skills: ['laravel', 'Kubernetes']), $this->allSupported()]);

        $skills = $this->runGeneration()->claims()->where('kind', ClaimKind::Skill)->get()->keyBy('position');

        $this->assertSame(ClaimStatus::Supported, $skills[0]->status);
        $this->assertSame('Laravel', $skills[0]->text);
        $this->assertSame(ClaimStatus::Unsupported, $skills[1]->status);
    }

    public function test_bullets_must_cite_facts_from_the_entry_they_are_listed_under()
    {
        $otherEntry = LedgerEntry::factory()->for($this->user)->ofType(LedgerEntryType::Project)->create();
        $otherFact = LedgerFact::factory()->for($otherEntry, 'entry')->create();

        $this->fakeLlm([
            $this->cvOutput(experience: [[
                'entry_id' => $this->entry->id,
                'bullets' => [['text' => 'Built a side project.', 'fact_ids' => [$otherFact->id]]],
            ]]),
            $this->allSupported(),
        ]);

        $bullet = $this->runGeneration()->claims()->where('section', DocumentClaim::SECTION_EXPERIENCE)->sole();

        $this->assertSame(ClaimStatus::Unsupported, $bullet->status);
        $this->assertSame('Cites facts from a different entry than the one it is listed under.', $bullet->verifier_note);
    }

    public function test_bullets_for_an_entry_outside_the_ledger_are_flagged()
    {
        $foreignEntry = LedgerEntry::factory()->create();

        $this->fakeLlm([
            $this->cvOutput(experience: [[
                'entry_id' => $foreignEntry->id,
                'bullets' => [['text' => 'Did things.', 'fact_ids' => [$this->fact->id]]],
            ]]),
            $this->allSupported(),
        ]);

        $bullet = $this->runGeneration()->claims()->where('section', DocumentClaim::SECTION_EXPERIENCE)->sole();

        $this->assertNull($bullet->ledger_entry_id);
        $this->assertSame(ClaimStatus::Unsupported, $bullet->status);
    }

    public function test_the_model_verdict_is_applied_and_missing_verdicts_fail_closed()
    {
        $this->fakeLlm([
            $this->cvOutput(summary: [
                ['text' => 'Improved API performance with caching.', 'fact_ids' => [$this->fact->id]],
                ['text' => 'An expert in distributed systems.', 'fact_ids' => [$this->fact->id]],
            ]),
            function (string $prompt) {
                $sentences = FakeStructuredLlm::payload($prompt, 'sentences');

                // Reject the second summary sentence and return no verdict for the bullet.
                return ['results' => [
                    ['claim_id' => $sentences[0]['claim_id'], 'supported' => true, 'reason' => 'Matches the fact.'],
                    ['claim_id' => $sentences[1]['claim_id'], 'supported' => false, 'reason' => '"Expert in distributed systems" is not in the facts.'],
                ]];
            },
        ]);

        $document = $this->runGeneration();
        $claims = $document->claims()->where('kind', ClaimKind::Claim)->get();

        $this->assertSame(
            [ClaimStatus::Supported, ClaimStatus::Unsupported, ClaimStatus::Unsupported],
            $claims->pluck('status')->all(),
        );
        $this->assertSame('The verifier returned no verdict for this sentence.', $claims[2]->verifier_note);
        $this->assertSame(DocumentStatus::NeedsReview, $document->status);
    }

    public function test_cover_letter_framing_goes_to_the_model_without_citations()
    {
        $llm = $this->fakeLlm([
            [
                'paragraphs' => [['sentences' => [
                    ['text' => 'I am writing to apply for the Senior Laravel Developer role.', 'kind' => 'framing', 'fact_ids' => []],
                    ['text' => 'I cut checkout API response times by 40% with query caching.', 'kind' => 'claim', 'fact_ids' => [$this->fact->id]],
                ]]],
                'gaps' => [],
            ],
            $this->allSupported(),
        ]);

        $document = GeneratedDocument::factory()->for($this->posting)->coverLetter()->create();
        GenerateDocument::dispatchSync($document);

        $sentences = FakeStructuredLlm::payload($llm->calls[1]['prompt'], 'sentences');

        $this->assertSame(['framing', 'claim'], array_column($sentences, 'kind'));
        $this->assertSame(DocumentStatus::Verified, $document->fresh()->status);
    }

    public function test_a_document_with_flagged_claims_cannot_be_approved_or_exported()
    {
        $document = GeneratedDocument::factory()->for($this->posting)->withStatus(DocumentStatus::NeedsReview)->create();
        DocumentClaim::factory()->for($document, 'document')->withStatus(ClaimStatus::Unsupported)->create();

        $this->actingAs($this->user)
            ->post(route('documents.approve', $document))
            ->assertSessionHasErrors('approve');

        $this->actingAs($this->user)->get(route('documents.export', $document))->assertForbidden();
        $this->assertNull($document->fresh()->approved_at);
    }

    public function test_removing_the_last_flagged_claim_makes_the_document_approvable()
    {
        $document = GeneratedDocument::factory()->for($this->posting)->withStatus(DocumentStatus::NeedsReview)->create();
        DocumentClaim::factory()->for($document, 'document')->withStatus(ClaimStatus::Supported)->create();
        $flagged = DocumentClaim::factory()->for($document, 'document')->withStatus(ClaimStatus::Unsupported)->create();

        $this->actingAs($this->user)->delete(route('claims.destroy', $flagged));

        $this->assertSame(DocumentStatus::Verified, $document->fresh()->status);
    }

    public function test_editing_a_claim_sends_it_back_through_verification()
    {
        $document = GeneratedDocument::factory()->for($this->posting)->withStatus(DocumentStatus::Approved)->create(['approved_at' => now()]);
        $claim = DocumentClaim::factory()->for($document, 'document')->withStatus(ClaimStatus::Supported)->create();
        $claim->facts()->attach($this->fact);

        $this->fakeLlm([fn (string $prompt) => ['results' => [
            ['claim_id' => $claim->id, 'supported' => false, 'reason' => 'Adds a team size not in the facts.'],
        ]]]);

        $this->actingAs($this->user)
            ->put(route('claims.update', $claim), ['text' => 'Led a team that cut response times by 40%.'])
            ->assertRedirect(route('documents.show', $document));

        $this->assertSame(ClaimStatus::Unsupported, $claim->fresh()->status);
        $this->assertSame(DocumentStatus::NeedsReview, $document->fresh()->status);
        $this->assertNull($document->fresh()->approved_at);
    }

    public function test_generation_requires_confirmed_facts()
    {
        $this->fact->update(['confirmed_at' => null]);

        $this->actingAs($this->user)
            ->post(route('documents.store', $this->posting), ['type' => 'cv'])
            ->assertSessionHasErrors('type');

        $this->assertSame(0, GeneratedDocument::count());
    }

    public function test_starting_generation_creates_a_document_and_queues_the_job()
    {
        $this->fakeLlm([$this->cvOutput(), $this->allSupported()]);

        $response = $this->actingAs($this->user)->post(route('documents.store', $this->posting), ['type' => 'cv']);

        $document = GeneratedDocument::sole();
        $response->assertRedirect(route('documents.show', $document));
        $this->assertSame(DocumentStatus::Verified, $document->status);
    }

    public function test_a_failed_generation_is_reported_on_the_document()
    {
        $document = GeneratedDocument::factory()->for($this->posting)->create();

        (new GenerateDocument($document))->failed(new \RuntimeException('Claude declined the request.'));

        $this->assertSame(DocumentStatus::Failed, $document->fresh()->status);
        $this->assertSame('Claude declined the request.', $document->fresh()->error);
    }

    public function test_users_cannot_view_or_edit_someone_elses_document()
    {
        $document = GeneratedDocument::factory()->for($this->posting)->create();
        $claim = DocumentClaim::factory()->for($document, 'document')->create();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get(route('documents.show', $document))->assertForbidden();
        $this->actingAs($intruder)->put(route('claims.update', $claim), ['text' => 'x'])->assertForbidden();
        $this->actingAs($intruder)->post(route('documents.approve', $document))->assertForbidden();
    }

    /**
     * @param  list<array<string, mixed>|\Closure>  $responses
     */
    private function fakeLlm(array $responses): FakeStructuredLlm
    {
        $llm = new FakeStructuredLlm($responses);
        $this->app->instance(StructuredLlm::class, $llm);

        return $llm;
    }

    private function runGeneration(): GeneratedDocument
    {
        $document = GeneratedDocument::factory()->for($this->posting)->create();

        GenerateDocument::dispatchSync($document);

        return $document->fresh();
    }

    /**
     * @param  list<array<string, mixed>>|null  $summary
     * @param  list<array<string, mixed>>|null  $experience
     * @param  list<string>  $skills
     * @return array<string, mixed>
     */
    private function cvOutput(?array $summary = null, ?array $experience = null, array $skills = ['Laravel']): array
    {
        return [
            'summary' => $summary ?? [['text' => 'Backend developer focused on fast APIs.', 'fact_ids' => [$this->fact->id]]],
            'experience' => $experience ?? [[
                'entry_id' => $this->entry->id,
                'bullets' => [['text' => 'Cut checkout API response times by 40% through query caching.', 'fact_ids' => [$this->fact->id]]],
            ]],
            'skills' => $skills,
            'gaps' => [['requirement' => 'Kubernetes', 'note' => 'Not in your ledger.']],
        ];
    }

    private function allSupported(): \Closure
    {
        return fn (string $prompt) => ['results' => array_map(
            fn (array $sentence) => ['claim_id' => $sentence['claim_id'], 'supported' => true, 'reason' => 'Supported by the cited facts.'],
            FakeStructuredLlm::payload($prompt, 'sentences'),
        )];
    }
}
