<?php

namespace Tests\Unit\Support;

use App\Support\SentryPiiScrubber;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Sentry\Event;

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
        $this->scrubber = new SentryPiiScrubber;
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

    public function test_is_blocked_key_matches_backblaze_b2_fields(): void
    {
        $this->assertTrue($this->callProtected('isBlockedKey', ['keyId']));
        $this->assertTrue($this->callProtected('isBlockedKey', ['key_id']));
        $this->assertTrue($this->callProtected('isBlockedKey', ['applicationKey']));
        $this->assertTrue($this->callProtected('isBlockedKey', ['appKey']));
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

    public function test_scrub_tags_redacts_card_shaped_strings(): void
    {
        $tags = [
            'route' => '/dashboard',
            'customer_id' => 'cus_4242424242424242',
            'note' => 'plain value',
        ];

        $event = $this->makeTagStubEvent($tags);
        $this->callProtected('scrubTags', [$event]);

        $stored = $event->getTags();
        $this->assertSame('/dashboard', $stored['route']);
        $this->assertSame('cus_[Filtered]', $stored['customer_id']);
        $this->assertStringNotContainsString('4242424242424242', $stored['customer_id']);
        $this->assertSame('plain value', $stored['note']);
    }

    public function test_scrub_tags_redacts_paymongo_token_value(): void
    {
        $tags = [
            'stripe_id' => 'cus_abc',
            'paymongo_public_key' => 'pk_live_abc',
            'sentry_token' => 'something',
        ];

        $event = $this->makeTagStubEvent($tags);
        $this->callProtected('scrubTags', [$event]);

        $stored = $event->getTags();
        $this->assertSame('cus_abc', $stored['stripe_id']);
        $this->assertSame('[Filtered]', $stored['paymongo_public_key']);
        $this->assertSame('something', $stored['sentry_token']);
    }

    public function test_scrub_tags_redacts_blocked_tag_keys(): void
    {
        $tags = [
            'route' => '/dashboard',
            'api_key' => 'safe-looking-value',
            'password_tag' => 'hunter2',
        ];

        $event = $this->makeTagStubEvent($tags);
        $this->callProtected('scrubTags', [$event]);

        $stored = $event->getTags();
        $this->assertSame('/dashboard', $stored['route']);
        $this->assertSame('[Filtered]', $stored['api_key']);
        $this->assertSame('[Filtered]', $stored['password_tag']);
    }

    public function test_scrub_tags_redacts_b2_key_id_tag_value(): void
    {
        $tags = [
            'b2_endpoint' => 'https://bucket.s3.us-west-001.backblazeb2.com:443',
            'b2_key_id' => '002abcdef01234567890',
            'b2_application_key' => 'the_application_key_value',
        ];

        $event = $this->makeTagStubEvent($tags);
        $this->callProtected('scrubTags', [$event]);

        $stored = $event->getTags();
        $this->assertSame('https://bucket.s3.us-west-001.backblazeb2.com:443', $stored['b2_endpoint']);
        $this->assertSame('[Filtered]', $stored['b2_key_id']);
        $this->assertSame('[Filtered]', $stored['b2_application_key']);
    }

    public function test_scrub_tags_returns_early_when_event_has_no_tags_method(): void
    {
        $event = new class {};

        $this->callProtected('scrubTags', [$event]);

        $this->assertTrue(true);
    }

    public function test_scrub_tags_skips_empty_tag_array(): void
    {
        $event = $this->makeTagStubEvent([]);

        $this->callProtected('scrubTags', [$event]);

        $this->assertSame([], $event->getTags());
    }

    public function test_scrub_contexts_redacts_object_data(): void
    {
        $event = $this->makeContextStubEvent([
            'organization' => new class
            {
                private array $data = [
                    'slug' => 'foo-hoa',
                    'paymongo_secret_key' => 'sk_live_xxx',
                ];

                public function getData(): array
                {
                    return $this->data;
                }

                public function setData(array $data): void
                {
                    $this->data = $data;
                }
            },
        ]);

        $this->callProtected('scrubContexts', [$event]);

        $stored = $event->getContexts()['organization']->getData();
        $this->assertSame('foo-hoa', $stored['slug']);
        $this->assertSame('[Filtered]', $stored['paymongo_secret_key']);
    }

    public function test_scrub_contexts_redacts_array_data(): void
    {
        $event = $this->makeContextStubEvent([
            'runtime' => [
                'name' => 'php',
                'api_key' => 'should_be_filtered',
            ],
        ]);

        $this->callProtected('scrubContexts', [$event]);

        $this->assertSame('[Filtered]', $event->getContexts()['runtime']['api_key']);
        $this->assertSame('php', $event->getContexts()['runtime']['name']);
    }

    public function test_scrub_contexts_replaces_blocked_context_names(): void
    {
        $event = $this->makeContextStubEvent([
            'password_context' => ['arbitrary' => 'value'],
            'safe' => 'hello',
        ]);

        $this->callProtected('scrubContexts', [$event]);

        $this->assertSame('[Filtered]', $event->getContexts()['password_context']);
        $this->assertSame('hello', $event->getContexts()['safe']);
    }

    public function test_scrub_contexts_returns_early_on_event_without_method(): void
    {
        $event = new class {};

        $this->callProtected('scrubContexts', [$event]);

        $this->assertTrue(true);
    }

    public function test_scrub_contexts_skips_empty_context_array(): void
    {
        $event = $this->makeContextStubEvent([]);

        $this->callProtected('scrubContexts', [$event]);

        $this->assertSame([], $event->getContexts());
    }

    public function test_invoke_calls_set_extra_set_breadcrumbs_and_preserves_event(): void
    {
        // Build the smallest event-like stub that satisfies every code path in
        // __invoke (breadcrumbs, extra, tags, contexts, user, request).
        $event = new class
        {
            public ?string $userSet = 'never-called';

            private array $extra = [
                'password' => 'top',
                'plain' => 'value',
            ];

            private array $breadcrumbs = [
                ['message' => 'crumb', 'data' => ['api_key' => 'k']],
            ];

            private array $contexts = [
                'app' => ['password' => 'c'],
            ];

            private array $tags = [
                'api_key' => 'tag-val',
            ];

            private ?object $user = null;

            private ?object $request = null;

            public function __construct()
            {
                $this->user = new class
                {
                    public function setUsername(?string $v): void {}
                    public function setEmail(?string $v): void {}
                    public function setIpAddress(?string $v): void {}
                    public function setData(array $d): void {}
                };
            }

            public function getExtra(): array { return $this->extra; }
            public function setExtra(array $extra): void { $this->extra = $extra; }

            public function getBreadcrumbs(): array { return $this->breadcrumbs; }
            public function setBreadcrumbs(array $b): void { $this->breadcrumbs = $b; }

            public function getContexts(): array { return $this->contexts; }
            public function setContexts(array $c): void { $this->contexts = $c; }

            public function getTags(): array { return $this->tags; }
            public function setTags(array $t): void { $this->tags = $t; }

            public function getUser(): ?object { return $this->user; }
            public function getRequest(): ?object { return $this->request; }
        };

        $result = $this->scrubber->__invoke($event);

        $this->assertSame($event, $result);
        $this->assertSame('[Filtered]', $event->getExtra()['password']);
        $this->assertSame('[Filtered]', $event->getBreadcrumbs()[0]['data']['api_key']);
        $this->assertSame('[Filtered]', $event->getTags()['api_key']);
        $this->assertSame('[Filtered]', $event->getContexts()['app']['password']);
    }

    private function makeTagStubEvent(array $tags): object
    {
        return new class($tags)
        {
            private array $stored;

            public function __construct(array $tags)
            {
                $this->stored = $tags;
            }

            public function getTags(): array
            {
                return $this->stored;
            }

            public function setTags(array $tags): void
            {
                $this->stored = $tags;
            }
        };
    }

    private function makeContextStubEvent(array $contexts): object
    {
        return new class($contexts)
        {
            private array $stored;

            public function __construct(array $contexts)
            {
                $this->stored = $contexts;
            }

            public function getContexts(): array
            {
                return $this->stored;
            }

            public function setContexts(array $contexts): void
            {
                $this->stored = $contexts;
            }
        };
    }
}
