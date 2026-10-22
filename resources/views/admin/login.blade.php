<!doctype html>
<html lang="id" data-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login Admin Neev</title>
  <link rel="stylesheet" href="{{ asset('assets/landing/css/admin.css') }}">
</head>
<body class="admin admin-login">
  <main class="admin-login__main">
    <section class="card admin-login__card" aria-labelledby="login-title">
      <div class="card__title" id="login-title">Login Admin Neev</div>

      <form method="POST" action="{{ route('admin.login.store') }}" class="form-grid">
        @csrf

        <label>
          Email
          <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
        </label>

        <label>
          Password
          <input type="password" name="password" required autocomplete="current-password">
        </label>

        @if ($errors->any())
          <p class="form-error" role="alert">{{ $errors->first() }}</p>
        @endif

        <button type="submit" class="btn-primary">Masuk</button>
      </form>
    </section>
  </main>
</body>
</html>
