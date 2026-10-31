<?php

namespace Tests\Feature;

use App\Enums\LedgerEntryType;
use App\Models\LedgerEntry;
use App\Models\LedgerFact;
use App\Models\User;
use App\Services\Llm\LlmException;
use App\Services\Llm\StructuredLlm;
use Dompdf\Dompdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use ZipArchive;

class LedgerImportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $output
     */
    private function fakeLlm(array $output): object
    {
        $llm = new class($output) implements StructuredLlm
        {
            public ?string $prompt = null;

            public function __construct(private array $output) {}

            public function generate(string $system, string $prompt, array $schema): array
            {
                $this->prompt = $prompt;

                return $this->output;
            }
        };

        $this->app->instance(StructuredLlm::class, $llm);

        return $llm;
    }

    private function row(string $title, string $organization, array $facts, string $type = 'experience'): array
    {
        return [
            'type' => $type, 'title' => $title, 'organization' => $organization, 'location' => '',
            'started_on' => '2020-01', 'ended_on' => '', 'skills' => ['PHP'], 'facts' => $facts,
        ];
    }

    public function test_a_cv_is_imported_as_unconfirmed_entries_and_facts()
    {
        $llm = $this->fakeLlm(['entries' => [
            $this->row('Backend Developer', 'Acme', ['Built the checkout API.', 'Cut response times by 40%.']),
            $this->row('BSc Computer Science', 'TU Delft', [], 'education'),
        ]]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('ledger.import'), ['cv' => UploadedFile::fake()->createWithContent('cv.txt', "Jane Doe\nBackend Developer at Acme")])
            ->assertRedirect(route('ledger.index'));

        $this->assertStringContainsString('Backend Developer at Acme', $llm->prompt);
        $this->assertSame(2, $user->ledgerEntries()->count());
        $this->assertSame(0, $user->ledgerEntries()->confirmed()->count());
        $this->assertSame(0, LedgerFact::whereNotNull('confirmed_at')->count());

        $entry = $user->ledgerEntries()->where('title', 'Backend Developer')->sole();
        $this->assertSame(LedgerEntryType::Experience, $entry->type);
        $this->assertSame('2020-01', $entry->started_on->format('Y-m'));
        $this->assertNull($entry->ended_on);
        $this->assertSame(['PHP'], $entry->skills);
        $this->assertSame(2, $entry->facts()->count());

        $this->actingAs($user)->get(route('ledger.index'))->assertInertia(fn ($page) => $page
            ->where('import.running', false)->where('import.result', ['entries' => 2, 'facts' => 2]));
    }

    public function test_existing_entries_are_kept_and_only_new_facts_are_added_to_them()
    {
        $user = User::factory()->create();
        $existing = LedgerEntry::factory()->for($user)->create([
            'type' => LedgerEntryType::Experience, 'title' => 'Backend Developer', 'organization' => 'Acme', 'confirmed_at' => now(),
        ]);
        $fact = LedgerFact::factory()->for($existing, 'entry')->create(['statement' => 'Built the checkout API.', 'confirmed_at' => now()]);
        $llm = $this->fakeLlm(['entries' => [
            $this->row('backend developer', 'ACME', ['built the checkout api.', 'Mentored two juniors.']),
        ]]);

        $this->actingAs($user)->post(route('ledger.import'), ['cv' => UploadedFile::fake()->createWithContent('cv.md', 'CV text')]);

        $this->assertStringContainsString('Built the checkout API.', $llm->prompt);
        $this->assertSame(1, $user->ledgerEntries()->count());
        $this->assertNotNull($existing->refresh()->confirmed_at);
        $this->assertNotNull($fact->refresh()->confirmed_at);

        $added = $existing->facts()->where('id', '!=', $fact->id)->sole();
        $this->assertSame('Mentored two juniors.', $added->statement);
        $this->assertNull($added->confirmed_at);
    }

    public function test_invalid_rows_and_dates_from_the_model_are_ignored()
    {
        $user = User::factory()->create();
        $this->fakeLlm(['entries' => [
            ['type' => 'hobby', 'title' => 'Chess', 'organization' => '', 'location' => '', 'started_on' => '', 'ended_on' => '', 'skills' => [], 'facts' => []],
            [...$this->row('', 'Acme', []), 'title' => '  '],
            [...$this->row('Developer', 'Acme', ['Wrote code.']), 'started_on' => 'March 2020', 'ended_on' => '2022-13'],
        ]]);

        $this->actingAs($user)->post(route('ledger.import'), ['cv' => UploadedFile::fake()->createWithContent('cv.txt', 'CV text')]);

        $entry = $user->ledgerEntries()->sole();
        $this->assertNull($entry->started_on);
        $this->assertNull($entry->ended_on);
    }

    public function test_text_is_read_from_pdf_and_word_files()
    {
        $pdf = new Dompdf;
        $pdf->loadHtml('<p>Senior Developer at Globex</p>');
        $pdf->render();

        $docx = tempnam(sys_get_temp_dir(), 'cv').'.docx';
        $zip = new ZipArchive;
        $zip->open($docx, ZipArchive::CREATE);
        $zip->addFromString('word/document.xml', '<w:document><w:body><w:p><w:r><w:t>Staff Engineer at Initech &amp; Co</w:t></w:r></w:p></w:body></w:document>');
        $zip->close();

        foreach ([
            [UploadedFile::fake()->createWithContent('cv.pdf', $pdf->output()), 'Senior Developer at Globex'],
            [new UploadedFile($docx, 'cv.docx', test: true), 'Staff Engineer at Initech & Co'],
        ] as [$file, $expected]) {
            $llm = $this->fakeLlm(['entries' => []]);
            $user = User::factory()->create();

            $this->actingAs($user)->post(route('ledger.import'), ['cv' => $file])->assertSessionHasNoErrors();

            $this->assertStringContainsString($expected, $llm->prompt);
        }
    }

    public function test_unsupported_or_empty_files_are_rejected()
    {
        $user = User::factory()->create();
        $this->fakeLlm(['entries' => []]);

        $this->actingAs($user)->post(route('ledger.import'), ['cv' => UploadedFile::fake()->create('cv.exe', 10)])->assertSessionHasErrors('cv');
        $this->actingAs($user)->post(route('ledger.import'), ['cv' => UploadedFile::fake()->createWithContent('cv.txt', "  \n ")])->assertSessionHasErrors('cv');
        $this->actingAs($user)->post(route('ledger.import'), [])->assertSessionHasErrors('cv');
    }

    public function test_a_failed_import_adds_nothing_and_reports_it()
    {
        $user = User::factory()->create();
        $this->app->instance(StructuredLlm::class, new class implements StructuredLlm
        {
            public function generate(string $system, string $prompt, array $schema): array
            {
                throw new LlmException('boom');
            }
        });

        $this->actingAs($user)->post(route('ledger.import'), ['cv' => UploadedFile::fake()->createWithContent('cv.txt', 'CV text')]);

        $this->assertSame(0, $user->ledgerEntries()->count());
        $this->actingAs($user)->get(route('ledger.index'))->assertInertia(fn ($page) => $page
            ->where('import.running', false)->has('import.result.error'));
    }
}
