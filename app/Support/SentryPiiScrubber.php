<?php

namespace App\Support;

use Sentry\Event;
use Sentry\EventHint;

/**
 * Strips sensitive fields from Sentry events before they're sent.
 *
 * The Sentry SDK auto-scrubs `password`, `secret`, `api_key`, etc. via its
 * built-in defaults. We extend the list with our domain-specific keys
 * (PayMongo secrets, B2 keys, etc.) and also scrub nested contexts,
 * breadcrumbs, tags, and card-shaped numbers anywhere in the payload.
 *
 * Bound in App\Providers\SentryServiceProvider as a Sentry event processor.
 */
class SentryPiiScrubber
{
    /** @var array<int, string> Field name fragments to scrub (case-insensitive). */
    protected array $blockedFragments = [
        'password',
        'passwd',
        'secret',
        'api_key',
        'apikey',
        'authorization',
        'access_token',
        'refresh_token',
        'paymongo_',
        'aws_secret',
        'aws_access_key',
        // Backblaze B2 API surfaces key material under these names. We also
        // catch `keyid` and `key_id` so any upstream S3-compatible client
        // that passes through `keyId:applicationKey` shape gets redacted.
        'keyid',
        'key_id',
        'applicationkey',
        'application_key',
        'appkey',
        'app_key',
        'mail_password',
        'remember_token',
        'csrf',
        'credit_card',
        'card_number',
        'cvv',
        'ssn',
        'bearer',
    ];

    /**
     * Card-shaped number regex — 13-19 digit runs allowing ` ` or `-`
     * separators between digits. The boundaries (alphanumeric-only, not
     * word-boundary) let us match identifiers like `cus_4242424242424242`
     * while rejecting clearly-non-card digit strings inside URLs, UUIDs,
     * phone numbers, etc.
     */
    protected string $cardNumberPattern = '/(?:(?<![A-Za-z0-9])(?:\d[ -]?){12,18}\d)(?![A-Za-z0-9])/';

    public function __invoke($event, ?EventHint $hint = null)
    {
        if (! is_object($event) || ! method_exists($event, 'setExtra')) {
            return $event;
        }

        if (method_exists($event, 'getBreadcrumbs') && method_exists($event, 'setBreadcrumbs')) {
            $event->setBreadcrumbs($this->scrubBreadcrumbs($event->getBreadcrumbs()));
        }

        if (method_exists($event, 'getExtra') && method_exists($event, 'setExtra')) {
            $event->setExtra($this->scrubRecursive($event->getExtra()));
        }

        $this->scrubContexts($event);
        $this->scrubTags($event);

        $this->scrubUser($event);

        if (method_exists($event, 'getRequest')) {
            $request = $event->getRequest();
            if ($request !== null && method_exists($request, 'getData') && method_exists($request, 'setData')) {
                $data = $request->getData();
                if (is_array($data)) {
                    $request->setData($this->scrubRecursive($data));
                }
            }
        }

        return $event;
    }

    /**
     * Walk every context (runtime, OS, app, organization, custom, …) and
     * scrub both the keys and any nested data. `getContexts()` returns an
     * array of `EventContext` objects, each carrying its payload as an
     * array accessible via `getData()`.
     *
     * The parameter is untyped because the Sentry SDK is an optional
     * dependency and the early `method_exists` guard handles both stubs
     * (tests) and the real SDK.
     */
    protected function scrubContexts($event): void
    {
        if (! method_exists($event, 'getContexts')) {
            return;
        }

        $contexts = $event->getContexts();
        if ($contexts === []) {
            return;
        }

        foreach ($contexts as $name => $context) {
            if (is_object($context) && method_exists($context, 'getData')) {
                $data = $context->getData();
                if (is_array($data)) {
                    $context->setData($this->scrubRecursive($data));
                }
            } elseif (is_array($context)) {
                $contexts[$name] = $this->scrubRecursive($context);
            }

            // Block-by-name: any context whose name leaks a secret (e.g.
            // "paymongo-credentials") is replaced wholesale. The name itself
            // isn't sensitive here, but a future custom integration might
            // choose that as a context key with sensitive extra fields.
            if (is_string($name) && $this->isBlockedKey($name)) {
                $contexts[$name] = '[Filtered]';
            }
        }

        if (method_exists($event, 'setContexts')) {
            $event->setContexts($contexts);
        }
    }

    /**
     * Scrub tag values that look like credentials. Tags are `string => string`,
     * but `Sentry::setTag('user_email', $user->email)` style leaks happen here
     * for any custom integration. We also redact any tag whose *key* matches
     * the blocked fragments (forward-compat — defensive even though tags
     * today should be plain).
     */
    protected function scrubTags($event): void
    {
        if (! method_exists($event, 'getTags')) {
            return;
        }

        $tags = $event->getTags();
        if (! is_array($tags) || $tags === []) {
            return;
        }

        foreach ($tags as $key => $value) {
            if (is_string($key) && $this->isBlockedKey($key)) {
                $tags[$key] = '[Filtered]';
                continue;
            }

            if (is_string($value) && preg_match($this->cardNumberPattern, $value)) {
                $tags[$key] = preg_replace($this->cardNumberPattern, '[Filtered]', $value);
            } elseif (is_string($value) && $value !== '' && (
                str_contains(strtolower($value), 'sk_live_')
                || str_contains(strtolower($value), 'sk_test_')
                || str_contains(strtolower($value), 'pk_live_')
                || str_contains(strtolower($value), 'pk_test_')
                || str_contains(strtolower($value), 'bearer ')
            )) {
                $tags[$key] = '[Filtered]';
            }
        }

        if (method_exists($event, 'setTags')) {
            $event->setTags($tags);
        }
    }

    protected function scrubBreadcrumbs(array $breadcrumbs): array
    {
        return array_map(function ($crumb) {
            if (isset($crumb['data']) && is_array($crumb['data'])) {
                $crumb['data'] = $this->scrubRecursive($crumb['data']);
            }

            return $crumb;
        }, $breadcrumbs);
    }

    protected function scrubUser($event): void
    {
        $user = $event->getUser();

        if ($user === null) {
            return;
        }

        if (method_exists($user, 'setUsername')) {
            $user->setUsername(null);
        }
        if (method_exists($user, 'setEmail')) {
            $user->setEmail(null);
        }
        if (method_exists($user, 'setIpAddress')) {
            $user->setIpAddress(null);
        }
        if (method_exists($user, 'setData')) {
            $existing = method_exists($user, 'getData') ? $user->getData() : [];
            $user->setData(is_array($existing) ? ['id' => $existing['id'] ?? null] : []);
        }
    }

    protected function scrubRecursive(array $data): array
    {
        $out = [];

        foreach ($data as $key => $value) {
            if (is_string($key) && $this->isBlockedKey($key)) {
                $out[$key] = '[Filtered]';

                continue;
            }

            if (is_array($value)) {
                $out[$key] = $this->scrubRecursive($value);
            } elseif (is_string($value)) {
                $out[$key] = preg_replace($this->cardNumberPattern, '[Filtered]', $value);
            } else {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    protected function isBlockedKey(string $key): bool
    {
        $lower = strtolower($key);

        foreach ($this->blockedFragments as $fragment) {
            if (str_contains($lower, $fragment)) {
                return true;
            }
        }

        return false;
    }
}
