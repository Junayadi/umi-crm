<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Masuk | UMI CRM</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/umi.css') }}">
</head>
<body>
<section class="login">
  <svg class="route" viewBox="0 0 1200 700" preserveAspectRatio="xMidYMid slice" aria-hidden="true"><path d="M-20 560C260 160 640 720 1220 110" fill="none" style="stroke:var(--bg);stroke-opacity:1" stroke-width="2" stroke-dasharray="2 11" stroke-linecap="round"/><circle cx="90" cy="470" r="6" style="fill:var(--bg)"/><circle cx="1110" cy="168" r="6" style="fill:var(--bg)"/></svg>

  <div class="lc">
    <div class="lh">
      <div class="logo">
        <img src="{{ asset('images/umi-logo-only.png') }}" alt="Logo UMI" onerror="this.style.display='none'">
      </div>
      <h1>CRM UMI</h1>
      <p>PT UMI TOUR TRAVEL</p>
    </div>

    <form method="POST" action="{{ route('login.store') }}">
      @csrf
      <label>Email
        <input type="email" name="email" value="{{ old('email') }}" placeholder="nama@perusahaan.com" autocomplete="username" required autofocus>
      </label>
      <label>Password
        <input type="password" name="password" placeholder="Masukkan password" autocomplete="current-password" required>
      </label>
      <label>Pilih Peran
        <select name="role" required>
          <option value="" disabled @selected(! old('role'))>Pilih peran</option>
          @foreach (\App\Enums\Role::cases() as $role)
            <option value="{{ $role->value }}" @selected(old('role') === $role->value)>{{ $role->label() }}</option>
          @endforeach
        </select>
      </label>
      <button class="btn" type="submit">Login</button>
      <p class="err" role="alert">{{ $errors->first() }}</p>
    </form>

    <p class="note">Lupa password? Hubungi Supervisor untuk mereset password.</p>
    @env('local')
      <p class="note">Akun contoh (lokal): tanti@contoh.com (Customer Service), manajemen@contoh.com (Manajemen), supervisor@contoh.com (Supervisor). Password: password</p>
    @endenv
  </div>
</section>
</body>
</html>
