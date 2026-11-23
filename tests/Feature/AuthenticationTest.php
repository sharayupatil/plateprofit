<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_a_working_login_page(): void
    {
        $this->get('/pos-imports')->assertRedirect(route('login'));
        $this->get('/login')->assertOk();
    }

    public function test_users_can_log_in_and_log_out(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->get('/pos-imports');
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/pos-imports');
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_invalid_credentials_do_not_log_in(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'incorrect'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_authenticated_users_can_view_the_import_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/pos-imports')->assertOk();
        $this->actingAs($user)->get('/pos-imports/create')->assertOk();
    }
}
