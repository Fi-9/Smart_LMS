<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Book;
use App\Models\Rack;
use App\Models\User;
use App\Services\AiBookScanPipelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiBatchImportFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.ollama.base_url', 'http://127.0.0.1:11434');
        Config::set('services.ollama.vision_model', 'gemma4:26b');
        Config::set('services.ollama.text_model', 'gemma4-id:26b');
        Config::set('services.ollama.web_model', 'gemma4-id:26b');
        Config::set('services.websearch.enabled', false);
        Config::set('services.websearch.base_url', '');

        Http::fake([
            'http://127.0.0.1:11434/api/tags' => Http::response([
                'models' => [
                    ['name' => 'gemma4:26b'],
                    ['name' => 'gemma4-id:26b'],
                ],
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
                    'front_image' => UploadedFile::fake()->image('front-1.jpg'),
                    'back_image' => UploadedFile::fake()->image('back-1.jpg'),
                ],
                [
                    'front_image' => UploadedFile::fake()->image('front-2.jpg'),
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
