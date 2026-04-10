<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Reset Password - Aquatic Plant Advisor</title>
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

  <h1 class="auth-title">Set new password</h1>
  <p class="auth-subtitle">Enter your new password below.</p>

  @if ($errors->any())
    <div class="alert alert-danger" style="margin-bottom:12px;">
      <ul style="margin:0; padding-left:18px;">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div class="form-group">
      <label>Email</label>
      <input
        type="email"
        name="email"
        class="form-control"
        value="{{ old('email', $email) }}"
        required
      >
    </div>

    <div class="form-group">
      <label>New password</label>
      <input
        type="password"
        name="password"
        class="form-control"
        required
      >
    </div>

    <div class="form-group">
      <label>Confirm password</label>
      <input
        type="password"
        name="password_confirmation"
        class="form-control"
        required
      >
    </div>

    <button type="submit" class="btn btn-primary" style="width:100%;margin-top:4px;">
      Reset password
    </button>

    <div class="auth-footer">
      <div style="margin-top:6px;">
        <a href="{{ url('/login') }}">Back to login</a>
      </div>
    </div>
  </form>
</div>

</body>
</html>
