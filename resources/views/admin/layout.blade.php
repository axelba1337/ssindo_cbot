{{-- resources/views/admin/layout.blade.php --}}
<!doctype html>
<html lang="id" data-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>@yield('title','Admin Chatbot')</title>
  @vite(['resources/css/admin.css'])
</head>

<body class="admin">
  <header class="admin__header">
    <div class="admin__brand">Neev Chatbot • Admin</div>
    <nav class="admin__nav">
      <a href="{{ route('admin.dashboard') }}">Dashboard</a>
      <a href="{{ route('admin.faq') }}">FAQ</a>
      <a href="{{ route('admin.unanswered') }}">Unanswered</a>

      <div class="theme-toggle">
        <label for="theme-toggle-input">
          <input type="checkbox" id="theme-toggle-input">
          <span id="theme-toggle-label">Light mode</span>
        </label>
      </div>
    </nav>
  </header>

  <main class="admin__main">
    @yield('content')
  </main>

  <footer class="admin__footer">
    <small>&copy; {{ date('Y') }} Neev</small>
  </footer>

  <!-- NOTIFICATION (pindah ke dalam body) -->
  <div id="notif" class="notif hidden">
    <span id="notif-msg"></span>
  </div>

  <script>
    (function () {
      const THEME_KEY = 'neev_admin_theme';

      function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);

        const toggle = document.getElementById('theme-toggle-input');
        const label = document.getElementById('theme-toggle-label');

        if (toggle) {
          toggle.checked = theme === 'dark';
        }

        if (label) {
          label.textContent = theme === 'dark' ? 'Dark mode' : 'Light mode';
        }
      }

      // load tema dari localStorage atau default light
      const savedTheme = localStorage.getItem(THEME_KEY) || 'light';
      applyTheme(savedTheme);

      const toggle = document.getElementById('theme-toggle-input');
      if (toggle) {
        toggle.addEventListener('change', function () {
          const newTheme = this.checked ? 'dark' : 'light';
          localStorage.setItem(THEME_KEY, newTheme);
          applyTheme(newTheme);
        });
      }
    })();
  </script>

  @yield('scripts')
</body>
</html>