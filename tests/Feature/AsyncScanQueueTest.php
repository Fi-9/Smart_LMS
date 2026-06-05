<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Jobs\ProcessBookScanJob;
use App\Models\BookInbox;
use App\Models\BookLookupCache;
use App\Models\ScanJob;
use App\Models\ScanPipelineLog;
use App\Models\ScanSession;
use App\Models\User;
use App\Services\GeminiService;
use App\Services\IsbnLookupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class AsyncScanQueueTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ScanSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Queue::fake();

        // Create admin operator
        $this->user = User::factory()->create(['role' => UserRole::ADMIN->value]);

        // Start scanning session
        $this->session = ScanSession::query()->create([
            'user_id' => $this->user->id,
            'operator_name' => 'Operator Test',
            'started_at' => now(),
            'book_count' => 0,
        ]);
    }

    public function test_enqueue_endpoint_returns_202_and_dispatches_job(): void
    {
        $frontFile = UploadedFile::fake()->create('cover_front.jpg', 150, 'image/jpeg');

        $response = $this->actingAs($this->user)->postJson('/book-scanner/enqueue', [
            'front' => $frontFile,
        ]);

        $response->assertStatus(202);
        $response->assertJsonPath('queued', true);
        $response->assertJsonStructure(['found', 'queued', 'scan_job_id', 'queue_number', 'message']);

        // Assert job was pushed to queue
        Queue::assertPushed(ProcessBookScanJob::class);

        // Verify ScanJob record was created
        $scanJob = ScanJob::first();
        $this->assertNotNull($scanJob);
        $this->assertEquals('waiting', $scanJob->status);
        $this->assertEquals(1, $response->json('queue_number'));
    }

    public function test_enqueue_endpoint_detects_cover_duplicates(): void
    {
        $frontFile = UploadedFile::fake()->create('cover_front.jpg', 150, 'image/jpeg');

        // Enqueue first time
        $response1 = $this->actingAs($this->user)->postJson('/book-scanner/enqueue', [
            'front' => $frontFile,
        ]);
        $response1->assertStatus(202);

        // Enqueue second time with same image (should hash to same value)
        $response2 = $this->actingAs($this->user)->postJson('/book-scanner/enqueue', [
            'front' => $frontFile,
        ]);

        $response2->assertStatus(200);
        $response2->assertJsonPath('warning', 'duplicate_detected');
        $response2->assertJsonPath('message', 'Buku ini kemungkinan sudah pernah dipindai.');

        // Enqueue with force = true
        $response3 = $this->actingAs($this->user)->postJson('/book-scanner/enqueue', [
            'front' => $frontFile,
            'force' => 'true',
        ]);
        $response3->assertStatus(202);
    }

    public function test_process_scan_job_executes_pipeline_and_auto_approves(): void
    {
        // Mock Gemini and Catalog lookups
        $this->mock(GeminiService::class, function ($mock): void {
            $mock->shouldReceive('extractBookSignals')
                ->once()
                ->andReturn([
                    'best' => [
                        'title' => 'Atomic Habits',
                        'author' => 'James Clear',
                        'isbn' => '9786020626314',
                        'publisher' => 'Gramedia Pustaka Utama',
                        'category' => 'Bisnis',
                    ],
                ]);
            $mock->shouldReceive('translateToIndonesian')
                ->zeroOrMoreTimes()
                ->andReturn('Mengubah Kebiasaan Buruk');
        });

        $this->mock(IsbnLookupService::class, function ($mock): void {
            // Google Books matches exactly
            $mock->shouldReceive('lookupGoogleByIsbnOnly')
                ->once()
                ->andReturn([
                    'title' => 'Atomic Habits',
                    'author' => 'James Clear',
                    'isbn' => '9786020626314',
                    'publisher' => 'Gramedia Pustaka Utama',
                    'published_year' => 2019,
                    'description' => 'Buku pengembangan diri terlaris...',
                    'category' => 'Bisnis',
                    'cover_url' => 'https://books.google.com/cover.jpg',
                    'source' => 'google',
                    'source_url' => 'https://books.google.com/info',
                ]);
            
            // OpenLibrary matches exactly too
            $mock->shouldReceive('lookupOpenLibraryByIsbnOnly')
                ->once()
                ->andReturn([
                    'title' => 'Atomic Habits',
                    'author' => 'James Clear',
                    'isbn' => '9786020626314',
                    'publisher' => 'Gramedia Pustaka Utama',
                    'published_year' => 2019,
                    'description' => 'Buku pengembangan diri terlaris...',
                    'category' => 'Bisnis',
                    'cover_url' => 'https://openlibrary.org/cover.jpg',
                    'source' => 'openlibrary',
                    'source_url' => 'https://openlibrary.org/info',
                ]);
        });

        $frontFile = UploadedFile::fake()->create('cover_front.jpg', 150, 'image/jpeg');
        $storedPath = $frontFile->storeAs('book-scans', 'front_test.jpg', 'public');

        $scanJob = ScanJob::query()->create([
            'scan_session_id' => $this->session->id,
            'front_cover_path' => $storedPath,
            'status' => 'waiting',
            'attempts' => 0,
            'queue_number' => 1,
        ]);

        // Run the job handler directly
        $job = new ProcessBookScanJob($scanJob->id);
        app()->call([$job, 'handle']);

        // Assert job completed successfully
        $scanJob->refresh();
        $this->assertEquals('completed', $scanJob->status);
        $this->assertNotNull($scanJob->finished_at);
        $this->assertGreaterThanOrEqual(90, $scanJob->confidence_score); // should be 100

        // Assert log was created in scan_pipeline_logs
        $logs = ScanPipelineLog::all();
        $this->assertNotEmpty($logs);
        $this->assertTrue($logs->contains('provider', 'Gemini'));
        $this->assertTrue($logs->contains('provider', 'GoogleBooks'));
        $this->assertTrue($logs->contains('provider', 'OpenLibrary'));

        // Assert book inbox has auto-approved record (score >= 90)
        $inbox = BookInbox::first();
        $this->assertNotNull($inbox);
        $this->assertEquals('approved', $inbox->status);
        $this->assertEquals('Atomic Habits', $inbox->title);
        $this->assertEquals(100, $inbox->confidence_score);

        // Assert book lookup cache was populated
        $cached = BookLookupCache::first();
        $this->assertNotNull($cached);
        $this->assertEquals('9786020626314', $cached->isbn);
    }

    public function test_subsequent_scan_hits_metadata_cache_instantly(): void
    {
        // Populate cache
        BookLookupCache::query()->create([
            'isbn' => '9786020626314',
            'title_author_hash' => sha1('atomic habits|james clear'),
            'title' => 'Atomic Habits',
            'author' => 'James Clear',
            'publisher' => 'Gramedia Pustaka Utama',
            'published_year' => 2019,
            'description' => 'Buku terlaris tentang kebiasaan.',
            'category' => 'Bisnis',
            'cover_url' => 'https://books.google.com/cover.jpg',
            'language' => 'id',
        ]);

        // Mock Gemini to return Atomic Habits cover signals
        $this->mock(GeminiService::class, function ($mock): void {
            $mock->shouldReceive('extractBookSignals')
                ->once()
                ->andReturn([
                    'best' => [
                        'title' => 'Atomic Habits',
                        'author' => 'James Clear',
                        'isbn' => '9786020626314',
                    ],
                ]);
        });

        // We do not mock IsbnLookupService, which means if it's hit, PHPUnit will throw Mockery exception!
        // This ensures the external providers are completely bypassed on cache hits.

        $frontFile = UploadedFile::fake()->create('cover_front.jpg', 150, 'image/jpeg');
        $storedPath = $frontFile->storeAs('book-scans', 'front_test.jpg', 'public');

        $scanJob = ScanJob::query()->create([
            'scan_session_id' => $this->session->id,
            'front_cover_path' => $storedPath,
            'status' => 'waiting',
            'attempts' => 0,
            'queue_number' => 1,
        ]);

        $job = new ProcessBookScanJob($scanJob->id);
        app()->call([$job, 'handle']);

        // Assert job completed via cache (should have confidence_score = 100)
        $scanJob->refresh();
        $this->assertEquals('completed', $scanJob->status);
        $this->assertEquals(100, $scanJob->confidence_score);

        // Assert book inbox has auto-approved record
        $inbox = BookInbox::latest('id')->first();
        $this->assertEquals('approved', $inbox->status);
        $this->assertEquals('Atomic Habits', $inbox->title);
    }

    public function test_retry_endpoint_resets_failed_job_and_dispatches_again(): void
    {
        $scanJob = ScanJob::query()->create([
            'scan_session_id' => $this->session->id,
            'front_cover_path' => 'book-scans/front_test.jpg',
            'status' => 'failed',
            'error_message' => 'Something failed',
            'attempts' => 1,
            'queue_number' => 1,
        ]);

        $response = $this->actingAs($this->user)->postJson("/book-scanner/retry/{$scanJob->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('message', 'Pekerjaan berhasil dikirim ulang ke antrean.');

        $scanJob->refresh();
        $this->assertEquals('waiting', $scanJob->status);
        $this->assertEquals(0, $scanJob->attempts);
        $this->assertNull($scanJob->error_message);

        Queue::assertPushed(ProcessBookScanJob::class);
    }

    public function test_save_inbox_with_existing_inbox_id_updates_record_instead_of_duplicating(): void
    {
        $inbox = BookInbox::query()->create([
            'scan_session_id' => $this->session->id,
            'scanned_by' => $this->user->id,
            'title' => 'Original Title',
            'author' => 'Original Author',
            'isbn' => '1234567890',
            'publisher' => 'Original Publisher',
            'published_year' => 2020,
            'description' => 'Original Description',
            'category' => 'Technology',
            'confidence' => 0.8,
            'status' => 'pending',
        ]);

        $this->session->update(['book_count' => 1]);

        $response = $this->actingAs($this->user)->postJson('/book-scanner/save-inbox', [
            'inbox_id' => $inbox->id,
            'title' => 'Updated Title',
            'author' => 'Updated Author',
            'isbn' => '0987654321',
            'publisher' => 'Updated Publisher',
            'published_year' => 2021,
            'description' => 'Updated Description',
            'category' => 'Science',
        ]);

        $response->assertOk();
        $response->assertJsonPath('saved', true);

        // Verify it updated the existing record
        $inbox->refresh();
        $this->assertEquals('Updated Title', $inbox->title);
        $this->assertEquals('Updated Author', $inbox->author);
        $this->assertEquals('0987654321', $inbox->isbn);
        $this->assertEquals(2021, $inbox->published_year);

        // Ensure total count in DB remains 1
        $this->assertEquals(1, BookInbox::count());

        // Ensure session book_count did not increment
        $this->session->refresh();
        $this->assertEquals(1, $this->session->book_count);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
