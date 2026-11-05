<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Category;
use App\Models\Source;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisor_can_create_an_account_with_a_one_time_temporary_password(): void
    {
        $supervisor = User::factory()->create(['role' => Role::Supervisor->value]);

        $response = $this->actingAs($supervisor)
            ->from(route('master-data.index'))
            ->post(route('master-data.users.store'), [
                'name' => 'Tanti',
                'email' => 'TANTI@contoh.com',
                'role' => Role::CustomerService->value,
            ]);

        $response->assertRedirect(route('master-data.index'))
            ->assertSessionHas('temporary_password');

        $user = User::where('email', 'tanti@contoh.com')->firstOrFail();
        $temporaryPassword = app('session.store')->get('temporary_password');
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check($temporaryPassword, $user->password));

        $this->get(route('master-data.index'))->assertSee($temporaryPassword);
        $this->get(route('master-data.index'))->assertDontSee($temporaryPassword);
    }

    public function test_supervisor_can_add_and_toggle_sources_and_categories_without_removing_the_last_active_option(): void
    {
        $supervisor = User::factory()->create(['role' => Role::Supervisor->value]);
        $source = Source::create(['name' => 'Instagram']);
        $category = Category::create(['name' => 'Umroh']);

        $this->actingAs($supervisor)->from(route('master-data.index'))
            ->post(route('master-data.sources.store'), ['source_name' => 'Facebook'])
            ->assertRedirect(route('master-data.index'));
        $this->from(route('master-data.index'))
            ->post(route('master-data.categories.store'), ['category_name' => 'Haji Khusus'])
            ->assertRedirect(route('master-data.index'));

        $this->assertDatabaseHas('sources', ['name' => 'Facebook', 'is_active' => true]);
        $this->assertDatabaseHas('categories', ['name' => 'Haji Khusus', 'is_active' => true]);

        $this->patch(route('master-data.sources.status', ['source' => $source]))
            ->assertRedirect(route('master-data.index'));
        $this->patch(route('master-data.categories.status', ['category' => $category]))
            ->assertRedirect(route('master-data.index'));
        $this->assertFalse($source->fresh()->is_active);
        $this->assertFalse($category->fresh()->is_active);

        $lastSource = Source::where('is_active', true)->firstOrFail();
        $lastCategory = Category::where('is_active', true)->firstOrFail();
        $this->from(route('master-data.index'))
            ->patch(route('master-data.sources.status', ['source' => $lastSource]))
            ->assertSessionHasErrors('source');
        $this->from(route('master-data.index'))
            ->patch(route('master-data.categories.status', ['category' => $lastCategory]))
            ->assertSessionHasErrors('category');
        $this->assertTrue($lastSource->fresh()->is_active);
        $this->assertTrue($lastCategory->fresh()->is_active);
    }

    public function test_supervisor_can_toggle_other_accounts_but_not_their_own_account(): void
    {
        $supervisor = User::factory()->create(['role' => Role::Supervisor->value]);
        $otherSupervisor = User::factory()->create(['role' => Role::Supervisor->value]);
        $staff = User::factory()->create(['role' => Role::CustomerService->value]);

        $this->actingAs($supervisor)->from(route('master-data.index'))
            ->patch(route('master-data.users.status', ['user' => $staff]))
            ->assertRedirect(route('master-data.index'));
        $this->assertFalse($staff->fresh()->is_active);
        $this->actingAs($staff->fresh())->get(route('dashboard'))
            ->assertRedirect(route('login'));
        $this->assertGuest();

        $this->actingAs($supervisor)->patch(route('master-data.users.status', ['user' => $supervisor]))->assertNotFound();
        $this->patch(route('master-data.users.status', ['user' => $otherSupervisor]))
            ->assertRedirect(route('master-data.index'));
        $this->assertTrue($supervisor->fresh()->is_active);
        $this->assertFalse($otherSupervisor->fresh()->is_active);
    }

    public function test_last_active_supervisor_cannot_be_deactivated(): void
    {
        $supervisor = User::factory()->create(['role' => Role::Supervisor->value]);
        $anotherUser = User::factory()->create(['role' => Role::Manajemen->value]);

        $this->actingAs($anotherUser)->from(route('master-data.index'))
            ->patch(route('master-data.users.status', ['user' => $supervisor]))
            ->assertForbidden();

        $this->actingAs($supervisor)->from(route('master-data.index'))
            ->patch(route('master-data.users.status', ['user' => $anotherUser]))
            ->assertRedirect(route('master-data.index'));

        $secondSupervisor = User::factory()->create(['role' => Role::Supervisor->value]);
        $this->patch(route('master-data.users.status', ['user' => $secondSupervisor]))
            ->assertRedirect(route('master-data.index'));
        $this->assertFalse($secondSupervisor->fresh()->is_active);
    }

    public function test_non_supervisors_cannot_view_or_change_master_data(): void
    {
        $manager = User::factory()->create(['role' => Role::Manajemen->value]);

        $this->actingAs($manager)->get(route('master-data.index'))->assertForbidden();
        $this->post(route('master-data.categories.store'), ['category_name' => 'Haji'])->assertForbidden();
    }
}
