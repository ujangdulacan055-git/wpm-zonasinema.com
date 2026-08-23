# Brief Teknis — Revisi Growth Agent ke Niche Film (dari Sports/Sagagoal)

Ditulis oleh: Cowork session (folder `wpm2_movie/docs`), 23 Agu 2026
Untuk: sesi Claude Code devs (eksekusi)
Menyelesaikan: `docs/ROADMAP.md` "Next" #1 (revisi Growth Agent)

---

## Latar belakang

Growth Agent (modul AI content automation) ikut ter-clone dari WPM 1
(Sagagoal). Toggle auto-publish udah dikonfirmasi **OFF** (lihat
`ROADMAP.md` "Now" — 0 job pending, aman), jadi ini **bukan lagi soal
keamanan**, murni soal kualitas: kalau nanti diaktifin, AI-nya masih
mikir ini situs bola, bukan situs film. Brief ini beresin itu.

Audit langsung ke `cms-admin/includes/growth-agent-service.php` dan
`cms-admin/includes/gsc-api.php` (23 Agu 2026) nemuin **8 titik system
prompt** + **2 array default source URL** yang masih hardcode niche
olahraga & nama "Sagagoal".

---

## Bagian A — 8 titik system prompt (`growth-agent-service.php`)

Ganti kata **"Sagagoal"** → **"ZonaSinema"**, dan deskripsi niche dari
**"livescore & sports news (football/basketball/F1)"** → **"movie
review & database (rating, genre, cast, sutradara, sinopsis)"** di
semua titik ini:

| Baris (skrg) | Fungsi | Snippet asli |
|---|---|---|
| ~399 | Topic clustering (SEO) | `"Growth Agent SEO strategist for Sagagoal, a livescore & sports news website"` |
| ~550 | Cannibalization review | `"Growth Agent SEO strategist for Sagagoal, a livescore & sports news website"` |
| ~2825 | Generate ide artikel baru | `"Growth Agent content strategist for Sagagoal, an Indonesian-language sports news & livescore portal covering football..., basketball/NBA, and Formula 1"` — **ada larangan eksplisit** `"Do not propose generic listicles or topics outside football/basketball/F1"`, ini WAJIB diganti jadi larangan versi film (misal: "topics outside movie reviews, cast/crew profiles, or genre roundups") |
| ~3727 | Revisi meta title/description | `"Sagagoal article (a livescore & sports news site)"` |
| ~3887 | Rewrite artikel decay (yang turun ranking) | `"Growth Agent content strategist for Sagagoal, a livescore & sports news website (football, basketball/NBA, Formula 1)"` |
| ~3901 | Rewrite artikel near-page-1 | sama persis kayak baris 3887, varian kedua |
| ~4018 | Ide artikel dari search query bertraffic | `"Growth Agent content strategist for Sagagoal, a livescore & sports news website (football, basketball/NBA, Formula 1)"` |
| ~4097 | Label konteks headline eksternal | `"Recent sports news headlines (context/inspiration only...)"` → ganti `"sports news"` jadi `"movie/entertainment news"` |

**Penting**: baris nomor di atas itu approximate (posisi pas audit 23 Agu
2026) — `grep -n "Sagagoal\|football, basketball" cms-admin/includes/growth-agent-service.php`
dulu buat konfirmasi posisi persis sebelum edit, biar gak kepeleset ke
baris yang salah kalau ada perubahan lain di antaranya.

Juga cek fungsi lain yang manggil `$defaultSystemPrompt` ini — pastikan
gak ada titik ke-9/10 yang kelewat ke-grep (audit ini dari satu pass
`grep -n "Sagagoal"` + `grep -n "football, basketball"`, kemungkinan
besar udah semua tapi devs tetap grep ulang buat mastiin nol sisa).

---

## Bagian B — 2 array default source URL (`gsc-api.php`)

Dua array terpisah, keduanya perlu diganti (jangan cuma satu — ini pola
duplikasi yang udah ada dari kode WPM 1, source_urls dipakai buat
"Full Draft Automation" spesifik, `sources` dipakai buat trending-
headlines context feed yang lebih umum):

**1. `sources` (~baris 1094-1097)** — dipakai buat context/inspirasi
umum (headline yang di-embed ke prompt generate ide):
```php
'sources' => [
    'https://sport.detik.com',
    'https://www.cnnindonesia.com/olahraga',
],
```
Ganti jadi:
```php
'sources' => [
    'https://hot.detik.com/movie',
    'https://www.cnnindonesia.com/hiburan/film',
],
```

**2. `source_urls` (~baris 1395-1398)** — dipakai spesifik buat "Full
Draft Automation" (fitur yang bisa auto-generate DAN auto-publish
draft — makanya ini yang paling kritis diganti, walau auto_publish
masih off sekarang):
```php
'source_urls' => [
    'https://www.detik.com/tag/sepak-bola',
    'https://www.cnnindonesia.com/olahraga',
],
```
Ganti jadi (4 sumber, riset 23 Agu 2026 — semua situs berita mainstream
Indonesia, section/tag film, update rutin):
```php
'source_urls' => [
    'https://hot.detik.com/movie',
    'https://www.cnnindonesia.com/hiburan/film',
    'https://www.detik.com/tag/film-indonesia',
    'https://www.kapanlagi.com/tag/review-film/',
],
```

**Verifikasi setelah ganti**: cek tiap URL punya `/rss` atau `/feed`
yang jalan (sama kayak catatan di baris ~1067 buat default lama) —
kalau nggak ada, sistem fallback ke HTML-scrape yang "best effort, bukan
guarantee" (komentar existing di kode). Kalau salah satu dari 4 URL di
atas ternyata gak punya RSS yang jalan pas dicek devs, laporkan balik,
kita cari pengganti.

---

## Bagian C — Kamus stopword/NLP (opsional, kualitas bukan blocker)

Ada kamus stopword custom (`growth-agent-service.php` ~baris 671-698)
yang di-tuning khusus buat bahasa berita olahraga Indonesia (dipakai
buat deteksi topic-similarity/duplicate content antar artikel). Kalau
dibiarin apa adanya, deteksi "artikel mirip" kemungkinan kurang akurat
buat konten film (istilah "starter, formasi, klasemen" beda total sama
istilah "sutradara, sinematografi, box office"). **Bukan blocker** —
sistem tetap jalan tanpa ini, cuma kualitas dedup-nya kurang optimal.
Kalau ada waktu lebih, devs bisa tambahin istilah film umum
(sutradara, pemeran, genre, box office, trailer, sekuel, dst) ke daftar
generic-term filter itu. Kalau nggak sempat, skip aja, catat sebagai
util item terpisah nanti — jangan sampai nunda Bagian A & B.

---

## Yang TIDAK berubah (di luar scope brief ini)

- Toggle `auto_draft_automation.enabled` dan `.auto_publish` — **tetap
  `false`**, jangan diaktifin sebagai bagian dari brief ini. Ini murni
  ganti isi prompt & source, bukan nyalain automation-nya. Aktivasi itu
  keputusan operator terpisah, kapan pun nanti.
- Struktur database/tabel Growth Agent — nggak disentuh.

---

## Setelah selesai

1. `grep -n "Sagagoal\|football, basketball\|sport.detik\|cnnindonesia.com/olahraga\|sepak-bola"` ke `growth-agent-service.php` DAN `gsc-api.php` — target: nol match tersisa (kecuali di komentar histori/changelog yang emang nyatet asal-usul kode, itu boleh dibiarin sebagai jejak audit).
2. `php -l` kedua file yang diedit.
3. Kalau ada UI admin (`cms-admin/pages/growth-agent.php`) yang nampilin
   preview/label niche, screenshot buat mastiin teksnya juga udah film,
   bukan olahraga lagi.
4. Update `docs/ROADMAP.md` — pindahin item ini dari "Next" ke "Done",
   isi ringkasan hasil (berapa titik prompt diganti, source_urls final).
