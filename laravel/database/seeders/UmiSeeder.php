<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Category;
use App\Models\Source;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Data awal dari prototype v3.
 * Semua akun contoh memakai password: password
 * Data leads contoh (35 hari terakhir) hanya dibuat di APP_ENV=local.
 */
class UmiSeeder extends Seeder
{
    public function run(): void
    {
        $sources = collect(['Instagram', 'Facebook', 'Linktree', 'WhatsApp'])
            ->map(fn (string $name) => Source::firstOrCreate(['name' => $name]));

        $categories = collect(['Umroh', 'Haji Khusus', 'Haji Mujamalah'])
            ->map(fn (string $name) => Category::firstOrCreate(['name' => $name]));

        $staff = collect(['Tanti', 'Asrofa', 'Nuri', 'Fajrin', 'Dadang'])
            ->map(fn (string $name) => $this->account($name, Str::lower($name).'@contoh.com', Role::CustomerService));

        $this->account('Manajemen', 'manajemen@contoh.com', Role::Manajemen);
        $this->account('Supervisor', 'supervisor@contoh.com', Role::Supervisor);

        if (app()->isLocal() && DB::table('leads')->doesntExist()) {
            $this->sampleLeads($staff, $sources, $categories);
        }
    }

    private function account(string $name, string $email, Role $role): User
    {
        $user = User::where('email', $email)->first() ?? new User;

        // forceFill: seeder tidak bergantung pada $fillable
        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => $role->value,
            'is_active' => true,
            'must_change_password' => false,
            'email_verified_at' => now(),
        ])->save();

        return $user;
    }

    /** Meniru generator data acak di prototype: 35 hari, tiap kategori x staf, hari ini selalu terisi. */
    private function sampleLeads(Collection $staff, Collection $sources, Collection $categories): void
    {
        mt_srand(11); // hasil sama setiap kali seed

        $now = now();
        $rows = [];

        for ($d = 0; $d < 35; $d++) {
            $date = today()->subDays($d)->toDateString();

            foreach ($categories as $category) {
                foreach ($staff as $user) {
                    if ($d !== 0 && mt_rand(0, 99) >= 50) {
                        continue;
                    }

                    $rows[] = [
                        'entry_date' => $date,
                        'user_id' => $user->id,
                        'source_id' => $sources[mt_rand(0, $sources->count() - 1)]->id,
                        'category_id' => $category->id,
                        'cold' => mt_rand(3, 10),
                        'warm' => mt_rand(1, 6),
                        'potential' => mt_rand(0, 4),
                        'closing' => mt_rand(0, 3),
                        'cancel' => mt_rand(0, 2),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('leads')->insert($chunk);
        }
    }
}
