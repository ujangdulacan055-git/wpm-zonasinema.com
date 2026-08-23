# Brief Teknis — Ganti Tema ke Navy+Gold, Fix Poster Grid, Bersihin Sisa Sagagoal di Homepage

Ditulis oleh: Cowork session (folder `wpm2_movie/docs`), 23 Agu 2026
Untuk: sesi Claude Code devs (eksekusi)
Konteks: operator liat homepage & "Semua Film" masih kerasa kayak portal
berita (bukan situs database film), dan warna maroon+gold dianggap
kemiripan visual sama situs film lain. Riset & keputusan warna final:
**Navy + Gold** (dipilih dari 4 opsi yang diajukan operator).

---

## Bagian A — Ganti warna navbar/genre-bar dari maroon ke navy

**File**: `assets/css/site.css`

Cuma 2 variabel CSS yang perlu diganti (`--pink` dan `--pink-2` — nama
variabelnya emang aneh/peninggalan lama, tapi cuma dipake di 4 tempat,
jadi aman diganti langsung tanpa rename variabel, biar diff minimal):

```css
/* SEKARANG (maroon) — baris ~36-39 di :root */
--pink: #6b1420;
--pink-2: #8a1f28;
```

Ganti jadi:

```css
/* Navy — ganti dari maroon (23 Agu 2026, keputusan operator: navy+gold
   biar beda dari situs film lain yang rata-rata merah/kuning) */
--pink: #0b2545;
--pink-2: #14396b;
```

Gold (`--accent`, `--accent-2`, `--accent-hover`, `--gold`) **TETAP**,
jangan diubah — operator pilih "Navy + Gold", bukan ganti dua-duanya.

**4 titik pemakaian buat diverifikasi setelah ganti** (semua otomatis
ke-update karena pake variabel, tapi cek visual biar mastiin kontras
masih bagus):
- Baris ~417: gradient navbar utama (background header)
- Baris ~442, ~446: tombol search
- Baris ~1683: satu gradient card lain

**Light theme** (`:root[data-theme="light"]`, baris ~69-100): `--pink`/
`--pink-2` gak di-override di situ (ikut inherit dari dark值) — cek pas
toggle ke light mode, warnanya masih kebaca gak. Kalau kurang kontras di
light mode, boleh tambahin override navy yang lebih terang dikit di
blok light theme itu.

Screenshot before/after navbar + genre bar (dark & light mode) buat
verifikasi.

---

## Bagian B — Fix bug: "Semua Film" masih render kartu artikel, bukan poster

**File**: `kategori.php`

Ditemukan 23 Agu 2026 (screenshot operator): halaman "Semua Film" (URL
tanpa parameter genre/tahun/populer) masih pake `wpm_article_card()`
(kartu blog: badge "BERITA", teks "35 artikel") padahal SELURUH konten
situs sekarang film. Root cause — baris ~37:

```php
$isFilmFilterMode = $genreSlug !== '' || $filmYear !== '' || $filmPopular;
```

Ini cuma `true` kalau ada filter aktif. Ganti jadi selalu `true` (situs
ini 100% film, gak ada mode "artikel biasa" lagi):

```php
$isFilmFilterMode = true; // ZonaSinema murni film — selalu true (23 Agu 2026)
```

**Efek samping yang WAJIB dicek** setelah ganti:
- Baris ~147-179 (blok penentuan `$pageTitle`/`$heroTitle` dst.) — pastiin
  blok default "Semua Film" (baris ~177) masih ke-trigger dengan benar
  buat kasus tanpa filter (logic `elseif` di situ mestinya masih jalan,
  cuma variabel kondisinya yang beda sekarang — baca ulang alurnya).
- Baris ~207-209 (badge "Film"/"Berita" & teks "film"/"artikel") — bakal
  otomatis selalu jadi "Film" sekarang, itu yang diinginkan.
- Baris ~235-237 (pemilihan grid class & card renderer) — bakal selalu
  `poster-grid` + `wpm_poster_card()`, itu yang diinginkan.
- Pastiin query `$where` (baris ~39, filter `page_id IN (SELECT page_id
  FROM films)`) **TIDAK** ikut jadi selalu-aktif kalau logic-nya nyambung
  ke `$isFilmFilterMode` yang sama — cek baris 38 (`if ($isFilmFilterMode)`)
  soalnya query itu makin dibatasi WHERE genre/tahun. Kalau ternyata WHERE
  clause itu nyambung ke variabel yang sama dan jadi salah scope
  (misalnya nge-block artikel yang belum ke-link ke tabel `films`),
  pisahin jadi 2 variabel: `$isFilmFilterMode` (buat WHERE, tetap
  kondisional ke genre/tahun/populer) vs `$useFilmCardLayout` (buat
  pilihan card renderer, selalu `true`). Grep dulu semua pemakaian
  `$isFilmFilterMode` di file ini sebelum edit, biar keliatan mana yang
  perlu tetep kondisional vs mana yang perlu selalu true.

`php -l` + screenshot "Semua Film" (dan genre/tahun/populer, pastiin
masih jalan normal) setelah fix.

---

## Bagian C — Redesign kartu poster: rating badge, TANPA badge streaming

Operator liat referensi visual (situs streaming) yang polanya bagus buat
ditiru **sebagian**: poster grid rapi + rating badge di pojok. Tapi ada
elemen di referensi itu yang **DILARANG ditiru** karena nyerempet pagar
keras (nyiratin ada konten buat ditonton):

**BOLEH ditiru** (netral, standar situs review film manapun):
- Layout grid poster rapi (sudah ada, `wpm_poster_card()`)
- **Badge rating bintang** di pojok poster (contoh: ⭐ 8.7) — kalau belum
  ada di `wpm_poster_card()` sekarang, tambahin overlay kecil pojok
  kiri/kanan atas pakai data `films.vote_average` yang udah ada dari
  integrasi TMDb kemarin.

**DILARANG ditiru** (pagar keras WO 003 — nyiratin fitur nonton):
- Badge kualitas **"CAM"/"HD"/"BLURAY"** (indikator kualitas rip bajakan)
- Badge **"EPS 8"/"EPS 12"** (nunjukkin ada episode buat ditonton)
- Filter genre bar **"BLURAY"** sebagai kategori

Kalau `wpm_poster_card()` di `includes/site-bootstrap.php` udah pernah
ada elemen semacam itu (cek dulu, kemungkinan besar nggak ada karena
brief TMDb kemarin udah nyuruh review pagar keras), **JANGAN ditambahin**
— cukup tambahin rating badge doang kalau belum ada.

---

## Bagian D — Bersihin sisa struktur Sagagoal di homepage

**File**: `index.php`, plus data di tabel `admins`

1. **Komentar header file** (baris ~5) masih literally nulis
   `"Sagagoal — public homepage. Tabbed news feed"` — update jadi
   deskripsi ZonaSinema yang akurat.
2. **Tab "Terbaru" / "Untuk Anda"** (baris ~81-84, ~108) — ini pola
   personalized-feed khas portal berita/livescore, bukan situs database
   film (IMDb/Letterboxd gak ada tab kayak gini). Ganti jadi label yang
   masuk akal buat situs film, misalnya:
   - "Terbaru" → tetep boleh dipake (film baru ditambahin, ini relevan)
   - "Untuk Anda" → ganti jadi **"Terpopuler"** (query berdasar
     `films.popularity`, yang udah ada dari integrasi TMDb) — lebih
     masuk akal buat situs film daripada "rekomendasi personalisasi" yang
     kita gak punya sistemnya.
3. **"Sedang Tren" sidebar** (baris ~137, fungsi `wpm_trending_item()` di
   `includes/site-bootstrap.php` baris ~906) — query-nya sendiri udah
   benar (published articles by views), TAPI byline-nya (`wpm_news_byline()`,
   baris ~819) nampilin **nama admin + waktu**, pola khas byline artikel
   berita. Buat situs film, ini kurang relevan — ganti jadi nampilin
   **rating film** aja (data `films.vote_average`, konsisten sama kartu
   poster di Bagian C), bukan nama penulis.
4. **"Admin Biang"** — ini BUKAN kode, itu data literal di tabel
   `admins.name` peninggalan WPM1 (ditemukan lewat screenshot, nol
   referensi di kode). Kalau byline diganti jadi rating (poin 3), ini
   otomatis gak keliatan lagi di frontend — gak perlu fix DB terpisah.
   Tapi kalau operator/devs mau tetep nampilin nama admin di tempat lain
   (misal dashboard cms-admin), rename manual nama admin itu lewat CMS
   (Users/Admins management) kapan aja, itu low-risk & bisa dikerjain
   operator langsung tanpa devs.

`php -l` + screenshot homepage penuh (hero, tabs, trending sidebar)
setelah semua perubahan Bagian D.

---

## Setelah semua selesai

1. `php -l` semua file yang diedit: `assets/css/site.css`, `kategori.php`,
   `index.php`, `includes/site-bootstrap.php` (kalau `wpm_poster_card()`/
   `wpm_trending_item()` ikut diedit).
2. Screenshot before/after: navbar+genre bar (warna baru), "Semua Film"
   (poster grid, bukan artikel), homepage penuh (tabs & trending baru).
3. Grep ulang pagar keras cepat: pastiin nol badge "CAM"/"HD"/"BLURAY"/
   "EPS" nyelip di CSS/PHP manapun.
4. Update `docs/ROADMAP.md` — tambahin entry Done buat perubahan ini.
