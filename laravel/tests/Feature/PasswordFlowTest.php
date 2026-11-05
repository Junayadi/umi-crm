<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_temporary_password_login_requires_a_change_before_accessing_the_app(): void
    {
        $user = User::factory()->create([
            'role' => Role::CustomerService->value,
            'password' => Hash::make('Temporary-123'),
            'must_change_password' => true,
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'Temporary-123',
            'role' => Role::CustomerService->value,
        ])->assertRedirect(route('password.edit'));

        $this->get(route('dashboard'))->assertRedirect(route('password.edit'));
        $this->get(route('password.edit'))->assertOk()->assertSee('Ganti Password');
    }

    public function test_user_can_change_temporary_password_and_access_the_app(): void
    {
        $user = User::factory()->create([
            'role' => Role::CustomerService->value,
            'password' => Hash::make('Temporary-123'),
            'must_change_password' => true,
        ]);

        $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'Temporary-123',
            'password' => 'NewPassword-456',
            'password_confirmation' => 'NewPassword-456',
        ])->assertRedirect(route('home'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'must_change_password' => false,
        ]);
        $this->assertTrue(Hash::check('NewPassword-456', $user->fresh()->password));
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_password_change_rejects_an_incorrect_current_password(): void
    {
        $user = User::factory()->create([
            'role' => Role::Manajemen->value,
            'password' => Hash::make('Current-123'),
            'must_change_password' => true,
        ]);

        $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'Wrong-123',
            'password' => 'NewPassword-456',
            'password_confirmation' => 'NewPassword-456',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue($user->fresh()->must_change_password);
        $this->assertTrue(Hash::check('Current-123', $user->fresh()->password));
    }

    public function test_supervisor_can_reset_a_users_password_and_the_temporary_password_is_shown_once(): void
    {
        $supervisor = User::factory()->create(['role' => Role::Supervisor->value]);
        $user = User::factory()->create([
            'name' => 'Tanti',
            'role' => Role::CustomerService->value,
            'password' => Hash::make('OldPassword-123'),
        ]);

        $this->actingAs($supervisor)->get(route('master-data.index'))
            ->assertOk()
            ->assertSee('Tanti')
            ->assertSee('Reset password');

        $response = $this->post(route('password.resets.store', ['user' => $user]));

        $response->assertRedirect(route('master-data.index'))
            ->assertSessionHas('temporary_password');
        $temporaryPassword = app('session.store')->get('temporary_password');

        $this->assertIsString($temporaryPassword);
        $this->assertGreaterThanOrEqual(8, strlen($temporaryPassword));
        $this->assertTrue(Hash::check($temporaryPassword, $user->fresh()->password));
        $this->assertTrue($user->fresh()->must_change_password);

        $this->get(route('master-data.index'))
            ->assertOk()
            ->assertSee($temporaryPassword)
            ->assertSee('Password hanya ditampilkan sekali');
        $this->get(route('master-data.index'))->assertDontSee($temporaryPassword);
    }

    public function test_only_supervisors_can_open_master_data(): void
    {
        $staff = User::factory()->create(['role' => Role::CustomerService->value]);

        $this->actingAs($staff)->get(route('master-data.index'))->assertForbidden();
    }
}
