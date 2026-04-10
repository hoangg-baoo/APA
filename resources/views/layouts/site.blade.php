{{-- resources/views/layouts/site.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>@yield('title', 'Aquatic Plant Advisor')</title>
  <link rel="stylesheet" href="{{ asset('assets/css/main.css') }}">
  <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="landing-body">

<header class="landing-header">
  <div class="landing-header-inner">
    <div class="landing-logo">
      <span class="logo-badge">AQ</span>
      <span class="logo-text">Aquatic Plant Advisor</span>
    </div>

    <nav class="landing-nav">
      <a href="{{ route('home') }}" class="nav-link @yield('nav_home_active')">Home</a>

      {{-- ✅ Plant Library: click to open page + hover to show dropdown categories --}}
      <div class="nav-dropdown">
        <a href="{{ route('plants.index') }}" class="nav-link nav-link-dropdown @yield('nav_plants_active')">
          Plant Library <span class="nav-caret">▾</span>
        </a>

        <div class="nav-dropdown-panel" aria-label="Plant categories dropdown">
          <div class="nav-dropdown-head">
            <div class="nav-dropdown-title">Browse by category</div>
            <div class="nav-dropdown-sub">Hover a category to see variants. Click “Plant Library” to view all plants.</div>
          </div>

          @php
            $menu = $plantNavMenu ?? [];
          @endphp

          @if(!empty($menu))
            <div class="nav-dropdown-body">
              {{-- Left: categories --}}
              <div class="nav-cat-list" id="navCatList">
                @foreach($menu as $idx => $cat)
                  <button
                    type="button"
                    class="nav-cat-item {{ $idx === 0 ? 'active' : '' }}"
                    data-cat="{{ $cat['slug'] }}"
                  >
                    <span class="nav-cat-name">{{ $cat['label'] }}</span>
                    <span class="nav-cat-arrow">›</span>
                  </button>
                @endforeach
              </div>

              {{-- Right: variants list (one panel per category) --}}
              <div class="nav-cat-panels" id="navCatPanels">
                @foreach($menu as $idx => $cat)
                  <div class="nav-cat-panel {{ $idx === 0 ? 'active' : '' }}" data-panel="{{ $cat['slug'] }}">
                    <div class="nav-panel-head">
                      <a class="nav-panel-title"
                         href="{{ route('plants.index', ['group' => $cat['slug']]) }}">
                        {{ $cat['label'] }}
                      </a>
                      <a class="nav-panel-viewall"
                         href="{{ route('plants.index', ['group' => $cat['slug']]) }}">
                        View group →
                      </a>
                    </div>

                    <div class="nav-variant-list">
                      @foreach(($cat['children'] ?? []) as $child)
                        <a class="nav-variant-item" href="{{ route('plants.show', $child['id']) }}">
                          <span class="nav-variant-dot"></span>
                          <span class="nav-variant-text">{{ $child['label'] }}</span>
                        </a>
                      @endforeach
                    </div>
                  </div>
                @endforeach
              </div>
            </div>

            <div class="nav-dropdown-foot">
              <a class="nav-dropdown-btn" href="{{ route('plants.index') }}">View all plants →</a>
            </div>
          @else
            <div class="nav-dropdown-empty">
              No categories yet. Add plants to database first.
            </div>
          @endif
        </div>
      </div>

      <a href="{{ route('tanks.my_tanks') }}" class="nav-link @yield('nav_tanks_active')">My Tanks</a>
      <a href="{{ route('qa.questions_list') }}" class="nav-link @yield('nav_qa_active')">Q&amp;A</a>
      <a href="{{ route('community.posts_list') }}" class="nav-link @yield('nav_com_active')">Community</a>
      <a href="{{ route('image.identify_plant') }}" class="nav-link @yield('nav_identify_active')">Identify</a>
    </nav>

    <div class="landing-auth" style="display:flex; align-items:center; gap:10px;">
      <a id="btn-login" href="{{ url('/login') }}" class="btn-nav">Login</a>
      <a id="btn-register" href="{{ url('/register') }}" class="btn-nav btn-nav-primary">Sign up free</a>

      <span id="hello-user" style="display:none; color:#e5e7eb; font-size:14px;">
        Hi, <strong id="hello-user-name"></strong>
      </span>

      <button id="btn-logout" class="btn-nav btn-nav-primary" style="display:none;">
        Logout
      </button>
    </div>
  </div>
</header>

<main class="landing-main">
  @yield('content')
</main>

<footer class="landing-footer">
  <div class="landing-footer-inner">
    <span>© {{ date('Y') }} Aquatic Plant Advisor. All rights reserved.</span>
    <span class="footer-links">
      <a href="#">Terms</a>
      <a href="#">Privacy</a>
    </span>
  </div>
</footer>

<script>
(() => {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  async function apiMe() {
    const res = await fetch('/api/me', {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
    });

    const json = await res.json().catch(() => null);
    if (!res.ok || !json || json.success !== true) return null;
    return json.data;
  }

  async function apiLogout() {
    const res = await fetch('/api/logout', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken,
      },
      credentials: 'same-origin',
    });
    return res.ok;
  }

  function setAuthUI(user) {
    const btnLogin = document.getElementById('btn-login');
    const btnRegister = document.getElementById('btn-register');
    const btnLogout = document.getElementById('btn-logout');

    const helloWrap = document.getElementById('hello-user');
    const helloName = document.getElementById('hello-user-name');

    if (user) {
      if (btnLogin) btnLogin.style.display = 'none';
      if (btnRegister) btnRegister.style.display = 'none';
      if (btnLogout) btnLogout.style.display = '';

      if (helloWrap) helloWrap.style.display = '';
      if (helloName) helloName.textContent = user.name || 'User';
    } else {
      if (btnLogin) btnLogin.style.display = '';
      if (btnRegister) btnRegister.style.display = '';
      if (btnLogout) btnLogout.style.display = 'none';

      if (helloWrap) helloWrap.style.display = 'none';
      if (helloName) helloName.textContent = '';
    }
  }

  function initPlantDropdown() {
    const catList = document.getElementById('navCatList');
    const panelsWrap = document.getElementById('navCatPanels');
    if (!catList || !panelsWrap) return;

    const items = Array.from(catList.querySelectorAll('.nav-cat-item'));
    const panels = Array.from(panelsWrap.querySelectorAll('.nav-cat-panel'));

    function setActive(slug) {
      items.forEach(btn => btn.classList.toggle('active', btn.dataset.cat === slug));
      panels.forEach(p => p.classList.toggle('active', p.dataset.panel === slug));
    }

    // Hover to switch category
    items.forEach(btn => {
      btn.addEventListener('mouseenter', () => setActive(btn.dataset.cat));
      btn.addEventListener('focus', () => setActive(btn.dataset.cat));
      btn.addEventListener('click', () => setActive(btn.dataset.cat));
    });
  }

  document.addEventListener('DOMContentLoaded', async () => {
    const btnLogout = document.getElementById('btn-logout');

    const me = await apiMe();
    setAuthUI(me);

    if (btnLogout) {
      btnLogout.addEventListener('click', async () => {
        await apiLogout();
        window.location.href = '{{ route('login') }}';
      });
    }

    initPlantDropdown();
  });
})();
</script>

</body>
</html>
