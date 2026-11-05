@extends('layouts.app')

@section('title', $mustChangePassword ? 'Ganti Password Wajib' : 'Ubah Password')

@section('content')
<div class="ph">
  <div>
    <h1>{{ $mustChangePassword ? 'Ganti Password' : 'Ubah Password' }}</h1>
    <p class="sub">Gunakan password baru minimal 8 karakter.</p>
  </div>
</div>

<div class="password-panel panel">
  @if ($mustChangePassword)
    <div class="dupm" role="status">Password akun ini direset oleh Supervisor. Ganti password sementara sebelum melanjutkan ke aplikasi.</div>
  @endif

  <form method="POST" action="{{ route('password.update') }}" class="password-form">
    @csrf
    @method('PUT')

    <label>Password Saat Ini
      <input type="password" name="current_password" autocomplete="current-password" required autofocus>
    </label>
    <label>Password Baru
      <input type="password" name="password" minlength="8" autocomplete="new-password" required>
    </label>
    <label>Konfirmasi Password Baru
      <input type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required>
    </label>

    @if ($errors->any())
      <div class="dupm" role="alert">{{ $errors->first() }}</div>
    @endif

    <button class="btn" type="submit">Simpan Password Baru</button>
  </form>
</div>
@endsection
