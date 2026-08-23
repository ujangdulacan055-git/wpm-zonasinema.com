# Brief Teknis — Cleanup Modul Backend Olahraga + Safety Stopgap Growth Agent

Ditulis oleh: Cowork session (folder wpm2_movie), 22 Agu 2026
Untuk: sesi Claude Code devs yang eksekusi coding di folder `wpm2_movie`
Rujukan: laporan Pre-Production Cleanup ZonaSinema (poin 3-8), temuan
tambahan yang belum diputuskan.

## Bagian A — Cabut modul backend olahraga yang ketemu di audit

Konfirmasi operator: **cabut sekalian**, sama kayak 5 file di poin 7
sebelumnya. Pola yang sama wajib diikuti — jangan hapus buta:

File yang dicabut:
- `cron/sync_f1_races.php`, `cron/sync_f1_standings.php`,
  `cron/sync_fixtures.php`, `cron/sync_leagues_teams.php`,
  `cron/sync_nba_games.php`
- `includes/ApiBasketballClient.php`, `includes/ApiFootballClient.php`,
  `includes/ApiFormula1Client.php`, `includes/FormulaOneSync.php`
- Halaman settings admin football/basket/f1 (di `cms-admin/pages/`)
- `assets/js/livescore.js`

Langkah:
1. `grep -rn` tiap nama file/class ke seluruh folder — terutama
   `cms-admin/includes/sidebar.php` (menu admin), `includes/site-bootstrap.php`,
   `includes/site-header.php`/`site-footer.php` (kalau ada `<script>` yang
   load `livescore.js`), dan file registrasi cron (`.cpanel.yml` atau
   catatan cron table kalau ada).
2. **Cek juga apakah cron job ini terdaftar aktif di cPanel/cron table.**
   Kalau ya, itu di luar akses coding — catat sebagai item yang perlu
   dinonaktifkan manual oleh operator via cPanel → Cron Jobs (sama pola
   kayak isu cron di WPM 1 dulu, `docs/ROADMAP.md` WPM 1).
3. Beresin semua referensi/link yang nemu dulu, baru hapus filenya.
4. `php -l` ulang semua file yang tersisa/diedit.

## Bagian B — Growth Agent: safety stopgap SEKARANG + rencana brief terpisah

**Temuan:** 8 system prompt di `cms-admin/includes/growth-agent-service.php`
masih berasumsi "livescore & sports news website" (transfer rumors,
standings, dst) — salah niche total buat situs film.

**Aksi SEKARANG (jangan tunda):**
1. Cek di CMS admin (`growth-agent.php` / `gsc-settings.php`) apakah
   toggle **"Full Draft Automation"** / auto-publish otomatis nyala di
   WPM 2. **Kalau nyala, matiin sekarang juga** — ini stopgap keamanan
   biar AI gak nulis & publish draft dengan asumsi niche yang salah
   selagi kita belum sempat revisi prompt-nya.
2. Kalau ada job yang udah terlanjur di-generate dengan asumsi sport
   (draft belum di-approve), tandai/tolak draft itu, jangan di-approve.
3. **Jangan revisi 8 system prompt itu di brief ini** — itu di luar scope,
   nyerempet area Growth Agent yang butuh baca `docs/GROWTH_AGENT_V2_PROPOSAL.md`
   + `docs/DECISIONS.md` dulu (per catatan project), dan idealnya
   dikelola lewat Prompt Control (UI admin), bukan hardcode ulang di
   kode. Ini jadi brief terpisah nanti — dicatat di `docs/ROADMAP.md`
   sebagai item Next.

## Setelah selesai

Laporkan: hasil grep Bagian A (file udah dicabut + status cron kalau
ketemu terdaftar), status toggle auto-publish Growth Agent (dimatikan/
sudah mati dari awal), dan `php -l` semua file yang disentuh.
