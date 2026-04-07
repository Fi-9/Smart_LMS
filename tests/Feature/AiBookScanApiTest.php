<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\AiBookScanPipelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class AiBookScanApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_authentication(): void
    {
        $response = $this->postJson('/api/ai/books/scan', []);

        $response->assertUnauthorized();
    }

    public function test_validates_images_and_mode(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN->value]);

        $response = $this->actingAs($user)->postJson('/api/ai/books/scan', [
            'mode' => 'invalid-mode',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['images', 'mode']);
    }

    public function test_uses_default_full_mode_when_mode_not_provided(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $file = UploadedFile::fake()->image('cover.jpg');

        $this->mock(AiBookScanPipelineService::class, function ($mock): void {
            $mock->shouldReceive('scan')
                ->once()
                ->withArgs(function (array $images, string $mode): bool {
                    return count($images) === 1 && $mode === 'full';
                })
                ->andReturn([
                    'title' => 'Clean Code',
                    'author' => 'Robert C. Martin',
                    'description' => null,
                    'publisher' => null,
                    'published_year' => null,
                    'isbn' => null,
                    'cover_url' => '/storage/book-scans/sample.jpg',
                    'source' => 'ai',
                ]);
        });

        $response = $this->actingAs($user)->post('/api/ai/books/scan', [
            'images' => [$file],
        ]);

        $response->assertOk();
        $response->assertJsonPath('title', 'Clean Code');
        $response->assertJsonPath('source', 'ai');
    }

    public function test_passes_simple_mode_to_pipeline(): void
    {
        $user = User::factory()->create(['role' => UserRole::STAFF->value]);
        $file = UploadedFile::fake()->image('front.png');

        $this->mock(AiBookScanPipelineService::class, function ($mock): void {
            $mock->shouldReceive('scan')
                ->once()
                ->withArgs(function (array $images, string $mode): bool {
                    return count($images) === 1 && $mode === 'simple';
                })
                ->andReturn([
                    'title' => null,
                    'author' => null,
                    'description' => null,
                    'publisher' => null,
                    'published_year' => null,
                    'isbn' => null,
                    'cover_url' => '/storage/book-scans/sample.jpg',
                    'source' => 'ai',
                ]);
        });

        $response = $this->actingAs($user)->post('/api/ai/books/scan', [
            'images' => [$file],
            'mode' => 'simple',
        ]);

        $response->assertOk();
        $response->assertJsonPath('source', 'ai');
    }

    public function test_returns_bad_gateway_when_pipeline_throws_runtime_exception(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $file = UploadedFile::fake()->image('cover.webp');

        $this->mock(AiBookScanPipelineService::class, function ($mock): void {
            $mock->shouldReceive('scan')
                ->once()
                ->andThrow(new RuntimeException('Ollama timeout'));
        });

        $response = $this->actingAs($user)->post('/api/ai/books/scan', [
            'images' => [$file],
        ]);

        $response->assertStatus(502);
        $response->assertJsonPath('message', 'Ollama timeout');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

