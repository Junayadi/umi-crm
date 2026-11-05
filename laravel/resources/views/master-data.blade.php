@extends('layouts.app')

@section('title', 'Master Data')

@section('content')
<div class="ph">
  <div>
    <h1>Master Data</h1>
    <p class="sub">Kelola staf dan akun, sumber leads, serta kategori layanan. Data yang dinonaktifkan tidak muncul di form input; riwayatnya tetap tersimpan.</p>
  </div>
</div>

@if ($errors->any())
  <div class="dupm master-errors" role="alert">
    @foreach ($errors->all() as $message)
      <div>{{ $message }}</div>
    @endforeach
  </div>
@endif

@if (session('temporary_password'))
  <div class="dupm password-temporary" role="status">
    <strong>Password sementara untuk {{ session('temporary_password_user') }}</strong>
    <code>{{ session('temporary_password') }}</code>
    <span>Salin dan berikan password ini langsung kepada pengguna. Password hanya ditampilkan sekali; pengguna wajib menggantinya saat login.</span>
  </div>
@endif

<div class="mg">
  <section class="panel">
    <h2 class="h2">Staf dan akun</h2>
    <form class="add master-user-form" method="POST" action="{{ route('master-data.users.store') }}">
      @csrf
      <input name="name" value="{{ old('name') }}" placeholder="Nama staf" aria-label="Nama staf" maxlength="255" required>
      <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" aria-label="Email" maxlength="255" required>
      <select name="role" aria-label="Peran" required>
        @foreach ($roles as $role)
          <option value="{{ $role->value }}" @selected(old('role', \App\Enums\Role::CustomerService->value) === $role->value)>{{ $role->label() }}</option>
        @endforeach
      </select>
      <button class="btn" type="submit">Tambah staf</button>
    </form>
    <div class="gw solo">
      <table class="sheet">
        <thead>
          <tr><th>Nama</th><th>Email</th><th>Peran</th><th>Status</th><th>Aksi</th></tr>
        </thead>
        <tbody>
          @forelse ($users as $user)
            <tr>
              <td>{{ $user->name }}</td>
              <td>{{ $user->email }}</td>
              <td>{{ $user->role->label() }}</td>
              <td>
                <span class="pill {{ $user->is_active ? 'on' : '' }}">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                @if ($user->must_change_password)
                  <div class="tag">Wajib ganti password</div>
                @endif
              </td>
              <td class="master-actions">
                @if ((int) $user->id === (int) auth()->id())
                  <span class="tag">Akun Anda</span>
                @else
                  <form class="inline" method="POST" action="{{ route('master-data.users.status', ['user' => $user]) }}" onsubmit="return confirm('Ubah status akun {{ $user->is_active ? 'menjadi nonaktif' : 'menjadi aktif' }}?')">
                    @csrf
                    @method('PATCH')
                    <button class="lk {{ $user->is_active ? 'd' : '' }}" type="submit">{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                  </form>
                  <form class="inline" method="POST" action="{{ route('password.resets.store', ['user' => $user]) }}" onsubmit="return confirm('Reset password akun ini?')">
                    @csrf
                    <button class="lk" type="submit">Reset password</button>
                  </form>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="empty">Belum ada akun.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

  <section class="panel">
    <h2 class="h2">Sumber leads</h2>
    <form class="add master-catalog-form" method="POST" action="{{ route('master-data.sources.store') }}">
      @csrf
      <input name="source_name" value="{{ old('source_name') }}" placeholder="Nama sumber" aria-label="Nama sumber" maxlength="100" required>
      <button class="btn" type="submit">Tambah</button>
    </form>
    <div class="gw solo">
      <table class="sheet">
        <thead><tr><th>Nama</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
          @forelse ($sources as $source)
            <tr>
              <td>{{ $source->name }}</td>
              <td><span class="pill {{ $source->is_active ? 'on' : '' }}">{{ $source->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
              <td>
                <form class="inline" method="POST" action="{{ route('master-data.sources.status', ['source' => $source]) }}" onsubmit="return confirm('Ubah status sumber leads ini?')">
                  @csrf
                  @method('PATCH')
                  <button class="lk {{ $source->is_active ? 'd' : '' }}" type="submit">{{ $source->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="3" class="empty">Belum ada sumber leads.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

  <section class="panel">
    <h2 class="h2">Kategori layanan</h2>
    <form class="add master-catalog-form" method="POST" action="{{ route('master-data.categories.store') }}">
      @csrf
      <input name="category_name" value="{{ old('category_name') }}" placeholder="Nama kategori" aria-label="Nama kategori" maxlength="100" required>
      <button class="btn" type="submit">Tambah</button>
    </form>
    <div class="gw solo">
      <table class="sheet">
        <thead><tr><th>Nama</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
          @forelse ($categories as $category)
            <tr>
              <td>{{ $category->name }}</td>
              <td><span class="pill {{ $category->is_active ? 'on' : '' }}">{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
              <td>
                <form class="inline" method="POST" action="{{ route('master-data.categories.status', ['category' => $category]) }}" onsubmit="return confirm('Ubah status kategori layanan ini?')">
                  @csrf
                  @method('PATCH')
                  <button class="lk {{ $category->is_active ? 'd' : '' }}" type="submit">{{ $category->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="3" class="empty">Belum ada kategori layanan.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
</div>
@endsection
