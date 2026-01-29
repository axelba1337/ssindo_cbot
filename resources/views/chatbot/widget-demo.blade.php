{{-- resources/views/chatbot/widget-demo.blade.php --}}
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Neev Assistant • Widget Demo</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">

  <link rel="stylesheet" href="{{ asset('assets/landing/css/chatbot-widget.css') }}">

  <style>
    body{
      margin:0;
      font-family:'Inter',system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,"Helvetica Neue",Arial,"Noto Sans",sans-serif;
      color:#e5e7eb;
      background:radial-gradient(circle at top,#1d4ed8,#020617 55%);
      min-height:100vh;
    }
  </style>
</head>
<body>
  <div id="cbot-shell" class="cbot-shell">
    <button id="cbot-resize" class="cbot-shell-resize" aria-label="Perbesar / perkecil" type="button">
      <img src="{{ asset('images/resize-svgrepo-com.svg') }}" alt="Resize">
    </button>

    <button id="cbot-minimize" class="cbot-shell-toggle" aria-label="Sembunyikan widget" type="button">
      <img src="{{ asset('images/minimize-x-svgrepo-com.svg') }}" alt="Tutup">
    </button>

    @include('chatbot._widget')
  </div>

  <div id="cbot-launcher" class="cbot-launcher-icon is-hidden" aria-label="Buka Neev Assistant" role="button" tabindex="0">
    <img src="{{ asset('images/neev-logo.png') }}" alt="Neev Assistant">
  </div>

  <script>window.CBOT_DEBUG = false;</script>
  <script src="{{ asset('assets/landing/js/chatbot-widget.js') }}" defer></script>
  <script src="{{ asset('assets/landing/js/chatbot-widget-shell.js') }}" defer></script>
</body>
</html>
