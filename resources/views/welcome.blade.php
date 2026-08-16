@php
  $services = [
    ['mark' => 'CS', 'title' => 'Custom Software', 'text' => 'Software yang dirancang sesuai alur kerja bisnis agar lebih praktis, mudah digunakan, dan siap berkembang.'],
    ['mark' => 'JK', 'title' => 'Jaringan Komputer', 'text' => 'Perencanaan dan instalasi jaringan kabel maupun Wi-Fi untuk koneksi yang stabil dan aman.'],
    ['mark' => 'CC', 'title' => 'CCTV', 'text' => 'Solusi kamera pengawas berkualitas yang dapat dipantau melalui perangkat mobile Anda.'],
    ['mark' => 'VD', 'title' => 'Video Dokumentasi', 'text' => 'Dokumentasi kegiatan, video profil perusahaan, hingga wedding cinematic yang berkesan.'],
  ];

  $products = [
    ['title' => 'SIMPUS', 'category' => 'Kelola Rekam Medis', 'text' => 'Aplikasi rekam medis untuk klinik dan puskesmas.', 'tone' => 'blue'],
    ['title' => 'Software Parkir', 'category' => 'Manajemen Data Parkir', 'text' => 'Sistem parkir yang dapat terintegrasi dengan palang pintu.', 'tone' => 'violet'],
    ['title' => 'HRMS', 'category' => 'Absensi & Penggajian', 'text' => 'Kelola kehadiran dan penggajian karyawan dalam satu aplikasi.', 'tone' => 'cyan'],
    ['title' => 'Website Company Profile', 'category' => 'Website Perusahaan', 'text' => 'Website profesional untuk memperkuat identitas bisnis Anda.', 'tone' => 'orange'],
    ['title' => 'Simple POS', 'category' => 'Kelola Penjualan', 'text' => 'Aplikasi kasir sederhana untuk transaksi dan laporan penjualan.', 'tone' => 'emerald'],
    ['title' => 'Anjungan Antrian', 'category' => 'Sistem Antrean', 'text' => 'Layanan antrean yang lebih tertib, terukur, dan mudah dipantau.', 'tone' => 'rose'],
    ['title' => 'Jaringan Wi-Fi / Kabel', 'category' => 'Infrastruktur IT', 'text' => 'Instalasi dan penataan jaringan untuk kantor maupun area publik.', 'tone' => 'indigo'],
    ['title' => 'CCTV', 'category' => 'Keamanan Terintegrasi', 'text' => 'Pemasangan CCTV untuk rumah, toko, kantor, dan fasilitas publik.', 'tone' => 'slate'],
  ];

  $customers = [
    'Puskesmas Banjar', 'Puskesmas Kokop', 'Puskesmas Sepulu',
    'Puskesmas Tragah', 'Puskesmas Kwanyar', 'Puskesmas Tanah Merah',
    'Pasar Wisata Juanda Sidoarjo', 'PT Bajrah Guna Mandiri Jakarta',
  ];

  $gallery = [
    ['label' => 'Custom Software', 'mark' => '</>', 'tone' => 'blue'],
    ['label' => 'Teknisi Printer', 'mark' => 'PR', 'tone' => 'orange'],
    ['label' => 'Teknisi Komputer', 'mark' => 'PC', 'tone' => 'violet'],
    ['label' => 'CCTV', 'mark' => 'CC', 'tone' => 'rose'],
    ['label' => 'Jaringan', 'mark' => 'NW', 'tone' => 'cyan'],
    ['label' => 'Videography', 'mark' => '▶', 'tone' => 'emerald'],
  ];
@endphp
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Neev Solusindo menyediakan custom software, jaringan komputer, CCTV, dan videography untuk mendukung bisnis Anda.">
  <title>Neev Solusindo — Solusi IT untuk Bisnis Anda</title>
  <link rel="icon" href="{{ asset('images/neev-logo.png') }}">
  <link rel="stylesheet" href="{{ asset('assets/landing/css/home.css') }}?v={{ filemtime(public_path('assets/landing/css/home.css')) }}">
  <link rel="stylesheet" href="{{ asset('assets/landing/css/chatbot-widget.css') }}?v={{ filemtime(public_path('assets/landing/css/chatbot-widget.css')) }}">
</head>
<body class="home-page" data-neevwork-url="https://neevwork.com/">
  <a class="skip-link" href="#main-content">Lewati ke konten utama</a>

  <div class="topbar">
    <div class="home-container topbar__inner">
      <span>Solusi IT, CCTV, jaringan, dan videography</span>
      <div class="topbar__contact" aria-label="Kontak cepat">
        <span>admin@neevwork.com</span>
        <span>+62 813-3511-7054</span>
      </div>
    </div>
  </div>

  <header class="site-header" id="site-header">
    <div class="home-container navbar">
      <a class="brand" href="#home" aria-label="Neev Solusindo, kembali ke beranda">
        <img src="{{ asset('images/neev-logo.png') }}" alt="" width="42" height="42">
        <span><strong>Neev</strong><small>Solusindo</small></span>
      </a>

      <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="main-nav" data-menu-toggle>
        <span></span><span></span><span></span>
        <span class="sr-only">Buka menu</span>
      </button>

      <nav class="main-nav" id="main-nav" aria-label="Navigasi utama" data-main-nav>
        <a href="#home">Home</a>
        <a href="#about">Tentang Kami</a>
        <a href="#products">Produk IT</a>
        <a href="#customers">Customer Kami</a>
        <a href="#videography">Videography</a>
        <a href="#gallery">Gallery</a>
        <a href="#contact">Contact</a>
      </nav>

      <button class="button button--compact button--primary navbar__cta" type="button" data-neevwork-cta>
        Kirim Pesan
      </button>
    </div>
  </header>

  <main id="main-content">
    <section class="hero" id="home">
      <div class="hero__orb hero__orb--one" aria-hidden="true"></div>
      <div class="hero__orb hero__orb--two" aria-hidden="true"></div>
      <div class="home-container hero__grid">
        <div class="hero__content">
          <p class="eyebrow"><span></span> Partner teknologi untuk bisnis bertumbuh</p>
          <h1>Kembangkan bisnis Anda <em>bersama kami.</em></h1>
          <p class="hero__lead">Menjalankan administrasi bisnis lebih mudah dengan solusi digital, jaringan, dan keamanan yang dirancang sesuai kebutuhan Anda.</p>
          <div class="hero__actions">
            <a class="button button--primary" href="#about">Cari Tahu Tentang Kami</a>
            <a class="button button--ghost" href="{{ route('chatbot') }}">Buka Neev Assistant</a>
          </div>
          <div class="hero__trust">
            <div><strong>4</strong><span>Layanan utama</span></div>
            <div><strong>8+</strong><span>Solusi bisnis</span></div>
            <div><strong>1</strong><span>Partner terintegrasi</span></div>
          </div>
        </div>

        <div class="hero__visual" aria-label="Ilustrasi solusi terintegrasi Neev">
          <div class="hero-card hero-card--main">
            <div class="hero-card__top"><span class="status-dot"></span> Neev Digital Workspace</div>
            <div class="dashboard-bars" aria-hidden="true">
              <i style="--height: 46%"></i><i style="--height: 68%"></i><i style="--height: 54%"></i><i style="--height: 88%"></i><i style="--height: 72%"></i>
            </div>
            <div class="dashboard-stats">
              <span><b>Software</b> yang adaptif</span>
              <span><b>Network</b> yang stabil</span>
            </div>
          </div>
          <div class="hero-card hero-card--float hero-card--cctv"><b>CCTV</b><span>Pantau dari mana saja</span></div>
          <div class="hero-card hero-card--float hero-card--support"><b>Siap membantu</b><span>Neev Assistant online</span></div>
        </div>
      </div>
    </section>

    <section class="section" id="about">
      <div class="home-container">
        <div class="section-heading section-heading--center">
          <p class="eyebrow eyebrow--center"><span></span> Tentang Kami <span></span></p>
          <h2>Teknologi yang bekerja untuk kebutuhan nyata</h2>
          <p>Kami memadukan pengembangan software, infrastruktur, keamanan, dan media untuk membantu operasional bisnis berjalan lebih sederhana.</p>
        </div>
        <div class="service-grid">
          @foreach ($services as $service)
            <article class="service-card">
              <div class="service-card__icon">{{ $service['mark'] }}</div>
              <h3>{{ $service['title'] }}</h3>
              <p>{{ $service['text'] }}</p>
            </article>
          @endforeach
        </div>
      </div>
    </section>

    <section class="section section--soft" id="products">
      <div class="home-container">
        <div class="section-heading section-heading--split">
          <div>
            <p class="eyebrow"><span></span> Produk IT</p>
            <h2>Produk dan jasa yang kami kerjakan</h2>
          </div>
          <p>Mulai dari aplikasi operasional hingga infrastruktur lapangan, setiap solusi dirancang agar mudah diterapkan.</p>
        </div>
        <div class="product-grid">
          @foreach ($products as $index => $product)
            <article class="product-card">
              <div class="product-card__visual visual--{{ $product['tone'] }}">
                <span>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                <i></i><i></i><i></i>
              </div>
              <div class="product-card__body">
                <p class="product-card__category">{{ $product['category'] }}</p>
                <h3>{{ $product['title'] }}</h3>
                <p>{{ $product['text'] }}</p>
                <button class="text-link" type="button" data-neevwork-cta>
                  Pelajari Lebih Lanjut <span aria-hidden="true">→</span>
                </button>
              </div>
            </article>
          @endforeach
        </div>
      </div>
    </section>

    <section class="section customers" id="customers">
      <div class="home-container">
        <div class="section-heading section-heading--center">
          <p class="eyebrow eyebrow--center"><span></span> Customer Kami <span></span></p>
          <h2>Dipercaya untuk mendukung pelayanan dan operasional</h2>
          <p>Beberapa pelanggan yang telah mempercayakan kebutuhan teknologi mereka kepada Neev Solusindo.</p>
        </div>
        <div class="customer-grid">
          @foreach ($customers as $customer)
            <div class="customer-card">
              <span>{{ collect(explode(' ', $customer))->filter()->map(fn ($word) => mb_substr($word, 0, 1))->take(2)->implode('') }}</span>
              <strong>{{ $customer }}</strong>
            </div>
          @endforeach
        </div>
      </div>
    </section>

    <section class="section section--dark" id="videography">
      <div class="home-container">
        <div class="section-heading section-heading--split section-heading--light">
          <div>
            <p class="eyebrow"><span></span> Videography</p>
            <h2>Cerita yang hidup dalam setiap frame</h2>
          </div>
          <p>Dokumentasi visual untuk momen personal, kegiatan, dan profil bisnis dengan pendekatan yang hangat dan sinematik.</p>
        </div>
        <div class="video-grid">
          @foreach ([
            ['Wedding Cinematic', 'Momen istimewa yang dirangkai menjadi cerita penuh emosi.'],
            ['Video Cinematic', 'Visual dinamis untuk kampanye, acara, dan kebutuhan kreatif.'],
            ['Company Profile Video', 'Perkenalkan nilai dan perjalanan perusahaan secara meyakinkan.'],
            ['Dokumentasi', 'Rekam kegiatan penting dengan hasil rapi dan siap dibagikan.'],
          ] as $index => [$title, $text])
            <article class="video-card">
              <div class="video-card__visual video-card__visual--{{ $index + 1 }}"><span aria-hidden="true">▶</span></div>
              <div><p>Produksi Neev</p><h3>{{ $title }}</h3><span>{{ $text }}</span></div>
              <button type="button" aria-label="Lihat detail {{ $title }}" data-neevwork-cta>↗</button>
            </article>
          @endforeach
        </div>
      </div>
    </section>

    <section class="section" id="gallery">
      <div class="home-container">
        <div class="section-heading section-heading--center">
          <p class="eyebrow eyebrow--center"><span></span> Gallery <span></span></p>
          <h2>Media kerjaan kami</h2>
          <p>Sekilas aktivitas tim di balik solusi software, instalasi perangkat, jaringan, dan produksi media.</p>
        </div>
        <div class="gallery-grid">
          @foreach ($gallery as $index => $item)
            <article class="gallery-card gallery-card--{{ $index + 1 }} visual--{{ $item['tone'] }}">
              <span class="gallery-card__mark">{{ $item['mark'] }}</span>
              <div><small>Proyek Neev</small><h3>{{ $item['label'] }}</h3></div>
            </article>
          @endforeach
        </div>
      </div>
    </section>

    <section class="cta-section">
      <div class="home-container cta-panel">
        <div><p>Siap memulai?</p><h2>Kami siap untuk melayani Anda.</h2></div>
        <button class="button button--light" type="button" data-neevwork-cta>Hubungi Kami <span aria-hidden="true">→</span></button>
      </div>
    </section>

    <section class="section contact-section" id="contact">
      <div class="home-container contact-grid">
        <div>
          <p class="eyebrow"><span></span> Contact</p>
          <h2>Mari bicarakan kebutuhan Anda</h2>
          <p class="contact-intro">Ceritakan tantangan bisnis atau kebutuhan teknis Anda. Tim Neev siap membantu menemukan langkah berikutnya.</p>
          <button class="button button--primary" type="button" data-neevwork-cta>Kirim Pesan</button>
        </div>
        <div class="contact-list">
          <div><span>01</span><p><small>WhatsApp</small><strong>+62 813-3511-7054</strong></p></div>
          <div><span>02</span><p><small>Email</small><strong>admin@neevwork.com</strong></p></div>
          <div><span>03</span><p><small>Lokasi</small><strong>Kwanyar, Kabupaten Bangkalan, Madura</strong></p></div>
        </div>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="home-container footer-main">
      <a class="brand brand--footer" href="#home"><img src="{{ asset('images/neev-logo.png') }}" alt="" width="42" height="42"><span><strong>Neev</strong><small>Solusindo</small></span></a>
      <p>Solusi digital dan infrastruktur yang membantu bisnis bekerja lebih baik.</p>
      <nav aria-label="Tautan footer">
        <button type="button" data-neevwork-cta>Privacy Policy</button>
        <button type="button" data-neevwork-cta>Term & Condition</button>
        <button type="button" data-neevwork-cta>FAQ</button>
      </nav>
    </div>
    <div class="home-container footer-bottom"><span>&copy; {{ date('Y') }} Neev Solusindo. All rights reserved.</span><a href="#home">Kembali ke atas ↑</a></div>
  </footer>

  <div class="redirect-modal" id="redirect-modal" hidden>
    <div class="redirect-modal__backdrop" data-modal-backdrop>
      <div class="redirect-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="redirect-title" aria-describedby="redirect-description" tabindex="-1">
        <button class="redirect-modal__close" type="button" aria-label="Tutup popup" data-modal-close>×</button>
        <div class="redirect-modal__icon" aria-hidden="true">↗</div>
        <p class="eyebrow"><span></span> Website eksternal</p>
        <h2 id="redirect-title">Website Neevwork</h2>
        <p id="redirect-description">Anda akan diarahkan ke website resmi Neevwork untuk melihat informasi lebih lengkap.</p>
        <div class="redirect-modal__actions">
          <button class="button button--ghost" type="button" data-modal-close>Batal</button>
          <button class="button button--primary" type="button" data-modal-continue>Lanjut ke Neevwork</button>
        </div>
      </div>
    </div>
  </div>

  <div id="cbot-shell" class="cbot-shell cbot-shell--hidden" aria-label="Neev Assistant">
    <button id="cbot-resize" class="cbot-shell-resize" aria-label="Perbesar atau perkecil chatbot" type="button">
      <img src="{{ asset('images/resize-svgrepo-com.svg') }}" alt="">
    </button>
    <button id="cbot-minimize" class="cbot-shell-toggle" aria-label="Sembunyikan chatbot" type="button">
      <img src="{{ asset('images/minimize-x-svgrepo-com.svg') }}" alt="">
    </button>
    @include('chatbot._widget')
  </div>
  <div id="cbot-launcher" class="cbot-launcher-icon" aria-label="Buka Neev Assistant" role="button" tabindex="0">
    <img src="{{ asset('images/neev-logo.png') }}" alt="">
    <span class="launcher-label">Tanya Neev</span>
  </div>

  <script>window.CBOT_DEBUG = false;</script>
  <script src="{{ asset('assets/landing/js/home.js') }}?v={{ filemtime(public_path('assets/landing/js/home.js')) }}" defer></script>
  <script src="{{ asset('assets/landing/js/chatbot-widget.js') }}?v={{ filemtime(public_path('assets/landing/js/chatbot-widget.js')) }}" defer></script>
  <script src="{{ asset('assets/landing/js/chatbot-widget-shell.js') }}?v={{ filemtime(public_path('assets/landing/js/chatbot-widget-shell.js')) }}" defer></script>
</body>
</html>
