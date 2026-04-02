<?php

namespace Database\Factories;

use App\Enums\BookStatus;
use App\Models\Category;
use App\Models\Rack;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'author' => fake()->name(),
            'isbn' => fake()->unique()->numerify('978##########'),
            'category_id' => Category::factory(),
            'rack_id' => Rack::factory(),
            'position_code' => 'A1',
            'cover_url' => fake()->imageUrl(),
            'qr_code_path' => '/storage/qrcodes/book-1.png',
            'status' => BookStatus::AVAILABLE->value,
        ];
    }
}
