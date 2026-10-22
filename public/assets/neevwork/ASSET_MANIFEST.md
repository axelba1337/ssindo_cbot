# Neevwork local assets

Updated: 2026-10-19 (Asia/Jakarta).

Source directory: `E:\Download\neevwork_files` (read only throughout this task).

Mapping evidence: saved `E:\Download\neevwork_files\neevwork.html`. No corresponding top-level saved Neevwork HTML file was found in `E:\Download`; the HTML inside the asset directory contains the original product headings, customer captions, and gallery descriptions. Reference: <https://neevwork.com/>. No web request or download was performed for this integration.

## Integration status

**18 valid images found; 13 unique images copied without conversion.** All eight product cards, all eight customer entries (three distinct logos), and two gallery photographs use local `asset()` URLs. Product images and customer logos use `object-fit: contain`; gallery photos use responsive editorial crops with `object-fit: cover`. Below-fold images include intrinsic dimensions, lazy loading, and asynchronous decoding. Missing-file checks preserve text-only fallbacks.

Paths below are relative to `public/assets/neevwork/`. Dimensions and byte counts are from the source files. Each copied file's SHA-256 was compared with its source and matched; originals were not modified.

| Saved source filename | Local path | Dimensions | Bytes | Matched content / status |
| --- | --- | --- | --- | --- |
| `dashboard.png` | `products/simpus.png` | 525 × 282 | 71,808 | SIMPUS; integrated |
| `parkir.png` | `products/software-parkir.png` | 533 × 321 | 118,624 | Software Parkir; integrated |
| `HRMS.png` | `products/hrms.png` | 645 × 344 | 90,325 | HRMS; integrated |
| `companyprofile.jpg` | `products/company-profile.jpg` | 448 × 228 | 18,370 | Website Company Profile; integrated |
| `POS.jpg` | `products/simple-pos.jpg` | 717 × 384 | 42,435 | Simple POS; integrated |
| `Antrian.png` | `products/anjungan-antrian.png` | 617 × 404 | 82,698 | Anjungan Antrian; integrated |
| `Jaringan.jpeg` | `products/networking.jpg` | 1153 × 677 | 82,337 | Jaringan Wi-Fi / Kabel; integrated |
| `CCTV1.png` | `products/cctv.png` | 535 × 345 | 479,891 | CCTV; integrated |
| `logo puskesmas.jpg` | `customers/puskesmas.jpg` | 260 × 300 | 16,617 | Shared by Banjar, Kokop, Sepulu, Tragah, Kwanyar, and Tanah Merah in the saved HTML; integrated with the same mapping |
| `logo bgm.jpg` | `customers/bajrah-guna-mandiri.jpg` | 512 × 512 | 42,568 | PT. Bajrah Guna Mandiri Jakarta; integrated |
| `logo pasar wisata.jpg` | `customers/pasar-wisata-juanda.jpg` | 1213 × 1154 | 143,806 | Pasar Wisata Juanda Sidoarjo; integrated |
| `teknisi printer 1.jpg` | `gallery/teknisi-printer.jpg` | 4624 × 2604 | 3,365,862 | Galeri Media / Teknisi Printer; integrated |
| `teknisi komputer 1.jpg` | `gallery/teknisi-komputer.jpg` | 2604 × 4624 | 2,173,964 | Galeri Media / Teknisi Komputer; integrated |

## Retained fallbacks and skipped files

- Hero: the saved page references `assets/images/background CCTV.jpg`, `assets/images/background_jaringan.jpg`, and `assets/images/background apk.jpg`. None exists in the supplied directory. The hero uses a restrained navy background; no product/gallery image is repurposed as its background. The `hero/` directory is intentionally empty.
- Gallery: no matching still photos for Custom Software, Instalasi CCTV, Jaringan, or Videography are present. Those categories use clean text-based fallbacks below the photographic gallery. Custom Software references in the saved gallery are videos, not downloaded still photographs.
- Five valid but unnecessary images were excluded: `logo neev.png` (existing branding is retained), `unnamed.jpg` (68 × 68 thumbnail), `StaticMapService.GetMapImage` (map preview), and `vt` / `vt(1)` (map tiles). No ad/tracking/script assets were copied.
- Repeated customer references reuse one local logo rather than duplicating files.
- Original full-resolution gallery JPEGs are retained for quality; they account for approximately 5.5 MB of the 6.73 MB asset set and are lazy loaded. No unnecessary image conversion or package installation was performed.

## Validation

- All 13 copies have source-matching SHA-256 checksums, valid PNG/JPEG MIME signatures, and intrinsic dimensions.
- All 13 files fully decoded with GD and returned HTTP 200 with the matching image MIME type from the local Laravel development server. One original PNG contains a legacy ICC profile notice; pixel decoding succeeds, and the source bytes are preserved.
- The rendered homepage contains 18 local image elements referencing 13 unique files: eight products, eight customer entries, and two gallery photographs. All rendered image dimensions match their files; all have alt text and lazy loading. All eight product-to-image mappings passed explicit checks.
- Existing homepage/chatbot tests passed: 8 tests, 39 assertions. The rendered page retains six contextual assistant actions and three official redirect buttons. Gemini was mocked; no paid API requests were made.
- Saved-HTML headings/captions establish every product/customer/gallery match; representative screenshots and both work photographs were visually inspected.
- Existing desktop/tablet/mobile grid breakpoints remain; media width is constrained to its card, logos retain their aspect ratios, and text fallbacks remain available.
- Browser/device rendering, interactive clicks, and measured horizontal-overflow verification were unavailable because this session exposes no browser. Responsive rules were reviewed at source level; this is not visual breakpoint validation.
- Chatbot JavaScript, APIs, authentication, database, RAG, and session code were not changed.
- This remains an academic prototype, not an official production deployment.
