<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AiObservabilityService;
use App\Models\ScanPipelineLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AiObservabilityController extends Controller
{
    protected $observabilityService;

    public function __construct(AiObservabilityService $observabilityService)
    {
        $this->observabilityService = $observabilityService;
    }

    /**
     * Display the main observability dashboard.
     */
    public function index(Request $request)
    {
        $range = $request->query('range', 'today');
        if (!in_array($range, ['today', '7days', '30days'])) {
            $range = 'today';
        }

        $stats = $this->observabilityService->stats($range);

        return view('admin.observability.index', [
            'stats' => $stats,
            'currentRange' => $range,
        ]);
    }

    /**
     * Get JSON stats for async charts/tables update.
     */
    public function stats(Request $request)
    {
        $range = $request->query('range', 'today');
        if (!in_array($range, ['today', '7days', '30days'])) {
            $range = 'today';
        }

        $stats = $this->observabilityService->stats($range);

        return response()->json($stats);
    }

    /**
     * Display the health status of AI providers.
     */
    public function providers()
    {
        $providers = ['Gemini', 'GoogleBooks', 'OpenLibrary', 'Tavily'];
        $providerStats = [];

        foreach ($providers as $provider) {
            $total = ScanPipelineLog::where('provider', $provider)->count();
            $success = ScanPipelineLog::where('provider', $provider)->where('status', 'success')->count();
            
            $avgLatency = ScanPipelineLog::where('provider', $provider)->avg('duration_ms');

            $lastSuccess = ScanPipelineLog::where('provider', $provider)
                ->where('status', 'success')
                ->orderBy('id', 'desc')
                ->first();

            $lastFailure = ScanPipelineLog::where('provider', $provider)
                ->where('status', 'failed')
                ->orderBy('id', 'desc')
                ->first();

            $successRate = $total > 0 ? round(($success / $total) * 100, 1) : 0.0;

            $providerStats[$provider] = [
                'name' => $provider,
                'total' => $total,
                'success' => $success,
                'failed' => $total - $success,
                'success_rate' => $successRate,
                'avg_latency' => $avgLatency ? (int) round($avgLatency) : 0,
                'last_success' => $lastSuccess ? $lastSuccess->created_at : null,
                'last_failure' => $lastFailure ? $lastFailure->created_at : null,
            ];
        }

        return view('admin.observability.providers', [
            'providerStats' => $providerStats,
        ]);
    }
}
