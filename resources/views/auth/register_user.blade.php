{{-- resources/views/auth/register_user.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Register - Aquatic Plant Advisor</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="stylesheet" href="{{ asset('assets/css/main.css') }}" />
</head>
<body class="body-auth">

<div class="auth-card">

  <div style="margin-bottom:12px;">
    <a href="{{ route('home') }}" class="btn btn-secondary btn-xs" style="text-decoration:none;">
      ← Back to Home
    </a>
  </div>

  <div class="logo" style="margin-bottom: 16px;">
    <div class="logo-icon"
         style="display:inline-block;width:24px;height:24px;border-radius:999px;
                background:linear-gradient(135deg,#22c55e,#0ea5e9);margin-right:6px;"></div>
    <span>Aquatic Plant Advisor</span>
  </div>

  <h1 class="auth-title">Create account</h1>
  <p class="auth-subtitle">Join the community and start tracking your tanks.</p>

  <div id="client-alert" class="alert alert-danger" style="display:none;margin-bottom:12px;"></div>

  <form id="registerForm" novalidate>
    @csrf

    <div class="form-group">
      <label for="reg-name">Full name</label>
      <input
        type="text"
        id="reg-name"
        name="name"
        class="form-control"
        placeholder="Your name"
        required
      />
    </div>

    <div class="form-group">
      <label for="reg-email">Email</label>
      <input
        type="email"
        id="reg-email"
        name="email"
        class="form-control"
        placeholder="you@example.com"
        required
      />
    </div>

    <div class="form-group">
      <label for="reg-password">Password</label>
      <input
        type="password"
        id="reg-password"
        name="password"
        class="form-control"
        placeholder="At least 8 characters"
        required
      />
    </div>

    <div class="form-group">
      <label for="reg-password-confirm">Confirm password</label>
      <input
        type="password"
        id="reg-password-confirm"
        name="password_confirmation"
        class="form-control"
        placeholder="Re-type your password"
        required
      />
    </div>

    <button id="btnRegister" type="submit" class="btn btn-primary" style="width:100%;margin-top:4px;">
      Sign up
    </button>

    <div class="auth-footer">
      <div style="margin-top:6px;">
        Already have an account?
        <a href="{{ url('/login') }}">Login</a>
      </div>
    </div>
  </form>
</div>

<script>
  const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  const alertBox = document.getElementById('client-alert');
  function showErr(msg) {
    alertBox.style.display = 'block';
    alertBox.textContent = msg || 'Register failed.';
  }
  function hideErr() {
    alertBox.style.display = 'none';
    alertBox.textContent = '';
  }

  document.getElementById('registerForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    hideErr();

    const form = e.currentTarget;
    const btn = document.getElementById('btnRegister');
    btn.disabled = true;

    const payload = {
      name: form.name.value.trim(),
      email: form.email.value.trim(),
      password: form.password.value,
      password_confirmation: form.password_confirmation.value,
    };

    try {
      const res = await fetch('/api/register', {
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
        const msg = json?.message || (res.status === 422 ? 'Invalid input.' : 'Register failed.');
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
