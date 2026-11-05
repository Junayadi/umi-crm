<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Source;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MasterDataController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureSupervisor($request);

        return view('master-data', [
            'users' => User::query()->orderBy('name')->get(),
            'sources' => Source::query()->orderBy('id')->get(),
            'categories' => Category::query()->orderBy('id')->get(),
            'roles' => Role::cases(),
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $this->ensureSupervisor($request);
        $request->merge(['email' => mb_strtolower(trim($request->input('email', '')))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::enum(Role::class)],
        ], [
            'name.required' => 'Nama staf wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Masukkan email yang valid.',
            'email.unique' => 'Email ini sudah terdaftar.',
            'role.enum' => 'Pilih peran yang valid.',
        ]);

        $temporaryPassword = Str::password(16);
        $user = DB::transaction(function () use ($data, $temporaryPassword): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => mb_strtolower($data['email']),
                'password' => Hash::make($temporaryPassword),
                'role' => $data['role'],
                'is_active' => true,
                'must_change_password' => true,
            ]);

            AuditLog::recordChange('tambah', $user, 'Akun staf ditambahkan.', null, [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->label(),
                'status' => 'Aktif',
            ]);

            return $user;
        });

        return redirect()->route('master-data.index')->with([
            'status' => 'Akun berhasil dibuat.',
            'temporary_password' => $temporaryPassword,
            'temporary_password_user' => $user->name,
        ]);
    }

    public function toggleUser(Request $request, User $user): RedirectResponse
    {
        $this->ensureSupervisor($request);
        abort_if((int) $request->user()->id === (int) $user->id, 404);

        if ($user->is_active && $user->isSupervisor()) {
            $activeSupervisors = User::query()
                ->where('role', Role::Supervisor->value)
                ->where('is_active', true)
                ->count();

            if ($activeSupervisors <= 1) {
                return back()->withErrors(['user' => 'Supervisor aktif terakhir tidak bisa dinonaktifkan.']);
            }
        }

        $old = ['status' => $user->is_active ? 'Aktif' : 'Nonaktif'];
        DB::transaction(function () use ($user, $old): void {
            $user->forceFill(['is_active' => ! $user->is_active])->save();
            AuditLog::recordChange('ubah', $user, 'Status akun staf diubah.', $old, [
                'status' => $user->is_active ? 'Aktif' : 'Nonaktif',
            ]);
        });

        return back()->with('status', $user->is_active ? 'Akun diaktifkan.' : 'Akun dinonaktifkan.');
    }

    public function storeSource(Request $request): RedirectResponse
    {
        $this->ensureSupervisor($request);

        $data = $request->validate([
            'source_name' => ['required', 'string', 'max:100', Rule::unique('sources', 'name')],
        ], [
            'source_name.required' => 'Nama sumber leads wajib diisi.',
            'source_name.unique' => 'Sumber leads ini sudah ada.',
        ]);

        DB::transaction(function () use ($data): void {
            $source = Source::create(['name' => trim($data['source_name']), 'is_active' => true]);
            AuditLog::recordChange('tambah', $source, 'Sumber leads ditambahkan.', null, [
                'nama' => $source->name,
                'status' => 'Aktif',
            ]);
        });

        return back()->with('status', 'Sumber leads ditambahkan.');
    }

    public function toggleSource(Request $request, Source $source): RedirectResponse
    {
        $this->ensureSupervisor($request);

        if ($source->is_active && Source::active()->count() <= 1) {
            return back()->withErrors(['source' => 'Minimal satu sumber leads harus tetap aktif.']);
        }

        $old = ['status' => $source->is_active ? 'Aktif' : 'Nonaktif'];
        DB::transaction(function () use ($source, $old): void {
            $source->is_active = ! $source->is_active;
            $source->save();
            AuditLog::recordChange('ubah', $source, 'Status sumber leads diubah.', $old, [
                'status' => $source->is_active ? 'Aktif' : 'Nonaktif',
            ]);
        });

        return back()->with('status', $source->is_active ? 'Sumber leads diaktifkan.' : 'Sumber leads dinonaktifkan.');
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $this->ensureSupervisor($request);

        $data = $request->validate([
            'category_name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')],
        ], [
            'category_name.required' => 'Nama kategori layanan wajib diisi.',
            'category_name.unique' => 'Kategori layanan ini sudah ada.',
        ]);

        DB::transaction(function () use ($data): void {
            $category = Category::create(['name' => trim($data['category_name']), 'is_active' => true]);
            AuditLog::recordChange('tambah', $category, 'Kategori layanan ditambahkan.', null, [
                'nama' => $category->name,
                'status' => 'Aktif',
            ]);
        });

        return back()->with('status', 'Kategori layanan ditambahkan.');
    }

    public function toggleCategory(Request $request, Category $category): RedirectResponse
    {
        $this->ensureSupervisor($request);

        if ($category->is_active && Category::active()->count() <= 1) {
            return back()->withErrors(['category' => 'Minimal satu kategori layanan harus tetap aktif.']);
        }

        $old = ['status' => $category->is_active ? 'Aktif' : 'Nonaktif'];
        DB::transaction(function () use ($category, $old): void {
            $category->is_active = ! $category->is_active;
            $category->save();
            AuditLog::recordChange('ubah', $category, 'Status kategori layanan diubah.', $old, [
                'status' => $category->is_active ? 'Aktif' : 'Nonaktif',
            ]);
        });

        return back()->with('status', $category->is_active ? 'Kategori layanan diaktifkan.' : 'Kategori layanan dinonaktifkan.');
    }

    private function ensureSupervisor(Request $request): void
    {
        abort_unless($request->user()->isSupervisor(), 403);
    }
}
