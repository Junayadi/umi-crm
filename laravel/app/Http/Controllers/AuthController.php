<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'role' => ['required', Rule::enum(Role::class)],
        ], [
            'email.required' => 'Masukkan email yang valid.',
            'email.email' => 'Masukkan email yang valid.',
            'password.required' => 'Password belum diisi.',
            'role.required' => 'Pilih peran terlebih dahulu.',
            'role.enum' => 'Pilih peran terlebih dahulu.',
        ]);

        // Email atau password salah: satu pesan umum supaya orang luar tidak bisa
        // menebak email mana yang terdaftar.
        if (! Auth::validate(['email' => $data['email'], 'password' => $data['password']])) {
            throw ValidationException::withMessages(['email' => 'Email atau password salah.']);
        }

        // Dari sini password sudah benar, jadi pesan yang lebih spesifik aman ditampilkan.
        $user = User::where('email', $data['email'])->firstOrFail();

        if (! $user->is_active) {
            throw ValidationException::withMessages(['email' => 'Akun ini nonaktif. Hubungi Supervisor.']);
        }

        if ($user->role->value !== $data['role']) {
            throw ValidationException::withMessages(['role' => 'Peran yang dipilih tidak sesuai dengan akun ini.']);
        }

        Auth::login($user);
        $request->session()->regenerate();

        if ($user->must_change_password) {
            return redirect()->route('password.edit');
        }

        return redirect()->intended(route('home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
