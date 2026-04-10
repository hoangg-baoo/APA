<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Forgot Password - Aquatic Plant Advisor</title>
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

  <h1 class="auth-title">Reset password</h1>
  <p class="auth-subtitle">
    Enter the email associated with your account and we'll send you a reset link.
  </p>

  @if (session('status'))
    <div class="alert alert-success" style="margin-bottom:12px;">
      {{ session('status') }}
    </div>
  @endif

  @if ($errors->any())
    <div class="alert alert-danger" style="margin-bottom:12px;">
      <ul style="margin:0; padding-left:18px;">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('password.email') }}">
    @csrf

    <div class="form-group">
      <label for="forgot-email">Email</label>
      <input
        type="email"
        id="forgot-email"
        name="email"
        class="form-control"
        placeholder="you@example.com"
        value="{{ old('email') }}"
        required
      />
    </div>

    <button type="submit" class="btn btn-primary" style="width:100%;margin-top:4px;">
      Send reset link
    </button>

    <div class="auth-footer">
      <div style="margin-top:6px;">
        Remember your password?
        <a href="{{ url('/login') }}">Back to login</a>
      </div>
    </div>
  </form>
</div>

</body>
</html>
