{{-- resources/views/chatbot/widget-demo.blade.php --}}
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Neev Assistant • Widget Demo</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">

  <link rel="stylesheet" href="{{ asset('assets/landing/css/chatbot-widget.css') }}">

  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      color: #e5e7eb;
      background: radial-gradient(circle at top, #1d4ed8, #020617 55%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden; /* Mencegah scroll jika tidak perlu */
    }

    /* Penempatan Teks Langsung di Background */
    .hero-text-bg {
      text-align: center;
      max-width: 700px;
      z-index: 1; /* Di bawah widget */
      pointer-events: none; /* Supaya klik bisa tembus ke belakang jika perlu */
    }

    .hero-text-bg h1 {
      font-size: 2.5rem;
      font-weight: 800;
      margin-bottom: 20px;
      letter-spacing: -0.025em;
      color: #ffffff;
    }

    .hero-text-bg p {
      font-size: 1.1rem;
      line-height: 1.7;
      color: rgba(255, 255, 255, 0.7);
      margin-bottom: 10px;
    }

    /* Khusus untuk Link agar tetap bisa diklik */
    .hero-text-bg a {
      pointer-events: auto; 
      color: #60a5fa;
      text-decoration: none;
      font-weight: 600;
      border-bottom: 1px solid rgba(96, 165, 250, 0.3);
    }

    /* Penyesuaian agar widget tidak menutupi teks secara kasar */
    .cbot-shell {
      z-index: 10;
      /* Jika ingin widget tetap di pojok kanan bawah, 
         pastikan CSS di chatbot-widget.css tidak di-override ke tengah */
    }
  </style>
</head>
<body>

  <div class="hero-text-bg">
    <h1>Selamat datang di Neev Assistant</h1>
    <p>
      Halaman ini adalah chatbot resmi yang membantu Anda terkait produk, <br>
      layanan, dan konsultasi dari <strong>Neev Solusindo</strong>.
    </p>
    <p>
      Website resmi perusahaan dapat Anda akses di <a href="https://neevwork.com/" target="_blank">sini</a>.
    </p>
  </div>

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