@php
  $heroSlides = array_values(array_filter([
    ['image' => 'products/networking.jpg', 'width' => 1153, 'height' => 677, 'label' => 'Jaringan komputer'],
    ['image' => 'gallery/teknisi-printer.jpg', 'width' => 4624, 'height' => 2604, 'label' => 'Maintenance perangkat IT'],
  ], fn ($slide) => is_file(public_path('assets/neevwork/'.$slide['image']))));

  $services = [
    ['number' => '01', 'title' => 'Custom Software', 'text' => 'Pembuatan software yang disesuaikan dengan alur kerja bisnis agar praktis, mudah digunakan, dan siap dikembangkan.', 'assistant_question' => 'Saya ingin membuat aplikasi untuk bisnis.'],
    ['number' => '02', 'title' => 'Jaringan Komputer', 'text' => 'Perencanaan dan instalasi jaringan kabel maupun Wi-Fi untuk koneksi kantor yang stabil dan tertata.', 'assistant_question' => 'Saya membutuhkan instalasi jaringan kantor.'],
    ['number' => '03', 'title' => 'CCTV', 'text' => 'Pemasangan kamera pengawas berkualitas yang dapat dihubungkan dan dipantau melalui perangkat mobile.', 'assistant_question' => 'Saya ingin konsultasi pemasangan CCTV.'],
    ['number' => '04', 'title' => 'Videography', 'text' => 'Dokumentasi kegiatan, video profil perusahaan, video cinematic, dan dokumentasi momen penting.'],
    ['number' => '05', 'title' => 'Maintenance IT', 'text' => 'Perawatan dan penanganan perangkat IT agar operasional bisnis tetap berjalan dengan baik.', 'assistant_question' => 'Saya membutuhkan bantuan maintenance IT.'],
  ];

  $products = [
    ['title' => 'SIMPUS', 'category' => 'Kelola Rekam Medis', 'text' => 'Aplikasi rekam medis untuk klinik atau puskesmas.', 'image' => 'simpus.png', 'width' => 525, 'height' => 282, 'alt' => 'Tampilan dashboard aplikasi rekam medis SIMPUS'],
    ['title' => 'Software Parkir', 'category' => 'Manajemen Data Parkir', 'text' => 'Sistem parkir yang dapat diintegrasikan dengan palang pintu.', 'image' => 'software-parkir.png', 'width' => 533, 'height' => 321, 'alt' => 'Tampilan Software Parkir Neevwork'],
    ['title' => 'HRMS', 'category' => 'Absensi & Penggajian', 'text' => 'Aplikasi pengelola absensi dan penggajian karyawan.', 'image' => 'hrms.png', 'width' => 645, 'height' => 344, 'alt' => 'Tampilan aplikasi HRMS untuk absensi dan penggajian'],
    ['title' => 'Website Company Profile', 'category' => 'Website Perusahaan', 'text' => 'Website profesional untuk memperkuat identitas perusahaan.', 'image' => 'company-profile.jpg', 'width' => 448, 'height' => 228, 'alt' => 'Contoh website company profile dari Neevwork'],
    ['title' => 'Simple POS', 'category' => 'Kelola Penjualan', 'text' => 'Aplikasi sederhana untuk mengelola transaksi dan data penjualan.', 'image' => 'simple-pos.jpg', 'width' => 717, 'height' => 384, 'alt' => 'Tampilan aplikasi Simple POS untuk penjualan'],
    ['title' => 'Anjungan Antrian', 'category' => 'Sistem Antrean', 'text' => 'Anjungan antrean yang dapat diintegrasikan dengan sistem lain.', 'image' => 'anjungan-antrian.png', 'width' => 617, 'height' => 404, 'alt' => 'Anjungan antrean yang ditampilkan pada katalog Neevwork'],
    ['title' => 'Jaringan Wi-Fi / Kabel', 'category' => 'Infrastruktur IT', 'text' => 'Pengembangan jaringan sekolah, kantor, dan koneksi point-to-point.', 'image' => 'networking.jpg', 'width' => 1153, 'height' => 677, 'alt' => 'Router dan switch jaringan dengan sambungan kabel', 'assistant_question' => 'Saya membutuhkan instalasi jaringan kantor.'],
    ['title' => 'CCTV', 'category' => 'Keamanan Terintegrasi', 'text' => 'Produk, instalasi, dan jasa pemeliharaan CCTV.', 'image' => 'cctv.png', 'width' => 535, 'height' => 345, 'alt' => 'Dokumentasi pemasangan dan pemantauan CCTV Neevwork', 'assistant_question' => 'Saya ingin konsultasi pemasangan CCTV.'],
  ];

  $customers = [
    ['name' => 'Puskesmas Banjar', 'image' => 'puskesmas.jpg', 'width' => 260, 'height' => 300],
    ['name' => 'Puskesmas Kokop', 'image' => 'puskesmas.jpg', 'width' => 260, 'height' => 300],
    ['name' => 'Puskesmas Sepulu', 'image' => 'puskesmas.jpg', 'width' => 260, 'height' => 300],
    ['name' => 'Puskesmas Tragah', 'image' => 'puskesmas.jpg', 'width' => 260, 'height' => 300],
    ['name' => 'Puskesmas Kwanyar', 'image' => 'puskesmas.jpg', 'width' => 260, 'height' => 300],
    ['name' => 'Puskesmas Tanah Merah', 'image' => 'puskesmas.jpg', 'width' => 260, 'height' => 300],
    ['name' => 'Pasar Wisata Juanda Sidoarjo', 'image' => 'pasar-wisata-juanda.jpg', 'width' => 1213, 'height' => 1154],
    ['name' => 'PT. Bajrah Guna Mandiri Jakarta', 'image' => 'bajrah-guna-mandiri.jpg', 'width' => 512, 'height' => 512],
  ];

  $videography = [
    ['title' => 'Wedding Cinematic', 'text' => 'Dokumentasi sinematik untuk merangkai momen istimewa menjadi cerita yang berkesan.'],
    ['title' => 'Video Cinematic', 'text' => 'Produksi visual untuk kegiatan, kampanye, dan kebutuhan kreatif perusahaan.'],
    ['title' => 'Company Profile Video', 'text' => 'Video profil untuk memperkenalkan layanan, nilai, dan perjalanan perusahaan.'],
    ['title' => 'Dokumentasi', 'text' => 'Perekaman kegiatan penting dengan hasil rapi dan siap dipublikasikan.'],
  ];

  $gallery = [
    ['code' => 'DEV', 'label' => 'Custom Software', 'text' => 'Pengembangan aplikasi sesuai proses bisnis.'],
    ['code' => 'PRT', 'label' => 'Teknisi Printer', 'text' => 'Penanganan dan pemeliharaan perangkat cetak.', 'image' => 'teknisi-printer.jpg', 'width' => 4624, 'height' => 2604, 'alt' => 'Dokumentasi perawatan printer di tempat kerja Neevwork'],
    ['code' => 'PC', 'label' => 'Teknisi Komputer', 'text' => 'Perawatan komputer dan perangkat kerja.', 'image' => 'teknisi-komputer.jpg', 'width' => 2604, 'height' => 4624, 'alt' => 'Teknisi Neevwork menangani perangkat komputer'],
    ['code' => 'CCTV', 'label' => 'Instalasi CCTV', 'text' => 'Pemasangan sistem pengawasan di lokasi.'],
    ['code' => 'NET', 'label' => 'Jaringan', 'text' => 'Penataan jaringan kabel dan Wi-Fi.'],
    ['code' => 'VID', 'label' => 'Videography', 'text' => 'Produksi dokumentasi dan video perusahaan.'],
  ];
@endphp
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
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
    <section class="hero" id="home" aria-labelledby="hero-title" data-hero-slideshow>
      <div class="hero__media" aria-hidden="true">
        @foreach ($heroSlides as $slide)
          <img class="hero__slide{{ $loop->first ? ' is-active' : '' }}" src="{{ asset('assets/neevwork/'.$slide['image']) }}" alt="" width="{{ $slide['width'] }}" height="{{ $slide['height'] }}" decoding="async" @if ($loop->first) fetchpriority="high" @endif data-hero-slide>
        @endforeach
      </div>
      <div class="home-container hero__content">
        <div class="hero__intro">
          <p class="hero__kicker" data-hero-reveal>Solusi Teknologi untuk Operasional Bisnis</p>
          <h1 id="hero-title" data-hero-reveal>Kembangkan bisnis Anda <span>bersama kami</span></h1>
          <p class="hero__description" data-hero-reveal>Menjalankan administrasi bisnis lebih mudah.</p>
          <div class="hero__actions" data-hero-reveal>
            <a class="button button--primary hero__button" href="#about">Cari Tahu Tentang Kami <span aria-hidden="true">↗</span></a>
            <a class="hero__link" href="#products">Lihat Produk IT <span aria-hidden="true">↓</span></a>
          </div>
        </div>
      </div>
      @if (count($heroSlides) > 1)
        <div class="home-container hero__controls" role="group" aria-label="Kontrol foto banner" hidden data-hero-controls>
          <button class="hero__arrow" type="button" aria-label="Foto sebelumnya" data-hero-previous><span aria-hidden="true">&#8592;</span></button>
          <div class="hero__dots" role="group" aria-label="Pilih foto banner">
            @foreach ($heroSlides as $slide)
              <button class="hero__dot" type="button" aria-label="Foto {{ $loop->iteration }}: {{ $slide['label'] }}" aria-current="{{ $loop->first ? 'true' : 'false' }}" data-hero-dot></button>
            @endforeach
          </div>
          <button class="hero__arrow" type="button" aria-label="Foto berikutnya" data-hero-next><span aria-hidden="true">&#8594;</span></button>
          <button class="hero__pause" type="button" aria-label="Jeda pergantian foto otomatis" aria-pressed="false" data-hero-pause>Jeda</button>
        </div>
      @endif
    </section>

    <section class="section about-section" id="about" aria-labelledby="about-title">
      <div class="home-container">
        <div class="section-intro section-intro--split" data-reveal>
          <div>
            <p class="section-label">Tentang Kami</p>
            <h2 id="about-title">Kami bergerak di bidang teknologi dan media</h2>
          </div>
          <p>Neev Solusindo membantu perusahaan dan instansi menjalankan kebutuhan operasional melalui software, infrastruktur jaringan, sistem keamanan, dan dokumentasi visual.</p>
        </div>

        <div class="service-grid" data-reveal-group>
          @foreach ($services as $service)
            <article class="service-item" data-reveal>
              <span>{{ $service['number'] }}</span>
              <h3>{{ $service['title'] }}</h3>
              <p>{{ $service['text'] }}</p>
              @if (!empty($service['assistant_question']))
                <button class="button button--compact button--secondary" type="button" data-neev-assistant-question="{{ $service['assistant_question'] }}" aria-label="Tanya Neev tentang {{ $service['title'] }}">Tanya Neev</button>
              @endif
            </article>
          @endforeach
        </div>
      </div>
    </section>

    <section class="section section--soft" id="products" aria-labelledby="products-title">
      <div class="home-container">
        <div class="section-intro" data-reveal>
          <p class="section-label">Produk IT</p>
          <h2 id="products-title">Produk dan jasa yang kami kerjakan</h2>
          <p>Solusi aplikasi dan infrastruktur untuk mendukung kebutuhan operasional perusahaan, instansi, dan layanan publik.</p>
        </div>

        <div class="product-grid" data-reveal-group>
          @foreach ($products as $product)
            <article class="product-item" data-reveal>
              @if (!empty($product['image']) && is_file(public_path('assets/neevwork/products/'.$product['image'])))
                <div class="product-item__media">
                  <img src="{{ asset('assets/neevwork/products/'.$product['image']) }}" alt="{{ $product['alt'] }}" width="{{ $product['width'] }}" height="{{ $product['height'] }}" loading="lazy" decoding="async">
                </div>
              @endif
              <div class="product-item__body">
                <p class="product-item__category">{{ $product['category'] }}</p>
                <h3>{{ $product['title'] }}</h3>
                <p class="product-item__description">{{ $product['text'] }}</p>
                @if (!empty($product['assistant_question']))
                  <button class="button button--compact button--secondary" type="button" data-neev-assistant-question="{{ $product['assistant_question'] }}" aria-label="Tanya Neev tentang {{ $product['title'] }}">Tanya Neev <span aria-hidden="true">↗</span></button>
                @endif
              </div>
            </article>
          @endforeach
        </div>
      </div>
    </section>

    <section class="section" id="customers" aria-labelledby="customers-title">
      <div class="home-container">
        <div class="section-intro section-intro--center" data-reveal>
          <p class="section-label">Customer Kami</p>
          <h2 id="customers-title">Pelanggan yang telah bekerja bersama kami</h2>
          <p>Beberapa instansi dan perusahaan yang tercantum pada profil layanan Neev Solusindo.</p>
        </div>

        <ul class="customer-grid" aria-label="Daftar pelanggan" data-reveal-group>
          @foreach ($customers as $customer)
            <li data-reveal>
              @if (!empty($customer['image']) && is_file(public_path('assets/neevwork/customers/'.$customer['image'])))
                <img class="customer-grid__logo" src="{{ asset('assets/neevwork/customers/'.$customer['image']) }}" alt="Logo {{ $customer['name'] }}" width="{{ $customer['width'] }}" height="{{ $customer['height'] }}" loading="lazy" decoding="async">
              @else
                <span aria-hidden="true">{{ str_pad((string) ($loop->iteration), 2, '0', STR_PAD_LEFT) }}</span>
              @endif
              <span class="customer-grid__name">{{ $customer['name'] }}</span>
            </li>
          @endforeach
        </ul>
      </div>
    </section>

    <section class="section section--navy" id="videography" aria-labelledby="videography-title">
      <div class="home-container videography-layout">
        <div class="section-intro section-intro--light" data-reveal>
          <p class="section-label">Videography</p>
          <h2 id="videography-title">Produk videography kami</h2>
          <p>Dokumentasi visual untuk momen personal, kegiatan, dan profil bisnis.</p>
          <button class="button button--outline-light" type="button" data-neevwork-cta>Lihat Informasi Lengkap</button>
        </div>

        <div class="videography-list" data-reveal-group>
          @foreach ($videography as $item)
            <article data-reveal>
              <span aria-hidden="true">{{ str_pad((string) ($loop->iteration), 2, '0', STR_PAD_LEFT) }}</span>
              <div><h3>{{ $item['title'] }}</h3><p>{{ $item['text'] }}</p></div>
            </article>
          @endforeach
        </div>
      </div>
    </section>

    <section class="section" id="gallery" aria-labelledby="gallery-title">
      <div class="home-container">
        <div class="section-intro" data-reveal>
          <p class="section-label">Galeri Media</p>
          <h2 id="gallery-title">Media kerjaan kami</h2>
          <p>Bidang pekerjaan yang ditangani tim Neev Solusindo.</p>
        </div>

        <div class="gallery-grid" data-reveal-group>
          @foreach ($gallery as $item)
            @if (!empty($item['image']) && is_file(public_path('assets/neevwork/gallery/'.$item['image'])))
              <figure class="gallery-item gallery-item--photo" data-reveal>
                <img class="gallery-item__photo" src="{{ asset('assets/neevwork/gallery/'.$item['image']) }}" alt="{{ $item['alt'] }}" width="{{ $item['width'] }}" height="{{ $item['height'] }}" loading="lazy" decoding="async">
                <figcaption class="gallery-item__caption"><h3>{{ $item['label'] }}</h3><p>{{ $item['text'] }}</p></figcaption>
              </figure>
            @endif
          @endforeach
        </div>
        <div class="gallery-notes" data-reveal-group>
          @foreach ($gallery as $item)
            @if (empty($item['image']) || !is_file(public_path('assets/neevwork/gallery/'.$item['image'])))
              <article class="gallery-note" data-reveal>
                <h3>{{ $item['label'] }}</h3>
                <p>{{ $item['text'] }}</p>
              </article>
            @endif
          @endforeach
        </div>
      </div>
    </section>

    <section class="company-cta" aria-label="Ajakan menghubungi Neev Solusindo">
      <div class="home-container company-cta__inner" data-reveal>
        <div><h2>Kami siap untuk melayani Anda!</h2></div>
        <button class="button button--light" type="button" data-neevwork-cta>Hubungi Kami</button>
      </div>
    </section>

    <section class="section contact-section" id="contact" aria-labelledby="contact-title">
      <div class="home-container contact-layout">
        <div class="section-intro" data-reveal>
          <p class="section-label">Contact</p>
          <h2 id="contact-title">Jangan ragu untuk menghubungi kami</h2>
          <p>Ceritakan kebutuhan software, jaringan, CCTV, atau videography Anda kepada tim Neev Solusindo.</p>
          <a class="button button--primary" href="https://wa.me/6281335117054" target="_blank" rel="noopener noreferrer">Chat via WhatsApp</a>
        </div>

        <address class="contact-list" data-reveal>
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
