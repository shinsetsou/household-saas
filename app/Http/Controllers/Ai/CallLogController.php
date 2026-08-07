<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\Controller;
use App\Models\AiCallLog;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class CallLogController extends Controller
{
    public function index(Request $request): LengthAwarePaginator
    {
        $perPage = min(100, max(1, (int) $request->query('per_page', 25)));

        return AiCallLog::query()
            ->when($request->query('agent'), fn ($q, $agent) => $q->where('agent', $agent))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('organization_id'), fn ($q, $id) => $q->where('organization_id', $id))
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
