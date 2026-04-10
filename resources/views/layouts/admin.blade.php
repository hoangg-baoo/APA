{{-- resources/views/layouts/admin.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>@yield('title', 'Admin – Aquatic Plant Advisor')</title>
  <link rel="stylesheet" href="{{ asset('assets/css/main.css') }}">
  <script defer src="{{ asset('assets/js/main.js') }}"></script>
  <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="app">

<header class="app-header">
  <div class="logo">Aquatic Plant Advisor – Admin</div>
  <div class="header-right">
    <span class="user-name">
      Hi, <strong id="admin-name">Admin</strong>
    </span>

    <button id="btn-admin-logout" class="btn btn-secondary btn-xs" style="margin-left:10px;">
      Logout
    </button>

    <!-- <div class="user-menu" style="margin-left:10px;">
      <img src="{{ asset('assets/img/avatar-default.png') }}" class="avatar" alt="Admin avatar">
      <span class="user-menu-arrow">▼</span>
    </div> -->
  </div>
</header>

<div class="app-body">
  <aside class="sidebar">
    <nav>
      <div class="sidebar-section">ADMIN</div>
      <a href="{{ url('/admin/dashboard') }}" class="sidebar-link @yield('sidebar_dashboard_active')">Dashboard</a>

      <div class="sidebar-section">Users &amp; access</div>
      <a href="{{ url('/admin/users') }}" class="sidebar-link @yield('sidebar_users_active')">Users &amp; roles</a>

      <div class="sidebar-section">Content</div>
      <a href="{{ url('/admin/qa') }}" class="sidebar-link @yield('sidebar_qa_active')">Q&amp;A moderation</a>
      <a href="{{ url('/admin/community-posts') }}" class="sidebar-link @yield('sidebar_posts_active')">Community posts</a>

      <div class="sidebar-section">Domain data</div>
      <a href="{{ url('/admin/plants') }}" class="sidebar-link @yield('sidebar_plants_active')">Plant library</a>
    </nav>
  </aside>

  <main class="content">
    @yield('content')
  </main>
</div>

<script>
  const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

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
        'X-CSRF-TOKEN': CSRF,
      },
      credentials: 'same-origin',
    });
    return res.ok;
  }

  document.addEventListener('DOMContentLoaded', async () => {
    const btn = document.getElementById('btn-admin-logout');
    const adminName = document.getElementById('admin-name');

    const me = await apiMe();
    if (me && adminName) adminName.textContent = me.name || 'Admin';

    if (btn) {
      btn.addEventListener('click', async () => {
        await apiLogout();
        window.location.href = '{{ route('admin.login') }}';
      });
    }
  });
</script>

</body>
</html>
