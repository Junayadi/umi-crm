<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Source;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisor_can_view_and_filter_audit_history(): void
    {
        $supervisor = User::factory()->create(['role' => Role::Supervisor->value]);
        $staff = User::factory()->create(['role' => Role::CustomerService->value]);

        AuditLog::create([
            'user_id' => $supervisor->id,
            'action' => 'tambah',
            'auditable_type' => User::class,
            'auditable_id' => $staff->id,
            'description' => 'Akun staf ditambahkan.',
            'new_values' => ['name' => $staff->name, 'email' => $staff->email],
        ]);
        AuditLog::create([
            'user_id' => $staff->id,
            'action' => 'ubah',
            'auditable_type' => Source::class,
            'auditable_id' => 1,
            'description' => 'Status sumber leads diubah.',
        ]);

        $this->actingAs($supervisor)
            ->get(route('audit-logs.index', ['action' => 'tambah', 'user_id' => $supervisor->id]))
            ->assertOk()
            ->assertSee('Log Perubahan')
            ->assertSee('Akun staf ditambahkan.')
            ->assertDontSee('Status sumber leads diubah.')
            ->assertSee('Terapkan filter');
    }

    public function test_non_supervisors_cannot_view_audit_history(): void
    {
        $manager = User::factory()->create(['role' => Role::Manajemen->value]);

        $this->actingAs($manager)->get(route('audit-logs.index'))->assertForbidden();
    }

    public function test_master_data_and_password_reset_are_audited_without_secrets(): void
    {
        $supervisor = User::factory()->create(['role' => Role::Supervisor->value]);

        $this->actingAs($supervisor)->post(route('master-data.users.store'), [
            'name' => 'Staf Baru',
            'email' => 'staf.baru@example.test',
            'role' => Role::CustomerService->value,
        ])->assertRedirect(route('master-data.index'));

        $staff = User::where('email', 'staf.baru@example.test')->firstOrFail();
        $temporaryPassword = session('temporary_password');
        $this->assertNotEmpty($temporaryPassword);

        $this->post(route('password.resets.store', ['user' => $staff]))
            ->assertRedirect(route('master-data.index'));
        $resetPassword = session('temporary_password');
        $this->assertNotEmpty($resetPassword);

        $this->patch(route('master-data.users.status', ['user' => $staff]))->assertRedirect();
        $statusLog = AuditLog::query()
            ->where('auditable_type', User::class)
            ->where('auditable_id', $staff->id)
            ->where('description', 'Status akun staf diubah.')
            ->firstOrFail();
        $this->assertSame(['status' => 'Aktif'], $statusLog->old_values);
        $this->assertSame(['status' => 'Nonaktif'], $statusLog->new_values);

        $source = Source::create(['name' => 'Instagram']);
        $category = Category::create(['name' => 'Umroh']);
        $this->post(route('master-data.sources.store'), ['source_name' => 'Facebook'])->assertRedirect();
        $this->post(route('master-data.categories.store'), ['category_name' => 'Haji'])->assertRedirect();
        $this->patch(route('master-data.sources.status', ['source' => $source]))->assertRedirect();
        $this->patch(route('master-data.categories.status', ['category' => $category]))->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $supervisor->id,
            'action' => 'reset_password',
            'auditable_type' => User::class,
            'auditable_id' => $staff->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'tambah',
            'auditable_type' => Source::class,
            'description' => 'Sumber leads ditambahkan.',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'tambah',
            'auditable_type' => Category::class,
            'description' => 'Kategori layanan ditambahkan.',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ubah',
            'auditable_type' => Source::class,
            'description' => 'Status sumber leads diubah.',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ubah',
            'auditable_type' => Category::class,
            'description' => 'Status kategori layanan diubah.',
        ]);

        $serializedLogs = AuditLog::query()->get()->toJson();
        $this->assertStringNotContainsString($temporaryPassword, $serializedLogs);
        $this->assertStringNotContainsString($resetPassword, $serializedLogs);
        $this->assertStringNotContainsString('"password":', strtolower($serializedLogs));
    }
}
