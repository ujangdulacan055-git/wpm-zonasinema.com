# Brief Teknis — Pre-Production Cleanup WPM 2 (ZonaSinema)

Ditulis oleh: Cowork session (folder wpm2_movie), 22 Agu 2026
Untuk: sesi Claude Code devs yang eksekusi coding di folder `wpm2_movie`
Rujukan: `docs/ROADMAP.md`, `BRIEF-BOOTSTRAP-WPM2-MOVIE.md`,
`BRIEF-IMPLEMENT-DETAIL-FILM.md`, `seed-wpm2-film-content.php`

## Konteks

Sebelum naik ke production, operator minta beresin poin 2–8 dari checklist
pre-production. **Repo Git independen (poin 1) SENGAJA ditunda** —
dikerjakan setelah semua poin di bawah ini kelar, jangan disentuh dulu.

Kerjakan urut sesuai nomor — beberapa poin saling bergantung (poin 2 harus
selesai duluan biar ada bukti visual buat verifikasi poin-poin lain).

---

## 2. Jalankan seed & verifikasi visual

File `seed-wpm2-film-content.php` (root folder) udah disiapkan sebelumnya
— sekali jalan, isi branding ZonaSinema + 6 artikel film dummy + unpublish
artikel bola lama (data tetap ada, cuma status jadi draft).

- Jalankan: `php seed-wpm2-film-content.php` dari root project di dalam
  container.
- Screenshot ulang: homepage (`index.php`) dan minimal satu halaman
  `/artikel/<slug-film>` (Detail Film).
- Kalau hasilnya udah sesuai mockup (marun+gold, konten film, bukan bola
  lagi) — **hapus file `seed-wpm2-film-content.php`** dari folder, itu
  bukan bagian dari aplikasi, cuma alat bantu sekali pakai.
- Kalau ada error pas jalanin (mis. kolom gak cocok, FK constraint), catat
  errornya dan laporkan — jangan asal ubah struktur tabel tanpa infoin.

## 3. Samain mobile nav ke desktop

Nav mobile drawer sekarang masih nampilin menu lama (Beranda/Berita).
Ganti jadi Genre/Populer/Tahun Rilis, sama persis kayak `.zc-nav-links` di
desktop (`includes/site-header.php`) — link placeholder ke kategori umum
gapapa dulu, yang penting kontennya konsisten sama desktop.

## 4. Bersihin CSS mati

Di `assets/css/site.css` cari class dari tema sport lama yang udah gak
kepake sama sekali di WPM 2: `.news-row`, `.news-list`, dan class sejenis
yang mereferensi konsep sport/livescore lama. Sebelum hapus, `grep -rn`
tiap class name ke seluruh folder (`.php` files) buat mastiin beneran gak
dipake — jangan hapus buta.

## 5. Genre bar & nav — kasih indikasi jelas "coming soon"

Karena skema database film masih di-hold, link Genre/Populer/Tahun Rilis
sementara masih ke kategori umum. Biar gak keliatan rusak/nyasar buat
pengunjung production, kasih salah satu dari ini (pilih yang paling
gampang diimplementasi cepat):
- Halaman kategori umum yang dituju dikasih heading yang make sense
  (misal "Semua Film" bukan judul kategori sport lama), ATAU
- Kasih badge kecil "Segera" di sebelah menu yang belum fungsional
  penuh (Genre/Tahun Rilis khususnya).
Jangan biarkan user klik menu dan ketemu halaman yang keliatan error/kosong
tanpa konteks.

## 6. Sortir sisa string "sagagoal"

Rujuk daftar ~20 file di `BRIEF-BOOTSTRAP-WPM2-MOVIE.md` bagian
"Sortir semua string sagagoal" — ini belum pernah dieksekusi. Grep ulang
sekarang (kode udah banyak berubah sejak brief itu ditulis):
```
grep -rniI "sagagoal" --include="*.php" --include="*.json" .
```
Ganti tiap match sesuai konteks (title/meta default, manifest.json
name/short_name, sitemap base URL, dll) ke identitas ZonaSinema. Yang di
komentar kode/dokumentasi historis boleh dibiarkan kalau memang cuma
catatan riwayat (bukan yang tampil ke user/mesin pencari).

## 7. Audit & cabut modul olahraga

`football.php`, `f1.php`, `basket.php`, `livescore-poll.php`,
`includes/SportsRegistry.php` — kalau masih ada di folder `wpm2_movie`,
harus dicabut/dihapus (bukan niche WPM 2). Sebelum hapus, cek dulu apa
masih ada yang me-reference file-file ini (`grep -rn` nama filenya) dari
`includes/site-header.php`, `includes/site-footer.php`, `sitemap.php`,
atau menu manapun — beresin referensinya dulu baru hapus filenya, biar
gak ada link mati/500 error.

## 8. Audit pagar keras menyeluruh (WAJIB sebelum lapor selesai)

Bukan cuma Detail Film — cek SELURUH halaman publik di folder ini:
```
grep -rniI "iframe\|embed\|streaming\|nonton\|hydrax\|vidstream\|streamtape\|doodstream\|mixdrop" --include="*.php" .
```
Setiap match harus direview manual — pastikan nol player/embed/link
streaming video pihak ketiga dalam bentuk apapun, di halaman manapun.
Laporkan hasil grep-nya (nol match, atau kalau ada match jelasin
konteksnya) sebagai bukti verifikasi.

---

## Setelah semua poin di atas selesai

Laporkan balik: ringkasan tiap poin (selesai/ada catatan), hasil grep
poin 8 sebagai bukti pagar keras aman, dan screenshot terbaru homepage +
Detail Film. **Repo Git baru (poin 1) baru dikerjakan setelah ini semua
approved** — jangan push/commit ke remote lama (`sagacrypto-wpm-goal`)
sama sekali di tahap ini.
