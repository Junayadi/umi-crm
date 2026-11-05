<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Category;
use App\Models\Lead;
use App\Models\Source;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_dashboard_filters_leads_and_calculates_status_totals(): void
    {
        $this->travelTo('2026-10-23 12:00:00');
        $manager = User::factory()->create(['role' => Role::Manajemen->value]);
        $staff = User::factory()->create(['name' => 'Tanti', 'role' => Role::CustomerService->value]);
        $otherStaff = User::factory()->create(['name' => 'Asrofa', 'role' => Role::CustomerService->value]);
        $category = Category::create(['name' => 'Umroh']);
        $instagram = Source::create(['name' => 'Instagram']);
        $facebook = Source::create(['name' => 'Facebook']);
        $this->createLead($staff, $category, $instagram, '2026-10-20', ['cold' => 3, 'closing' => 2]);
        $this->createLead($staff, $category, $facebook, '2026-10-20', ['cold' => 8, 'closing' => 5]);
        $this->createLead($otherStaff, $category, $instagram, '2026-10-20', ['cold' => 5, 'closing' => 4]);

        $response = $this->actingAs($manager)->get(route('dashboard', [
            'from' => '2026-10-01',
            'to' => '2026-10-23',
            'source' => $instagram->id,
            'staff' => $staff->id,
        ]));

        $response->assertOk()
            ->assertSee('Dashboard Analitik')
            ->assertSee('Closing per sumber leads')
            ->assertViewHas('totals', fn ($totals): bool => (int) $totals->entries === 1
                && (int) $totals->cold === 3
                && (int) $totals->closing === 2)
            ->assertViewHas('chartGroups', fn (array $groups): bool => count($groups) === 1
                && $groups[0]['name'] === 'Tanti');
    }

    public function test_customer_service_dashboard_only_shows_the_signed_in_users_leads(): void
    {
        $this->travelTo('2026-10-23 12:00:00');
        $staff = User::factory()->create(['name' => 'Tanti', 'role' => Role::CustomerService->value]);
        $otherStaff = User::factory()->create(['name' => 'Asrofa', 'role' => Role::CustomerService->value]);
        $category = Category::create(['name' => 'Umroh']);
        $source = Source::create(['name' => 'Instagram']);
        $this->createLead($staff, $category, $source, '2026-10-20', ['closing' => 2]);
        $this->createLead($otherStaff, $category, $source, '2026-10-20', ['closing' => 7]);

        $response = $this->actingAs($staff)->get(route('dashboard', [
            'from' => '2026-10-01',
            'to' => '2026-10-23',
            'staff' => $otherStaff->id,
        ]));

        $response->assertOk()
            ->assertSee('Diagram Saya')
            ->assertSee('Prospek Anda per sumber leads')
            ->assertDontSee('Asrofa')
            ->assertViewHas('totals', fn ($totals): bool => (int) $totals->entries === 1
                && (int) $totals->closing === 2)
            ->assertViewHas('chartStep', fn (float $step): bool => $step === 0.5);
    }

    public function test_dashboard_rejects_a_date_range_longer_than_31_days_without_redirecting(): void
    {
        $manager = User::factory()->create(['role' => Role::Manajemen->value]);

        $response = $this->actingAs($manager)->get(route('dashboard', [
            'from' => '2026-09-01',
            'to' => '2026-10-23',
        ]));

        $response->assertOk()
            ->assertSee('Rentang tanggal maksimal 31 hari.')
            ->assertViewHas('totals', fn ($totals): bool => (int) $totals->entries === 0);
    }

    public function test_dashboard_export_downloads_a_real_xlsx_with_the_selected_filters(): void
    {
        $this->travelTo('2026-10-23 12:00:00');
        $manager = User::factory()->create(['role' => Role::Supervisor->value]);
        $staff = User::factory()->create(['name' => 'Tanti', 'role' => Role::CustomerService->value]);
        $otherStaff = User::factory()->create(['name' => 'Asrofa', 'role' => Role::CustomerService->value]);
        $category = Category::create(['name' => 'Umroh']);
        $source = Source::create(['name' => 'Instagram']);
        $otherSource = Source::create(['name' => 'Facebook']);
        $this->createLead($staff, $category, $source, '2026-10-20', ['closing' => 2]);
        $this->createLead($otherStaff, $category, $otherSource, '2026-10-20', ['closing' => 9]);

        $response = $this->actingAs($manager)->get(route('dashboard.export', [
            'from' => '2026-10-01',
            'to' => '2026-10-23',
            'source' => $source->id,
        ]));

        $response->assertDownload('umi-crm-2026-10-01-sd-2026-10-23.xlsx')
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $xlsx = $response->streamedContent();
        $this->assertStringStartsWith('PK', $xlsx);

        $archivePath = tempnam(sys_get_temp_dir(), 'umi-test-xlsx-');
        $this->assertNotFalse($archivePath);
        file_put_contents($archivePath, $xlsx);
        $archive = new \ZipArchive;

        try {
            $this->assertTrue($archive->open($archivePath));
            $worksheet = $archive->getFromName('xl/worksheets/sheet1.xml');
            $this->assertNotFalse($worksheet);
            $this->assertStringContainsString('Tanti', $worksheet);
            $this->assertStringContainsString('Instagram', $worksheet);
            $this->assertStringContainsString('Closing', $worksheet);
            $this->assertStringNotContainsString('Asrofa', $worksheet);
            $this->assertStringNotContainsString('Facebook', $worksheet);

            $document = new \DOMDocument;
            $this->assertTrue($document->loadXML($worksheet));
            $this->assertNotFalse($archive->getFromName('xl/styles.xml'));
        } finally {
            $archive->close();
            @unlink($archivePath);
        }
    }

    public function test_customer_service_excel_export_cannot_include_another_staffs_leads(): void
    {
        $this->travelTo('2026-10-23 12:00:00');
        $staff = User::factory()->create(['name' => 'Tanti', 'role' => Role::CustomerService->value]);
        $otherStaff = User::factory()->create(['name' => 'Asrofa', 'role' => Role::CustomerService->value]);
        $category = Category::create(['name' => 'Umroh']);
        $source = Source::create(['name' => 'Instagram']);
        $this->createLead($staff, $category, $source, '2026-10-20', ['closing' => 2]);
        $this->createLead($otherStaff, $category, $source, '2026-10-20', ['closing' => 9]);

        $response = $this->actingAs($staff)->get(route('dashboard.export', [
            'from' => '2026-10-01',
            'to' => '2026-10-23',
            'staff' => $otherStaff->id,
        ]));
        $xlsx = $response->streamedContent();
        $archivePath = tempnam(sys_get_temp_dir(), 'umi-test-xlsx-');
        $this->assertNotFalse($archivePath);
        file_put_contents($archivePath, $xlsx);
        $archive = new \ZipArchive;

        try {
            $this->assertTrue($archive->open($archivePath));
            $worksheet = $archive->getFromName('xl/worksheets/sheet1.xml');
            $this->assertNotFalse($worksheet);
            $this->assertStringContainsString('Tanti', $worksheet);
            $this->assertStringNotContainsString('Asrofa', $worksheet);
        } finally {
            $archive->close();
            @unlink($archivePath);
        }
    }

    public function test_home_redirects_each_role_to_its_prototype_landing_page(): void
    {
        $category = Category::create(['name' => 'Umroh']);
        $staff = User::factory()->create(['role' => Role::CustomerService->value]);
        $manager = User::factory()->create(['role' => Role::Manajemen->value]);

        $this->actingAs($staff)->get(route('home'))
            ->assertRedirect(route('leads.index', ['category' => $category]));
        $this->actingAs($manager)->get(route('home'))
            ->assertRedirect(route('dashboard'));
    }

    /** @param array<string, int> $counts */
    private function createLead(User $user, Category $category, Source $source, string $date, array $counts = []): Lead
    {
        return Lead::create([
            'entry_date' => $date,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'source_id' => $source->id,
            ...$counts,
        ]);
    }
}
