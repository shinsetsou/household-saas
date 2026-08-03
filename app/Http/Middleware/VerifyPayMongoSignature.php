<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyPayMongoSignature
{
    private const TOLERANCE_SECONDS = 300;

    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('paymongo.webhook_signing_secret');

        if ($secret === '') {
            return response('Webhook signing secret not configured', 500);
        }

        $header = (string) $request->header('Paymongo-Signature', '');

        if ($header === '') {
            return response('Missing signature', 401);
        }

        $parts = [];
        foreach (explode(',', $header) as $segment) {
            $segment = trim($segment);
            if ($segment === '' || ! str_contains($segment, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $segment, 2);
            $parts[trim($k)] = trim($v);
        }

        $timestamp = $parts['t'] ?? null;
        $providedSignature = $parts[config('app.env') === 'production' ? 'li' : 'te'] ?? null;

        if ($timestamp === null || $providedSignature === null) {
            return response('Malformed signature header', 401);
        }

        if (abs(time() - (int) $timestamp) > self::TOLERANCE_SECONDS) {
            return response('Signature timestamp out of tolerance', 401);
        }

        $signedPayload = $timestamp.'.'.$request->getContent();
        $expected = hash_hmac('sha256', $signedPayload, $secret);

        if (! hash_equals($expected, $providedSignature)) {
            return response('Invalid signature', 401);
        }

        return $next($request);
    }
}
