<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_guest_is_redirected_to_login_page(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_staff_is_redirected_to_scanner_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::STAFF->value,
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect(route('book-scanner.index'));
    }

    public function test_login_page_can_be_rendered(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Masuk ke panel admin');
    }
}
