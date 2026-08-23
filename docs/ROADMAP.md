# WPM 2 — ZonaSinema.com — Roadmap

> Kepala/money site kedua (WPM 2), independen total dari Skema 1 (WPM 1 —
> sagagoal.com dkk). Niche: Movie/Film — review & database murni, BUKAN
> situs streaming. Rujukan: `WO 003` (Command Center), `BRIEF-BOOTSTRAP-WPM2-MOVIE.md`,
> `BRIEF-IMPLEMENT-DETAIL-FILM.md`, `BRIEF-PRE-PRODUCTION-CLEANUP.md`,
> `BRIEF-CLEANUP-SPORT-BACKEND-GROWTH-AGENT-SAFETY.md` — semua di folder
> `docs/` ini (dipindah dari root 22 Agu 2026 biar rapih). Roadmap visual
> HTML: `docs/skema-2-wpm2-movie.html` (dipindah dari `cc-wpm/skema-2/`).

Terakhir diperbarui: **23 Agustus 2026**

---

## Legend status

| Status | Arti |
|---|---|
| 🔴 Blocked | Tidak bisa lanjut tanpa input/aksi dari luar |
| 🟡 In Progress | Sedang dikerjakan sekarang |
| 🟢 Ready | Scope-nya jelas, siap dikerjakan, belum mulai |
| ⏸️ On Hold | Sengaja ditunda atas keputusan operator |
| ✅ Done | Selesai |

---

## Pagar keras (jangan pernah dilanggar)

Situs ini **review & database film murni**. Operator sudah final menolak
model streaming/nonton (WO 003, 18–20 Agu 2026) karena risiko UU Hak Cipta
28/2014, blokir Kominfo, dan gak bisa AdSense. **Tidak boleh ada player
video, iframe embed provider (Hydrax/Vidstream/Streamtape/DoodStream/
MixDrop dll), atau link streaming pihak ketiga dalam bentuk apapun** di
halaman manapun. Trailer resmi cuma boleh sebagai link-out ke YouTube
channel official studio. Permintaan susulan buat nambah fitur nonton harus
ditolak kecuali WO 003 diubah eksplisit oleh operator.

Audit menyeluruh (22 Agu 2026): grep `iframe|embed|streaming|nonton|
hydrax|vidstream|streamtape|doodstream|mixdrop` ke seluruh folder → 22
match, semua legit (komentar sanitizer, field ad-network embed yang
di-sanitize, keyword blocklist SEO internal). **Nol pelanggaran.**

---

## Now

- 🟡 **Strategi monetisasi diubah (23 Agu 2026): TANPA AdSense dulu.**
  Operator mutusin ZonaSinema jalan dulu **tanpa monetisasi apapun** —
  tujuan awal murni bangun traffic & impresi organik di Google Search
  Console (GSC), belum pasang iklan/AdSense. Ini beda dari rencana awal
  WO 003 yang nyebut "model AdSense sama seperti Skema 1" — dicatat di
  sini sebagai update resmi, bukan dihapus dari histori (lihat arsip
  "20 Agu 2026" di bawah buat rencana awalnya).
  **Dampak langsung ke integrasi TMDb (Next #1):** karena situs tanpa
  monetisasi = bukan "commercial" menurut definisi TMDb ("primary purpose
  is to create revenue"), jawaban **"personal use"** pas daftar API key
  jadi akurat, bukan lagi abu-abu — bisa pakai **Developer Plan gratis**
  TMDb tanpa risiko ToS. Kalau nanti AdSense dipasang beneran, WAJIB
  balik cek status API key ini (upgrade ke Commercial Plan $149/bulan
  atau ganti sumber data).
- ✅ **Growth Agent auto-publish — dikonfirmasi aman (22 Agu 2026).**
  `auto_draft_automation.enabled` = `false`, `.auto_publish` = `false`
  (udah `false` dari sononya, gak perlu diubah), 0 job `auto_draft_article`
  pending di tab "Perlu Tindakan". **Catatan buat nanti:** `source_urls` di
  setting yang sama masih nunjuk ke sumber olahraga (detik.com/tag/sepak-bola,
  cnnindonesia.com/olahraga) — gak berbahaya selama `enabled:false`, tapi
  wajib diganti ke sumber film/entertainment sebelum fitur ini dipakai buat
  ZonaSinema (masuk brief revisi prompt di "Next" #2 di bawah).

## Catatan referensi (bukan rencana aktif)

- **Arsitektur website streaming (torrent vs streaming server).** Sempat
  didiskusikan 22 Agu 2026 sebagai pengetahuan umum — operator sempat
  mempertimbangkan ulang model streaming, lalu memutuskan **cuma dicatat
  aja, bukan dieksekusi**. Ringkasan teknis: *Torrent* = peer → download
  potongan file → gabungkan (lemah: bergantung seeder, tidak stabil, sulit
  scale ke banyak penonton). *Streaming* = server → kirim potongan video →
  player → tonton. **Pagar keras WO 003 tetap berlaku tanpa perubahan**:
  arsitektur "streaming server" di atas cuma legal kalau kontennya
  berlisensi resmi — kalau kontennya film bajakan (self-hosted atau embed
  provider pihak ketiga), tetap ditolak final (UU Hak Cipta 28/2014, risiko
  blokir Kominfo, gak kompatibel AdSense). **Belum ada keputusan eksekusi
  apapun** — niche ZonaSinema tetap review & database film murni.

## Next

1. ⏸️ **Setup GSC (Google Search Console) — di-hold, 23 Agu 2026.**
   Operator belum punya akun Gmail khusus buat ZonaSinema, ditunda dulu
   sampai step lain siap naik online. `robots.txt` baru udah dibikin
   duluan (situs sebelumnya nol robots.txt sama sekali) isinya **blok
   semua crawler** (`Disallow: /`) — jadi walaupun GSC belum disetup,
   situs udah aman dari crawl dini. Panduan verifikasi domain (via TXT
   record Cloudflare) udah ditulis di chat, tinggal jalanin nanti pas
   siap. **WAJIB diinget**: baru buka `robots.txt` + submit sitemap
   **setelah** integrasi TMDb (selesai 23 Agu 2026, lihat Done) dan film
   asli udah live — sudah terpenuhi, tinggal nunggu step Gmail operator.
2. Sinkronisasi berkala data TMDb (Bagian E brief integrasi, opsional) —
   cron mingguan refresh `vote_average`/`vote_count`/`popularity` film
   yang udah ada, throttle pakai `films.synced_at`. Bukan prioritas.
3. **Follow-up dari revisi Growth Agent (24 Agu 2026)** — 3 area yang
   sengaja TIDAK disentuh pas revisi prompt kemarin karena di luar scope
   brief, butuh brief terpisah kalau mau dipakai buat ZonaSinema:
   - `cms_growth_agent_extract_title_context()` (`growth-agent-service.php`
     ~baris 6043-6091) — `$themes` keyword mapping (juara/menang/gol dst
     → deskripsi visual) + fallback `'sports news moment'`, dipakai buat
     generate image-prompt AI. Masih 100% football/sports-specific.
   - `cms_growth_agent_build_cover_image_prompt()` (~baris 6112+) —
     template default gambar masih "Editorial sports photo", match ke
     `sport_key` dari tabel `sports` yang sudah tidak relevan buat film.
   - `cms_growth_agent_generate_auto_draft_article()` (titik prompt ke-9,
     ~baris 6360-6371) — kalimat niche pembuka udah diganti, tapi bagian
     lanjutan soal pemilihan `sport_key`/`category_slug` masih kasih
     contoh spesifik olahraga ("a football transfer, an NBA game, an F1
     race") — terikat ke skema kategori yang beneran dipakai situs,
     perlu diselaraskan sama skema `films`/`film_genres` yang baru.
   Bukan blocker (auto-publish tetap off), tapi kalau image_agent atau
   auto-draft dipakai sebelum ini dibenerin, hasilnya masih bakal
   nyerempet visual/kategori olahraga.
4. **Logo baru** — operator udah bikin (reel film + wordmark "ZONASINEMA",
   versi light & dark) — nunggu file asli (SVG/PNG HD) buat dipasang
   ganti logo "ZS" placeholder di header + favicon.
5. **Follow-up kecil dari redesign 24 Agu 2026** — byline di hero card &
   list-row (`wpm_news_byline()`, dipakai `wpm_news_hero_card()`/
   `wpm_news_list_row()`) masih nampilin nama admin + waktu ("Admin Biang
   Olahraga · 7 jam yang lalu"), pola khas portal berita. Cuma byline
   sidebar "Sedang Tren" yang diganti jadi rating (scope brief kemarin).
   Kalau mau konsisten, byline di hero/list-row juga bisa diganti jadi
   rating film — belum dikerjain, nunggu keputusan/brief lanjutan.
6. **Halaman admin buat review "Request Movie"** — tabel `movie_requests`
   & halaman publik `request-film.php` udah jalan (24 Agu 2026), tapi
   belum ada UI di `cms-admin` buat operator liat/kelola request yang
   masuk (masih harus query manual ke DB). Perlu halaman admin baru
   (list + ubah status baru/dilihat/ditambahkan/ditolak) — di luar scope
   brief kemarin, follow-up terpisah kalau operator mau.

## Done (arsip)

- **24 Agu 2026** — **Bar bawah footer biru + logo footer digedein**
  (permintaan operator langsung dari screenshot). `.footer-bottom` +
  disclaimer TMDb dibungkus `.footer-bottom-bar` baru, background
  gradient `var(--pink)`/`var(--pink-2)` (sama kayak navbar). Logo
  footer naik dari 44px ke 60px tinggi.
- **24 Agu 2026** — **Ganti label + logo promo card**. Ticker "● Breaking"
  diganti "Film Terpopuler" (dot indikator live dihapus, query urut
  `films.popularity DESC` bukan cuma artikel terbaru). Sidebar "Sedang
  Tren" jadi "🔥 Banyak Dicari" — sekalian dinaikin dari 4 item (dibatasi
  `is_featured=1`, cuma nongol 2 film) jadi **10 item tanpa filter
  featured** (permintaan operator langsung, list kelihatan penuh
  sekarang). Promo card "Aplikasi ZonaSinema" — ikon roket diganti logo
  asli (`.app-promo-card__logo`, 72px/52px mobile); judulnya ternyata
  **udah benar "ZonaSinema"** duluan (klaim prompt yang bilang masih
  "Sagagoal" gak sesuai kondisi file, sama kayak beberapa prompt Cowork
  sebelumnya — dicek dulu sebelum apply, cuma bagian logo yang beneran
  diganti). Grep ulang `Sagagoal` project-wide → nol match di luar
  komentar docblock historis. Verifikasi: `php -l` 3 file lolos,
  screenshot ticker+sidebar+promo card dicek visual.
- **24 Agu 2026** — **Logo permanen/hardcode + Slider Arrow + PerPage 50**.
  Logo ZonaSinema (`assets/img/branding/logo-zonasinema-white-transparent.png`,
  1672x471px) dihardcode langsung di `includes/site-header.php` &
  `includes/site-footer.php` — BUKAN lagi dari `site_settings.logo_path`
  (final, gak akan ganti-ganti). Nol teks nama/tagline terpisah di
  samping logo lagi. `.crypto-logo__mark--wide` gantiin box 40x40 lama
  (akar bug "logo kepenyet" yang berulang beberapa kali). Slider "Film
  Terbaru" homepage sekarang ada tombol panah kiri/kanan (`index.php` +
  CSS `.poster-slider__arrow`), auto redup/disable pas mentok ujung, JS
  inline (bukan `site.js`). `kategori.php` `$perPage` naik 9→50, biar
  "Semua Film" (35 film) muat 1 halaman tanpa pagination — dikonfirmasi
  `hasPagination: false` lewat DOM check. Verifikasi: `php -l` 3 file
  lolos, logo dicek `naturalWidth`/`naturalHeight` (bukan broken image),
  slider dicek via screenshot before/after (poster kepotong beda posisi
  setelah klik panah — `scrollLeft` sendiri kebaca 0 lewat JS query,
  kuirk tooling browser yang udah beberapa kali kejadian sepanjang sesi
  ini, bukan bug situs).
- **24 Agu 2026** — **Verifikasi ulang + fitur "Request Movie"** (prompt
  "Sisa Kerjaan yang Belum Pasti Kepasang"). Poin 1-3 (redesign navy/gold,
  poster grid fix, sagagoal cleanup) dicek pakai grep persis dari prompt
  — **sudah kepasang semua** dari sesi sebelumnya (1 sisa docblock
  "Sagagoal public front-end" di `includes/site-bootstrap.php` dibersihin
  sekalian). Poin 2 (logo baru) — kode header sudah siap terima logo asli
  lewat `site_settings.logo_path` (admin-configurable, fallback ke mark
  "ZS" kalau kosong), tinggal nunggu file dari operator, nol kode yang
  perlu diubah. Poin 3 (Bagian F, hover polish poster card) **ternyata
  belum ada** — ditambahkan: scale 1.035 + border gold + shadow saat
  hover (`.poster-card:hover .poster-card__media`), gradient overlay
  tipis di bawah poster (dekoratif doang, bukan buat tombol nonton),
  judul di-truncate 2 baris (`-webkit-line-clamp`). Poin 4 (Request
  Movie) **belum ada, diimplementasi penuh**: toggle "Mode Gelap" dicabut
  dari `includes/site-header.php` (tema gelap/terang tetap jalan otomatis
  via localStorage/`prefers-color-scheme`, cuma switch manualnya yang
  hilang), diganti link "Request Movie" (pill gold, icon `film` baru di
  `wpm_icon()`) di desktop nav DAN mobile drawer. Halaman baru
  `request-film.php`: form judul/tahun/catatan/nama/kontak, honeypot +
  CSRF (helper baru `wpm_csrf_*()` di `site-bootstrap.php`, terpisah dari
  `cms_csrf_*()` admin) + rate-limit 5 request/jam per hash-IP (bukan IP
  mentah). Tabel `movie_requests` dibuat di DB dev `wpm2` (migration
  diarsip di `docs/db-migrations/2026-08-24-movie_requests.sql`, masih
  perlu dijalanin manual ke DB production). Diuji end-to-end (submit form
  beneran → row masuk DB → test data dibersihin lagi). Verifikasi: `php
  -l` semua file (site.css, site-header.php, site-footer.php,
  site-bootstrap.php, request-film.php, kategori.php, index.php,
  artikel.php) lolos; grep pagar keras `CAM|HD|BLURAY|EPS ` persis dari
  brief → 6 hit, semua ke-cross-check manual dan **ternyata false
  positive** (substring "eps " di dalam kata "keeps", nol pelanggaran
  beneran).
- **24 Agu 2026** — **Redesign homepage: tema biru cerah + Gold, fix bug
  poster grid, bersihin sisa Sagagoal** (`docs/BRIEF-REDESIGN-HOMEPAGE-
  TEMA-BIRU.md`). (A) Navbar/genre-bar diganti dari maroon ke navy
  (`--pink`/`--pink-2` di `site.css`), lalu dicerahin lagi ke biru terang
  (`#1a5fb4`/`#3b82e0`) atas request langsung operator pas review visual
  — navy pertama kegelapan. Gold (`--accent`) gak diubah. (B) **Bug fix**:
  "Semua Film" (`kategori.php` tanpa filter) sebelumnya masih render
  `wpm_article_card()` (kartu "BERITA") karena `$isFilmFilterMode` cuma
  true kalau ada genre/tahun/populer aktif — diganti selalu `true` (situs
  100% film, gak ada mode "artikel biasa" lagi), termasuk query WHERE-nya
  (restrict ke page_id yang ada row `films`) yang sekarang emang harus
  selalu aktif. (C) Rating badge (`films.vote_average`) ditambahin ke
  `wpm_poster_card()` (butuh join tabel `films` di 5 query: index.php ×2,
  kategori.php, artikel.php ×2) — nol badge kualitas/episode ("CAM"/"HD"/
  "BLURAY"/"EPS") ditiru dari referensi visual operator, sesuai pagar
  keras. (D) Homepage: docblock header, tab "Untuk Anda" → "Terpopuler"
  (urut `films.popularity`, bukan personalized-feed yang gak ada
  sistemnya), byline sidebar "Sedang Tren" (`wpm_trending_item()`) diganti
  dari nama admin+waktu jadi rating film. Verifikasi: `php -l` semua file
  edit (site.css, kategori.php, index.php, artikel.php, site-bootstrap.php)
  lolos, grep badge streaming nol match, screenshot dark+light mode +
  "Semua Film" + tab Terpopuler dicek visual.
- **16 Agu 2026** — WO 003 dibuka: operator mau bikin WPM independen baru,
  gurita terpisah total dari Skema 1.
- **18–20 Agu 2026** — Diskusi niche: opsi streaming full & katalog+embed
  provider pihak ketiga dipertimbangkan lalu **ditolak final** (risiko UU
  Hak Cipta 28/2014 + blokir Kominfo + gak bisa AdSense). Niche final:
  Movie/Film review & database murni, model sama seperti Skema 1
  (AdSense).
- **20 Agu 2026** — Brand & domain FINAL: `zonasinema.com`. (Model
  monetisasi awal yang dicatat saat itu: AdSense, sama kayak Skema 1 —
  lihat update 23 Agu 2026 di "Now" di atas buat perubahannya.)
- **21 Agu 2026** — Bootstrap folder `wpm2_movie`, clone `cms-admin` dari
  `wpm_sagagoal.com`, brief teknis tertulis dikirim ke sesi Claude Code
  devs (`BRIEF-BOOTSTRAP-WPM2-MOVIE.md`).
- **21 Agu 2026** — Redesign visual homepage (marun + gold, Bebas Neue +
  Manrope, poster-slider card) diimplementasi & lolos `php -l`.
- **21 Agu 2026** — Mockup Halaman Detail Film dibuat & direview operator.
- **21 Agu 2026** — `DB_NAME` dibenerin ke DB `wpm2` (terpisah dari
  `wpm1_version1`) — blocker verifikasi visual selesai.
- **22 Agu 2026** — Seed data (`seed-wpm2-film-content.php`) dijalankan:
  branding ZonaSinema, 6 artikel film dummy, artikel bola lama di-unpublish.
  File seed dihapus setelah dipakai. Homepage & Detail Film ter-screenshot,
  render bersih sesuai mockup.
- **22 Agu 2026** — Halaman Detail Film diimplementasi jadi kode PHP beneran
  (`artikel.php`, `assets/css/site.css`, `includes/site-bootstrap.php`) —
  hero backdrop-blur, rating/genre badge dummy, sinopsis, cast & crew dummy,
  jadwal tayang bioskop dummy, related movies pakai `wpm_poster_card()`
  query beneran, tombol trailer link-out YouTube saja (nol embed). Lolos
  `php -l`, lolos grep pagar keras.
- **24 Agu 2026** — **Revisi Growth Agent ke niche film selesai**
  (`docs/BRIEF-REVISI-GROWTH-AGENT-NICHE-FILM.md`). 9 titik system prompt
  di `growth-agent-service.php` diganti "Sagagoal, livescore & sports news
  website (football/basketball/F1)" → "ZonaSinema, movie review & database
  website" (audit ulang nemuin 1 titik ke-9 di luar 8 yang dicatat brief
  awal). 2 array default source URL di `gsc-api.php` (`sources` &
  `source_urls`, dua array terpisah) diganti ke 4 sumber film Indonesia
  (`hot.detik.com/movie` — RSS jalan; 3 lainnya fallback HTML-scrape best
  effort, gapapa). ~25 istilah generik film ditambahin ke kamus stopword
  dedup-content (opsional, dikerjain sekalian). Verifikasi: nol match
  "Sagagoal"/football-basketball tersisa (kecuali komentar historis),
  lolos `php -l` kedua file, toggle auto-publish tetap `false`. 3 area
  terkait (image-prompt keyword mapping, cover-image template, sport_key
  picklist di titik prompt ke-9) sengaja belum disentuh — dicatat di
  "Next" sebagai follow-up terpisah.
- **23 Agu 2026** — **Integrasi TMDb API + skema database film selesai**
  (`docs/BRIEF-INTEGRASI-TMDB-SKEMA-FILM.md`). Tabel `films`/`film_genres`/
  `film_genre_map` dibuat, 8 genre di-seed (mapping label Indonesia genre
  bar). ~35 film diimport dari `/movie/popular` TMDb (detail+credits+videos
  per film) — sinopsis ditulis ulang gaya editorial (bukan copy overview
  mentah, buat SEO), poster/rating/durasi/sutradara/cast/trailer key asli.
  6 artikel dummy lama di-unpublish. Genre bar & filter tahun & "Terpopuler"
  di `includes/site-header.php`/`kategori.php` sekarang beneran query
  `films`/`film_genre_map` (sebelumnya semua link identik, sekarang tiap
  genre nampilin film beda-beda — diverifikasi visual). `artikel.php`
  (Detail Film) rating/genre/cast/sutradara/durasi/tanggal rilis sekarang
  dari data asli `films`, elemen disembunyikan (bukan di-fabricate) kalau
  datanya nggak ada (age rating, penulis naskah, studio — nggak ditarik di
  skema import ini). Jadwal tayang bioskop TETAP placeholder (di luar
  scope TMDb API). Atribusi wajib "Powered by TMDb" ditambahkan ke footer.
  `TMDB_API_TOKEN` disimpan di `.env` (gitignored, bukan hardcode). Lolos
  `php -l` semua file, lolos grep pagar keras (nol iframe/embed/streaming
  beneran, trailer cuma `<a target="_blank">` ke YouTube). Script import
  sekali-jalan dihapus setelah dipakai.
- **22 Agu 2026** — Mobile nav drawer disamain ke desktop (Genre/Populer/
  Tahun Rilis).
- **22 Agu 2026** — CSS mati tema olahraga lama dibersihin dari `site.css`
  (2968 → 2244 baris, -863 baris): `.news-filter-row*`, `.livescore-*`,
  `.fixture-*`, `.sport-grid/.sport-card*`, `.f1-*`, `.live-now-widget*`,
  `.news-layout--ad-only` — digrep dulu, nol pemakaian tersisa sebelum
  dihapus.
- **22 Agu 2026** — `kategori.php` heading default diganti "Semua Film"
  (Opsi A dari brief — indikasi konteks film, bukan badge "segera").
- **22 Agu 2026** — Sortir string `sagagoal` selesai di ~17 file
  (manifest.json, title/meta halaman publik, `tentang.php` ditulis ulang,
  footer, login/prompt-control cms-admin) + 1 temuan tambahan (disclaimer
  footer "Data live score..." diganti jadi disclaimer jadwal bioskop).
- **22 Agu 2026** — 5 file modul olahraga dari brief awal (`football.php`,
  `f1.php`, `basket.php`, `livescore-poll.php`, `SportsRegistry.php`)
  dikonfirmasi sudah gak ada, nol referensi tersisa.
- **22 Agu 2026** — Audit pagar keras menyeluruh: nol pelanggaran (lihat
  bagian "Pagar keras" di atas).
- **22 Agu 2026** — Bagian dari Cleanup Pra-Produksi: sisa modul backend
  olahraga (scope diperluas) dicabut — cron sync scripts, `Api*Client.php`,
  `FormulaOneSync.php`, `settings-form-{football,basketball,f1}.php`,
  `livescore.js`, link menu admin mati di `sidebar.php` & `dashboard.php`,
  ENUM `placement_scope` di `ads.php` (0 baris kepengaruh, dikonfirmasi
  dulu sebelum diubah).
- **22 Agu 2026** — **Infrastruktur produksi disiapkan operator sendiri**:
  cPanel baru, domain, Cloudflare, dan **repo Git independen baru** (lepas
  dari remote `sagacrypto-wpm-goal` punya WPM 1). Isolasi Skema 1 ↔ Skema 2
  sekarang lengkap di semua lapisan (folder, database, kredensial, hosting,
  repo).
