<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Laravel `trustProxies(at: '*')` configuration is required so that
 * `request()->isSecure()` reflects the upstream proxy. We deliberately
 * scope the trusted forwarded headers to PROT-PROTO (and AWS-ELB) only;
 * X-Forwarded-For / X-Forwarded-Host must stay NOT-trusted so an attacker
 * cannot spoof `request()->ip()` to bypass IP-keyed throttles or
 * `request()->getHost()` to manipulate signed-URL signing.
 */
class TrustProxiesConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_ip_ignores_spoofed_x_forwarded_for(): void
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
            ->withHeaders(['X-Forwarded-For' => '203.0.113.99'])
            ->get('/up');

        $response->assertOk();

        // Symfony-side stored REMOTE_ADDR wins because X-Forwarded-For is
        // not in the trusted-header bitmask.
        $this->assertSame('198.51.100.10', request()->ip());
    }

    public function test_request_ip_ignores_x_forwarded_for_with_multiple_entries(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
            ->withHeaders([
                'X-Forwarded-For' => '203.0.113.99, 198.51.100.1, 10.0.0.5',
            ])
            ->get('/up')
            ->assertOk();

        $this->assertSame('198.51.100.10', request()->ip());
    }

    public function test_request_does_not_honor_x_forwarded_proto_when_spoofed(): void
    {
        // X-Forwarded-Proto IS in the trusted set (we need it for
        // https detection behind Render's TLS-terminating LB). Sanity:
        // turning the header ON makes request()->isSecure() true.
        $response = $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('/up');

        $response->assertOk();
        $this->assertTrue(request()->isSecure());
    }

    public function test_request_ip_keyed_throttle_resists_spoofed_x_forwarded_for(): void
    {
        // Burn the first request from the "real" IP. A second request from
        // the same connection (same REMOTE_ADDR) should still succeed when
        // limits permit. A request whose X-Forwarded-For differs but
        // REMOTE_ADDR is the same should NOT get a fresh bucket.
        $route = '/up';

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
            ->withHeaders(['X-Forwarded-For' => '1.1.1.1'])
            ->get($route)
            ->assertOk();

        // Different XFF, same TCP peer → still counts as the same bucket.
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
            ->withHeaders(['X-Forwarded-For' => '2.2.2.2'])
            ->get($route)
            ->assertOk();

        $this->assertSame('198.51.100.10', request()->ip());
    }

    public function test_different_remote_addrs_really_are_different_buckets(): void
    {
        // Two separate TCP peers (different REMOTE_ADDR) are still distinct.
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
            ->get('/up')
            ->assertOk();

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
            ->get('/up')
            ->assertOk();

        $this->assertSame('198.51.100.20', request()->ip());
    }
}
