<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Jobs\ProcessAiBatchScanBook;
use App\Models\Book;
use App\Models\Rack;
use App\Models\User;
use App\Services\AiBatchScanDraftService;
use App\Services\AiBookScanPipelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AiBatchImportFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.n8n.base_url', 'http://127.0.0.1:5678');
        Config::set('services.n8n.api_key', 'n8n-secret');
        Config::set('services.gemini.model', 'gemini-2.5-flash');
        Config::set('services.gemini.vision_model', 'gemini-2.5-flash');
        Config::set('services.websearch.enabled', false);

        Http::fake([
            'http://127.0.0.1:5678/healthz' => Http::response([
                'status' => 'ok',
            ], 200),
        ]);
    }

    public function test_batch_scan_stores_review_draft_and_renders_grouped_result(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN->value]);

        $this->mock(AiBookScanPipelineService::class, function ($mock): void {
            $mock->shouldReceive('scan')
                ->twice()
                ->andReturn(
                    [
                        'title' => 'Atomic Habits',
                        'author' => 'James Clear',
                        'category' => 'Self Improvement',
                        'description' => 'Desc 1',
                        'publisher' => null,
                        'published_year' => null,
                        'isbn' => '9780735211292',
                        'cover_url' => '/storage/book-scans/atomic.jpg',
                        'source_url' => null,
                        'source' => 'google',
                        'field_sources' => [
                            'title' => 'AI Cover',
                            'category' => 'Google Books',
                            'description' => 'Back Cover',
                        ],
                    ],
                    [
                        'title' => 'Deep Work',
                        'author' => 'Cal Newport',
                        'category' => 'Productivity',
                        'description' => 'Desc 2',
                        'publisher' => null,
                        'published_year' => null,
                        'isbn' => '9781455586691',
                        'cover_url' => '/storage/book-scans/deepwork.jpg',
                        'source_url' => null,
                        'source' => 'google',
                        'field_sources' => [
                            'title' => 'AI Cover',
                            'category' => 'Google Books',
                        ],
                    ],
                );
        });

        $response = $this->actingAs($user)->post(route('books.import.ai-batch-scan'), [
            'mode' => 'full',
            'books' => [
                [
                    'front_image' => UploadedFile::fake()->create('front-1.jpg', 10, 'image/jpeg'),
                    'back_image' => UploadedFile::fake()->create('back-1.jpg', 10, 'image/jpeg'),
                ],
                [
                    'front_image' => UploadedFile::fake()->create('front-2.jpg', 10, 'image/jpeg'),
                ],
            ],
        ]);

        $response->assertRedirect(route('books.import'));
        $response->assertSessionHas('bulk_import_ai_scan_draft_token');

        $page = $this->actingAs($user)->get(route('books.import'));

        $page->assertOk();
        $page->assertSee('Atomic Habits');
        $page->assertSee('Deep Work');
        $page->assertSee('Self Improvement');
        $page->assertSee('Productivity');
        $page->assertSee('Judul: AI Cover');
        $page->assertSee('Kategori: Google Books');
    }

    public function test_batch_scan_can_be_dispatched_to_queue_and_status_checked(): void
    {
        Queue::fake();

        $user = User::factory()->create(['role' => UserRole::ADMIN->value]);

        $response = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('books.import.ai-batch-scan'), [
                'mode' => 'full',
                'books' => [
                    [
                        'front_image' => UploadedFile::fake()->create('front-1.jpg', 10, 'image/jpeg'),
                    ],
                    [
                        'front_image' => UploadedFile::fake()->create('front-2.jpg', 10, 'image/jpeg'),
                        'back_image' => UploadedFile::fake()->create('back-2.jpg', 10, 'image/jpeg'),
                    ],
                ],
            ]);

        $response->assertAccepted();
        $response->assertJsonStructure([
            'message',
            'draft_token',
            'status_url',
            'summary' => ['total', 'pending'],
        ]);

        Queue::assertPushed(ProcessAiBatchScanBook::class, 2);

        $draftToken = (string) $response->json('draft_token');
        $draft = app(AiBatchScanDraftService::class)->get($draftToken);

        $this->assertNotNull($draft);
        $this->assertSame('queued', $draft['status']);
        $this->assertSame(2, $draft['summary']['pending']);

        $statusResponse = $this->actingAs($user)
            ->get(route('books.import.ai-batch-status', ['token' => $draftToken]));

        $statusResponse->assertOk();
        $statusResponse->assertJsonPath('summary.total', 2);
        $statusResponse->assertJsonPath('summary.pending', 2);
    }

    public function test_batch_scan_can_be_cancelled(): void
    {
        Queue::fake();

        $user = User::factory()->create(['role' => UserRole::ADMIN->value]);

        $response = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('books.import.ai-batch-scan'), [
                'mode' => 'full',
                'books' => [
                    [
                        'front_image' => UploadedFile::fake()->create('front-1.jpg', 10, 'image/jpeg'),
                    ],
                    [
                        'front_image' => UploadedFile::fake()->create('front-2.jpg', 10, 'image/jpeg'),
                    ],
                ],
            ]);

        $draftToken = (string) $response->json('draft_token');

        $cancelResponse = $this->actingAs($user)
            ->withHeader('Accept', 'application/json')
            ->post(route('books.import.ai-batch-cancel', ['token' => $draftToken]));

        $cancelResponse->assertOk();
        $cancelResponse->assertJsonPath('draft.status', 'cancelled');
        $cancelResponse->assertJsonPath('draft.summary.cancelled', 2);
    }

    public function test_review_commit_creates_books_and_categories_from_scan_draft(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $rack = Rack::factory()->create(['name' => 'Rack AI', 'rows' => 2, 'columns' => 2]);
        $draftToken = 'draft-token-123';

        Cache::put('bulk-import-ai-draft:' . $draftToken, [
            'mode' => 'full',
            'generated_at' => now()->toIso8601String(),
            'books' => [
                [
                    'scan_id' => 'scan-1',
                    'title' => 'Clean Architecture',
                    'author' => 'Robert C. Martin',
                    'category_name' => 'Software Engineering',
                    'description' => 'Architecture guide',
                    'isbn' => '9780134494166',
                    'cover_url' => '/storage/book-scans/clean-architecture.jpg',
                    'source' => 'google',
                    'scan_status' => 'success',
                ],
            ],
        ], now()->addMinutes(30));

        $response = $this->actingAs($user)->post(route('books.import.ai-review-commit'), [
            'draft_token' => $draftToken,
            'books' => [
                [
                    'scan_id' => 'scan-1',
                    'title' => 'Clean Architecture',
                    'author' => 'Robert C. Martin',
                    'category_name' => 'Software Engineering',
                    'description' => 'Architecture guide',
                    'isbn' => '9780134494166',
                    'cover_url' => '/storage/book-scans/clean-architecture.jpg',
                    'rack_id' => $rack->id,
                ],
            ],
        ]);

        $response->assertRedirect(route('books.import'));
        $this->assertDatabaseHas('categories', ['name' => 'Software Engineering']);
        $this->assertDatabaseHas('books', [
            'title' => 'Clean Architecture',
            'author' => 'Robert C. Martin',
            'isbn' => '9780134494166',
        ]);

        $book = Book::query()->where('isbn', '9780134494166')->first();
        $this->assertNotNull($book);
        $this->assertSame($rack->id, $book->rack_id);
    }
}
