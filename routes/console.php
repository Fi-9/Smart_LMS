<?php

use App\Services\AiInfrastructureService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ai:status', function () {
    /** @var AiInfrastructureService $service */
    $service = app(AiInfrastructureService::class);

    $summary = $service->runtimeSummary();
    $this->info('AI Runtime Summary');
    $this->table(
        ['Capability', 'Provider', 'Model', 'Status', 'Notes'],
        [
            ['Vision', $summary['vision']['provider'], $summary['vision']['model'] ?? '-', $summary['vision']['status_label'], $summary['vision']['note']],
            ['Text', $summary['text']['provider'], $summary['text']['model'] ?? '-', $summary['text']['status_label'], $summary['text']['note']],
            ['Websearch', $summary['websearch']['provider'], $summary['websearch']['model'] ?? '-', $summary['websearch']['status_label'], $summary['websearch']['note']],
        ]
    );

    $this->newLine();
    $this->info('Connectivity Checks');
    $this->table(
        ['Service', 'State', 'Endpoint', 'Details'],
        collect($service->diagnostics())
            ->map(fn (array $row): array => [
                $row['service'] ?? '-',
                strtoupper((string) ($row['status'] ?? 'unknown')),
                $row['endpoint'] ?? '-',
                $row['detail'] ?? '-',
            ])
            ->values()
            ->all()
    );

    $this->newLine();
    $this->line('Recommended batch scan mode: ' . strtoupper($summary['recommended_scan_mode']));
})->purpose('Show current AI runtime configuration and connectivity status');
