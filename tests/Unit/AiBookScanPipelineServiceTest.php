<?php

namespace Tests\Unit;

use App\Services\AiBookScanPipelineService;
use App\Services\CoverImageService;
use App\Services\IsbnLookupService;
use App\Services\OllamaService;
use App\Services\WebBookDescriptionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class AiBookScanPipelineServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_back_cover_description_is_kept_over_provider_and_web_description(): void
    {
        Storage::fake('public');

        $ollama = Mockery::mock(OllamaService::class);
        $isbnLookup = Mockery::mock(IsbnLookupService::class);
        $webDescription = Mockery::mock(WebBookDescriptionService::class);
        $coverService = Mockery::mock(CoverImageService::class);

        $ollama->shouldReceive('extractBookSignals')
            ->once()
            ->andReturn([
                'images' => [
                    ['index' => 0, 'view' => 'front', 'cover_box' => null],
                    ['index' => 1, 'view' => 'back', 'cover_box' => null],
                ],
                'best' => [
                    'isbn' => null,
                    'title' => 'Laskar Pelangi',
                    'author' => 'Andrea Hirata',
                    'category' => 'Novel',
                    'description' => 'Sinopsis dari back cover.',
                    'front_image_index' => 0,
                ],
            ]);

        $isbnLookup->shouldReceive('searchGoogleByTitleAuthorOnly')
            ->once()
            ->with('Laskar Pelangi', 'Andrea Hirata')
            ->andReturn([
                'title' => 'Laskar Pelangi',
                'author' => 'Andrea Hirata',
                'category' => 'Novel Indonesia',
                'description' => 'Deskripsi dari Google Books.',
                'publisher' => 'Bentang',
                'published_year' => '2005',
                'isbn' => '9789791227204',
                'cover_url' => 'https://example.com/google.jpg',
                'source' => 'google',
                'source_url' => 'https://books.google.com/example',
            ]);

        $isbnLookup->shouldReceive('lookupOpenLibraryByIsbn')->never();
        $isbnLookup->shouldReceive('lookupOpenLibraryByTitleAuthor')->never();
        $webDescription->shouldReceive('resolveForDomains')->never();
        $webDescription->shouldReceive('resolve')->never();
        $coverService->shouldReceive('cropFrontCover')->once()->andReturn(null);
        $coverService->shouldReceive('normalizeCoverFromUpload')->once()->andReturn('/storage/book-scans/front-normalized.jpg');

        $service = new AiBookScanPipelineService(
            $ollama,
            $isbnLookup,
            $webDescription,
            $coverService
        );

        $result = $service->scan([
            UploadedFile::fake()->image('front.jpg'),
            UploadedFile::fake()->image('back.jpg'),
        ], 'full');

        $this->assertSame('Sinopsis dari back cover.', $result['description']);
        $this->assertSame('Novel', $result['category']);
        $this->assertSame('9789791227204', $result['isbn']);
        $this->assertSame('google', $result['source']);
        $this->assertSame('Back Cover', $result['field_sources']['description']);
        $this->assertSame('Google Books', $result['field_sources']['category']);
        $this->assertSame('Google Books', $result['field_sources']['isbn']);
    }

    public function test_trusted_websearch_is_used_when_provider_description_is_missing(): void
    {
        Storage::fake('public');

        $ollama = Mockery::mock(OllamaService::class);
        $isbnLookup = Mockery::mock(IsbnLookupService::class);
        $webDescription = Mockery::mock(WebBookDescriptionService::class);
        $coverService = Mockery::mock(CoverImageService::class);

        $ollama->shouldReceive('extractBookSignals')
            ->once()
            ->andReturn([
                'images' => [
                    ['index' => 0, 'view' => 'front', 'cover_box' => null],
                ],
                'best' => [
                    'isbn' => null,
                    'title' => 'Bumi',
                    'author' => 'Tere Liye',
                    'category' => 'Fantasi',
                    'description' => null,
                    'front_image_index' => 0,
                ],
            ]);

        $isbnLookup->shouldReceive('searchGoogleByTitleAuthorOnly')
            ->twice()
            ->with('Bumi', 'Tere Liye')
            ->andReturn([
                'title' => 'Bumi',
                'author' => 'Tere Liye',
                'category' => 'Fantasi',
                'description' => null,
                'publisher' => 'Gramedia',
                'published_year' => '2014',
                'isbn' => '9786020300115',
                'cover_url' => null,
                'source' => 'google',
                'source_url' => 'https://books.google.com/bumi',
            ]);

        $isbnLookup->shouldReceive('lookupOpenLibraryByIsbn')->never();
        $isbnLookup->shouldReceive('lookupOpenLibraryByTitleAuthor')
            ->once()
            ->with('Bumi', 'Tere Liye')
            ->andReturn(null);

        $webDescription->shouldReceive('resolveForDomains')
            ->once()
            ->with('Bumi', 'Tere Liye', ['gramedia.com', 'gramedia.digital'])
            ->andReturn([
                'description' => 'Sinopsis resmi dari Gramedia.',
                'source_url' => 'https://www.gramedia.com/products/bumi',
                'source' => 'websearch',
                'confidence' => 0.92,
            ]);

        $webDescription->shouldReceive('resolve')->never();
        $coverService->shouldReceive('cropFrontCover')->once()->andReturn(null);
        $coverService->shouldReceive('normalizeCoverFromUpload')->once()->andReturn('/storage/book-scans/bumi-front.jpg');

        $service = new AiBookScanPipelineService(
            $ollama,
            $isbnLookup,
            $webDescription,
            $coverService
        );

        $result = $service->scan([
            UploadedFile::fake()->image('front.jpg'),
        ], 'full');

        $this->assertSame('Sinopsis resmi dari Gramedia.', $result['description']);
        $this->assertSame('websearch', $result['source']);
        $this->assertSame('https://www.gramedia.com/products/bumi', $result['source_url']);
        $this->assertSame('Web Resmi Gramedia', $result['field_sources']['description']);
    }

    public function test_open_library_description_is_used_before_websearch_when_google_has_no_description(): void
    {
        Storage::fake('public');

        $ollama = Mockery::mock(OllamaService::class);
        $isbnLookup = Mockery::mock(IsbnLookupService::class);
        $webDescription = Mockery::mock(WebBookDescriptionService::class);
        $coverService = Mockery::mock(CoverImageService::class);

        $ollama->shouldReceive('extractBookSignals')
            ->once()
            ->andReturn([
                'images' => [
                    ['index' => 0, 'view' => 'front', 'cover_box' => null],
                ],
                'best' => [
                    'isbn' => null,
                    'title' => 'Keep Going',
                    'author' => 'Austin Kleon',
                    'category' => null,
                    'description' => null,
                    'front_image_index' => 0,
                ],
            ]);

        $isbnLookup->shouldReceive('searchGoogleByTitleAuthorOnly')
            ->twice()
            ->with('Keep Going', 'Austin Kleon')
            ->andReturn([
                'title' => 'Keep Going',
                'author' => 'Austin Kleon',
                'category' => 'Non-fiksi',
                'description' => null,
                'publisher' => 'Google Publisher',
                'published_year' => '2019',
                'isbn' => '9781523506640',
                'cover_url' => null,
                'source' => 'google',
                'source_url' => 'https://books.google.com/keep-going',
            ]);

        $isbnLookup->shouldReceive('lookupOpenLibraryByIsbn')->never();
        $isbnLookup->shouldReceive('lookupOpenLibraryByTitleAuthor')
            ->once()
            ->with('Keep Going', 'Austin Kleon')
            ->andReturn([
                'title' => 'Keep Going',
                'author' => 'Austin Kleon',
                'category' => 'Non-fiksi',
                'description' => 'Deskripsi dari Open Library untuk Keep Going.',
                'publisher' => 'Workman Publishing',
                'published_year' => '2019',
                'isbn' => '9781523506640',
                'cover_url' => null,
                'source' => 'openlibrary',
                'source_url' => 'https://openlibrary.org/books/OL123/Keep_Going',
            ]);

        $webDescription->shouldReceive('resolveForDomains')->never();
        $webDescription->shouldReceive('resolve')->never();
        $coverService->shouldReceive('cropFrontCover')->once()->andReturn(null);
        $coverService->shouldReceive('normalizeCoverFromUpload')->once()->andReturn('/storage/book-scans/keep-going-front.jpg');

        $service = new AiBookScanPipelineService(
            $ollama,
            $isbnLookup,
            $webDescription,
            $coverService
        );

        $result = $service->scan([
            UploadedFile::fake()->image('front.jpg'),
        ], 'full');

        $this->assertSame('Deskripsi dari Open Library untuk Keep Going.', $result['description']);
        $this->assertSame('Open Library', $result['field_sources']['description']);
        $this->assertSame('Non-fiksi', $result['category']);
        $this->assertSame('9781523506640', $result['isbn']);
    }

    public function test_noisy_ai_title_uses_keyword_candidate_for_metadata_lookup(): void
    {
        Storage::fake('public');

        $ollama = Mockery::mock(OllamaService::class);
        $isbnLookup = Mockery::mock(IsbnLookupService::class);
        $webDescription = Mockery::mock(WebBookDescriptionService::class);
        $coverService = Mockery::mock(CoverImageService::class);

        $ollama->shouldReceive('extractBookSignals')
            ->once()
            ->andReturn([
                'images' => [
                    ['index' => 0, 'view' => 'front', 'cover_box' => null],
                ],
                'best' => [
                    'isbn' => null,
                    'title' => 'The Endmatic Enduring Vision: A Enduring Vision: A History of the American People',
                    'author' => 'Adimitra Nursalim',
                    'category' => 'Sejarah',
                    'description' => null,
                    'front_image_index' => 0,
                ],
            ]);

        $isbnLookup->shouldReceive('searchGoogleByTitleAuthorOnly')
            ->once()
            ->with('The Endmatic Enduring Vision: A Enduring Vision: A History of the American People', 'Adimitra Nursalim')
            ->andReturn(null);

        $isbnLookup->shouldReceive('searchGoogleByTitleAuthorOnly')
            ->once()
            ->with('The Endmatic Enduring Vision A Enduring Vision A History of the American People', 'Adimitra Nursalim')
            ->andReturn(null);

        $isbnLookup->shouldReceive('searchGoogleByTitleAuthorOnly')
            ->once()
            ->with('The Endmatic Enduring Vision', 'Adimitra Nursalim')
            ->andReturn(null);

        $isbnLookup->shouldReceive('searchGoogleByTitleAuthorOnly')
            ->once()
            ->with('A Enduring Vision', 'Adimitra Nursalim')
            ->andReturn(null);

        $isbnLookup->shouldReceive('searchGoogleByTitleAuthorOnly')
            ->once()
            ->with('A History of the American People', 'Adimitra Nursalim')
            ->andReturn([
                'title' => 'The Enduring Vision: A History of the American People',
                'author' => 'Boyer Clark Kett',
                'category' => 'Sejarah',
                'description' => 'Deskripsi dari Google Books.',
                'publisher' => 'Cengage',
                'published_year' => '2010',
                'isbn' => '9781111342643',
                'cover_url' => null,
                'source' => 'google',
                'source_url' => 'https://books.google.com/enduring-vision',
            ]);
        $isbnLookup->shouldReceive('lookupOpenLibraryByIsbn')->never();
        $isbnLookup->shouldReceive('lookupOpenLibraryByTitleAuthor')
            ->never();
        $webDescription->shouldReceive('resolveForDomains')->never();
        $webDescription->shouldReceive('resolve')->never();
        $coverService->shouldReceive('cropFrontCover')->once()->andReturn(null);
        $coverService->shouldReceive('normalizeCoverFromUpload')->once()->andReturn('/storage/book-scans/enduring-vision-front.jpg');

        $service = new AiBookScanPipelineService(
            $ollama,
            $isbnLookup,
            $webDescription,
            $coverService
        );

        $result = $service->scan([
            UploadedFile::fake()->image('front.jpg'),
        ], 'full');

        $this->assertSame('Deskripsi dari Google Books.', $result['description']);
        $this->assertSame('Google Books', $result['field_sources']['description']);
        $this->assertSame('9781111342643', $result['isbn']);
    }

    public function test_open_library_without_description_falls_through_to_websearch_using_clean_provider_title(): void
    {
        Storage::fake('public');

        $ollama = Mockery::mock(OllamaService::class);
        $isbnLookup = Mockery::mock(IsbnLookupService::class);
        $webDescription = Mockery::mock(WebBookDescriptionService::class);
        $coverService = Mockery::mock(CoverImageService::class);

        $ollama->shouldReceive('extractBookSignals')
            ->once()
            ->andReturn([
                'images' => [
                    ['index' => 0, 'view' => 'front', 'cover_box' => null],
                ],
                'best' => [
                    'isbn' => null,
                    'title' => '}KEEP GOING',
                    'author' => 'AUSTIN KLEON',
                    'category' => 'Non-fiksi',
                    'description' => null,
                    'front_image_index' => 0,
                ],
            ]);

        $isbnLookup->shouldReceive('searchGoogleByTitleAuthorOnly')
            ->times(3)
            ->with('KEEP GOING', 'AUSTIN KLEON')
            ->andReturn([
                'title' => 'Keep Going',
                'author' => 'Austin Kleon',
                'category' => 'Non-fiksi',
                'description' => null,
                'publisher' => 'Open Library Publisher',
                'published_year' => '2019',
                'isbn' => '9781523506640',
                'cover_url' => null,
                'source' => 'openlibrary',
                'source_url' => 'https://openlibrary.org/books/OL1',
            ]);
        $isbnLookup->shouldReceive('lookupOpenLibraryByIsbn')->never();
        $isbnLookup->shouldReceive('lookupOpenLibraryByTitleAuthor')
            ->once()
            ->with('KEEP GOING', 'AUSTIN KLEON')
            ->andReturn([
                'title' => 'Keep Going',
                'author' => 'Austin Kleon',
                'category' => 'Non-fiksi',
                'description' => null,
                'publisher' => 'Open Library Publisher',
                'published_year' => '2019',
                'isbn' => '9781523506640',
                'cover_url' => null,
                'source' => 'openlibrary',
                'source_url' => 'https://openlibrary.org/books/OL1',
            ]);

        $webDescription->shouldReceive('resolveForDomains')
            ->once()
            ->with('KEEP GOING', 'AUSTIN KLEON', ['gramedia.com', 'gramedia.digital'])
            ->andReturn([
                'description' => 'Deskripsi resmi web untuk Keep Going.',
                'source_url' => 'https://www.gramedia.com/products/keep-going',
                'source' => 'websearch',
                'confidence' => 0.94,
            ]);

        $webDescription->shouldReceive('resolve')->never();
        $coverService->shouldReceive('cropFrontCover')->once()->andReturn(null);
        $coverService->shouldReceive('normalizeCoverFromUpload')->once()->andReturn('/storage/book-scans/keep-going-clean.jpg');

        $service = new AiBookScanPipelineService(
            $ollama,
            $isbnLookup,
            $webDescription,
            $coverService
        );

        $result = $service->scan([
            UploadedFile::fake()->image('front.jpg'),
        ], 'full');

        $this->assertSame('KEEP GOING', $result['title']);
        $this->assertSame('AUSTIN KLEON', $result['author']);
        $this->assertSame('Deskripsi resmi web untuk Keep Going.', $result['description']);
        $this->assertSame('websearch', $result['source']);
    }

    public function test_english_google_description_is_translated_to_indonesian_and_category_is_localized(): void
    {
        Storage::fake('public');

        $ollama = Mockery::mock(OllamaService::class);
        $isbnLookup = Mockery::mock(IsbnLookupService::class);
        $webDescription = Mockery::mock(WebBookDescriptionService::class);
        $coverService = Mockery::mock(CoverImageService::class);

        $ollama->shouldReceive('extractBookSignals')
            ->once()
            ->andReturn([
                'images' => [
                    ['index' => 0, 'view' => 'front', 'cover_box' => null],
                ],
                'best' => [
                    'isbn' => null,
                    'title' => 'Keep Going',
                    'author' => 'Austin Kleon',
                    'category' => null,
                    'description' => null,
                    'front_image_index' => 0,
                ],
            ]);

        $isbnLookup->shouldReceive('searchGoogleByTitleAuthorOnly')
            ->once()
            ->with('Keep Going', 'Austin Kleon')
            ->andReturn([
                'title' => 'Keep Going',
                'author' => 'Austin Kleon',
                'category' => 'Self-Help',
                'description' => 'Keep Working. Keep Playing. Keep Creating.',
                'publisher' => 'Workman',
                'published_year' => '2019',
                'isbn' => '9781523506640',
                'cover_url' => null,
                'source' => 'google',
                'source_url' => 'https://books.google.com/keep-going',
            ]);

        $isbnLookup->shouldReceive('lookupOpenLibraryByIsbn')->never();
        $isbnLookup->shouldReceive('lookupOpenLibraryByTitleAuthor')->never();
        $webDescription->shouldReceive('resolveForDomains')->never();
        $webDescription->shouldReceive('resolve')->never();
        $ollama->shouldReceive('translateTextToIndonesian')
            ->once()
            ->with('Keep Working. Keep Playing. Keep Creating.')
            ->andReturn('Terus Berkarya. Terus Bermain. Terus Mencipta.');
        $coverService->shouldReceive('cropFrontCover')->once()->andReturn(null);
        $coverService->shouldReceive('normalizeCoverFromUpload')->once()->andReturn('/storage/book-scans/keep-going-front.jpg');

        $service = new AiBookScanPipelineService(
            $ollama,
            $isbnLookup,
            $webDescription,
            $coverService
        );

        $result = $service->scan([
            UploadedFile::fake()->image('front.jpg'),
        ], 'full');

        $this->assertSame('Terus Berkarya. Terus Bermain. Terus Mencipta.', $result['description']);
        $this->assertSame('Pengembangan Diri', $result['category']);
    }
}
