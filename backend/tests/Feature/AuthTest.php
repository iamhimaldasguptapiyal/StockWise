<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_is_created_as_staff(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'New Person',
            'email' => 'new@example.com',
            'password' => 'Secret1234',
            'password_confirmation' => 'Secret1234',
            'role' => 'admin', // must be ignored
        ]);

        $response->assertCreated()->assertJsonStructure(['token', 'user' => ['id', 'email', 'role']]);
        $this->assertSame('staff', $response->json('user.role'));
        $this->assertNotSame('Secret1234', User::first()->password, 'Password must be hashed');
    }

    public function test_registration_rejects_weak_password(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Weak',
            'email' => 'weak@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
    }

    public function test_login_returns_token_and_bad_password_is_rejected(): void
    {
        User::create(['name' => 'A', 'email' => 'a@example.com', 'password' => 'Secret1234']);

        $this->postJson('/api/auth/login', ['email' => 'a@example.com', 'password' => 'wrong'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $token = $this->postJson('/api/auth/login', ['email' => 'a@example.com', 'password' => 'Secret1234'])
            ->assertOk()
            ->json('token');

        $this->withToken($token)->getJson('/api/auth/me')->assertOk()->assertJsonPath('user.email', 'a@example.com');
    }

    public function test_protected_routes_require_a_token(): void
    {
        $this->getJson('/api/products')->assertUnauthorized();
    }
}
