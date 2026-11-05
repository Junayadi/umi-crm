@extends('layouts.app')

@section('title', 'Input Data Leads: '.$category->name)

@section('content')
@php
    $isCs = auth()->user()->isCustomerService();
    $formAction = $editing
        ? route('leads.update', ['category' => $category, 'lead' => $editing])
        : route('leads.store', ['category' => $category]);
@endphp

<div class="ph">
  <div>
    <h1>Input Data Leads: {{ $category->name }}</h1>
    <p class="sub">Isi jumlah prospek hari ini untuk tiap status. Satu baris per tanggal, admin, sumber, dan kategori; jika sudah ada, edit baris itu.</p>
  </div>
</div>

<form method="POST" action="{{ $formAction }}" id="lead-form">
  @csrf
  @if ($editing)
    @method('PUT')
  @endif
  <input type="hidden" name="confirm_high" id="confirm_high" value="0">

  <div class="qf">
    <label>Tanggal
      <input type="date" name="entry_date" required
             value="{{ old('entry_date', $editing ? $editing->entry_date->toDateString() : today()->toDateString()) }}"
             max="{{ today()->toDateString() }}"
             @unless ($editing) min="{{ $minDate }}" @endunless>
    </label>

    <label>Nama Admin
      @if ($isCs)
        <input value="{{ auth()->user()->name }}" disabled title="Terisi otomatis dari akun yang login">
      @else
        <select name="user_id" required>
          @foreach ($admins as $admin)
            <option value="{{ $admin->id }}" @selected((int) old('user_id', $editing?->user_id) === $admin->id)>{{ $admin->name }}</option>
          @endforeach
        </select>
      @endif
    </label>

    <label>Sumber Leads
      <select name="source_id" required>
        @foreach ($sources as $source)
          <option value="{{ $source->id }}" @selected((int) old('source_id', $editing?->source_id) === $source->id)>{{ $source->name }}</option>
        @endforeach
      </select>
    </label>

    @foreach ($statuses as $key => $label)
      <label class="w">{{ $label }}
        <input type="number" name="{{ $key }}" min="0" inputmode="numeric"
               value="{{ old($key, $editing ? $editing->{$key} : 0) }}">
      </label>
    @endforeach

    <div class="ac">
      <button class="btn" type="submit">{{ $editing ? 'Perbarui Data' : 'Simpan Data' }}</button>
      @if ($editing)
        <a class="btn ghost" href="{{ route('leads.index', ['category' => $category]) }}">Batal</a>
      @endif
    </div>

    @if ($errors->any())
      <div class="dupm" role="alert">
        @foreach ($errors->all() as $message)
          <div>{{ $message }}</div>
        @endforeach
      </div>
    @endif

    @if ($duplicate)
      <div class="dupm" role="alert">
        Entri ini sudah ada ({{ collect($statuses)->map(fn ($label, $key) => $label.' '.$duplicate->{$key})->implode(', ') }}).
        @can('update', $duplicate)
          <a class="lk" href="{{ route('leads.index', ['category' => $category, 'edit' => $duplicate->id]) }}">Edit entri itu</a>
        @endcan
      </div>
    @endif

    @if (session('high_warning'))
      <div class="dupm" id="high-warn" role="alert">
        Ada angka di atas 10 ({{ session('high_warning') }}). Lanjutkan menyimpan?
        <button class="lk" type="button" id="force-save">Ya, simpan</button>
        <button class="lk" type="button" id="recheck">Periksa lagi</button>
      </div>
    @endif
  </div>
</form>

<div class="gw">
  <table class="sheet">
    <thead>
      <tr>
        <th class="rn">#</th>
        <th>Tanggal</th>
        <th>Nama Admin</th>
        <th>Sumber Leads</th>
        @foreach ($statuses as $label)
          <th class="n">{{ $label }}</th>
        @endforeach
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($leads as $lead)
        <tr class="{{ $editing && $editing->id === $lead->id ? 'ed' : '' }}">
          <td class="rn">{{ $leads->firstItem() + $loop->index }}</td>
          <td>{{ $lead->entry_date->translatedFormat('j M Y') }}</td>
          <td>{{ $lead->user->name }}</td>
          <td>{{ $lead->source->name }}</td>
          @foreach ($statuses as $key => $label)
            <td class="n">{{ $lead->{$key} }}</td>
          @endforeach
          <td>
            @can('update', $lead)
              <a class="lk" href="{{ route('leads.index', ['category' => $category, 'edit' => $lead->id]) }}">Edit</a>
              <form class="inline" method="POST" action="{{ route('leads.destroy', ['category' => $category, 'lead' => $lead]) }}"
                    onsubmit="return confirm('Hapus entri ini?')">
                @csrf
                @method('DELETE')
                <button class="lk d" type="submit">Hapus</button>
              </form>
            @else
              <span class="tag">{{ $lead->user_id === auth()->id() ? 'Terkunci' : 'Hanya lihat' }}</span>
            @endcan
          </td>
        </tr>
      @empty
        <tr><td colspan="{{ 5 + count($statuses) }}" class="empty">Belum ada entri untuk {{ $category->name }}.</td></tr>
      @endforelse
    </tbody>
    @if ($totals && $totals->entries > 0)
      <tfoot>
        <tr>
          <td class="rn"></td>
          <td colspan="3">Jumlah {{ $totals->entries }} entri</td>
          @foreach ($statuses as $key => $label)
            <td class="n">{{ $totals->{$key} }}</td>
          @endforeach
          <td></td>
        </tr>
      </tfoot>
    @endif
  </table>
</div>

@if ($leads->hasPages())
  <div class="pg">
    @if ($leads->previousPageUrl())
      <a href="{{ $leads->previousPageUrl() }}">&larr; Sebelumnya</a>
    @endif
    <span>Halaman {{ $leads->currentPage() }}</span>
    @if ($leads->hasMorePages())
      <a href="{{ $leads->nextPageUrl() }}">Berikutnya &rarr;</a>
    @endif
  </div>
@endif
@endsection

@push('scripts')
<script>
(function () {
  var force = document.getElementById('force-save'), recheck = document.getElementById('recheck');
  if (force) {
    force.addEventListener('click', function () {
      document.getElementById('confirm_high').value = '1';
      document.getElementById('lead-form').requestSubmit();
    });
  }
  if (recheck) {
    recheck.addEventListener('click', function () { document.getElementById('high-warn').hidden = true; });
  }
})();
</script>
@endpush
