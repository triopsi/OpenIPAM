<?php

namespace Tests\Feature;

use App\Filament\Auth\FirstUserRegister;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Verify that browser setup creates exactly the first account and then closes.
 */
class FirstUserSetupTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Send a fresh installation's login request to the installer.
     *
     * Expect: Login redirects to registration and the empty installation displays the welcome form.
     */
    public function test_empty_installation_redirects_login_to_setup(): void
    {
        $this->get('/admin/login')->assertRedirect('/admin/register');
        $this->get('/admin/register')->assertOk()->assertSee('Welcome to OpenIPAM');
    }

    /**
     * Create the initial account, hash its password and sign the user in.
     *
     * Expect: Exactly one account is created with a hashed password, is authenticated and can open the dashboard.
     */
    public function test_first_account_can_be_created_in_the_browser(): void
    {
        Livewire::test(FirstUserRegister::class)
            ->fillForm([
                'name' => 'Network Operator',
                'email' => 'operator@example.test',
                'password' => 'a-long-test-password',
                'passwordConfirmation' => 'a-long-test-password',
            ])
            ->call('register')
            ->assertHasNoFormErrors()
            ->assertRedirect('/admin');

        $user = User::sole();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('a-long-test-password', $user->password));
        $this->assertSame('en', $user->language);
        $this->assertFalse($user->email_two_factor_enabled);
        $this->get('/admin')->assertOk();
    }

    /**
     * Reject short passwords without creating an account.
     *
     * Expect: A password shorter than twelve characters produces an error and creates no account.
     */
    public function test_setup_requires_a_password_of_at_least_twelve_characters(): void
    {
        Livewire::test(FirstUserRegister::class)
            ->fillForm([
                'name' => 'Network Operator',
                'email' => 'operator@example.test',
                'password' => 'shortpass',
                'passwordConfirmation' => 'shortpass',
            ])
            ->call('register')
            ->assertHasFormErrors(['password' => 'min']);

        $this->assertDatabaseCount('users', 0);
    }

    /**
     * Require matching password confirmation before saving the first user.
     *
     * Expect: Mismatched password confirmation produces an error and creates no account.
     */
    public function test_setup_requires_matching_password_confirmation(): void
    {
        Livewire::test(FirstUserRegister::class)
            ->fillForm([
                'name' => 'Network Operator',
                'email' => 'operator@example.test',
                'password' => 'a-long-test-password',
                'passwordConfirmation' => 'a-different-password',
            ])
            ->call('register')
            ->assertHasFormErrors(['password' => 'same']);

        $this->assertDatabaseCount('users', 0);
    }

    /**
     * Hide first-user registration when any account already exists.
     *
     * Expect: Registration redirects to login and the login response contains no registration link.
     */
    public function test_existing_installation_redirects_setup_to_login(): void
    {
        User::factory()->create();

        $this->get('/admin/register')->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertOk()->assertDontSee('href="'.route('filament.admin.auth.register').'"', false);
    }

    /**
     * Recheck account existence when an older installer form is submitted.
     *
     * Expect: A previously opened form reports completion, preserves the sole account and leaves the guest unauthenticated.
     */
    public function test_stale_setup_form_cannot_create_a_second_account(): void
    {
        $page = Livewire::test(FirstUserRegister::class)->fillForm([
            'name' => 'Second Operator',
            'email' => 'second@example.test',
            'password' => 'a-long-test-password',
            'passwordConfirmation' => 'a-long-test-password',
        ]);

        User::factory()->create();

        $page->call('register')->assertHasErrors(['data.email']);

        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
    }
}
