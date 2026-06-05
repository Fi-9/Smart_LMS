<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Jobs\ProcessBookScanJob;
use App\Jobs\EnrichBookWithTavilyJob;
use App\Models\BookInbox;
use App\Models\ScanJob;
use App\Models\ScanSession;
use App\Models\User;
use App\Services\GeminiService;
use App\Services\IsbnLookupService;
use App\Services\WebBookDescriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class StagedPipelineTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ScanSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Queue::fake();

        $this->user = User::factory()->create(['role' => UserRole::ADMIN->value]);

        $this->session = ScanSession::query()->create([
            'user_id' => $this->user->id,
            'operator_name' => 'Operator Pipeline Test',
            'started_at' => now(),
            'book_count' => 0,
        ]);
    }

    /**
     * Test a successful pipeline execution.
     */
    public function test_staged_pipeline_success(): void
    {
        $this->mock(GeminiService::class, function ($mock): void {
            $mock->shouldReceive('extractBookSignals')
                ->once()
                ->andReturn([
                    'best' => [
                        'title' => 'Atomic Habits',
                        'author' => 'James Clear',
                        'isbn' => '9786020626314',
                        'publisher' => 'Gramedia Pustaka Utama',
                        'category' => 'Self-Help',
                        'description' => 'Atomic Habits description',
                    ],
                ]);
        });

        $this->mock(IsbnLookupService::class, function ($mock): void {
            $mock->shouldReceive('lookupGoogleByIsbnOnly')
                ->once()
                ->andReturn([
                    'title' => 'Atomic Habits',
                    'author' => 'James Clear',
                    'isbn' => '9786020626314',
                    'publisher' => 'Gramedia Pustaka Utama',
                    'published_year' => 2019,
                    'description' => 'Buku terlaris tentang membangun kebiasaan baik.',
                    'category' => 'Self-Help',
                    'cover_url' => 'https://books.google.com/cover.jpg',
                    'source' => 'google',
                    'source_url' => 'https://books.google.com/info',
                ]);

            $mock->shouldReceive('lookupOpenLibraryByIsbnOnly')
                ->once()
                ->andReturn(null);
        });

        $frontFile = UploadedFile::fake()->create('cover_front.jpg', 150, 'image/jpeg');
        $frontPath = $frontFile->storeAs('book-scans', 'front.jpg', 'public');

        $scanJob = ScanJob::query()->create([
            'scan_session_id' => $this->session->id,
            'front_cover_path' => $frontPath,
            'status' => 'waiting',
            'attempts' => 0,
            'queue_number' => 1,
        ]);

        // Run the job handler
        $job = new ProcessBookScanJob($scanJob->id);
        app()->call([$job, 'handle']);

        $scanJob->refresh();
        $this->assertEquals('completed', $scanJob->status);
        $this->assertEquals('completed', $scanJob->current_stage);
        $this->assertEquals('completed', $scanJob->stage_status);
        $this->assertNotEmpty($scanJob->pipeline_metrics);
        $this->assertArrayHasKey('identification', $scanJob->pipeline_metrics);
        $this->assertArrayHasKey('lookup', $scanJob->pipeline_metrics);
        $this->assertArrayHasKey('enrichment', $scanJob->pipeline_metrics);
        $this->assertArrayHasKey('fallback', $scanJob->pipeline_metrics);
        $this->assertArrayHasKey('inbox', $scanJob->pipeline_metrics);

        // Verify Tavily job was dispatched post-inbox creation
        Queue::assertPushed(EnrichBookWithTavilyJob::class);
    }

    /**
     * Test Tavily asynchronous enrichment job execution.
     */
    public function test_tavily_enrichment_job(): void
    {
        $inbox = BookInbox::query()->create([
            'scan_session_id' => $this->session->id,
            'scanned_by' => $this->user->id,
            'title' => 'Tavily Book',
            'author' => 'Tavily Author',
            'isbn' => '1234567890',
            'status' => 'pending',
            'description' => null, // empty to trigger enrichment
        ]);

        $this->mock(WebBookDescriptionService::class, function ($mock): void {
            $mock->shouldReceive('resolveByIsbn')
                ->once()
                ->with('1234567890')
                ->andReturn([
                    'title' => 'Tavily Book',
                    'author' => 'Tavily Author',
                    'description' => 'Highly enriched description from Tavily web search.',
                    'source_url' => 'https://tavily.com/results',
                ]);
        });

        $job = new EnrichBookWithTavilyJob($inbox->id);
        app()->call([$job, 'handle']);

        $inbox->refresh();
        $this->assertEquals('Highly enriched description from Tavily web search.', $inbox->description);
        $this->assertEquals('https://tavily.com/results', $inbox->source_url);
        $this->assertStringContainsString('Enriched with Tavily web search description.', $inbox->processing_notes);
        $this->assertArrayHasKey('tavily_enriched', $inbox->source_chain);
        $this->assertTrue($inbox->source_chain['tavily_enriched']);
    }
}
