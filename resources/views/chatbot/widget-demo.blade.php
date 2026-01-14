{{-- resources/views/chatbot/widget-demo.blade.php --}}
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Neev Assistant • Widget Demo</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">

  @vite(['resources/css/chatbot-widget.css'])

  <style>
    /* Template halaman mirip neevwork (sederhana) */
    body{
      margin:0;
      font-family:'Inter',system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,"Helvetica Neue",Arial,"Noto Sans",sans-serif;
      color:#e5e7eb;
      background:radial-gradient(circle at top,#1d4ed8,#020617 55%);
      min-height:100vh;
      display:flex;
      flex-direction:column;
    }
    .site-shell{flex:1;display:flex;flex-direction:column}
    .site-header{
      display:flex;align-items:center;justify-content:space-between;
      padding:16px 40px;color:#e5e7eb;
    }
    .site-logo{display:flex;align-items:center;gap:8px;font-weight:600}
    .site-logo img{width:26px;height:26px}
    .site-nav a{
      margin-left:20px;font-size:14px;color:#cbd5f5;text-decoration:none;
    }
    .site-nav a:hover{text-decoration:underline}
    .site-hero{
      flex:1;display:flex;align-items:center;
      padding:40px;max-width:1040px;width:100%;margin:0 auto;
    }
    .site-hero-text h1{font-size:40px;margin:0 0 10px}
    .site-hero-text p{margin:0 0 18px;max-width:520px;color:#9ca3af}
    .site-hero-badge{
      display:inline-flex;align-items:center;gap:6px;
      padding:4px 10px;border-radius:999px;
      background:rgba(15,23,42,.8);
      border:1px solid rgba(148,163,184,.4);
      font-size:13px;margin-bottom:10px;
    }

    /* SHELL WIDGET – hanya halaman ini */
    .cbot-shell{
      position:fixed;
      left:16px;
      bottom:16px;
      width:360px;
      height:min(520px, 75vh);   /* supaya tidak terlalu tinggi */
      min-width:320px;
      min-height:360px;
      max-width:720px;
      max-height:90vh;
      z-index:9999;
      display:flex;
      flex-direction:column;
    }
    /* mode “wide” seperti contoh vercel */
    .cbot-shell--wide{
      width:min(680px, 95vw);
      height:min(520px, 80vh);
    }

    .cbot-shell .cbot{
      height:100%;
      margin:0;
      border-radius:16px;

      /* jadikan layout fleksibel supaya area chat menyesuaikan height shell */
      display:flex;
      flex-direction:column;
    }
    .cbot-shell .cbot__messages{
      flex:1 1 auto;
      height:auto;
    }
    .cbot-shell .quick{
      border-top:1px dashed rgba(148,163,184,.35);
    }

    .cbot-shell--hidden{display:none}

    /* tombol minimize (pojok kanan header) */
    .cbot-shell-toggle{
      position:absolute;
      right:14px;
      top:12px;
      width:26px;
      height:26px;
      border-radius:999px;
      border:0;
      background:rgba(15,23,42,.75);
      cursor:pointer;
      display:flex;
      align-items:center;
      justify-content:center;
      z-index:20;
      padding:0;
    }
    .cbot-shell-toggle img{
      width:14px;height:14px;filter:invert(1);
    }
    .cbot-shell-toggle:hover{
      background:rgba(15,23,42,.95);
    }

    /* tombol resize (kiri header, di depan logo) */
    .cbot-shell-resize{
      position:absolute;
      left:14px;
      top:12px;
      width:26px;
      height:26px;
      border-radius:999px;
      border:0;
      background:rgba(15,23,42,.75);
      cursor:pointer;
      display:flex;
      align-items:center;
      justify-content:center;
      z-index:20;
      padding:0;
    }
    .cbot-shell-resize img{
      width:14px;height:14px;filter:invert(1);
    }
    .cbot-shell-resize:hover{
      background:rgba(15,23,42,.95);
    }

    /* icon launcher saat minimize */
    .cbot-launcher-icon{
      position:fixed;
      left:18px;
      bottom:18px;
      width:52px;
      height:52px;
      border-radius:999px;
      background:linear-gradient(135deg,#2563eb,#22c55e);
      display:flex;
      align-items:center;
      justify-content:center;
      cursor:pointer;
      box-shadow:0 18px 35px rgba(15,23,42,.65);
      z-index:9998;
    }
    .cbot-launcher-icon img{
      width:28px;height:28px;
    }
    .cbot-launcher-icon.is-hidden{display:none}

    .cbot-dragging{
      cursor:grabbing !important;
      user-select:none;
    }
  </style>
</head>
<body>
<div class="site-shell">
  <header class="site-header">
    <div class="site-logo">
      <img src="{{ asset('images/neev-logo.png') }}" alt="Neev">
      <span>CV Neev Solusindo</span>
    </div>
    <nav class="site-nav">
      <a href="#">Products</a>
      <a href="#">Solutions</a>
      <a href="#">Resources</a>
      <a href="#">Contact</a>
    </nav>
  </header>

  <section class="site-hero">
    <div class="site-hero-text">
      <div class="site-hero-badge">
        <span>Neev Assistant</span>
        <span>Widget demo di pojok kiri</span>
      </div>
      <h1>Bantu jawab pertanyaan pengunjung situs.</h1>
      <p>Ini hanya halaman contoh. Fokusnya adalah widget Neev Assistant yang bisa di-minimize, digeser, dan di-resize.</p>
    </div>
  </section>
</div>

{{-- SHELL widget (floating) --}}
<div id="cbot-shell" class="cbot-shell">
  {{-- tombol resize (kiri header) --}}
  <button id="cbot-resize" class="cbot-shell-resize" aria-label="Perbesar / perkecil">
    <img src="{{ asset('images/resize-svgrepo-com.svg') }}" alt="Resize">
  </button>

  {{-- tombol minimize (kanan header) --}}
  <button id="cbot-minimize" class="cbot-shell-toggle" aria-label="Sembunyikan widget">
    <img src="{{ asset('images/minimize-x-svgrepo-com.svg') }}" alt="Tutup">
  </button>

  {{-- pakai widget asli, TANPA mengubah resources/views/chatbot/widget.blade.php --}}
  @include('chatbot.widget')
</div>

<div id="cbot-launcher" class="cbot-launcher-icon is-hidden" aria-label="Buka Neev Assistant">
  <img src="{{ asset('images/neev-logo.png') }}" alt="Neev Assistant">
</div>

@vite([
  'resources/js/chatbot-widget.js',        // logika chat yang sudah ada
  'resources/js/chatbot-widget-shell.js',  // drag/minimize/resize khusus halaman ini
])
</body>
</html>