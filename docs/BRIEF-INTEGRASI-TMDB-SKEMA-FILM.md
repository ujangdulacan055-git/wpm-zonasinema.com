# Brief Teknis — Integrasi TMDb API + Skema Database Film + Wiring Genre Bar

Ditulis oleh: Cowork session (folder `wpm2_movie/docs`), 23 Agu 2026
Untuk: sesi Claude Code devs (eksekusi)
Menyelesaikan: `docs/ROADMAP.md` poin 8 ("Skema Database Film", status ⏸️ On hold)

**Status: ✅ SELESAI dieksekusi devs, 23 Agu 2026** — lihat `ROADMAP.md`
arsip "Done" buat hasil lengkapnya. Satu koreksi ketauan pas eksekusi:
PK asli tabel `pages` itu `page_id`, bukan `id` — SQL di Bagian B di
bawah udah diperbaiki biar dokumentasi ini akurat buat referensi nanti.

---

## Latar belakang

Homepage & Halaman Detail Film sekarang render pakai **6 artikel dummy**
dari seed script satu-kali (`seed-wpm2-film-content.php`, sudah dihapus
setelah dipakai). Genre bar di `includes/site-header.php` (Aksi / Horror /
Komedi / Sci-Fi / Romansa / Drama / Animasi / Thriller / 2025 / 2026 /
Terpopuler) **belum konek ke apapun** — semua link-nya sama persis
(`wpm_url_kategori()` tanpa parameter), jadi klik genre manapun hasilnya
identik. Rating badge, cast/crew, genre di Halaman Detail Film juga masih
dummy hardcoded.

Brief ini nyambungin semuanya ke data film beneran via **TMDb API**, biar
genre bar, rating, filter tahun, dan "Terpopuler" semuanya fungsional.

## Pagar keras — WAJIB dibaca dulu (WO 003, tidak berubah)

TMDb API cuma dipakai buat ambil **metadata** (judul, poster, sinopsis,
rating, genre, cast/crew, tanggal rilis, key trailer YouTube). **TIDAK ADA
data streaming/link nonton apapun yang diambil atau ditampilkan.** Trailer
cuma boleh jadi link-out `target="_blank"` ke `youtube.com/watch?v={key}`
— **DILARANG** di-embed sebagai iframe/player di halaman manapun. Ini
konsisten sama audit pagar keras 22 Agu 2026 (nol pelanggaran) — jangan
sampai integrasi ini yang jadi pelanggaran pertama.

---

## Bagian A — Setup TMDb API (sudah dieksekusi operator, 23 Agu 2026)

**Update 23 Agu 2026**: ZonaSinema jalan **tanpa AdSense/monetisasi apapun**
buat sekarang — tujuan awal murni bangun traffic & impresi organik di GSC
(lihat `ROADMAP.md` bagian "Now"). Karena situs gak menghasilkan revenue,
ini **beneran non-commercial** menurut definisi TMDb sendiri ("primary
purpose is to create revenue") — jadi:

1. Operator daftar akun gratis di https://www.themoviedb.org/signup
2. Ajukan API key di Settings → API, jawab **"Yes — personal use"** pas
   ditanya tipe penggunaan → dapet **Developer Plan (gratis, no monthly
   fee)**, bukan Commercial Plan ($149/bulan) — ini akurat, bukan
   akal-akalan, karena situs beneran belum ada monetisasi.
   ⚠️ **Kalau nanti AdSense dipasang beneran**, WAJIB balik ke sini dan
   upgrade ke Commercial Plan (atau ganti sumber data ke alternatif lebih
   murah kayak IMDb API via API.market, ~$5-10/bulan) — jangan biarin
   situs monetized tetep pakai key personal use, itu pelanggaran ToS.
3. Simpan **API Read Access Token (v4 auth)** — bukan API key v3 — ke
   environment variable baru, terpisah dari kredensial lain: `TMDB_API_TOKEN`
   di `.env` atau config server (JANGAN hardcode di kode, JANGAN commit ke
   Git).
4. **Wajib attribution**: syarat pakai TMDb API adalah nampilin logo/teks
   "Powered by TMDb — This product uses the TMDb API but is not endorsed
   or certified by TMDb" di footer situs (lihat brand guidelines TMDb).
   Tambahin ke `includes/site-footer.php`.

---

## Bagian B — Skema database baru

Tabel baru, **terpisah dari `pages`** biar gak nyampur sama kolom lama
(`league_id`, `sport_key` dkk peninggalan sports — biarin aja kosong buat
row film, jangan diotak-atik dulu, itu scope cleanup lain).

```sql
CREATE TABLE films (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  page_id INT UNSIGNED NOT NULL,           -- FK ke pages.page_id (1:1)
  tmdb_id INT UNSIGNED NOT NULL,
  original_title VARCHAR(255),
  release_date DATE NULL,
  runtime_minutes SMALLINT UNSIGNED NULL,
  vote_average DECIMAL(3,1) NULL,          -- rating TMDb, 0.0–10.0
  vote_count INT UNSIGNED NULL,
  popularity DECIMAL(10,3) NULL,           -- buat sort "Terpopuler"
  poster_path VARCHAR(255) NULL,           -- path relatif TMDb image CDN
  backdrop_path VARCHAR(255) NULL,
  trailer_youtube_key VARCHAR(32) NULL,    -- buat link-out, BUKAN embed
  cast_json JSON NULL,                     -- top 8-10 cast, {name, character, profile_path}
  crew_json JSON NULL,                     -- sutradara/writer utama
  synced_at DATETIME NULL,                 -- kapan terakhir ditarik dari TMDb
  UNIQUE KEY uq_page (page_id),
  UNIQUE KEY uq_tmdb (tmdb_id),
  FOREIGN KEY (page_id) REFERENCES pages(page_id) ON DELETE CASCADE
);

CREATE TABLE film_genres (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tmdb_genre_id INT UNSIGNED NOT NULL UNIQUE,
  slug VARCHAR(50) NOT NULL UNIQUE,        -- 'aksi', 'horror', 'komedi', dst
  label_id VARCHAR(50) NOT NULL            -- label Indonesia buat ditampilin
);

CREATE TABLE film_genre_map (
  film_id INT UNSIGNED NOT NULL,
  genre_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (film_id, genre_id),
  FOREIGN KEY (film_id) REFERENCES films(id) ON DELETE CASCADE,
  FOREIGN KEY (genre_id) REFERENCES film_genres(id) ON DELETE CASCADE
);
```

### Seed `film_genres` — mapping TMDb genre ID ke label genre bar existing

Genre bar yang udah ada di homepage (`includes/site-header.php` baris
175-187) harus dipetain ke genre ID resmi TMDb ini (`GET /genre/movie/list`):

| Label genre bar | TMDb genre_id | slug |
|---|---|---|
| Aksi | 28 (Action) | `aksi` |
| Horror | 27 (Horror) | `horror` |
| Komedi | 35 (Comedy) | `komedi` |
| Sci-Fi | 878 (Science Fiction) | `sci-fi` |
| Romansa | 10749 (Romance) | `romansa` |
| Drama | 18 (Drama) | `drama` |
| Animasi | 16 (Animation) | `animasi` |
| Thriller | 53 (Thriller) | `thriller` |

"2025" / "2026" bukan genre — itu filter `release_date` per tahun (query
`WHERE YEAR(release_date) = 2025`), bukan lewat tabel genre.
"Terpopuler" — sort `ORDER BY popularity DESC`, bukan filter, jadi gak
butuh row genre juga.

---

## Bagian C — Import data film dari TMDb

Bikin script sekali-jalan (pola sama kayak `seed-wpm2-film-content.php`
kemarin, tapi manggil API beneran) buat narik daftar film awal:

1. Pakai endpoint `GET /discover/movie` (bisa difilter by genre, tahun,
   sort by popularity) atau `GET /movie/popular` buat starting list —
   target awal ~30-50 film biar homepage & kategori gak keliatan kosong.
2. Untuk tiap film: `GET /movie/{id}` (detail + runtime + genres),
   `GET /movie/{id}/credits` (cast/crew), `GET /movie/{id}/videos`
   (filter `type=Trailer` & `site=YouTube`, ambil `key` yang pertama).
3. Insert ke `pages` (title, slug dari title, excerpt dari overview TMDb,
   content ditulis manual/AI-assist berdasar overview — JANGAN full-copy
   sinopsis TMDb mentah-mentah biar bukan duplicate content buat SEO,
   reword jadi gaya editorial ZonaSinema), `featured_image` = URL poster
   TMDb (`https://image.tmdb.org/t/p/w780{poster_path}`).
4. Insert ke `films` (semua field teknis) + `film_genre_map` (dari
   `genre_ids` yang dikembaliken API, cocokin ke `film_genres.tmdb_genre_id`).
5. Artikel dummy lama (6 biji dari seed sebelumnya) — konfirmasi ke
   operator: dihapus/di-unpublish, atau dibiarin nyampur sama data TMDb?
   Rekomendasi: **unpublish** (bukan hapus), biar gak ke-index Google
   sebagai konten setengah-jadi.
6. `php -l` semua file baru, cek query `film_genre_map` gak N+1 query
   parah pas render homepage (pakai JOIN, bukan query per-card).

---

## Bagian D — Wiring genre bar & filter (yang bikin situ komplain kemarin)

1. `includes/site-header.php` baris 175-187: ganti tiap link genre dari
   `wpm_url_kategori()` polos jadi `wpm_url_kategori('aksi')` dst (atau
   pola URL apapun yang dipakai `kategori.php` — cek existing routing
   dulu), passing slug genre.
2. `kategori.php`: terima parameter genre/tahun, query `films` JOIN
   `film_genre_map` JOIN `film_genres` WHERE slug = ? (atau
   `WHERE YEAR(release_date) = ?` buat filter tahun), render pakai
   `wpm_poster_card()` yang udah ada.
3. "Terpopuler": route/anchor sendiri, query `ORDER BY films.popularity DESC`.
4. `artikel.php` (Halaman Detail Film): ganti rating/genre/cast/cinema-
   schedule dummy hardcoded jadi query beneran ke tabel `films` (JOIN by
   `page_id`). Jadwal tayang bioskop TETAP dummy/dikosongin (TMDb gak
   nyediain data jadwal bioskop Indonesia — itu di luar scope API ini,
   biarin placeholder "Cek jadwal di bioskop terdekat" tanpa data spesifik
   biar gak misleading).
5. Screenshot before/after genre bar setelah wiring, verifikasi klik tiap
   genre emang nampilin film beda-beda (bukan semua nyasar ke satu
   halaman kayak sekarang).

---

## Bagian E — Sinkronisasi berkala (opsional, bahas belakangan)

TMDb data (rating, popularity) berubah dari waktu ke waktu. Kalau mau
selalu update, bisa bikin cron sync mingguan yang refresh `vote_average`,
`vote_count`, `popularity` buat film yang udah ada (`synced_at` dipakai
buat throttle biar gak nge-hit rate limit TMDb). **Ini bukan prioritas
sekarang** — cukup dicatat sebagai future improvement, jangan dikerjain
duluan sebelum data awal & wiring genre bar selesai.

---

## Ringkasan urutan eksekusi

1. Operator: bikin akun TMDb + API token (Bagian A).
2. Devs: bikin tabel `films`, `film_genres`, `film_genre_map` (Bagian B).
3. Devs: import ~30-50 film awal dari TMDb (Bagian C).
4. Devs: wiring genre bar, filter tahun, Terpopuler, halaman detail film
   ke data beneran (Bagian D).
5. Devs: screenshot verifikasi + `php -l` + cek pagar keras (grep ulang
   trailer link, pastikan cuma `<a target="_blank">`, nol `<iframe>`).
6. Update `docs/ROADMAP.md` poin 8 dari "On hold" jadi "Done" setelah semua
   di atas selesai & diverifikasi.
