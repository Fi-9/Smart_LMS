@extends('layouts.app')

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <h1 class="page-title text-2xl font-bold tracking-tight text-foreground">AI Observability & Analytics</h1>
            <p class="page-subtitle text-sm text-muted">Pantau performa, latency, kegagalan stage, dan health provider AI secara real-time.</p>
        </div>

        {{-- Date range picker --}}
        <div class="inline-flex rounded-xl border border-border bg-surface p-1 shadow-sm">
            <a href="{{ route('admin.observability.index', ['range' => 'today']) }}" 
               data-range="today" 
               class="px-4 py-2 text-xs font-semibold rounded-lg transition-all duration-200 {{ $currentRange === 'today' ? 'bg-primary-600 text-white shadow-sm' : 'text-muted hover:text-foreground' }}">
                Today
            </a>
            <a href="{{ route('admin.observability.index', ['range' => '7days']) }}" 
               data-range="7days" 
               class="px-4 py-2 text-xs font-semibold rounded-lg transition-all duration-200 {{ $currentRange === '7days' ? 'bg-primary-600 text-white shadow-sm' : 'text-muted hover:text-foreground' }}">
                Last 7 Days
            </a>
            <a href="{{ route('admin.observability.index', ['range' => '30days']) }}" 
               data-range="30days" 
               class="px-4 py-2 text-xs font-semibold rounded-lg transition-all duration-200 {{ $currentRange === '30days' ? 'bg-primary-600 text-white shadow-sm' : 'text-muted hover:text-foreground' }}">
                Last 30 Days
            </a>
        </div>
    </div>

    {{-- Top Stats Grid --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        {{-- Success Rate Card --}}
        <x-card class="relative overflow-hidden transition-all duration-300 hover:shadow-md hover:-translate-y-0.5">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">Success Rate</p>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </span>
            </div>
            <div class="mt-4">
                <h3 class="text-3xl font-bold tracking-tight text-foreground" id="metric-success-rate">{{ $stats['success_rate'] }}%</h3>
                <p class="mt-1 text-xs text-muted">Dari <span id="metric-total-scans">{{ $stats['total_scans'] }}</span> total scan</p>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-emerald-500 to-teal-400"></div>
        </x-card>

        {{-- Avg Latency Card --}}
        <x-card class="relative overflow-hidden transition-all duration-300 hover:shadow-md hover:-translate-y-0.5">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">Avg Latency</p>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-500/10 text-blue-600 dark:text-blue-400">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </span>
            </div>
            <div class="mt-4">
                <h3 class="text-3xl font-bold tracking-tight text-foreground" id="metric-latency">{{ number_format(($stats['avg_latency']['total'] ?? 0) / 1000, 2) }}s</h3>
                <p class="mt-1 text-xs text-muted">Durasi total pipeline</p>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-blue-500 to-indigo-400"></div>
        </x-card>

        {{-- Cache Hit Rate Card --}}
        <x-card class="relative overflow-hidden transition-all duration-300 hover:shadow-md hover:-translate-y-0.5">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">Cache Hit Rate</p>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-500/10 text-violet-600 dark:text-violet-400">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                </span>
            </div>
            <div class="mt-4">
                <h3 class="text-3xl font-bold tracking-tight text-foreground" id="metric-cache-hit-rate">{{ $stats['cache_hit_rate'] }}%</h3>
                <p class="mt-1 text-xs text-muted">Penghematan API hit</p>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-violet-500 to-purple-400"></div>
        </x-card>

        {{-- Avg Completeness Card --}}
        <x-card class="relative overflow-hidden transition-all duration-300 hover:shadow-md hover:-translate-y-0.5">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">Avg Completeness</p>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><path d="m9 12 2 2 4-4"/></svg>
                </span>
            </div>
            <div class="mt-4">
                <h3 class="text-3xl font-bold tracking-tight text-foreground" id="metric-avg-completeness">{{ $stats['avg_completeness'] }}%</h3>
                <p class="mt-1 text-xs text-muted">Kelengkapan metadata</p>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-indigo-500 to-blue-400"></div>
        </x-card>

        {{-- Lowest Completeness Card --}}
        <x-card class="relative overflow-hidden transition-all duration-300 hover:shadow-md hover:-translate-y-0.5">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">Lowest Completeness</p>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-500/10 text-rose-600 dark:text-rose-400">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </span>
            </div>
            <div class="mt-4">
                <h3 class="text-3xl font-bold tracking-tight text-foreground" id="metric-lowest-completeness">{{ $stats['lowest_completeness'] }}%</h3>
                <p class="mt-1 text-xs text-muted">Kelengkapan terendah</p>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-rose-500 to-red-400"></div>
        </x-card>

        {{-- Avg Confidence Card --}}
        <x-card class="relative overflow-hidden transition-all duration-300 hover:shadow-md hover:-translate-y-0.5">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wider text-muted">Avg Confidence</p>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400">
                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                </span>
            </div>
            <div class="mt-4">
                <h3 class="text-3xl font-bold tracking-tight text-foreground" id="metric-confidence">{{ $stats['avg_confidence'] }}%</h3>
                <p class="mt-1 text-xs text-muted font-medium text-emerald-600 dark:text-emerald-400">Target auto-approve: 95%</p>
            </div>
            <div class="absolute bottom-0 inset-x-0 h-1 bg-gradient-to-r from-amber-500 to-orange-400"></div>
        </x-card>
    </div>

    {{-- Main Charts Section --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-6">
        {{-- Pipeline Latency Stage Bar Chart --}}
        <x-card>
            <div class="flex items-center justify-between border-b border-border pb-3 mb-4">
                <h2 class="text-sm font-bold tracking-tight text-foreground uppercase">Pipeline Latency Per Stage (ms)</h2>
                <span class="text-xs text-muted">Waktu proses rata-rata</span>
            </div>
            <div class="relative h-64 w-full">
                <canvas id="pipelineLatencyChart"></canvas>
            </div>
        </x-card>

        {{-- Scan Volume & Confidence Trend Line Chart --}}
        <x-card>
            <div class="flex items-center justify-between border-b border-border pb-3 mb-4">
                <h2 class="text-sm font-bold tracking-tight text-foreground uppercase">Scan Volume & Confidence Trend</h2>
                <span class="text-xs text-muted">Statistik harian</span>
            </div>
            <div class="relative h-64 w-full">
                <canvas id="trendChart"></canvas>
            </div>
        </x-card>
    </div>

    {{-- Stage Failure & Provider hit rates Section --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 mb-6">
        {{-- Stage Failure Distribution --}}
        <x-card>
            <div class="flex items-center justify-between border-b border-border pb-3 mb-4">
                <h2 class="text-sm font-bold tracking-tight text-foreground uppercase">Stage Failure Distribution</h2>
                <span class="text-xs text-muted">Lokasi kegagalan scan</span>
            </div>
            <div class="space-y-4">
                @php
                    $stages = [
                        'identification' => ['name' => 'Stage 1: Identification (Gemini/OCR)', 'color' => 'bg-rose-500'],
                        'lookup' => ['name' => 'Stage 2: Catalog Lookup (Google/OL)', 'color' => 'bg-amber-500'],
                        'enrichment' => ['name' => 'Stage 3: Enrichment & Confidence', 'color' => 'bg-indigo-500'],
                        'fallback' => ['name' => 'Stage 4: Fallback Engine', 'color' => 'bg-violet-500'],
                        'inbox' => ['name' => 'Stage 5: Admin Inbox Save', 'color' => 'bg-emerald-500'],
                    ];
                    $totalFailures = array_sum($stats['failure_distribution']);
                @endphp
                
                @if($totalFailures > 0)
                    @foreach($stages as $stageKey => $stageInfo)
                        @php
                            $failCount = $stats['failure_distribution'][$stageKey] ?? 0;
                            $pct = $totalFailures > 0 ? ($failCount / $totalFailures) * 100 : 0;
                        @endphp
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="font-medium text-foreground">{{ $stageInfo['name'] }}</span>
                                <span class="font-bold text-rose-600 dark:text-rose-400">{{ $failCount }} error ({{ round($pct, 1) }}%)</span>
                            </div>
                            <div class="h-2.5 w-full rounded-full bg-gray-100 dark:bg-slate-800 overflow-hidden">
                                <div class="h-full rounded-full {{ $stageInfo['color'] }}" id="fail-bar-{{ $stageKey }}" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="flex h-48 flex-col items-center justify-center text-center">
                        <span class="text-emerald-500 mb-2">
                            <svg viewBox="0 0 24 24" class="h-12 w-12" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
                        </span>
                        <p class="text-sm font-semibold text-foreground">Tidak ada kegagalan terdeteksi</p>
                        <p class="text-xs text-muted mt-1">Seluruh scan berjalan 100% lancar pada periode ini.</p>
                    </div>
                @endif
            </div>
        </x-card>

        {{-- Provider Hit Rate & Health Link --}}
        <x-card class="flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between border-b border-border pb-3 mb-4">
                    <h2 class="text-sm font-bold tracking-tight text-foreground uppercase">Metadata Source Distribution</h2>
                    <span class="text-xs text-muted">Sumber data terisi</span>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-3">
                        @php
                            $sources = [
                                'cache' => 'Cache Database',
                                'google_books' => 'Google Books Only',
                                'openlibrary' => 'OpenLibrary Only',
                                'google_books+openlibrary' => 'Google + OpenLibrary',
                                'gemini_vision' => 'Gemini Vision (Fallback)',
                                'websearch' => 'Tavily Web Search',
                            ];
                            $totalInbox = array_sum($stats['source_distribution']);
                        @endphp

                        @if($totalInbox > 0)
                            @foreach($sources as $srcKey => $srcLabel)
                                @php
                                    $count = $stats['source_distribution'][$srcKey] ?? 0;
                                    $pct = $totalInbox > 0 ? ($count / $totalInbox) * 100 : 0;
                                @endphp
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-muted">{{ $srcLabel }}</span>
                                    <span class="font-bold text-foreground" id="source-val-{{ $srcKey }}">{{ $count }} ({{ round($pct) }}%)</span>
                                </div>
                            @endforeach
                        @else
                            <p class="text-xs text-muted py-4 text-center">Tidak ada data scan tersimpan.</p>
                        @endif
                    </div>
                    
                    <div class="flex items-center justify-center">
                        <div class="relative h-32 w-32">
                            <canvas id="sourcePieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 border-t border-border pt-4 flex items-center justify-between">
                <div>
                    <h4 class="text-xs font-bold text-foreground">API Provider Health Status</h4>
                    <p class="text-[0.68rem] text-muted">Lihat latency & error rate spesifik tiap provider API.</p>
                </div>
                <a href="{{ route('admin.observability.providers') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-primary-50 px-3 py-2 text-xs font-semibold text-primary-700 transition hover:bg-primary-100 dark:bg-primary-500/10 dark:text-primary-300 dark:hover:bg-primary-500/20">
                    Provider Health
                    <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </a>
            </div>
        </x-card>

        {{-- Most Missing Metadata Card --}}
        <x-card>
            <div class="flex items-center justify-between border-b border-border pb-3 mb-4">
                <h2 class="text-sm font-bold tracking-tight text-foreground uppercase">Most Missing Metadata</h2>
                <span class="text-xs text-muted">Persentase field kosong</span>
            </div>
            <div class="space-y-3" id="most-missing-container">
                @forelse($stats['most_missing'] as $field => $data)
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-medium text-foreground capitalize">{{ $field }}</span>
                            <span class="font-bold text-muted" id="missing-pct-val-{{ $field }}">{{ $data['count'] }} buku ({{ $data['percentage'] }}%)</span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-gray-100 dark:bg-slate-800 overflow-hidden">
                            <div class="h-full rounded-full bg-amber-500" id="missing-bar-{{ $field }}" style="width: {{ $data['percentage'] }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-muted py-4 text-center">Tidak ada data scan tersimpan.</p>
                @endforelse
            </div>
        </x-card>
    </div>

    {{-- Recent Failures --}}
    <x-card>
        <div class="flex items-center justify-between border-b border-border pb-3 mb-4">
            <h2 class="text-sm font-bold tracking-tight text-foreground uppercase">Recent Failed Scans (10 Terakhir)</h2>
            <span class="text-xs text-muted">Membutuhkan penanganan manual</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-xs text-foreground">
                <thead>
                    <tr class="border-b border-border bg-muted/40 font-semibold text-muted">
                        <th class="px-4 py-3">Scan ID</th>
                        <th class="px-4 py-3">Waktu</th>
                        <th class="px-4 py-3">Operator</th>
                        <th class="px-4 py-3">Stage Terakhir</th>
                        <th class="px-4 py-3">Detail Kegagalan</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border" id="recent-failures-tbody">
                    @forelse($stats['recent_failures'] as $fail)
                        <tr class="hover:bg-muted/20 transition-all duration-150">
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-primary-600 dark:text-primary-400">#{{ $fail['id'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-muted">{{ \Carbon\Carbon::parse($fail['created_at'])->setTimezone('Asia/Jakarta')->format('d M Y H:i') }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $fail['operator_name'] }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="inline-flex items-center rounded-md bg-rose-50 px-2 py-1 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/10 dark:bg-rose-500/10 dark:text-rose-400">
                                    {{ ucfirst($fail['current_stage']) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-medium text-rose-600 dark:text-rose-400 max-w-xs truncate" title="{{ $fail['stage_message'] }}">
                                {{ $fail['stage_message'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <form method="POST" action="{{ route('book-scanner.retry', ['job' => $fail['id']]) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1 rounded bg-primary-600 px-2.5 py-1 text-[0.7rem] font-bold text-white shadow transition hover:bg-primary-700">
                                        <svg viewBox="0 0 24 24" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                                        Retry
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-muted">
                                Tidak ada kegagalan scan pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
@endsection

@push('scripts')
    {{-- Load Chart.js from CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Raw stats data injected from Blade
            let statsData = @json($stats);

            // 1. Pipeline Latency Chart
            const latencyCtx = document.getElementById('pipelineLatencyChart').getContext('2d');
            let latencyChart = new Chart(latencyCtx, {
                type: 'bar',
                data: {
                    labels: ['Identification', 'Catalog Lookup', 'Enrichment', 'Fallback', 'Inbox Save'],
                    datasets: [{
                        label: 'Avg Duration (ms)',
                        data: [
                            statsData.avg_latency.identification || 0,
                            statsData.avg_latency.lookup || 0,
                            statsData.avg_latency.enrichment || 0,
                            statsData.avg_latency.fallback || 0,
                            statsData.avg_latency.inbox || 0
                        ],
                        backgroundColor: [
                            'rgba(244, 63, 94, 0.8)',  // Rose
                            'rgba(245, 158, 11, 0.8)', // Amber
                            'rgba(99, 102, 241, 0.8)', // Indigo
                            'rgba(139, 92, 246, 0.8)', // Violet
                            'rgba(16, 185, 129, 0.8)'  // Emerald
                        ],
                        borderColor: [
                            'rgb(244, 63, 94)',
                            'rgb(245, 158, 11)',
                            'rgb(99, 102, 241)',
                            'rgb(139, 92, 246)',
                            'rgb(16, 185, 129)'
                        ],
                        borderWidth: 1,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(156, 163, 175, 0.15)' },
                            ticks: { precision: 0 }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });

            // 2. Trend Chart (Volume & Confidence)
            const trendCtx = document.getElementById('trendChart').getContext('2d');
            let trendChart = new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: statsData.trends.map(t => t.date),
                    datasets: [
                        {
                            label: 'Volume (Buku)',
                            data: statsData.trends.map(t => t.volume),
                            borderColor: 'rgb(99, 102, 241)',
                            backgroundColor: 'rgba(99, 102, 241, 0.05)',
                            fill: true,
                            tension: 0.3,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Avg Confidence (%)',
                            data: statsData.trends.map(t => t.confidence),
                            borderColor: 'rgb(245, 158, 11)',
                            backgroundColor: 'rgba(245, 158, 11, 0.05)',
                            borderDash: [5, 5],
                            tension: 0.3,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top', labels: { boxWidth: 12, font: { size: 10 } } }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            grid: { color: 'rgba(156, 163, 175, 0.15)' },
                            ticks: { precision: 0 },
                            title: { display: true, text: 'Volume Buku', font: { size: 10, weight: 'bold' } }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            min: 0,
                            max: 100,
                            grid: { drawOnChartArea: false },
                            title: { display: true, text: 'Confidence (%)', font: { size: 10, weight: 'bold' } }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });

            // 3. Source Pie Chart
            const pieCtx = document.getElementById('sourcePieChart').getContext('2d');
            let sourcePieChart = new Chart(pieCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Cache', 'Google Books', 'OpenLibrary', 'Google + OL', 'Gemini Vision', 'Web Search'],
                    datasets: [{
                        data: [
                            statsData.source_distribution.cache || 0,
                            statsData.source_distribution.google_books || 0,
                            statsData.source_distribution.openlibrary || 0,
                            statsData.source_distribution['google_books+openlibrary'] || 0,
                            statsData.source_distribution.gemini_vision || 0,
                            statsData.source_distribution.websearch || 0
                        ],
                        backgroundColor: [
                            '#8b5cf6', // purple
                            '#3b82f6', // blue
                            '#06b6d4', // cyan
                            '#14b8a6', // teal
                            '#f43f5e', // rose
                            '#f59e0b'  // amber
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    cutout: '65%'
                }
            });

            // Handle AJAX filters switching
            const filterLinks = document.querySelectorAll('a[data-range]');
            filterLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const range = this.getAttribute('data-range');

                    // Update active button state
                    filterLinks.forEach(l => {
                        l.classList.remove('bg-primary-600', 'text-white', 'shadow-sm');
                        l.classList.add('text-muted', 'hover:text-foreground');
                    });
                    this.classList.remove('text-muted', 'hover:text-foreground');
                    this.classList.add('bg-primary-600', 'text-white', 'shadow-sm');

                    // Fetch statistics via API
                    fetch(`{{ route('admin.observability.stats') }}?range=${range}`)
                        .then(response => response.json())
                        .then(data => {
                            // Update Cards content
                            document.getElementById('metric-success-rate').textContent = `${data.success_rate}%`;
                            document.getElementById('metric-total-scans').textContent = data.total_scans;
                            document.getElementById('metric-latency').textContent = `${((data.avg_latency.total || 0) / 1000).toFixed(2)}s`;
                            document.getElementById('metric-cache-hit-rate').textContent = `${data.cache_hit_rate}%`;
                            document.getElementById('metric-avg-completeness').textContent = `${data.avg_completeness}%`;
                            document.getElementById('metric-lowest-completeness').textContent = `${data.lowest_completeness}%`;
                            document.getElementById('metric-confidence').textContent = `${data.avg_confidence}%`;
                            document.getElementById('metric-queue-status').textContent = 
                                `${data.queue.processing} Proses / ${data.queue.waiting} Antre`;

                            // Update Most Missing Metadata progress bars and labels
                            const container = document.getElementById('most-missing-container');
                            if (container && data.most_missing) {
                                let html = '';
                                for (let field in data.most_missing) {
                                    const item = data.most_missing[field];
                                    html += `
                                        <div>
                                            <div class="flex items-center justify-between text-xs mb-1">
                                                <span class="font-medium text-foreground capitalize">${field}</span>
                                                <span class="font-bold text-muted" id="missing-pct-val-${field}">${item.count} buku (${item.percentage}%)</span>
                                            </div>
                                            <div class="h-2 w-full rounded-full bg-gray-100 dark:bg-slate-800 overflow-hidden">
                                                <div class="h-full rounded-full bg-amber-500" id="missing-bar-${field}" style="width: ${item.percentage}%"></div>
                                            </div>
                                        </div>`;
                                }
                                if (Object.keys(data.most_missing).length === 0) {
                                    html = '<p class="text-xs text-muted py-4 text-center">Tidak ada data scan tersimpan.</p>';
                                }
                                container.innerHTML = html;
                            }

                            // Update Pipeline Latency Chart
                            latencyChart.data.datasets[0].data = [
                                data.avg_latency.identification || 0,
                                data.avg_latency.lookup || 0,
                                data.avg_latency.enrichment || 0,
                                data.avg_latency.fallback || 0,
                                data.avg_latency.inbox || 0
                            ];
                            latencyChart.update();

                            // Update Trend Chart
                            trendChart.data.labels = data.trends.map(t => t.date);
                            trendChart.data.datasets[0].data = data.trends.map(t => t.volume);
                            trendChart.data.datasets[1].data = data.trends.map(t => t.confidence);
                            trendChart.update();

                            // Update Source Pie Chart
                            sourcePieChart.data.datasets[0].data = [
                                data.source_distribution.cache || 0,
                                data.source_distribution.google_books || 0,
                                data.source_distribution.openlibrary || 0,
                                data.source_distribution['google_books+openlibrary'] || 0,
                                data.source_distribution.gemini_vision || 0,
                                data.source_distribution.websearch || 0
                            ];
                            sourcePieChart.update();

                            // Update source distribution text values
                            const sourcesMap = {
                                'cache': 'cache',
                                'google_books': 'google_books',
                                'openlibrary': 'openlibrary',
                                'google_books\\+openlibrary': 'google_books+openlibrary',
                                'gemini_vision': 'gemini_vision',
                                'websearch': 'websearch'
                            };

                            let sumInbox = 0;
                            for (let key in data.source_distribution) {
                                sumInbox += data.source_distribution[key] || 0;
                            }

                            for (let key in data.source_distribution) {
                                const elementId = `source-val-${key}`;
                                const el = document.getElementById(elementId);
                                if (el) {
                                    const count = data.source_distribution[key] || 0;
                                    const pct = sumInbox > 0 ? Math.round((count / sumInbox) * 100) : 0;
                                    el.textContent = `${count} (${pct}%)`;
                                }
                            }

                            // Update Stage Failure bars
                            let sumFail = 0;
                            for (let k in data.failure_distribution) {
                                sumFail += data.failure_distribution[k] || 0;
                            }
                            
                            for (let k in data.failure_distribution) {
                                const failCount = data.failure_distribution[k] || 0;
                                const bar = document.getElementById(`fail-bar-${k}`);
                                if (bar) {
                                    const pct = sumFail > 0 ? (failCount / sumFail) * 100 : 0;
                                    bar.style.width = `${pct}%`;
                                    // Update label text if present
                                    const labelParent = bar.parentElement.previousElementSibling;
                                    if (labelParent && labelParent.lastElementChild) {
                                        labelParent.lastElementChild.textContent = `${failCount} error (${pct.toFixed(1)}%)`;
                                    }
                                }
                            }

                            // Update Recent Failures Table
                            const tbody = document.getElementById('recent-failures-tbody');
                            if (tbody) {
                                if (data.recent_failures.length === 0) {
                                    tbody.innerHTML = `
                                        <tr>
                                            <td colspan="6" class="px-4 py-8 text-center text-muted">
                                                Tidak ada kegagalan scan pada periode ini.
                                            </td>
                                        </tr>`;
                                } else {
                                    let html = '';
                                    data.recent_failures.forEach(fail => {
                                        const date = new Date(fail.created_at);
                                        const formattedDate = date.toLocaleDateString('id-ID', {
                                            day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
                                        });

                                        html += `
                                            <tr class="hover:bg-muted/20 transition-all duration-150">
                                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-primary-600 dark:text-primary-400">#${fail.id}</td>
                                                <td class="whitespace-nowrap px-4 py-3 text-muted">${formattedDate}</td>
                                                <td class="whitespace-nowrap px-4 py-3">${fail.operator_name}</td>
                                                <td class="whitespace-nowrap px-4 py-3">
                                                    <span class="inline-flex items-center rounded-md bg-rose-50 px-2 py-1 text-xs font-medium text-rose-700 ring-1 ring-inset ring-rose-600/10 dark:bg-rose-500/10 dark:text-rose-400">
                                                        ${fail.current_stage.charAt(0).toUpperCase() + fail.current_stage.slice(1)}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 font-medium text-rose-600 dark:text-rose-400 max-w-xs truncate" title="${fail.stage_message}">
                                                    ${fail.stage_message}
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                                    <form method="POST" action="/book-scanner/retry/${fail.id}" class="inline">
                                                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                                        <button type="submit" class="inline-flex items-center gap-1 rounded bg-primary-600 px-2.5 py-1 text-[0.7rem] font-bold text-white shadow transition hover:bg-primary-700">
                                                            <svg viewBox="0 0 24 24" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                                                            Retry
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>`;
                                    });
                                    tbody.innerHTML = html;
                                }
                            }
                        })
                        .catch(err => console.error("Error fetching stats:", err));
                });
            });
        });
    </script>
@endpush
