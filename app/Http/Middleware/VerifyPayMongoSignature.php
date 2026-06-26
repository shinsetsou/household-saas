<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyPayMongoSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('paymongo.webhook_secret');

        if ($secret === '') {
            return response('Webhook secret not configured', 500);
        }

        $signature = $request->header('Paymongo-Signature');

        if (! $signature) {
            return response('Missing signature', 401);
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        if (! hash_equals($expected, $signature)) {
            return response('Invalid signature', 401);
        }

        return $next($request);
    }
}