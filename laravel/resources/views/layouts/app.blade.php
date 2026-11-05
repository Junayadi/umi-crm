<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'UMI CRM') | UMI CRM</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/umi.css') }}">
</head>
<body class="app">
@php
    $me = auth()->user();
    // Menu kategori: hanya kategori aktif. (Nanti bisa dipindah ke View Composer.)
    $navCategories = \App\Models\Category::active()->orderBy('id')->get();
    $activeCategoryId = request()->route('category')?->id;
    $isCustomerService = $me->isCustomerService();
@endphp

<div id="app" class="shell">
  <aside id="side" class="side">
    <div class="brand">
      <div class="logo"><img src="{{ asset('images/umi-logo-only.png') }}" alt="Logo UMI" onerror="this.style.display='none'"></div>
      <div><b>UMI CRM</b><small>PT UMI Tour Travel</small></div>
    </div>

    <nav aria-label="Menu utama">
      @unless ($isCustomerService)
        <div class="gl">Manajemen</div>
        <a class="ni {{ request()->routeIs('dashboard') ? 'on' : '' }}" href="{{ route('dashboard') }}">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20V10M10 20V4M16 20v-8M22 20H2"/></svg>
          <span>Dashboard Analitik</span>
        </a>
      @endunless

      <div class="gl">Customer Service</div>
      <button class="ni" type="button" id="nav-toggle" aria-expanded="true">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
        <span>Input Data Leads</span>
        <svg class="chev" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
      </button>
      <div class="subm" id="nav-sub">
        @foreach ($navCategories as $c)
          <a class="ni {{ $activeCategoryId === $c->id ? 'on' : '' }}" href="{{ route('leads.index', ['category' => $c]) }}">{{ $c->name }}</a>
        @endforeach
      </div>
      @if ($isCustomerService)
        <a class="ni {{ request()->routeIs('dashboard') ? 'on' : '' }}" href="{{ route('dashboard') }}">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20V10M10 20V4M16 20v-8M22 20H2"/></svg>
          <span>Diagram Saya</span>
        </a>
      @endif
      @if (auth()->user()->isSupervisor())
        <div class="gl">Pengelolaan</div>
        <a class="ni {{ request()->routeIs('master-data.*') ? 'on' : '' }}" href="{{ route('master-data.index') }}">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3m14 0h3"/></svg>
          <span>Master Data</span>
        </a>
        <a class="ni {{ request()->routeIs('audit-logs.*') ? 'on' : '' }}" href="{{ route('audit-logs.index') }}">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
          <span>Log Perubahan</span>
        </a>
      @endif
    </nav>

    <div class="user">
      <b>{{ $me->name }}</b>
      <span>Peran: {{ $me->role->label() }}</span>
      <a class="ni account-link {{ request()->routeIs('password.edit') ? 'on' : '' }}" href="{{ route('password.edit') }}">Ubah Password</a>
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="btn ghost logout-btn" type="submit">Logout</button>
      </form>
    </div>
  </aside>
  <div class="scrim" id="scrim"></div>

  <div>
    <header class="tb">
      <button id="menu" type="button" aria-label="Buka menu"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
      UMI CRM
    </header>
    <main class="main">
      @yield('content')
    </main>
  </div>
</div>

<div id="toast" role="status">{{ session('status') }}</div>

<script>
(function () {
  var side = document.getElementById('side'), scrim = document.getElementById('scrim');
  document.getElementById('menu').addEventListener('click', function () { side.classList.add('open'); scrim.classList.add('show'); });
  scrim.addEventListener('click', function () { side.classList.remove('open'); scrim.classList.remove('show'); });

  var tg = document.getElementById('nav-toggle'), sub = document.getElementById('nav-sub');
  tg.addEventListener('click', function () {
    var open = tg.getAttribute('aria-expanded') === 'true';
    tg.setAttribute('aria-expanded', String(!open));
    sub.hidden = open;
  });

  var t = document.getElementById('toast');
  if (t.textContent.trim()) {
    t.classList.add('show');
    setTimeout(function () { t.classList.remove('show'); }, 3600);
  }
})();
</script>
@stack('scripts')
</body>
</html>
