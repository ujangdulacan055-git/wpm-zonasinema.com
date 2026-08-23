# Brief Teknis (Update) — Cleanup Modul Backend Sport (Scope Diperluas) + Cara Matiin Growth Agent Auto-Publish

Ditulis oleh: Cowork session (folder wpm2_movie), 22 Agu 2026
Untuk: sesi Claude Code devs (eksekusi) & operator (buat toggle manual)
Menggantikan/melengkapi: `BRIEF-CLEANUP-SPORT-BACKEND-GROWTH-AGENT-SAFETY.md`
bagian A — bagian ini ditemukan scope-nya lebih besar dari perkiraan awal.

---

## Bagian A — Cabut modul backend olahraga (SCOPE DIPERLUAS)

Konfirmasi operator: **cabut semua, intinya hapus file** — tapi WAJIB
lewat urutan cek-referensi-dulu di bawah, jangan hapus buta. Ini butuh
akses PHP/DB buat testing beneran (Cowork session gak punya runtime buat
verifikasi aman), jadi dikerjakan devs.

### File yang dicabut (gabungan temuan lama + baru)

**Sudah diketahui sebelumnya:**
- `cron/sync_f1_races.php`, `cron/sync_f1_standings.php`,
  `cron/sync_fixtures.php`, `cron/sync_leagues_teams.php`,
  `cron/sync_nba_games.php`
- `includes/ApiBasketballClient.php`, `includes/ApiFootballClient.php`,
  `includes/ApiFormula1Client.php`, `includes/FormulaOneSync.php`
- `cms-admin/partials/settings-form-football.php`,
  `settings-form-basketball.php`, `settings-form-f1.php`
- `assets/js/livescore.js`

**Temuan baru (audit 22 Agu 2026) — PENTING, beda kategori risiko:**
- `cms-admin/includes/sidebar.php` — ada 5 item menu admin: "Football
  Matches" (→ `matches.php`), "NBA Games" (→ `nba-games.php`), "F1 Races"
  (→ `f1-races.php`), "F1 Podium" (→ `f1-podium.php`), "F1 Standings"
  (→ `f1-standings.php`). **File tujuannya SEMUA SUDAH TIDAK ADA** di
  folder — ini link mati yang sudah ada dari SEBELUM kerjaan WPM 2 (bukan
  regresi baru kita), tapi tetap harus dibersihin dari sidebar sekarang
  selagi diaudit.
- `cms-admin/pages/dashboard.php` — tombol "Livescore API Settings" link
  ke `livescore-api-settings.php`, file juga sudah tidak ada.
- `cms-admin/pages/ads.php` — **nyentuh struktur database**, bukan cuma
  kode: kolom `advertisements.placement_scope` (ENUM) di-`ALTER TABLE`
  include nilai `'football'` dan `'basket'` (baris ~109), plus dua opsi
  scope di UI form (`'football' => 'Sepak Bola (/football)'`,
  `'basket' => 'Basket (/basket)'`, baris ~176-177). **Cek dulu apakah ada
  row `advertisements` existing yang pakai scope ini** sebelum ubah ENUM —
  kalau ada, putuskan reassign ke `'global'` atau hapus row-nya (konfirmasi
  ke operator kalau ada data ads aktif yang kepengaruh).

### Langkah wajib per file/area

1. `grep -rn` nama file/class/fungsi terkait ke SELURUH folder sebelum
   hapus apapun.
2. Untuk sidebar.php & dashboard.php: hapus item menu yang link-nya mati,
   verifikasi sidebar admin masih render normal (gak ada notice/error PHP
   soal array key hilang, dst).
3. Untuk `ads.php`: cek data existing dulu (`SELECT COUNT(*) FROM
   advertisements WHERE placement_scope IN ('football','basket')`) sebelum
   ubah ENUM. Kalau ada data, laporkan ke operator dulu, jangan langsung
   ubah/hapus data ads.
4. Cek juga apakah cron entries buat script sync yang dihapus masih
   terdaftar di cPanel Cron Jobs — kalau iya, itu di luar akses coding,
   catat sebagai item yang perlu dinonaktifkan manual oleh operator.
5. `php -l` semua file yang tersisa/diedit setelah selesai.
6. Screenshot admin dashboard + sidebar setelah cleanup, buat bukti nol
   regresi.

---

## Bagian B — Cara matiin Growth Agent auto-publish (buat operator ATAU devs)

**Lokasi setting:** `gsc_settings.opportunity_thresholds_json`, key JSON
`auto_draft_automation`, dua field yang relevan:
- `enabled` — gerbang GENERATION (bikin draft otomatis)
- `auto_publish` — gerbang PUBLISH (draft langsung tayang tanpa review
  manual) — **ini yang paling berbahaya kalau nyala bareng prompt yang
  masih salah niche**

### Opsi 1 — Manual lewat UI admin (operator, paling gampang & aman)

1. Login ke `cms-admin` WPM 2 (ZonaSinema).
2. Buka menu **Growth Agent** → cari panel **"Full Draft Automation —
   Jadwal & Sumber"**.
3. **Uncheck "Nyalakan Full Draft Automation"** (kalau nyala).
4. Scroll ke checkbox **auto-publish** (deskripsinya kira-kira "kalau
   nyala, artikel hasil scrape + AI dari Full Draft Automation di atas
   LANGSUNG TAYANG") — **pastikan ini juga UNCHECKED**, bahkan kalau poin
   3 udah off (defense in depth).
5. Klik **Simpan**. Notifikasi harus muncul "Full Draft Automation
   DIMATIKAN — cron tidak akan generate draft baru."

### Opsi 2 — Lewat devs (kalau operator belum sempat login)

Devs bisa jalanin query langsung (via PHP script sekali-jalan yang baca
`$pdo`, sama pola kayak `seed-wpm2-film-content.php` kemarin) buat set
`enabled` dan `auto_publish` jadi `false` di JSON tersebut — JANGAN raw
SQL string replace di kolom JSON, pakai helper `cms_gsc_set_opportunity_threshold_key()`
yang udah ada di `cms-admin/includes/gsc-api.php` biar formatnya konsisten
sama yang dipakai `growth-agent.php`.

### Setelah dimatikan

Cek tab "Perlu Tindakan" di Growth Agent — kalau ada job `auto_draft_article`
yang statusnya masih pending/draft dari sebelum toggle dimatikan, JANGAN
di-approve dulu sampai prompt-nya direvisi (brief terpisah, item Next di
`docs/ROADMAP.md`).
