<?php

namespace App\Http\Controllers;

use App\Models\AiUsageLog;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;

class AiConsumptionController extends Controller
{
    public function index()
    {
        $summary = Cache::remember('ai-consumption.summary', now()->addSeconds(60), function () {
            $row = AiUsageLog::query()
                ->selectRaw('COALESCE(SUM(total_tokens), 0) as total_tokens')
                ->selectRaw('COALESCE(SUM(estimated_cost), 0) as estimated_cost')
                ->selectRaw("SUM(CASE WHEN service = 'vision_extraction' THEN 1 ELSE 0 END) as vision_requests")
                ->selectRaw("SUM(CASE WHEN service = 'text_parsing' THEN 1 ELSE 0 END) as text_parser_requests")
                ->first();

            return [
                'totalTokens' => (int) ($row?->total_tokens ?? 0),
                'estimatedCost' => round((float) ($row?->estimated_cost ?? 0), 4),
                'visionRequests' => (int) ($row?->vision_requests ?? 0),
                'textParserRequests' => (int) ($row?->text_parser_requests ?? 0),
            ];
        });

        $logs = AiUsageLog::query()
            ->with('user:id,name')
            ->latest()
            ->take(20)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'timestamp' => $log->created_at?->toDateTimeString(),
                'service' => $log->service,
                'model' => $log->model,
                'user' => $log->user?->name ?? 'System',
                'tokens' => (int) $log->total_tokens,
                'cost' => (float) $log->estimated_cost,
            ]);

        return Inertia::render('ai-consumption', [
            'summary' => $summary,
            'logs' => $logs,
        ]);
    }
}