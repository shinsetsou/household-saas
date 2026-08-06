<?php

namespace Tests\Unit;

use App\Support\Seo;
use Tests\TestCase;

class SeoTest extends TestCase
{
    public function test_make_appends_site_name_to_title_when_missing(): void
    {
        $seo = Seo::make(title: 'Household lookup');

        $this->assertStringContainsString('BarangayBooks', $seo->title);
        $this->assertStringContainsString('Household lookup', $seo->title);
    }

    public function test_make_does_not_double_append_site_name(): void
    {
        $seo = Seo::make(title: 'Household lookup — BarangayBooks');

        $occurrences = substr_count($seo->title, 'BarangayBooks');
        $this->assertSame(1, $occurrences);
    }

    public function test_make_truncates_title_to_60_chars(): void
    {
        $seo = Seo::make(title: str_repeat('a', 200));

        $this->assertLessThanOrEqual(60, mb_strlen($seo->title));
    }

    public function test_make_truncates_description_to_160_chars(): void
    {
        $seo = Seo::make(description: str_repeat('a', 500));

        $this->assertLessThanOrEqual(160, mb_strlen($seo->description));
    }

    public function test_with_no_index_returns_new_instance(): void
    {
        $seo = Seo::make();
        $noIndex = $seo->withNoIndex();

        $this->assertNotSame($seo, $noIndex);
        $this->assertFalse($seo->noIndex);
        $this->assertTrue($noIndex->noIndex);
    }

    public function test_with_schema_merges_payloads(): void
    {
        $seo = Seo::make();
        $first = $seo->withSchema([['@type' => 'Organization', 'name' => 'X']]);
        $merged = $first->withSchema([['@type' => 'FAQPage']]);

        $this->assertCount(2, $merged->schema);
    }

    public function test_render_includes_required_meta_tags(): void
    {
        $seo = Seo::make(
            title: 'Welcome',
            description: 'A test page',
            canonical: 'https://example.test/',
        );

        $html = $seo->render();

        $this->assertStringContainsString('name="description"', $html);
        $this->assertStringContainsString('rel="canonical"', $html);
        $this->assertStringContainsString('property="og:title"', $html);
        $this->assertStringContainsString('property="og:image"', $html);
        $this->assertStringContainsString('name="twitter:card"', $html);
        $this->assertStringNotContainsString('noindex', $html);
        $this->assertStringNotContainsString('<title>', $html);
    }

    public function test_render_emits_noindex_meta_when_disabled(): void
    {
        $seo = Seo::make(title: 'Hidden')->withNoIndex();

        $html = $seo->render();

        $this->assertStringContainsString('noindex, nofollow', $html);
        $this->assertStringNotContainsString('rel="canonical"', $html);
    }

    public function test_render_includes_schema_json_ld(): void
    {
        $seo = Seo::make()->withSchema([
            ['@type' => 'Organization', 'name' => 'BarangayBooks'],
        ]);

        $html = $seo->render();

        $this->assertStringContainsString('application/ld+json', $html);
        $this->assertStringContainsString('"@type":"Organization"', $html);
    }
}
