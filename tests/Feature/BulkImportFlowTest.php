<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Jobs\GenerateBookQrCodeJob;
use App\Models\Category;
use App\Models\Rack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BulkImportFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_validates_and_commit_imports_with_default_rack_and_queue(): void
    {
        Queue::fake();

        $user = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $category = Category::factory()->create();
        $defaultRack = Rack::factory()->create(['name' => 'A']);

        $csv = implode("\n", [
            'title,author,isbn,category_id,rack_id,position_code,cover_url,status',
            "Book One,Author One,9781111111111,{$category->id},,A1,,available",
            "Book Two,Author Two,9781111111111,{$category->id},{$defaultRack->id},A2,,available",
        ]);

        $file = UploadedFile::fake()->createWithContent('books.csv', $csv);

        $preview = $this->actingAs($user)->postJson('/api/books/import/preview', [
            'file' => $file,
        ]);

        $preview->assertOk();
        $preview->assertJsonPath('summary.total_rows', 2);
        $preview->assertJsonPath('summary.valid_rows', 1);
        $preview->assertJsonPath('summary.invalid_rows', 1);

        $previewToken = $preview->json('preview_token');

        $commit = $this->actingAs($user)->postJson('/api/books/import/commit', [
            'preview_token' => $previewToken,
        ]);

        $commit->assertOk();
        $commit->assertJsonPath('imported', 1);

        $this->assertDatabaseHas('books', [
            'title' => 'Book One',
            'rack_id' => $defaultRack->id,
            'position_code' => 'A1',
        ]);

        Queue::assertPushed(GenerateBookQrCodeJob::class, 1);
    }
}
