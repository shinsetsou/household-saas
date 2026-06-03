<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password-secure-123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_new_user_can_register_without_password_confirmation(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Ana Cruz',
            'email' => 'ana@example.com',
            'password' => 'super-secret-123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'ana@example.com']);
    }

    public function test_register_page_does_not_render_confirm_password_field(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertDontSee('password_confirmation', false);
    }
}
