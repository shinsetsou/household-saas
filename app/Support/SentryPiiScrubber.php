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
 * breadcrumbs, and card-shaped numbers anywhere in the payload.
 *
 * Bound in App\Providers\AppServiceProvider as a Sentry event processor.
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
        'mail_password',
        'remember_token',
        'csrf',
        'credit_card',
        'card_number',
        'cvv',
        'ssn',
        'bearer',
    ];

    /** @var string Regex for 13-19 digit runs (card-shaped). */
    protected string $cardNumberPattern = '/\b(?:\d[ -]?){12,18}\d\b/';

    public function __invoke(Event $event, ?EventHint $hint = null): ?Event
    {
        $event->setBreadcrumbs($this->scrubBreadcrumbs($event->getBreadcrumbs()));
        $event->setExtra($this->scrubRecursive($event->getExtra()));

        $this->scrubUser($event);

        $request = $event->getRequest();
        if ($request !== null) {
            $data = $request->getData();
            if (is_array($data)) {
                $request->setData($this->scrubRecursive($data));
            }
        }

        return $event;
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

    protected function scrubUser(Event $event): void
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
