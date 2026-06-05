<?php

namespace App\Jobs;

use App\Models\BookInbox;
use App\Models\ScanPipelineLog;
use App\Services\WebBookDescriptionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class EnrichBookWithTavilyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 180;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly int $bookInboxId
    ) {
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(WebBookDescriptionService $webSearchService): void
    {
        $inbox = BookInbox::find($this->bookInboxId);
        if (!$inbox) {
            return;
        }

        // Only search if description is missing or extremely short (e.g. less than 30 chars)
        $description = trim($inbox->description ?? '');
        if (strlen($description) > 30) {
            Log::channel('ai_scan')->info("Skipping Tavily enrichment: description already present and sufficient length", ['book_inbox_id' => $inbox->id]);
            return;
        }

        Log::channel('ai_scan')->info("Running EnrichBookWithTavilyJob for book inbox", [
            'book_inbox_id' => $inbox->id,
            'title' => $inbox->title,
        ]);

        $start = microtime(true);
        try {
            $webData = null;
            if ($inbox->isbn) {
                $webData = $webSearchService->resolveByIsbn($inbox->isbn);
            }

            if (!$webData && $inbox->title) {
                $webData = $webSearchService->resolve($inbox->title, $inbox->author);
            }

            $duration = (int) round((microtime(true) - $start) * 1000);

            if ($webData && !empty($webData['description'])) {
                $newDescription = $webData['description'];
                
                // Update inbox with new description and source
                $inbox->update([
                    'description' => $newDescription,
                    'source_url' => $webData['source_url'] ?? $inbox->source_url,
                    'processing_notes' => ($inbox->processing_notes ? $inbox->processing_notes . "\n" : "") . "Enriched with Tavily web search description.",
                    'source_chain' => array_merge(is_array($inbox->source_chain) ? $inbox->source_chain : [], [
                        'tavily_enriched' => true,
                        'tavily_data' => $webData,
                    ]),
                ]);

                ScanPipelineLog::query()->create([
                    'scan_id' => null,
                    'provider' => 'Tavily',
                    'duration_ms' => $duration,
                    'status' => 'success',
                ]);

                Log::channel('ai_scan')->info("Tavily enrichment successful for book inbox", ['book_inbox_id' => $inbox->id]);
            } else {
                ScanPipelineLog::query()->create([
                    'scan_id' => null,
                    'provider' => 'Tavily',
                    'duration_ms' => $duration,
                    'status' => 'failed',
                    'error' => 'No description found on the web',
                ]);

                Log::channel('ai_scan')->info("Tavily enrichment: no description found on the web", ['book_inbox_id' => $inbox->id]);
            }
        } catch (Throwable $e) {
            $duration = (int) round((microtime(true) - $start) * 1000);
            ScanPipelineLog::query()->create([
                'scan_id' => null,
                'provider' => 'Tavily',
                'duration_ms' => $duration,
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            Log::channel('ai_scan')->error("Tavily enrichment job failed: " . $e->getMessage(), [
                'book_inbox_id' => $inbox->id,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
