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

    public function test_other_pages_are_noindex_without_structured_data(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('noindex, nofollow', false)
            ->assertDontSee('application/ld+json', false);
    }

    public function test_robots_sitemap_and_llms_are_served(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: '.route('sitemap'), false);
        $this->get('/sitemap.xml')->assertOk()->assertSee('<loc>'.route('home').'</loc>', false);
        $this->get('/llms.txt')->assertOk()->assertSee('# Job Scout')->assertSee('pastes or links to', false)->assertSee('general document', false);
    }
}
