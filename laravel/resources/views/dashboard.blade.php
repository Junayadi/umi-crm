@extends('layouts.app')

@section('title', $title)

@section('content')
@php
    $colorKeys = ['cold' => 'cold', 'warm' => 'warm', 'potential' => 'pot', 'closing' => 'close', 'cancel' => 'cancel'];
    $cardOrder = ['closing', 'cold', 'warm', 'potential', 'cancel'];
    $chartWidth = max(720, count($chartGroups) * 100 + 60);
    $chartLeft = 42;
    $chartRight = 12;
    $chartTopPadding = 18;
    $chartBottom = 38;
    $plotWidth = $chartWidth - $chartLeft - $chartRight;
    $plotHeight = 290 - $chartTopPadding - $chartBottom;
    $groupWidth = $plotWidth / max(count($chartGroups), 1);
    $barWidth = min(26, $groupWidth * .8 / max(count($statuses), 1));
    $maxClosingSource = max([1, ...array_column($closingBySource, 'value')]);
    $maxClosingCategory = max([1, ...array_column($closingByCategory, 'value')]);
@endphp

<div class="ph">
  <div>
    <h1>{{ $title }}</h1>
    <p class="sub">{{ $isCustomerService ? 'Perkembangan prospek Anda sendiri.' : 'Pantau prospek seluruh staf Customer Service.' }}</p>
  </div>
</div>

<form class="fl dashboard-filters" method="GET" action="{{ route('dashboard') }}" id="dashboard-filters">
  <label>Dari Tanggal
    <input type="date" name="from" value="{{ $from }}" max="{{ today()->toDateString() }}" required>
  </label>
  <label>Sampai Tanggal
    <input type="date" name="to" value="{{ $to }}" max="{{ today()->toDateString() }}" required>
  </label>
  @unless ($isCustomerService)
    <label>Staf CS
      <select name="staff">
        <option value="">Semua CS</option>
        @foreach ($staff as $person)
          <option value="{{ $person->id }}" @selected((string) $selectedStaff === (string) $person->id)>{{ $person->name }}</option>
        @endforeach
      </select>
    </label>
  @endunless
  <label>Kategori Layanan
    <select name="category">
      <option value="">Semua</option>
      @foreach ($categories as $category)
        <option value="{{ $category->id }}" @selected((string) $selectedCategory === (string) $category->id)>{{ $category->name }}</option>
      @endforeach
    </select>
  </label>
  <label>Sumber Leads
    <select name="source">
      <option value="">Semua</option>
      @foreach ($sources as $source)
        <option value="{{ $source->id }}" @selected((string) $selectedSource === (string) $source->id)>{{ $source->name }}</option>
      @endforeach
    </select>
  </label>
  <div class="filter-actions">
    <button class="btn" type="submit">Terapkan</button>
    <a class="btn ghost" href="{{ route('dashboard') }}">Reset</a>
    @unless ($isCustomerService)
      <button class="btn acc" type="submit" formaction="{{ route('dashboard.export') }}">Export ke Excel (.xlsx)</button>
    @endunless
  </div>
  <p class="dashboard-hint">Rentang tanggal maksimal 31 hari. Filter diperbarui otomatis saat nilai berubah.</p>
  @if ($filterErrors->isNotEmpty())
    <div class="dupm" role="alert">{{ $filterErrors->first() }}</div>
  @endif
</form>

<div class="cards">
  @foreach ($cardOrder as $key)
    @php $label = $statuses[$key]; @endphp
    <div class="card {{ $key === 'closing' ? 'big' : '' }}" style="--k:var(--c-{{ $colorKeys[$key] }})">
      <div class="l">Total {{ $label }}</div>
      <div class="v">{{ number_format((int) ($totals->{$key} ?? 0), 0, ',', '.') }}</div>
      <div class="s">{{ $periodLabel }}</div>
    </div>
  @endforeach
</div>

@if (! $totals || (int) $totals->entries === 0)
  <p class="empty panel">Belum ada data untuk filter ini. Coba ubah tanggal atau filter lain.</p>
@else
  @unless ($isCustomerService)
    <div class="two">
      <section class="panel">
        <h2 class="h2">Closing per sumber leads</h2>
        @foreach ($closingBySource as $item)
          <div class="hb">
            <span>{{ $item['name'] }}</span>
            <i><b style="width:{{ $item['value'] / $maxClosingSource * 100 }}%"></b></i>
            <em>{{ $item['value'] }}</em>
          </div>
        @endforeach
      </section>
      <section class="panel">
        <h2 class="h2">Closing per kategori layanan</h2>
        @foreach ($closingByCategory as $item)
          <div class="hb">
            <span>{{ $item['name'] }}</span>
            <i><b style="width:{{ $item['value'] / $maxClosingCategory * 100 }}%"></b></i>
            <em>{{ $item['value'] }}</em>
          </div>
        @endforeach
      </section>
    </div>
  @endunless

  <section class="panel mt">
    <div class="chead">
      <h2>{{ $isCustomerService ? 'Prospek Anda per sumber leads' : 'Performa per staf' }}</h2>
      <div class="lg">
        @foreach ($statuses as $key => $label)
          <span><i style="background:var(--c-{{ $colorKeys[$key] }})"></i>{{ $label }}</span>
        @endforeach
      </div>
    </div>
    <div class="chart-scroll">
      <svg viewBox="0 0 {{ $chartWidth }} 290" role="img" aria-label="Grafik batang prospek {{ $isCustomerService ? 'per sumber leads' : 'per staf' }}" style="min-width:{{ $chartWidth }}px">
        @for ($tick = 0; $tick <= 4; $tick++)
          @php $y = $chartTopPadding + $plotHeight - ($plotHeight * $tick / 4); @endphp
          <line x1="{{ $chartLeft }}" x2="{{ $chartWidth - $chartRight }}" y1="{{ $y }}" y2="{{ $y }}" class="gl2" />
          <text x="{{ $chartLeft - 8 }}" y="{{ $y + 4 }}" text-anchor="end" class="ax">{{ $chartStep * $tick }}</text>
        @endfor
        @foreach ($chartGroups as $groupIndex => $group)
          @php
              $barStart = $chartLeft + $groupWidth * $groupIndex + ($groupWidth - $barWidth * count($statuses)) / 2;
          @endphp
          @foreach ($statuses as $key => $label)
            @php
                $value = $group['values'][$key];
                $height = $plotHeight * $value / $chartTop;
                $x = $barStart + $barWidth * $loop->index;
            @endphp
            <rect x="{{ $x + 1 }}" y="{{ $chartTopPadding + $plotHeight - $height }}" width="{{ max(1, $barWidth - 2) }}" height="{{ $height }}" rx="3" style="fill:var(--c-{{ $colorKeys[$key] }})">
              <title>{{ $group['name'] }}, {{ $label }}: {{ $value }}</title>
            </rect>
            <text x="{{ $x + $barWidth / 2 }}" y="{{ $chartTopPadding + $plotHeight - $height - 4 }}" text-anchor="middle" class="vl">{{ $value }}</text>
          @endforeach
          <text x="{{ $chartLeft + $groupWidth * $groupIndex + $groupWidth / 2 }}" y="{{ 290 - 10 }}" text-anchor="middle" class="ax b">{{ $group['name'] }}</text>
        @endforeach
      </svg>
    </div>
  </section>

  <section class="panel mt">
    <div class="chead">
      <h2>Rincian entri ({{ $totals->entries }} baris)</h2>
      @if ($leads->hasPages())
        <span class="tag">Halaman {{ $leads->currentPage() }}</span>
      @endif
    </div>
    <div class="gw solo tall">
      <table class="sheet">
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>Nama Admin</th>
            <th>Kategori</th>
            <th>Sumber Leads</th>
            @foreach ($statuses as $label)
              <th class="n">{{ $label }}</th>
            @endforeach
          </tr>
        </thead>
        <tbody>
          @foreach ($leads as $lead)
            <tr>
              <td>{{ $lead->entry_date->locale('id')->translatedFormat('j M Y') }}</td>
              <td>{{ $lead->user->name }}</td>
              <td>{{ $lead->category->name }}</td>
              <td>{{ $lead->source->name }}</td>
              @foreach ($statuses as $key => $label)
                <td class="n">{{ $lead->{$key} }}</td>
              @endforeach
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @if ($leads->hasPages())
          <div class="pg">
        @if ($leads->previousPageUrl())
          <a href="{{ $leads->previousPageUrl() }}">&larr; Sebelumnya</a>
        @endif
        <span>Halaman {{ $leads->currentPage() }}</span>
        @if ($leads->nextPageUrl())
          <a href="{{ $leads->nextPageUrl() }}">Berikutnya &rarr;</a>
        @endif
      </div>
    @endif
  </section>
@endif
@endsection

@push('scripts')
<script>
(function () {
  var filters = document.getElementById('dashboard-filters');
  if (!filters) return;

  filters.addEventListener('change', function (event) {
    if (event.target.matches('input, select')) filters.requestSubmit();
  });
})();
</script>
@endpush
