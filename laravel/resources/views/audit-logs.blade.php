@extends('layouts.app')

@section('title', 'Log Perubahan')

@section('content')
<div class="ph">
  <div>
    <h1>Log Perubahan</h1>
    <p class="sub">Riwayat aktivitas leads dan pengelolaan data, termasuk siapa yang melakukan perubahan dan kapan.</p>
  </div>
</div>

@if ($errors->any())
  <div class="dupm audit-errors" role="alert">
    @foreach ($errors->all() as $message)
      <div>{{ $message }}</div>
    @endforeach
  </div>
@endif

<form class="fl audit-filters" method="GET" action="{{ route('audit-logs.index') }}">
  <label>Dari Tanggal
    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}">
  </label>
  <label>Sampai Tanggal
    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}">
  </label>
  <label>Pelaku
    <select name="user_id">
      <option value="">Semua pengguna</option>
      @foreach ($users as $person)
        <option value="{{ $person->id }}" @selected((string) ($filters['user_id'] ?? '') === (string) $person->id)>{{ $person->name }}</option>
      @endforeach
    </select>
  </label>
  <label>Aktivitas
    <select name="action">
      <option value="">Semua aktivitas</option>
      @foreach ($actions as $action)
        <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ match ($action) { 'tambah' => 'Tambah', 'ubah' => 'Ubah', 'hapus' => 'Hapus', 'reset_password' => 'Reset password' } }}</option>
      @endforeach
    </select>
  </label>
  <div class="audit-filter-actions">
    <button class="btn" type="submit">Terapkan filter</button>
    <a class="btn ghost" href="{{ route('audit-logs.index') }}">Reset</a>
  </div>
</form>

<section class="panel">
  <div class="chead">
    <h2>Aktivitas terbaru</h2>
    <span class="tag">{{ $logs->total() }} catatan</span>
  </div>
  <div class="gw solo audit-table-wrap">
    <table class="sheet audit-table">
      <thead>
        <tr><th>Waktu</th><th>Pelaku</th><th>Aktivitas</th><th>Objek dan ringkasan</th><th>Detail</th></tr>
      </thead>
      <tbody>
        @forelse ($logs as $log)
          @php
            $targetType = class_basename($log->auditable_type ?? ($log->lead_id ? \App\Models\Lead::class : ''));
            $targetLabel = match ($targetType) {
                'Lead' => 'Data leads',
                'User' => 'Akun staf',
                'Source' => 'Sumber leads',
                'Category' => 'Kategori layanan',
                default => 'Data',
            };
            $actionLabel = match ($log->action) {
                'tambah' => 'Tambah',
                'ubah' => 'Ubah',
                'hapus' => 'Hapus',
                'reset_password' => 'Reset password',
                default => ucfirst($log->action),
            };
          @endphp
          <tr>
            <td>{{ $log->created_at->locale('id')->translatedFormat('j M Y, H:i') }}</td>
            <td>{{ $log->user?->name ?? 'Pengguna dihapus' }}</td>
            <td><span class="pill {{ in_array($log->action, ['tambah', 'reset_password'], true) ? 'on' : '' }}">{{ $actionLabel }}</span></td>
            <td class="audit-summary"><strong>{{ $targetLabel }}</strong><span>{{ $log->description }}</span></td>
            <td>
              @if ($log->old_values || $log->new_values)
                <details class="audit-details">
                  <summary>Lihat detail</summary>
                  <div class="audit-values">
                    @foreach ([['Sebelum', $log->old_values], ['Sesudah', $log->new_values]] as [$heading, $values])
                      @if ($values)
                        <div>
                          <b>{{ $heading }}</b>
                          <dl>
                            @foreach ($values as $key => $value)
                              <dt>{{ str($key)->replace('_', ' ')->title() }}</dt>
                              <dd>{{ is_bool($value) ? ($value ? 'Ya' : 'Tidak') : $value }}</dd>
                            @endforeach
                          </dl>
                        </div>
                      @endif
                    @endforeach
                  </div>
                </details>
              @else
                <span class="tag">Tidak ada rincian</span>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="empty">Belum ada aktivitas yang sesuai dengan filter.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if ($logs->hasPages())
    <div class="pg">
      @if ($logs->previousPageUrl())
        <a href="{{ $logs->previousPageUrl() }}">&larr; Sebelumnya</a>
      @endif
      <span>Halaman {{ $logs->currentPage() }}</span>
      @if ($logs->nextPageUrl())
        <a href="{{ $logs->nextPageUrl() }}">Berikutnya &rarr;</a>
      @endif
    </div>
  @endif
</section>
@endsection
