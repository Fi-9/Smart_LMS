<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Book;
use App\Models\Category;
use App\Models\Rack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookPositionConstraintTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_store_two_books_with_same_position_in_same_rack(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN->value]);
        $category = Category::factory()->create();
        $rack = Rack::factory()->create();

        Book::factory()->create([
            'category_id' => $category->id,
            'rack_id' => $rack->id,
            'position_code' => 'A1',
        ]);

        $payload = [
            'title' => 'Duplicate Position',
            'author' => 'Tester',
            'isbn' => '9780000000001',
            'category_id' => $category->id,
            'rack_id' => $rack->id,
            'position_code' => 'A1',
            'status' => 'available',
        ];

        $response = $this->actingAs($user)->postJson('/api/books', $payload);

        $response->assertStatus(422);
    }
}
