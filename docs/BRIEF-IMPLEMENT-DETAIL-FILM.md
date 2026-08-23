# Brief Teknis — Implementasi Halaman Detail Film (dari Mockup ke Kode PHP)

Ditulis oleh: Cowork session (folder wpm2_movie), 21 Agu 2026
Untuk: sesi Claude Code devs yang eksekusi coding di folder `wpm2_movie`
Rujukan: mockup canvas "ZonaSinema Detail Film"
(https://claude.ai/code/artifact/c8278e14-7bfa-42c6-9b89-12c7bc237cd2),
sudah direview & di-approve secara visual oleh operator.
`docs/ROADMAP.md` item Next #4.

## Konteks

Mockup Halaman Detail Film udah final secara visual. Sekarang perlu
diimplementasi jadi kode PHP beneran di `artikel.php` (halaman detail
artikel yang sudah ada, dipakai ulang buat film — situs ini belum punya
tabel `films` terpisah, skema database film sengaja **di-hold dulu** atas
keputusan operator, lihat `docs/ROADMAP.md`).

**Pendekatan: tetap jalan di atas tabel `pages` yang ada sekarang.** Kolom
yang belum ada di DB (rating, genre, cast, jadwal tayang bioskop) diisi
**placeholder/dummy di tampilan** — JANGAN nambah kolom/tabel baru dulu,
JANGAN nunggu skema DB fix. Begitu skema film fix nanti, tinggal disambung
ke data asli (ganti dummy → query beneran), styling & struktur HTML-nya
gak perlu berubah.

## Pagar keras (wajib, sama seperti semua halaman lain)

Tombol trailer **HANYA link-out ke YouTube** (`target="_blank"`,
`rel="noopener"`), **BUKAN embed**. Tidak boleh ada iframe/embed player
video/link streaming pihak ketiga dalam bentuk apapun di halaman ini.

## Struktur halaman (dari mockup, urutan top-to-bottom)

1. **Hero section** — full-width, backdrop blur di belakang (pakai cover
   image artikel yang ada, di-blur via CSS `filter: blur()`), lalu di atas
   backdrop: poster besar (pakai cover image yang sama atau field terpisah
   kalau ada), badge genre (pill kecil, styling sama kayak
   `.zc-genre-bar` tapi versi pill individual — lihat `.genre-pill` di
   mockup), judul (font Bebas Neue, besar), rating badge (bintang + angka
   dummy, mis. 8.2/10 — style mirip `.poster-card__rating` yang sudah ada
   tapi versi besar), info tahun/durasi/rating usia (dummy kalau kolom
   belum ada), ringkasan singkat, tombol "Tonton Trailer di YouTube"
   (link-out doang).
2. **Sinopsis** — pakai `article-prose`/`article-synopsis` style yang
   sudah ada di `site.css` (mirip `.article-prose`), isi dari field
   `content`/`excerpt` artikel yang sudah ada.
3. **Cast & Crew** — grid foto bulat + nama. Karena belum ada data
   cast di DB, render **section ini cuma kalau ada data dummy/manual**
   (jangan paksa render grid kosong kalau nanti gak ada apa-apa) — atau
   kasih 4-6 item dummy dulu biar layout kelihatan, dengan komentar kode
   yang jelas ini placeholder nunggu skema film.
4. **Sidebar jadwal tayang bioskop** — card list nama bioskop + jam
   tayang, dummy data dulu (array PHP hardcoded dengan komentar jelas ini
   placeholder).
5. **Related movies** — reuse `wpm_poster_card()` yang sudah ada di
   `includes/site-bootstrap.php` (dipakai homepage), tarik artikel lain
   dari kategori/tag yang sama biar bukan hardcoded.
6. Footer — sudah ada, gak perlu diubah.

## CSS yang perlu ditambah ke `assets/css/site.css`

Style baru yang dipakai di mockup tapi belum ada di `site.css` existing
(cek dulu biar gak duplikat kalau sebagian udah mirip class yang ada):
`.film-hero`, `.film-hero__backdrop`, `.film-hero__poster`,
`.film-hero__meta`, `.film-hero__badges`, `.genre-pill`,
`.film-hero__rating`, `.film-hero__facts`, `.btn-trailer`, `.film-body`,
`.film-section`, `.film-synopsis`, `.cast-grid`, `.cast-item`,
`.sidebar-block`, `.cinema-item`, `.time-pill`.

Semua warna/font WAJIB pakai token yang sudah ada di `:root`
(`var(--pink)`, `var(--accent)`, `var(--font-display)`,
`var(--font-body)`, `var(--surface-glass)`, `var(--border-glass)`, dst) —
JANGAN hardcode hex baru. Referensi lengkap tiap class ada di source
mockup (`Main.dc.html` dalam canvas — bisa di-inspect langsung dari link
mockup di atas, klik kanan → Inspect di browser buat liat computed CSS).

## Yang TIDAK dikerjakan di brief ini

- Skema database film (tabel `films`, kolom rating/genre/cast/jadwal) —
  di-hold, dibahas terpisah nanti sesuai `docs/ROADMAP.md`.
- Genre bar & menu Genre/Populer/Tahun Rilis di navbar — masih placeholder
  link ke kategori umum, nunggu skema film juga.
- Mobile nav drawer sync — item roadmap terpisah.

## Verifikasi sebelum lapor selesai

- `php -l` semua file yang diedit.
- Screenshot render beneran di browser (pastikan `DB_NAME` di
  `cms-admin/config/database.php` udah bener nunjuk ke DB WPM 2 sendiri,
  bukan `wpm1_version1` — kalau belum dibenerin, koordinasi dulu sama
  operator sebelum lanjut, ini blocker terpisah yang tercatat di
  `docs/ROADMAP.md`).
- Cek ulang manual: nol player/embed/link streaming di halaman ini.
