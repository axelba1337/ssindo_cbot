@php
  $services = [
    ['number' => '01', 'title' => 'Custom Software', 'text' => 'Pembuatan software yang disesuaikan dengan alur kerja bisnis agar praktis, mudah digunakan, dan siap dikembangkan.'],
    ['number' => '02', 'title' => 'Jaringan Komputer', 'text' => 'Perencanaan dan instalasi jaringan kabel maupun Wi-Fi untuk koneksi kantor yang stabil dan tertata.'],
    ['number' => '03', 'title' => 'CCTV', 'text' => 'Pemasangan kamera pengawas berkualitas yang dapat dihubungkan dan dipantau melalui perangkat mobile.'],
    ['number' => '04', 'title' => 'Videography', 'text' => 'Dokumentasi kegiatan, video profil perusahaan, video cinematic, dan dokumentasi momen penting.'],
  ];

  $products = [
    ['title' => 'SIMPUS', 'category' => 'Kelola Rekam Medis', 'text' => 'Aplikasi rekam medis untuk klinik atau puskesmas.'],
    ['title' => 'Software Parkir', 'category' => 'Manajemen Data Parkir', 'text' => 'Sistem parkir yang dapat diintegrasikan dengan palang pintu.'],
    ['title' => 'HRMS', 'category' => 'Absensi & Penggajian', 'text' => 'Aplikasi pengelola absensi dan penggajian karyawan.'],
    ['title' => 'Website Company Profile', 'category' => 'Website Perusahaan', 'text' => 'Website profesional untuk memperkuat identitas perusahaan.'],
    ['title' => 'Simple POS', 'category' => 'Kelola Penjualan', 'text' => 'Aplikasi sederhana untuk mengelola transaksi dan data penjualan.'],
    ['title' => 'Anjungan Antrian', 'category' => 'Sistem Antrean', 'text' => 'Anjungan antrean yang dapat diintegrasikan dengan sistem lain.'],
    ['title' => 'Jaringan Wi-Fi / Kabel', 'category' => 'Infrastruktur IT', 'text' => 'Pengembangan jaringan sekolah, kantor, dan koneksi point-to-point.'],
    ['title' => 'CCTV', 'category' => 'Keamanan Terintegrasi', 'text' => 'Produk, instalasi, dan jasa pemeliharaan CCTV.'],
  ];

  $customers = [
    'Puskesmas Banjar',
    'Puskesmas Kokop',
    'Puskesmas Sepulu',
    'Puskesmas Tragah',
    'Puskesmas Kwanyar',
    'Puskesmas Tanah Merah',
    'Pasar Wisata Juanda Sidoarjo',
    'PT. Bajrah Guna Mandiri Jakarta',
  ];

  $videography = [
    ['title' => 'Wedding Cinematic', 'text' => 'Dokumentasi sinematik untuk merangkai momen istimewa menjadi cerita yang berkesan.'],
    ['title' => 'Video Cinematic', 'text' => 'Produksi visual untuk kegiatan, kampanye, dan kebutuhan kreatif perusahaan.'],
    ['title' => 'Company Profile Video', 'text' => 'Video profil untuk memperkenalkan layanan, nilai, dan perjalanan perusahaan.'],
    ['title' => 'Dokumentasi', 'text' => 'Perekaman kegiatan penting dengan hasil rapi dan siap dipublikasikan.'],
  ];

  $gallery = [
    ['code' => 'DEV', 'label' => 'Custom Software', 'text' => 'Pengembangan aplikasi sesuai proses bisnis.'],
    ['code' => 'PRT', 'label' => 'Teknisi Printer', 'text' => 'Penanganan dan pemeliharaan perangkat cetak.'],
    ['code' => 'PC', 'label' => 'Teknisi Komputer', 'text' => 'Perawatan komputer dan perangkat kerja.'],
    ['code' => 'CCTV', 'label' => 'Instalasi CCTV', 'text' => 'Pemasangan sistem pengawasan di lokasi.'],
    ['code' => 'NET', 'label' => 'Jaringan', 'text' => 'Penataan jaringan kabel dan Wi-Fi.'],
    ['code' => 'VID', 'label' => 'Videography', 'text' => 'Produksi dokumentasi dan video perusahaan.'],
  ];
@endphp
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Neev Solusindo menyediakan custom software, jaringan komputer, CCTV, dan videography untuk mendukung operasional bisnis.">
  <title>Neev Solusindo — Solusi IT untuk Bisnis Anda</title>
  <link rel="icon" href="{{ asset('images/neev-logo.png') }}">
  <link rel="stylesheet" href="{{ asset('assets/landing/css/home.css') }}?v={{ filemtime(public_path('assets/landing/css/home.css')) }}">
  <link rel="stylesheet" href="{{ asset('assets/landing/css/chatbot-widget.css') }}?v={{ filemtime(public_path('assets/landing/css/chatbot-widget.css')) }}">
</head>
<body class="home-page" data-neevwork-url="https://neevwork.com/">
  <a class="skip-link" href="#main-content">Lewati ke konten utama</a>

  <div class="topbar">
    <div class="home-container topbar__inner">
      <p>Untuk pertanyaan lebih lanjut: <a href="https://wa.me/6281335117054" target="_blank" rel="noopener noreferrer">+62 813-3511-7054</a></p>
      <div class="topbar__contact" aria-label="Kontak cepat">
        <a href="mailto:admin@neevwork.com">admin@neevwork.com</a>
        <span>Kwanyar, Kab. Bangkalan, Madura</span>
      </div>
    </div>
  </div>

  <header class="site-header" id="site-header">
    <div class="home-container navbar">
      <a class="brand" href="#home" aria-label="Neev Solusindo, kembali ke beranda">
        <img src="{{ asset('images/neev-logo.png') }}" alt="" width="44" height="44">
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
    <section class="hero" id="home" aria-labelledby="hero-title">
      <div class="home-container hero__content">
        <p class="hero__kicker">Solusi Teknologi untuk Operasional Bisnis</p>
        <h1 id="hero-title">Kembangkan bisnis Anda bersama kami</h1>
        <p>Menjalankan administrasi bisnis lebih mudah.</p>
        <a class="button button--primary hero__button" href="#about">Cari Tahu Tentang Kami</a>
      </div>
    </section>

    <section class="section about-section" id="about" aria-labelledby="about-title">
      <div class="home-container">
        <div class="section-intro section-intro--split">
          <div>
            <p class="section-label">Tentang Kami</p>
            <h2 id="about-title">Kami bergerak di bidang teknologi dan media</h2>
          </div>
          <p>Neev Solusindo membantu perusahaan dan instansi menjalankan kebutuhan operasional melalui software, infrastruktur jaringan, sistem keamanan, dan dokumentasi visual.</p>
        </div>

        <div class="service-grid">
          @foreach ($services as $service)
            <article class="service-item">
              <span>{{ $service['number'] }}</span>
              <h3>{{ $service['title'] }}</h3>
              <p>{{ $service['text'] }}</p>
            </article>
          @endforeach
        </div>
      </div>
    </section>

    <section class="section section--soft" id="products" aria-labelledby="products-title">
      <div class="home-container">
        <div class="section-intro">
          <p class="section-label">Produk IT</p>
          <h2 id="products-title">Produk dan jasa yang kami kerjakan</h2>
          <p>Solusi aplikasi dan infrastruktur untuk mendukung kebutuhan operasional perusahaan, instansi, dan layanan publik.</p>
        </div>

        <div class="product-grid">
          @foreach ($products as $index => $product)
            <article class="product-item">
              <div class="product-item__number" aria-hidden="true">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</div>
              <p class="product-item__category">{{ $product['category'] }}</p>
              <h3>{{ $product['title'] }}</h3>
              <p>{{ $product['text'] }}</p>
            </article>
          @endforeach
        </div>
      </div>
    </section>

    <section class="section" id="customers" aria-labelledby="customers-title">
      <div class="home-container">
        <div class="section-intro section-intro--center">
          <p class="section-label">Customer Kami</p>
          <h2 id="customers-title">Pelanggan yang telah bekerja bersama kami</h2>
          <p>Beberapa instansi dan perusahaan yang tercantum pada profil layanan Neev Solusindo.</p>
        </div>

        <ul class="customer-grid" aria-label="Daftar pelanggan">
          @foreach ($customers as $customer)
            <li><span aria-hidden="true">{{ str_pad((string) ($loop->iteration), 2, '0', STR_PAD_LEFT) }}</span>{{ $customer }}</li>
          @endforeach
        </ul>
      </div>
    </section>

    <section class="section section--navy" id="videography" aria-labelledby="videography-title">
      <div class="home-container videography-layout">
        <div class="section-intro section-intro--light">
          <p class="section-label">Videography</p>
          <h2 id="videography-title">Produk videography kami</h2>
          <p>Dokumentasi visual untuk momen personal, kegiatan, dan profil bisnis.</p>
          <button class="button button--outline-light" type="button" data-neevwork-cta>Lihat Informasi Lengkap</button>
        </div>

        <div class="videography-list">
          @foreach ($videography as $item)
            <article>
              <span aria-hidden="true">{{ str_pad((string) ($loop->iteration), 2, '0', STR_PAD_LEFT) }}</span>
              <div><h3>{{ $item['title'] }}</h3><p>{{ $item['text'] }}</p></div>
            </article>
          @endforeach
        </div>
      </div>
    </section>

    <section class="section" id="gallery" aria-labelledby="gallery-title">
      <div class="home-container">
        <div class="section-intro section-intro--center">
          <p class="section-label">Galeri Media</p>
          <h2 id="gallery-title">Media kerjaan kami</h2>
          <p>Bidang pekerjaan yang ditangani tim Neev Solusindo.</p>
        </div>

        <div class="gallery-grid">
          @foreach ($gallery as $item)
            <article class="gallery-item">
              <span>{{ $item['code'] }}</span>
              <div><h3>{{ $item['label'] }}</h3><p>{{ $item['text'] }}</p></div>
            </article>
          @endforeach
        </div>
      </div>
    </section>

    <section class="company-cta" aria-label="Ajakan menghubungi Neev Solusindo">
      <div class="home-container company-cta__inner">
        <div><p>Ajakan untuk bertindak</p><h2>Kami siap untuk melayani Anda!</h2></div>
        <button class="button button--light" type="button" data-neevwork-cta>Hubungi Kami</button>
      </div>
    </section>

    <section class="section contact-section" id="contact" aria-labelledby="contact-title">
      <div class="home-container contact-layout">
        <div class="section-intro">
          <p class="section-label">Contact</p>
          <h2 id="contact-title">Jangan ragu untuk menghubungi kami</h2>
          <p>Ceritakan kebutuhan software, jaringan, CCTV, atau videography Anda kepada tim Neev Solusindo.</p>
          <a class="button button--primary" href="https://wa.me/6281335117054" target="_blank" rel="noopener noreferrer">Chat via WhatsApp</a>
        </div>

        <address class="contact-list">
          <a href="https://wa.me/6281335117054" target="_blank" rel="noopener noreferrer">
            <span>WhatsApp</span><strong>+62 813-3511-7054</strong>
          </a>
          <a href="mailto:admin@neevwork.com">
            <span>Email</span><strong>admin@neevwork.com</strong>
          </a>
          <a href="https://www.google.com/maps/search/?api=1&amp;query=CV+Neev+Solusindo+Jl.+Raya+Kauman+No.137+Kwanyar+Bangkalan" target="_blank" rel="noopener noreferrer">
            <span>Lokasi</span><strong>Jl. Raya Kauman No.137, Kauman, Ketetang, Kec. Kwanyar, Kabupaten Bangkalan, Jawa Timur 69163</strong>
          </a>
        </address>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="home-container footer-main">
      <div class="footer-brand">
        <a class="brand brand--footer" href="#home">
          <img src="{{ asset('images/neev-logo.png') }}" alt="" width="42" height="42">
          <span><strong>Neev</strong><small>Solusindo</small></span>
        </a>
        <p>Solusi software, jaringan, CCTV, dan videography untuk kebutuhan operasional.</p>
      </div>
      <nav aria-label="Navigasi footer">
        <strong>Navigasi</strong>
        <a href="#about">Tentang Kami</a>
        <a href="#products">Produk IT</a>
        <a href="#customers">Customer Kami</a>
        <a href="#contact">Contact</a>
      </nav>
      <div class="footer-contact">
        <strong>Kontak</strong>
        <a href="mailto:admin@neevwork.com">admin@neevwork.com</a>
        <a href="https://wa.me/6281335117054" target="_blank" rel="noopener noreferrer">+62 813-3511-7054</a>
        <span>Kwanyar, Kab. Bangkalan, Madura</span>
      </div>
    </div>
    <div class="home-container footer-bottom">
      <span>&copy; {{ date('Y') }} Neev Solusindo. All rights reserved.</span>
      <span>Prototipe integrasi website perusahaan dan chatbot; bukan website produksi resmi Neevwork.</span>
    </div>
  </footer>

  <div class="redirect-modal" id="redirect-modal" hidden>
    <div class="redirect-modal__backdrop" data-modal-backdrop>
      <div class="redirect-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="redirect-title" aria-describedby="redirect-description" tabindex="-1">
        <button class="redirect-modal__close" type="button" aria-label="Tutup popup" data-modal-close>&times;</button>
        <p class="section-label">Website eksternal</p>
        <h2 id="redirect-title">Website Resmi Neevwork</h2>
        <p id="redirect-description">Anda akan diarahkan ke website resmi Neevwork untuk melihat informasi dan mengirim pesan.</p>
        <div class="redirect-modal__actions">
          <button class="button button--secondary" type="button" data-modal-close>Batal</button>
          <button class="button button--primary" type="button" data-modal-continue>Lanjut ke Neevwork</button>
        </div>
      </div>
    </div>
  </div>

  <div id="cbot-shell" class="cbot-shell cbot-shell--hidden" role="dialog" aria-label="Neev Assistant">
    <button id="cbot-resize" class="cbot-shell-resize" aria-label="Perbesar atau perkecil chatbot" type="button">
      <img src="{{ asset('images/resize-svgrepo-com.svg') }}" alt="">
    </button>
    <button id="cbot-minimize" class="cbot-shell-toggle" aria-label="Minimalkan chatbot" type="button">
      <img src="{{ asset('images/minimize-x-svgrepo-com.svg') }}" alt="">
    </button>
    @include('chatbot._widget')
  </div>
  <button id="cbot-launcher" class="cbot-launcher-icon" type="button" aria-label="Buka Neev Assistant" aria-controls="cbot-shell" aria-expanded="false">
    <img src="{{ asset('images/neev-logo.png') }}" alt="">
    <span class="launcher-label">Tanya Neev</span>
  </button>

  <script>window.CBOT_DEBUG = false;</script>
  <script src="{{ asset('assets/landing/js/home.js') }}?v={{ filemtime(public_path('assets/landing/js/home.js')) }}" defer></script>
  <script src="{{ asset('assets/landing/js/chatbot-widget.js') }}?v={{ filemtime(public_path('assets/landing/js/chatbot-widget.js')) }}" defer></script>
  <script src="{{ asset('assets/landing/js/chatbot-widget-shell.js') }}?v={{ filemtime(public_path('assets/landing/js/chatbot-widget-shell.js')) }}" defer></script>
</body>
</html>
