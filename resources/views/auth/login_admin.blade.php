{{-- resources/views/auth/login_admin.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Login – Aquatic Plant Advisor</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="stylesheet" href="{{ asset('assets/css/main.css') }}">
</head>
<body class="body-auth">

<div class="auth-card">
  <h1 style="margin-bottom:4px;">Admin console</h1>
  <p style="font-size:13px;color:#9ca3af;margin-bottom:18px;">
    Restricted area. Admin accounts only.
  </p>

  <div id="client-alert" class="alert alert-danger" style="display:none;margin-bottom:12px;"></div>

  <form id="adminLoginForm" novalidate>
    @csrf

    <div class="form-group">
      <label>Admin Email</label>
      <input
        type="email"
        name="email"
        class="form-control"
        placeholder="admin@example.com"
        required
      >
    </div>

    <div class="form-group">
      <label>Password</label>
      <input
        type="password"
        name="password"
        class="form-control"
        required
      >
    </div>

    <div class="form-group" style="display:flex;justify-content:space-between;align-items:center;">
      <label style="font-size:13px;">
        <input type="checkbox" name="remember" value="1">
        Remember me
      </label>
    </div>

    <button id="btnAdminLogin" type="submit" class="btn btn-primary" style="width:100%;margin-top:4px;">
      Login to admin
    </button>
  </form>
</div>

<script>
  const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  const alertBox = document.getElementById('client-alert');
  function showErr(msg) {
    alertBox.style.display = 'block';
    alertBox.textContent = msg || 'Login failed.';
  }
  function hideErr() {
    alertBox.style.display = 'none';
    alertBox.textContent = '';
  }

  async function apiLogoutSilent() {
    try {
      await fetch('/api/logout', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF,
        },
        credentials: 'same-origin',
      });
    } catch (_) {}
  }

  document.getElementById('adminLoginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    hideErr();

    const form = e.currentTarget;
    const btn = document.getElementById('btnAdminLogin');
    btn.disabled = true;

    const payload = {
      email: form.email.value.trim(),
      password: form.password.value,
      remember: form.remember.checked ? true : false,
    };

    try {
      const res = await fetch('/api/login', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': CSRF,
        },
        credentials: 'same-origin',
        body: JSON.stringify(payload),
      });

      const json = await res.json().catch(() => null);

      if (!res.ok || !json || json.success !== true) {
        showErr(json?.message || 'Login failed.');
        btn.disabled = false;
        return;
      }

      // check role admin
      if ((json?.data?.role || '') !== 'admin') {
        await apiLogoutSilent();
        showErr('Only admin can access this area.');
        btn.disabled = false;
        return;
      }

      window.location.href = '{{ route('admin.dashboard') }}';
    } catch (err) {
      showErr('Network error. Please try again.');
      btn.disabled = false;
    }
  });
</script>

</body>
</html>
