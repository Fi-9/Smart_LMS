<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Jobs\ProcessBookScanJob;
use App\Models\BookInbox;
use App\Models\BookLookupCache;
use App\Models\ScanJob;
use App\Models\ScanSession;
use App\Models\User;
use App\Services\GeminiService;
use App\Services\IsbnLookupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class PipelineValidationTest extends TestCase
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
            'operator_name' => 'Operator Pipeline Test',
            'started_at' => now(),
            'book_count' => 0,
        ]);
    }

    /**
     * Test A: Buku populer dengan ISBN (Front + Back Cover)
     * Kunci: ISBN terdeteksi dari Back Cover, dicocokkan ke API, menghasilkan confidence tinggi >= 90% (Auto-Approved).
     */
    public function test_pipeline_a_popular_book_front_and_back_cover(): void
    {
        $this->mock(GeminiService::class, function ($mock): void {
            $mock->shouldReceive('extractBookSignals')
                ->once()
                ->andReturn([
                    'best' => [
                        'title' => 'Atomic Habits',
                        'author' => 'James Clear',
                        'isbn' => '9786020626314', // Didapat dari back cover
                        'publisher' => 'Gramedia Pustaka Utama',
                        'category' => 'Self-Help',
                        'description' => 'Sinopsis dari Back Cover buku Atomic Habits.',
                    ],
                ]);
            $mock->shouldReceive('translateToIndonesian')
                ->zeroOrMoreTimes()
                ->andReturn('Sinopsis dari Back Cover buku Atomic Habits.');
        });

        $this->mock(IsbnLookupService::class, function ($mock): void {
            $mock->shouldReceive('lookupGoogleByIsbnOnly')
                ->once()
                ->with('9786020626314')
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
                ->with('9786020626314')
                ->andReturn(null);
        });

        $frontFile = UploadedFile::fake()->create('cover_front.jpg', 150, 'image/jpeg');
        $backFile = UploadedFile::fake()->create('cover_back.jpg', 150, 'image/jpeg');

        $frontPath = $frontFile->storeAs('book-scans', 'front_a.jpg', 'public');
        $backPath = $backFile->storeAs('book-scans', 'back_a.jpg', 'public');

        $scanJob = ScanJob::query()->create([
            'scan_session_id' => $this->session->id,
            'front_cover_path' => $frontPath,
            'back_cover_path' => $backPath,
            'status' => 'waiting',
            'attempts' => 0,
            'queue_number' => 1,
        ]);

        // Run the job handler
        $job = new ProcessBookScanJob($scanJob->id);
        app()->call([$job, 'handle']);

        $scanJob->refresh();
        $this->assertEquals('completed', $scanJob->status);
        $this->assertGreaterThanOrEqual(95, $scanJob->confidence_score);

        $inbox = BookInbox::first();
        $this->assertNotNull($inbox);
        $this->assertEquals('approved', $inbox->status); // Auto-approved
        $this->assertEquals('9786020626314', $inbox->isbn);
        $this->assertEquals('Sinopsis dari Back Cover buku Atomic Habits.', $inbox->description); // Mempertahankan sinopsis back cover
    }

    /**
     * Test B: Buku populer (Front Cover Only)
     * Kunci: Ketiadaan cover belakang membuat ISBN tidak terdeteksi dari gambar.
     * Pencarian Google Books menggunakan title/author. Confidence dibatasi maksimal 90% (Status Pending).
     */
    public function test_pipeline_b_popular_book_front_cover_only(): void
    {
        $this->mock(GeminiService::class, function ($mock): void {
            $mock->shouldReceive('extractBookSignals')
                ->once()
                ->andReturn([
                    'best' => [
                        'title' => 'Atomic Habits',
                        'author' => 'James Clear',
                        'isbn' => null, // Tidak terdeteksi karena tidak ada back cover
                        'publisher' => null,
                        'category' => 'Self-Help',
                        'description' => null,
                    ],
                ]);
        });

        $this->mock(IsbnLookupService::class, function ($mock): void {
            $mock->shouldReceive('searchGoogleByTitleAuthorOnly')
                ->once()
                ->with('Atomic Habits', 'James Clear')
                ->andReturn([
                    'title' => 'Atomic Habits',
                    'author' => 'James Clear',
                    'isbn' => '9786020626314', // API memberikan ISBN
                    'publisher' => 'Gramedia Pustaka Utama',
                    'published_year' => 2019,
                    'description' => 'Buku terlaris tentang membangun kebiasaan baik.',
                    'category' => 'Self-Help',
                    'cover_url' => 'https://books.google.com/cover.jpg',
                    'source' => 'google',
                    'source_url' => 'https://books.google.com/info',
                ]);

            $mock->shouldReceive('searchOpenLibraryByTitleAuthorOnly')
                ->once()
                ->andReturn(null);
        });

        $frontFile = UploadedFile::fake()->create('cover_front.jpg', 150, 'image/jpeg');
        $frontPath = $frontFile->storeAs('book-scans', 'front_b.jpg', 'public');

        $scanJob = ScanJob::query()->create([
            'scan_session_id' => $this->session->id,
            'front_cover_path' => $frontPath,
            'back_cover_path' => null,
            'status' => 'waiting',
            'attempts' => 0,
            'queue_number' => 1,
        ]);

        // Run the job handler
        $job = new ProcessBookScanJob($scanJob->id);
        app()->call([$job, 'handle']);

        $scanJob->refresh();
        $this->assertEquals('completed', $scanJob->status);
        
        // Confidence tidak boleh >= 95 karena ISBN tidak ditemukan di visual cover (hanya dicocokkan via title/author search)
        // Jadi harus pending
        $this->assertLessThan(95, $scanJob->confidence_score);

        $inbox = BookInbox::first();
        $this->assertNotNull($inbox);
        $this->assertEquals('pending', $inbox->status); // Pending review
        $this->assertEquals('9786020626314', $inbox->isbn);
        $this->assertEquals('Buku terlaris tentang membangun kebiasaan baik.', $inbox->description); // Menggunakan provider description karena vision null
    }

    /**
     * Test C: Buku lokal Indonesia (Front + Back Cover)
     * Kunci: Memverifikasi deskripsi bahasa Indonesia dipertahankan/diterjemahkan secara benar.
     */
    public function test_pipeline_c_local_indonesian_book_indonesian_description(): void
    {
        $this->mock(GeminiService::class, function ($mock): void {
            $mock->shouldReceive('extractBookSignals')
                ->once()
                ->andReturn([
                    'best' => [
                        'title' => 'Laskar Pelangi',
                        'author' => 'Andrea Hirata',
                        'isbn' => '9789791227204',
                        'publisher' => 'Bentang Pustaka',
                        'category' => 'Fiksi',
                        'description' => 'Kisah perjuangan sepuluh anak di Belitung.',
                    ],
                ]);
            $mock->shouldReceive('translateToIndonesian')
                ->zeroOrMoreTimes()
                ->andReturn('Kisah perjuangan sepuluh anak di Belitung.');
        });

        $this->mock(IsbnLookupService::class, function ($mock): void {
            $mock->shouldReceive('lookupGoogleByIsbnOnly')
                ->once()
                ->andReturn([
                    'title' => 'Laskar Pelangi',
                    'author' => 'Andrea Hirata',
                    'isbn' => '9789791227204',
                    'publisher' => 'Bentang Pustaka',
                    'published_year' => 2005,
                    'description' => 'The rainbow troops is a story about...', // Deskripsi bahasa inggris di Google
                    'category' => 'Fiction',
                    'cover_url' => 'https://books.google.com/laskar.jpg',
                    'source' => 'google',
                    'source_url' => 'https://books.google.com/info',
                ]);

            $mock->shouldReceive('lookupOpenLibraryByIsbnOnly')
                ->once()
                ->andReturn(null);
        });

        $frontFile = UploadedFile::fake()->create('cover_front.jpg', 150, 'image/jpeg');
        $backFile = UploadedFile::fake()->create('cover_back.jpg', 150, 'image/jpeg');

        $frontPath = $frontFile->storeAs('book-scans', 'front_c.jpg', 'public');
        $backPath = $backFile->storeAs('book-scans', 'back_c.jpg', 'public');

        $scanJob = ScanJob::query()->create([
            'scan_session_id' => $this->session->id,
            'front_cover_path' => $frontPath,
            'back_cover_path' => $backPath,
            'status' => 'waiting',
            'attempts' => 0,
            'queue_number' => 1,
        ]);

        // Run the job handler
        $job = new ProcessBookScanJob($scanJob->id);
        app()->call([$job, 'handle']);

        $scanJob->refresh();
        $this->assertEquals('completed', $scanJob->status);

        $inbox = BookInbox::first();
        $this->assertNotNull($inbox);
        
        // Deskripsi harus dalam Bahasa Indonesia (berasal dari Back Cover)
        $this->assertEquals('Kisah perjuangan sepuluh anak di Belitung.', $inbox->description);
    }

    /**
     * Test D: Buku lama tanpa ISBN (Front + Back Cover)
     * Kunci: ISBN harus bernilai NULL (tidak menghasilkan cache_xxxx palsu) dan status pending.
     */
    public function test_pipeline_d_vintage_book_no_isbn(): void
    {
        $this->mock(GeminiService::class, function ($mock): void {
            $mock->shouldReceive('extractBookSignals')
                ->once()
                ->andReturn([
                    'best' => [
                        'title' => 'Buku Tua Tanpa ISBN',
                        'author' => 'Pujangga Lama',
                        'isbn' => null, // Tidak ada ISBN
                        'publisher' => 'Balai Pustaka',
                        'category' => 'Sastra',
                        'description' => 'Karya sastra klasik nusantara.',
                    ],
                ]);
            $mock->shouldReceive('translateToIndonesian')
                ->zeroOrMoreTimes()
                ->andReturn('Karya sastra klasik nusantara.');
        });

        $this->mock(IsbnLookupService::class, function ($mock): void {
            // lookupGoogleByTitleAuthor returns null
            $mock->shouldReceive('searchGoogleByTitleAuthorOnly')
                ->once()
                ->with('Buku Tua Tanpa ISBN', 'Pujangga Lama')
                ->andReturn(null);

            $mock->shouldReceive('searchOpenLibraryByTitleAuthorOnly')
                ->once()
                ->andReturn(null);
        });

        $frontFile = UploadedFile::fake()->create('cover_front.jpg', 150, 'image/jpeg');
        $backFile = UploadedFile::fake()->create('cover_back.jpg', 150, 'image/jpeg');

        $frontPath = $frontFile->storeAs('book-scans', 'front_d.jpg', 'public');
        $backPath = $backFile->storeAs('book-scans', 'back_d.jpg', 'public');

        $scanJob = ScanJob::query()->create([
            'scan_session_id' => $this->session->id,
            'front_cover_path' => $frontPath,
            'back_cover_path' => $backPath,
            'status' => 'waiting',
            'attempts' => 0,
            'queue_number' => 1,
        ]);

        // Run the job handler
        $job = new ProcessBookScanJob($scanJob->id);
        app()->call([$job, 'handle']);

        $scanJob->refresh();
        $this->assertEquals('completed', $scanJob->status);

        $inbox = BookInbox::first();
        $this->assertNotNull($inbox);
        $this->assertEquals('pending', $inbox->status); // Harus pending (karena confidence gemini only = 60)
        
        // Kunci perbaikan: ISBN harus benar-benar null, tidak boleh cache_xxxx
        $this->assertNull($inbox->isbn);

        // Pastikan cache yang terbuat juga menyimpan ISBN null
        $cache = BookLookupCache::first();
        $this->assertNotNull($cache);
        $this->assertNull($cache->isbn);
    }
}
