@extends('layouts.app')

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('admin.observability.index') }}" class="text-xs font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 flex items-center gap-1">
                    <svg viewBox="0 0 24 24" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                    Kembali ke Observability
                </a>
            </div>
            <h1 class="page-title text-2xl font-bold tracking-tight text-foreground">API Provider Health Status</h1>
            <p class="page-subtitle text-sm text-muted">Pemantauan real-time untuk status koneksi, latency, dan success rate dari API eksternal.</p>
        </div>
    </div>

    {{-- Provider Cards Grid --}}
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4 mb-6">
        @foreach($providerStats as $providerName => $stat)
            @php
                // Status calculation
                if ($stat['total'] === 0) {
                    $statusText = 'No Data';
                    $statusColor = 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300';
                    $dotColor = 'bg-gray-400';
                } elseif ($stat['success_rate'] >= 90.0) {
                    $statusText = 'Healthy';
                    $statusColor = 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400';
                    $dotColor = 'bg-emerald-500';
                } elseif ($stat['success_rate'] >= 60.0) {
                    $statusText = 'Degraded';
                    $statusColor = 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400';
                    $dotColor = 'bg-amber-500';
                } else {
                    $statusText = 'Critical';
                    $statusColor = 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400';
                    $dotColor = 'bg-rose-500';
                }
            @endphp

            <x-card class="relative overflow-hidden transition-all duration-300 hover:shadow-md">
                <div class="flex items-center justify-between border-b border-border pb-3 mb-4">
                    <div>
                        <h2 class="text-base font-bold text-foreground">{{ $providerName }}</h2>
                        <p class="text-[0.68rem] text-muted">
                            @if($providerName === 'Gemini')
                                AI Image Recognition
                            @elseif($providerName === 'GoogleBooks')
                                Google Books API
                            @elseif($providerName === 'OpenLibrary')
                                Open Library API
                            @elseif($providerName === 'Tavily')
                                Tavily Web Search API
                            @endif
                        </p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusColor }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $dotColor }}"></span>
                        {{ $statusText }}
                    </span>
                </div>

                <div class="space-y-4">
                    {{-- Success rate & Latency --}}
                    <div class="grid grid-cols-2 gap-2 text-center bg-muted/30 p-2.5 rounded-lg">
                        <div>
                            <p class="text-[0.65rem] uppercase tracking-wider text-muted font-medium">Success Rate</p>
                            <p class="text-lg font-extrabold text-foreground mt-0.5">{{ $stat['success_rate'] }}%</p>
                        </div>
                        <div>
                            <p class="text-[0.65rem] uppercase tracking-wider text-muted font-medium">Avg Latency</p>
                            <p class="text-lg font-extrabold text-foreground mt-0.5">
                                {{ $stat['avg_latency'] >= 1000 ? number_format($stat['avg_latency'] / 1000, 2) . 's' : $stat['avg_latency'] . 'ms' }}
                            </p>
                        </div>
                    </div>

                    {{-- Request counters --}}
                    <div class="space-y-1.5 text-xs">
                        <div class="flex justify-between">
                            <span class="text-muted">Total Requests:</span>
                            <span class="font-bold text-foreground">{{ $stat['total'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted">Successful calls:</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ $stat['success'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted">Failed calls:</span>
                            <span class="font-bold text-rose-600 dark:text-rose-400">{{ $stat['failed'] }}</span>
                        </div>
                    </div>

                    {{-- Timestamps --}}
                    <div class="border-t border-border pt-3 space-y-2 text-[0.7rem] text-muted">
                        <div>
                            <p class="font-semibold text-foreground/80 mb-0.5">Last Successful Call:</p>
                            @if($stat['last_success'])
                                <p>{{ \Carbon\Carbon::parse($stat['last_success'])->setTimezone('Asia/Jakarta')->format('d M Y H:i:s') }}</p>
                            @else
                                <p class="italic text-muted">Belum pernah sukses</p>
                            @endif
                        </div>
                        <div>
                            <p class="font-semibold text-foreground/80 mb-0.5">Last Failed Call:</p>
                            @if($stat['last_failure'])
                                <p class="text-rose-600 dark:text-rose-400 font-medium">
                                    {{ \Carbon\Carbon::parse($stat['last_failure'])->setTimezone('Asia/Jakarta')->format('d M Y H:i:s') }}
                                </p>
                            @else
                                <p class="text-emerald-600 dark:text-emerald-400 font-medium flex items-center gap-1">
                                    <svg viewBox="0 0 24 24" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                    Tidak ada error tercatat
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </x-card>
        @endforeach
    </div>

    {{-- Alert info --}}
    <div class="rounded-xl border border-blue-200 bg-blue-50/50 p-4 dark:border-blue-900/30 dark:bg-blue-950/10">
        <div class="flex items-start gap-3">
            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                <svg viewBox="0 0 24 24" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            </span>
            <div>
                <h3 class="text-xs font-bold text-blue-900 dark:text-blue-300">Bagaimana status ditentukan?</h3>
                <p class="mt-1 text-xs text-blue-800 dark:text-blue-400 leading-relaxed">
                    Status <strong>Healthy</strong> diberikan jika Success Rate berada di atas 90%. Status <strong>Degraded</strong> menunjukkan Success Rate antara 60% sampai 90%, mengindikasikan adanya beberapa limitasi kuota atau gangguan jaringan intermiten. Status <strong>Critical</strong> menunjukkan kegagalan beruntun atau kehabisan kuota API.
                </p>
            </div>
        </div>
    </div>
@endsection
