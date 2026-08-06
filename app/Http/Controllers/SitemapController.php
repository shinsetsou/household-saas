<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $base = rtrim(config('app.url') ?: url('/'), '/');

        $pages = [
            ['loc' => $base.'/', 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['loc' => $base.'/#features', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['loc' => $base.'/#how', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['loc' => $base.'/#pricing', 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => $base.'/#faq', 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['loc' => route('login'), 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['loc' => route('register'), 'priority' => '0.9', 'changefreq' => 'monthly'],
            ['loc' => route('privacy'), 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => route('terms'), 'priority' => '0.3', 'changefreq' => 'yearly'],
        ];

        $xml = ['<?xml version="1.0" encoding="UTF-8"?>'];
        $xml[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($pages as $page) {
            $xml[] = '  <url>';
            $xml[] = '    <loc>'.e($page['loc']).'</loc>';
            $xml[] = '    <lastmod>'.now()->toAtomString().'</lastmod>';
            $xml[] = '    <changefreq>'.$page['changefreq'].'</changefreq>';
            $xml[] = '    <priority>'.$page['priority'].'</priority>';
            $xml[] = '  </url>';
        }

        $xml[] = '</urlset>';

        return response(implode("\n", $xml), 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
        ]);
    }
}
