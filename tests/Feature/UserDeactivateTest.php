<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDeactivateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test deactivation page loads successfully.
     */
    public function test_deactivation_page_renders_successfully(): void
    {
        $response = $this->get(route('account.delete.show'));

        $response->assertStatus(200);
        $response->assertSee('Delete Account');
    }

    /**
     * Test validation checks when fields are empty.
     */
    public function test_deactivation_fails_with_validation_errors_when_fields_are_empty(): void
    {
        $response = $this->post(route('account.delete.submit'), [
            'contact' => '',
            'password' => '',
            'confirm' => '',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['contact', 'password', 'confirm']);
    }

    /**
     * Test deactivation fails when password or user identifier is wrong.
     */
    public function test_deactivation_fails_with_incorrect_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'testuser@example.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        // Wrong password
        $response = $this->post(route('account.delete.submit'), [
            'contact' => 'testuser@example.com',
            'password' => 'wrongpassword',
            'confirm' => '1',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['contact']);
        $this->assertTrue($user->fresh()->is_active);

        // Wrong email
        $response2 = $this->post(route('account.delete.submit'), [
            'contact' => 'nonexistent@example.com',
            'password' => 'password123',
            'confirm' => '1',
        ]);

        $response2->assertStatus(302);
        $response2->assertSessionHasErrors(['contact']);
    }

    /**
     * Test deactivation fails when the account is already deactivated.
     */
    public function test_deactivation_fails_when_account_is_already_inactive(): void
    {
        $user = User::factory()->create([
            'email' => 'inactiveuser@example.com',
            'password' => bcrypt('password123'),
            'is_active' => false,
        ]);

        $response = $this->post(route('account.delete.submit'), [
            'contact' => 'inactiveuser@example.com',
            'password' => 'password123',
            'confirm' => '1',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['contact']);
    }

    /**
     * Test successful deactivation toggles is_active and revokes tokens.
     */
    public function test_successful_deactivation(): void
    {
        $user = User::factory()->create([
            'phone' => '1234567890',
            'password' => bcrypt('mysecretpass'),
            'is_active' => true,
        ]);

        // Create a mock personal access token for this user
        $user->createToken('test-token');
        $this->assertCount(1, $user->tokens);

        $response = $this->post(route('account.delete.submit'), [
            'contact' => '1234567890',
            'password' => 'mysecretpass',
            'confirm' => '1',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        // Check user is now deactivated
        $user = $user->fresh();
        $this->assertFalse((bool) $user->is_active);

        // Check tokens are revoked
        $this->assertCount(0, $user->tokens);
    }
}
