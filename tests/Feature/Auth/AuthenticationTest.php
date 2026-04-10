<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    public function test_login_page_loads(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_login_with_valid_credentials(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email'     => 'test@example.com',
            'password'  => bcrypt('Password1'),
        ]);

        $response = $this->post('/login', [
            'email'    => 'test@example.com',
            'password' => 'Password1',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_wrong_password_fails(): void
    {
        $tenant = Tenant::factory()->create();
        User::factory()->create([
            'tenant_id' => $tenant->id,
            'email'     => 'test@example.com',
        ]);

        $response = $this->post('/login', [
            'email'    => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_login_with_nonexistent_email_fails(): void
    {
        $response = $this->post('/login', [
            'email'    => 'nobody@example.com',
            'password' => 'Password1',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_register_validates_required_fields(): void
    {
        $response = $this->post('/register', []);

        $response->assertSessionHasErrors(['tenant_name', 'name', 'email', 'password', 'phone']);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        $tenant = Tenant::factory()->create();
        User::factory()->create([
            'tenant_id' => $tenant->id,
            'email'     => 'taken@example.com',
        ]);

        $response = $this->post('/register', [
            'tenant_name'           => 'Outra Empresa',
            'name'                  => 'Maria',
            'email'                 => 'taken@example.com',
            'password'              => 'Password1',
            'password_confirmation' => 'Password1',
            'phone'                 => '11999887766',
            'accept_terms'          => true,
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_register_rejects_weak_password(): void
    {
        $response = $this->post('/register', [
            'tenant_name'           => 'Empresa',
            'name'                  => 'User',
            'email'                 => 'user@test.com',
            'password'              => '123',
            'password_confirmation' => '123',
            'phone'                 => '11999887766',
            'accept_terms'          => true,
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_register_rejects_honeypot(): void
    {
        $response = $this->post('/register', [
            'tenant_name'           => 'Bot Corp',
            'name'                  => 'Bot',
            'email'                 => 'bot@spam.com',
            'password'              => 'Password1',
            'password_confirmation' => 'Password1',
            'phone'                 => '11999887766',
            'accept_terms'          => true,
            'website_url'           => 'http://spam.com', // honeypot
        ]);

        $response->assertStatus(422);
    }

    public function test_logout_works(): void
    {
        $this->actingAsTenant();

        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_authenticated_user_redirected_from_login(): void
    {
        $this->actingAsTenant();

        $response = $this->get('/login');
        $response->assertRedirect('/');
    }
}
