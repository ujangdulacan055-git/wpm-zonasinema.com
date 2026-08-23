# Brief Teknis — Bootstrap WPM 2 "wpm2_movie" (zonasinema.com)

Ditulis oleh: Cowork session (folder wpm2-zonasinema-com), 21 Agu 2026
Untuk: sesi Claude Code devs yang eksekusi coding di folder `wpm2_movie`
Rujukan: Work Order 003 (Command Center), `docs/DECISIONS.md`, `docs/GROWTH_AGENT_V2_PROPOSAL.md` di project WPM 1

## Konteks

Bootstrap money site kedua (WPM 2) — independen, gurita terpisah total dari
Skema 1 (sagagoal.com dkk). Niche: **Movie/Film — review & database murni**.
**BUKAN** situs streaming/nonton, **TIDAK BOLEH** ada player/embed/link video
streaming dalam bentuk apapun (keputusan final operator, lihat WO 003 —
sudah ditolak karena risiko UU Hak Cipta 28/2014 & blokir Kominfo).

- Folder sumber clone: `wpm_sagagoal.com` (paling bersih menurut operator)
- Folder tujuan: `wpm2_movie` (sudah dibuat, masih kosong)
- Kedua folder ada di: `/Users/donnie/htdocs/docker-projects/php8/www/`

## Langkah kerja

### 1. Clone struktur dasar

Copy dari `wpm_sagagoal.com/` ke `wpm2_movie/`:
- `cms-admin/` (seluruh isi)
- `includes/`, `services/`, `assets/`, `cron/`
- File PHP frontend generik yang niche-agnostic: `index.php`, `kategori.php`,
  `artikel.php`, `page.php`, `pencarian.php`, `sitemap.php`,
  `generate-sitemap-static.php`, `.htaccess`, `.cpanel.yml`, `.gitignore`,
  `manifest.json`, `offline.html`, `sw.js`

**JANGAN** ikut copy modul spesifik olahraga (lihat langkah 3).

### 2. Isolasi database & kredensial

Edit `cms-admin/config/database.php`:
- `DB_NAME` di source = `'wpm1_version1'` → **ganti** ke nama baru yang
  genuinely terpisah dari SEMUA DB Skema 1 (`wpm_cms_goal`,
  `wpm_cms_olahraga77`, `wpm_cms_wcm1_version2`, `wpm_cms_wcm2_version1`,
  dst termasuk WCM 3 V.1/V.2). Saran penamaan: `wpm2_movie` atau
  `wpm_cms_zonasinema` — konfirmasi ke operator sebelum create DB fisiknya.

Edit `cms-admin/config/app.php` (line-line ini ditemukan di source):
- Line 15: `CMS_ADMIN_NAME` → tetap `'WCM'` (standar branding lintas
  tentakel, logo & nama admin panel seragam)
- Line 16: `CMS_ADMIN_TAGLINE` → dari `'SAGAGOAL.COM'` jadi nama brand WPM 2
  (mis. `'ZONASINEMA.COM'`)
- Line 26: `CMS_AI_ENC_SECRET` → **generate ulang** (jangan reuse hash yang
  ada di source, itu punya WPM 1). Pakai random 64-hex baru, misalnya
  `bin2hex(random_bytes(32))`.
- Line 32: `GROWTH_AGENT_DIGEST_TOKEN` → reset ke placeholder
  (`'GANTI_DENGAN_TOKEN_ACAK_ASLI'` atau sejenis, biar operator generate
  token asli sendiri nanti pas mau pakai fitur ini).

File-file lain yang otomatis ikut branding karena baca constant di atas
(tidak perlu diedit manual, cuma FYI): `cms-admin/login.php`,
`cms-admin/includes/header.php`, `cms-admin/includes/sidebar.php`,
`cms-admin/api/cc-export.php`.

### 3. Audit & cabut modul olahraga

Modul-modul ini di source-nya spesifik olahraga (livescore/football/f1/basket)
dan **WAJIB dicabut/tidak di-copy** ke `wpm2_movie`:
- `football.php`, `f1.php`, `basket.php`, `livescore-poll.php` (root)
- `includes/SportsRegistry.php`
- Cek isi `includes/SpecialPages.php`, `includes/site-header.php`,
  `includes/site-footer.php`, `includes/site-bootstrap.php` — ada referensi
  ke sport_key/livescore, harus disortir/dihapus bagian itu (jangan
  copy-paste mentah, baca dulu satu-satu).
- Cek `cms-admin/pages/` dan `cms-admin/actions/` untuk halaman admin yang
  spesifik manage pertandingan/livescore — cabut juga.

### 4. Sortir semua string "sagagoal"

Hasil grep di source (`grep -ri sagagoal`), file-file ini mengandung
referensi ke sagagoal dan HARUS dicek/diganti satu-satu (jangan asal
find-replace tanpa baca konteks — ada yang di komentar, ada yang di nama
file dokumentasi):
```
kategori.php
index.php
cms-admin/login.php
cms-admin/config/app.php
cms-admin/includes/functions.php
cms-admin/includes/growth-agent-service.php
cms-admin/includes/gsc-api.php
cms-admin/api/seo-generate.php
cms-admin/api/article-generate.php
cms-admin/api/growth-agent-digest.php
cms-admin/api/faq-generate.php
cms-admin/pages/banners.php
cms-admin/pages/site-settings.php
cms-admin/pages/prompt-control-edit.php
artikel.php
generate-sitemap-static.php
pencarian.php
includes/site-footer.php
includes/site-header.php
includes/site-bootstrap.php
.claude/settings.local.json  (JANGAN dicopy — ini punya WPM 1)
.cpanel.yml
manifest.json
sitemap.php
page.php
tentang.php
```
Yang paling umum: nama domain di title/meta default, contoh artikel/seed
data, link kanonik, sitemap base URL, PWA manifest name/short_name,
deploy path di `.cpanel.yml`.

### 5. Modul baru: data film

Tambahkan modul manajemen data film menggantikan modul olahraga yang
dicabut — minimal field: judul, poster, sinopsis, rating, cast/crew,
genre, tahun rilis, jadwal tayang bioskop. **Tidak ada field/kolom untuk
URL player, embed code, atau link streaming dalam bentuk apapun** — ini
pagar keras dari WO 003, bukan opsional.

### 6. Dokumentasi wajib di `wpm2_movie/`

Tulis 3 file ini (pola sama seperti tentakel lain):
- `CLAUDE.md` — project instructions, sebut niche (review film, bukan
  streaming), larangan fitur player/embed, dan catat kalau folder ini
  independen dari Skema 1 (bukan tentakel gurita yang sama).
- `docs/HANDOFF.md`
- `docs/ROADMAP.md`

## Pagar keras (wajib dicek ulang sebelum publish apapun)

Setiap halaman film TIDAK BOLEH ada:
- Player video
- Embed iframe ke provider video (Hydrax, Vidstream, Streamtape, DoodStream,
  MixDrop, dll)
- Link "nonton"/"streaming" ke pihak ketiga manapun

Kalau ada permintaan susulan nambah fitur nonton/streaming/embed video —
itu di luar cakupan WO 003 dan harus ditolak, kecuali operator secara
eksplisit membatalkan keputusan itu di dokumen WO 003.

## Yang belum final (jangan diputuskan sepihak oleh devs)

- Nama DB fisik final — konfirmasi ke operator dulu sebelum `CREATE DATABASE`
- Palet warna & layout homepage (poster-heavy, rating badge) — desain visual
  menyusul terpisah
- GSC/domain/hosting production — di luar scope brief ini, menyusul fase
  pra-launch
