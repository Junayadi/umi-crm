<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(Request $request): View
    {
        return view('password-change', [
            'mustChangePassword' => $request->user()->must_change_password,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'current_password.current_password' => 'Password saat ini tidak sesuai.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak sama.',
            'password.different' => 'Password baru harus berbeda dari password saat ini.',
        ]);

        $user = $request->user();
        $user->forceFill([
            'password' => Hash::make($data['password']),
            'must_change_password' => false,
        ])->save();

        $request->session()->regenerate();

        return redirect()->route('home')->with('status', 'Password berhasil diperbarui.');
    }

    public function reset(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isSupervisor(), 403);
        abort_if((int) $request->user()->id === (int) $user->id, 404);

        $temporaryPassword = Str::password(16);
        $wasPasswordChangeRequired = $user->must_change_password;
        DB::transaction(function () use ($user, $temporaryPassword, $wasPasswordChangeRequired): void {
            $user->forceFill([
                'password' => Hash::make($temporaryPassword),
                'must_change_password' => true,
            ])->save();

            AuditLog::recordChange('reset_password', $user, 'Password akun direset oleh Supervisor.', [
                'wajib_ganti_password' => $wasPasswordChangeRequired,
            ], [
                'wajib_ganti_password' => true,
            ]);
        });

        return redirect()->route('master-data.index')->with([
            'temporary_password' => $temporaryPassword,
            'temporary_password_user' => $user->name,
        ]);
    }
}
