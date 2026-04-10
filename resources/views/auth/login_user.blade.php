{{-- resources/views/auth/login_user.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Login – Aquatic Plant Advisor</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="stylesheet" href="{{ asset('assets/css/main.css') }}">
</head>
<body class="body-auth">

<div class="auth-card">

  <div style="margin-bottom:12px;">
    <a href="{{ route('home') }}" class="btn btn-secondary btn-xs" style="text-decoration:none;">
      ← Back to Home
    </a>
  </div>

  <h1 style="margin-bottom:4px;">Welcome back</h1>
  <p style="font-size:13px;color:#9ca3af;margin-bottom:18px;">
    Log in to manage your tanks and join the community.
  </p>

  <div id="client-alert" class="alert alert-danger" style="display:none;margin-bottom:12px;"></div>

  <form id="loginForm" novalidate>
    @csrf

    <div class="form-group">
      <label>Email</label>
      <input
        type="email"
        name="email"
        class="form-control"
        placeholder="you@example.com"
        value="{{ old('email') }}"
        required
      >
    </div>

    <div class="form-group">
      <label>Password</label>
      <input
        type="password"
        name="password"
        class="form-control"
        placeholder="••••••••"
        required
      >
    </div>

    <div class="form-group" style="display:flex;justify-content:space-between;align-items:center;">
      <label style="font-size:13px;">
        <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
        Remember me
      </label>
      <a href="{{ url('/forgot-password') }}" style="font-size:13px;color:#38bdf8;">
        Forgot password?
      </a>
    </div>

    <button id="btnLogin" type="submit" class="btn btn-primary" style="width:100%;margin-top:4px;">
      Login
    </button>
  </form>

  <p style="margin-top:14px;font-size:13px;color:#9ca3af;">
    New here?
    <a href="{{ url('/register') }}" style="color:#f97316;">Create an account</a>
  </p>
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

  document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    hideErr();

    const form = e.currentTarget;
    const btn = document.getElementById('btnLogin');
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
        const msg = json?.message || (res.status === 422 ? 'Invalid input.' : 'Login failed.');
        showErr(msg);
        btn.disabled = false;
        return;
      }

      window.location.href = '{{ route('tanks.my_tanks') }}';
    } catch (err) {
      showErr('Network error. Please try again.');
      btn.disabled = false;
    }
  });
</script>

</body>
</html>
