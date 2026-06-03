<?php

namespace Tests\Feature;

use Tests\TestCase;

class BrandHeaderTest extends TestCase
{
    public function test_register_page_renders_brand_header(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertSee('Barangay', false);
        $response->assertSee('Books', false);
        $response->assertSee('from-brand-500', false);
        $response->assertSee('to-brand-700', false);
    }
}
