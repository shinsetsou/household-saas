<?php

namespace Tests\Unit\Support;

use App\Support\SentryPiiScrubber;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\State\Scope;

/**
 * The Sentry SDK is not installed in this project's test environment, but
 * the scrubber class itself can be exercised by reflecting on its private
 * methods. Tests focus on the scrubbing rules, not on Sentry event objects.
 */
class SentryPiiScrubberTest extends TestCase
{
    protected SentryPiiScrubber $scrubber;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scrubber = new SentryPiiScrubber();
    }

    protected function callProtected(string $method, array $args)
    {
        $reflection = new ReflectionClass($this->scrubber);
        $m = $reflection->getMethod($method);
        $m->setAccessible(true);

        return $m->invokeArgs($this->scrubber, $args);
    }

    public function test_is_blocked_key_matches_password(): void
    {
        $this->assertTrue($this->callProtected('isBlockedKey', ['password']));
        $this->assertTrue($this->callProtected('isBlockedKey', ['user_password']));
        $this->assertTrue($this->callProtected('isBlockedKey', ['Password']));
    }

    public function test_is_blocked_key_matches_paymongo_secrets(): void
    {
        $this->assertTrue($this->callProtected('isBlockedKey', ['PAYMONGO_SECRET_KEY']));
        $this->assertTrue($this->callProtected('isBlockedKey', ['paymongo_webhook_secret']));
        $this->assertTrue($this->callProtected('isBlockedKey', ['PAYMONGO_PUBLIC_KEY']));
    }

    public function test_is_blocked_key_matches_aws_keys(): void
    {
        $this->assertTrue($this->callProtected('isBlockedKey', ['aws_secret_access_key']));
        $this->assertTrue($this->callProtected('isBlockedKey', ['aws_access_key_id']));
    }

    public function test_is_blocked_key_matches_token_keys(): void
    {
        $this->assertTrue($this->callProtected('isBlockedKey', ['access_token']));
        $this->assertTrue($this->callProtected('isBlockedKey', ['refresh_token']));
        $this->assertTrue($this->callProtected('isBlockedKey', ['remember_token']));
        $this->assertTrue($this->callProtected('isBlockedKey', ['_token']));
    }

    public function test_is_blocked_key_matches_payment_keys(): void
    {
        $this->assertTrue($this->callProtected('isBlockedKey', ['card_number']));
        $this->assertTrue($this->callProtected('isBlockedKey', ['cvv']));
    }

    public function test_is_blocked_key_passes_safe_keys(): void
    {
        $this->assertFalse($this->callProtected('isBlockedKey', ['email']));
        $this->assertFalse($this->callProtected('isBlockedKey', ['name']));
        $this->assertFalse($this->callProtected('isBlockedKey', ['amount']));
        $this->assertFalse($this->callProtected('isBlockedKey', ['organization_id']));
        $this->assertFalse($this->callProtected('isBlockedKey', ['reference_number']));
    }

    public function test_scrub_recursive_redacts_blocked_top_level_keys(): void
    {
        $data = [
            'email' => 'user@example.com',
            'password' => 'secret123',
            'name' => 'Alice',
        ];

        $scrubbed = $this->callProtected('scrubRecursive', [$data]);

        $this->assertSame('user@example.com', $scrubbed['email']);
        $this->assertSame('[Filtered]', $scrubbed['password']);
        $this->assertSame('Alice', $scrubbed['name']);
    }

    public function test_scrub_recursive_redacts_nested_blocked_keys(): void
    {
        $data = [
            'user' => [
                'email' => 'user@example.com',
                'password' => 'secret',
                'profile' => [
                    'name' => 'Alice',
                    'paymongo_secret_key' => 'sk_live_xxx',
                ],
            ],
        ];

        $scrubbed = $this->callProtected('scrubRecursive', [$data]);

        $this->assertSame('[Filtered]', $scrubbed['user']['password']);
        $this->assertSame('[Filtered]', $scrubbed['user']['profile']['paymongo_secret_key']);
        $this->assertSame('Alice', $scrubbed['user']['profile']['name']);
        $this->assertSame('user@example.com', $scrubbed['user']['email']);
    }

    public function test_scrub_recursive_redacts_card_shaped_numbers_in_strings(): void
    {
        $data = [
            'note' => 'paid with card 4242 4242 4242 4242 today',
            'description' => 'normal text',
        ];

        $scrubbed = $this->callProtected('scrubRecursive', [$data]);

        $this->assertStringContainsString('[Filtered]', $scrubbed['note']);
        $this->assertStringNotContainsString('4242 4242 4242 4242', $scrubbed['note']);
        $this->assertSame('normal text', $scrubbed['description']);
    }

    public function test_scrub_recursive_preserves_non_string_scalars(): void
    {
        $data = [
            'count' => 42,
            'flag' => true,
            'amount' => 199.00,
        ];

        $scrubbed = $this->callProtected('scrubRecursive', [$data]);

        $this->assertSame(42, $scrubbed['count']);
        $this->assertTrue($scrubbed['flag']);
        $this->assertSame(199.00, $scrubbed['amount']);
    }

    public function test_scrub_breadcrumbs_redacts_data_arrays(): void
    {
        $breadcrumbs = [
            ['message' => 'login attempt', 'data' => ['password' => 'secret']],
            ['message' => 'pageview', 'data' => ['path' => '/dashboard']],
        ];

        $scrubbed = $this->callProtected('scrubBreadcrumbs', [$breadcrumbs]);

        $this->assertSame('[Filtered]', $scrubbed[0]['data']['password']);
        $this->assertSame('/dashboard', $scrubbed[1]['data']['path']);
    }
}