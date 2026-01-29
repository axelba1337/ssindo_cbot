{{-- resources/views/layouts/app.blade.php --}}
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>@yield('title','Neev Chatbot')</title>
  @vite([
    'resources/css/app.css',
    'resources/js/app.js'
  ])
</head>
<body>
  @yield('content')

  <script src="{{ asset('assets/landing/js/chatbot-widget.js') }}" defer></script>

  @yield('scripts')
</body>
</html>