<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $base = rtrim(config('app.url') ?: url('/'), '/');

        $lines = [
            'User-agent: *',
            'Disallow: /dashboard',
            'Disallow: /profile',
            'Disallow: /onboarding/',
            'Disallow: /invitations/',
            'Disallow: /billing/',
            'Allow: /',
            '',
            'Sitemap: '.$base.'/sitemap.xml',
            '',
        ];

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
}
