<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ai;

use App\AI\AIManager;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class FounderBriefController extends Controller
{
    public function today(AIManager $manager): JsonResponse
    {
        $cacheKey = 'founder_brief:'.now()->toDateString();

        $payload = Cache::remember($cacheKey, now()->addMinutes(60), function () use ($manager) {
            $response = $manager->agent('founder')
                ->task('daily-brief')
                ->costCapCents((int) config('ai.agents.founder.cost_cap_cents', 10))
                ->run();

            if ($response->isError()) {
                return ['error' => $response->error];
            }

            $decoded = json_decode($response->text, true);

            return is_array($decoded) ? $decoded : ['raw' => $response->text];
        });

        return response()->json($payload);
    }
}
