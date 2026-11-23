<?php

namespace Tests\Feature;

use Tests\TestCase;

class SeoTest extends TestCase
{
    public function test_landing_page_has_meta_tags_and_structured_data(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<meta property="og:image"', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            ->assertSee('<link rel="canonical" href="'.route('home').'">', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('index, follow', false)
            ->assertSee('paste or link any vacancy', false)
            ->assertSee('General CV and cover letter drafted from the ledger without a job', false);
    }

    public function test_landing_page_has_faq_and_howto_structured_data(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('"@type":"FAQPage"', false)
            ->assertSee('"@type":"HowTo"', false)
            ->assertSee('Does Job Scout apply to jobs for me?', false);
    }

    public function test_other_pages_are_noindex_without_structured_data(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('noindex, nofollow', false)
            ->assertDontSee('application/ld+json', false);
    }

    public function test_robots_sitemap_and_llms_are_served(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: '.route('sitemap'), false)->assertSee('Allow: /favicon.ico', false);
        $this->get('/sitemap.xml')->assertOk()->assertSee('<loc>'.route('home').'</loc>', false);
        $this->get('/llms.txt')->assertOk()->assertSee('# Job Scout')->assertSee('pastes or links to', false)->assertSee('general document', false)->assertSee('Frequently asked questions', false)->assertSee('Typical workflow', false);
    }

    public function test_public_pages_are_indexable_with_their_own_canonical_and_schema(): void
    {
        $this->get('/faq')
            ->assertOk()
            ->assertSee('index, follow', false)
            ->assertSee('<link rel="canonical" href="'.route('pages.faq').'">', false)
            ->assertSee('"@type":"FAQPage"', false)
            ->assertSee('Does Job Scout apply to jobs for me?', false);

        $this->get('/how-it-works')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('pages.how-it-works').'">', false)
            ->assertSee('"@type":"HowTo"', false)
            ->assertSee('Build your ledger', false);

        $this->get('/features')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('pages.features').'">', false)
            ->assertSee('"@type":"WebPage"', false)
            ->assertSee('Fact ledger', false);
    }

    public function test_sitemap_and_llms_list_the_public_pages(): void
    {
        $sitemap = $this->get('/sitemap.xml')->assertOk();
        $llms = $this->get('/llms.txt')->assertOk();

        foreach (['pages.how-it-works', 'pages.features', 'pages.faq'] as $name) {
            $sitemap->assertSee('<loc>'.route($name).'</loc>', false);
            $llms->assertSee(route($name), false);
        }
    }
}
