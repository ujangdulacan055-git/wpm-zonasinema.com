#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * ZonaSinema — import film dari TMDb API. REUSABLE, jalanin kapan pun mau
 * nambah film baru (24 Agu 2026, brief "Perluas Import Data Film TMDb") —
 * beda dari script sekali-jalan pertama (import-tmdb-films.php, sudah
 * dihapus 23 Agu 2026 setelah dipakai buat 35 film awal). Skema tabel
 * films/film_genres/film_genre_map TIDAK diubah, script ini cuma nambah
 * data (idempotent — skip film yang tmdb_id-nya udah ada).
 *
 * CLI ONLY — jangan taruh di folder yang bisa diakses browser/URL publik
 * tanpa guard ini. Kalau ke-trigger lewat HTTP, langsung exit (lihat
 * guard di bawah).
 *
 * Cara pakai (dari root project, di dalam container/server):
 *   php scripts/import-tmdb.php --source=popular --pages=10
 *
 * Opsi:
 *   --source=popular|top_rated|now_playing|upcoming   (default: popular)
 *   --pages=N                                          (default: 1, tiap halaman ~20 film)
 *
 * Butuh TMDB_API_TOKEN di .env (root project) — TIDAK PERNAH hardcode
 * token di file ini. Throttle ~200ms antar request API, sopan ke rate
 * limit TMDb (key masih "Personal use"/Developer Plan, lihat
 * docs/ROADMAP.md bagian "Now").
 *
 * Pagar keras WO 003: cuma ambil metadata (judul/poster/sinopsis/rating/
 * genre/cast/crew/trailer key). Trailer key disimpan buat link-out/modal
 * YouTube doang di frontend — script ini sendiri NOL nyentuh apapun yang
 * berhubungan sama player/embed/streaming, cuma nulis ke DB.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Forbidden — script CLI-only.\n");
}

require_once __DIR__ . '/../cms-admin/config/database.php'; // $pdo

// ── Parse argumen CLI ───────────────────────────────────────────────────
$options = getopt('', ['source::', 'pages::']);
$source = trim((string) ($options['source'] ?? 'popular'));
$pages = max(1, (int) ($options['pages'] ?? 1));

$allowedSources = ['popular', 'top_rated', 'now_playing', 'upcoming'];
if (!in_array($source, $allowedSources, true)) {
    fwrite(STDERR, "ERROR: --source harus salah satu dari: " . implode(', ', $allowedSources) . "\n");
    exit(1);
}

// ── Load token dari .env (root project) ─────────────────────────────────
$envPath = __DIR__ . '/../.env';
if (!is_file($envPath)) {
    fwrite(STDERR, "ERROR: .env tidak ditemukan di root project.\n");
    exit(1);
}
$env = parse_ini_file($envPath);
$token = trim((string) ($env['TMDB_API_TOKEN'] ?? ''));
if ($token === '') {
    fwrite(STDERR, "ERROR: TMDB_API_TOKEN kosong di .env.\n");
    exit(1);
}

function tmdb_get(string $path, string $token, array $query = []): array
{
    $url = 'https://api.themoviedb.org/3' . $path;
    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Accept: application/json'],
        CURLOPT_TIMEOUT => 20,
    ]);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($res === false) {
        throw new RuntimeException("cURL error on $path: $err");
    }
    $decoded = json_decode($res, true);
    if ($code !== 200 || !is_array($decoded)) {
        throw new RuntimeException("TMDb API error on $path: HTTP $code — " . substr((string) $res, 0, 300));
    }
    return $decoded;
}

function slugify(string $s): string
{
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? $s;
    return trim($s, '-') ?: 'film';
}

/** Sama pola kayak import awal (23 Agu 2026) — editorial paragraph, BUKAN
 * copy overview TMDb mentah-mentah (biar bukan duplicate content SEO). */
function build_editorial_content(string $title, string $overview, ?string $director, array $castNames, ?string $releaseYear, array $genreNames): string
{
    $genreLine = $genreNames !== [] ? implode(', ', $genreNames) : 'berbagai genre';
    $castLine = $castNames !== [] ? implode(', ', array_slice($castNames, 0, 4)) : 'jajaran pemeran yang belum diumumkan lengkap';
    $directorLine = $director !== null && $director !== '' ? $director : 'sutradara yang belum tercatat';

    $p1 = sprintf(
        '%s adalah film %s yang disutradarai oleh %s, dengan jajaran pemeran termasuk %s.',
        $title,
        $releaseYear !== null ? "rilisan $releaseYear" : 'rilisan terbaru',
        $directorLine,
        $castLine
    );

    $p2 = $overview !== ''
        ? 'Secara garis besar, ' . lcfirst($overview)
        : 'Sinopsis lengkap film ini belum tersedia — cek kembali setelah data diperbarui.';

    $p3 = sprintf('Masuk dalam kategori %s, %s cocok buat penonton yang mencari tontonan baru untuk diulas lebih lanjut di ZonaSinema.', $genreLine, $title);

    return '<p>' . htmlspecialchars($p1, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<p>' . htmlspecialchars($p2, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<p>' . htmlspecialchars($p3, ENT_QUOTES, 'UTF-8') . '</p>';
}

echo "== Import TMDb — source={$source}, pages={$pages} ==\n";

// Genre map (tmdb_genre_id -> films.genre row id) — dibaca dari DB, bukan
// hardcode, biar tetap sinkron kalau nanti ada genre baru ditambahin manual.
$genreRows = $pdo->query('SELECT id, tmdb_genre_id FROM film_genres')->fetchAll();
$genreIdMap = [];
foreach ($genreRows as $row) {
    $genreIdMap[(int) $row['tmdb_genre_id']] = (int) $row['id'];
}
if ($genreIdMap === []) {
    fwrite(STDERR, "ERROR: tabel film_genres kosong — jalanin migration dulu.\n");
    exit(1);
}

$genreNamesResp = tmdb_get('/genre/movie/list', $token, ['language' => 'id-ID']);
$genreNameById = [];
foreach ($genreNamesResp['genres'] ?? [] as $g) {
    $genreNameById[(int) $g['id']] = (string) $g['name'];
}

$categoryId = (int) $pdo->query("SELECT id FROM article_categories WHERE slug = 'film' LIMIT 1")->fetchColumn();
$authorId = (int) $pdo->query('SELECT admin_id FROM admins ORDER BY admin_id ASC LIMIT 1')->fetchColumn();

// ── Kumpulin kandidat dari N halaman endpoint /movie/{source} ───────────
$candidates = [];
for ($page = 1; $page <= $pages; $page++) {
    try {
        $resp = tmdb_get("/movie/{$source}", $token, ['language' => 'id-ID', 'page' => $page]);
    } catch (Throwable $e) {
        fwrite(STDERR, "- GAGAL narik halaman {$page}: " . $e->getMessage() . "\n");
        continue;
    }
    foreach ($resp['results'] ?? [] as $m) {
        $candidates[] = $m;
    }
    usleep(200000);
    if (($resp['total_pages'] ?? 0) < $page + 1) {
        break;
    }
}
echo '- ' . count($candidates) . " kandidat film ditarik dari /movie/{$source} ({$pages} halaman)\n";

$insertPage = $pdo->prepare(
    'INSERT INTO pages (
        title, slug, featured_image, content, excerpt, meta_title, meta_description,
        status, published_at, category_id, author_id, is_featured, is_trending, noindex,
        created_at, updated_at
    ) VALUES (
        :title, :slug, :featured_image, :content, :excerpt, :meta_title, :meta_description,
        :status, NOW(), :category_id, :author_id, 0, 0, 0,
        NOW(), NOW()
    )'
);

$insertFilm = $pdo->prepare(
    'INSERT INTO films (
        page_id, tmdb_id, original_title, release_date, runtime_minutes,
        vote_average, vote_count, popularity, poster_path, backdrop_path,
        trailer_youtube_key, cast_json, crew_json, synced_at
    ) VALUES (
        :page_id, :tmdb_id, :original_title, :release_date, :runtime_minutes,
        :vote_average, :vote_count, :popularity, :poster_path, :backdrop_path,
        :trailer_youtube_key, :cast_json, :crew_json, NOW()
    )'
);

$insertGenreMap = $pdo->prepare('INSERT IGNORE INTO film_genre_map (film_id, genre_id) VALUES (:film_id, :genre_id)');
$existsStmt = $pdo->prepare('SELECT COUNT(*) FROM films WHERE tmdb_id = :id');
$slugCheck = $pdo->prepare('SELECT COUNT(*) FROM pages WHERE slug = :slug');

$total = count($candidates);
$imported = 0;
$skippedDuplicate = 0;
$skippedNoGenre = 0;
$failed = 0;
$i = 0;

foreach ($candidates as $m) {
    $i++;
    $tmdbId = (int) ($m['id'] ?? 0);
    $titleForLog = (string) ($m['title'] ?? "tmdb_id={$tmdbId}");

    if ($tmdbId <= 0) {
        echo "[{$i}/{$total}] Skip (data tidak valid)\n";
        $failed++;
        continue;
    }

    $existsStmt->execute(['id' => $tmdbId]);
    if ((int) $existsStmt->fetchColumn() > 0) {
        echo "[{$i}/{$total}] Skip (already exists): \"{$titleForLog}\" (tmdb_id: {$tmdbId})\n";
        $skippedDuplicate++;
        continue;
    }

    try {
        $detail = tmdb_get("/movie/{$tmdbId}", $token, ['language' => 'id-ID']);
        usleep(200000);
        $credits = tmdb_get("/movie/{$tmdbId}/credits", $token, ['language' => 'id-ID']);
        usleep(200000);
        $videos = tmdb_get("/movie/{$tmdbId}/videos", $token, ['language' => 'en-US']);
        usleep(200000);
    } catch (Throwable $e) {
        echo "[{$i}/{$total}] GAGAL fetch \"{$titleForLog}\": " . $e->getMessage() . "\n";
        $failed++;
        continue;
    }

    $title = (string) ($detail['title'] ?? $titleForLog);

    // Genre — film HARUS punya minimal 1 genre yang match ke 8 genre bar
    // yang ada, kalau nol match, SKIP film ini (jangan insert tanpa genre,
    // ganggu filter genre bar homepage).
    $genreIds = array_map(static fn ($g) => (int) $g['id'], $detail['genres'] ?? []);
    $matchedGenreIds = array_values(array_filter($genreIds, static fn (int $gid) => isset($genreIdMap[$gid])));
    if ($matchedGenreIds === []) {
        echo "[{$i}/{$total}] Skip (nol genre yang match): \"{$title}\"\n";
        $skippedNoGenre++;
        continue;
    }
    $genreNames = array_values(array_filter(array_map(static fn (int $gid) => $genreNameById[$gid] ?? null, $genreIds)));

    $slug = slugify($title);
    $slugCheck->execute(['slug' => $slug]);
    if ((int) $slugCheck->fetchColumn() > 0) {
        $slug .= '-' . $tmdbId;
    }

    $overview = (string) ($detail['overview'] ?? '');
    $releaseDate = (string) ($detail['release_date'] ?? '');
    $releaseYear = $releaseDate !== '' ? substr($releaseDate, 0, 4) : null;
    $runtime = $detail['runtime'] ?? null;

    $director = null;
    foreach ($credits['crew'] ?? [] as $c) {
        if (($c['job'] ?? '') === 'Director') {
            $director = (string) $c['name'];
            break;
        }
    }
    $topCast = array_slice($credits['cast'] ?? [], 0, 10);
    $castNames = array_map(static fn ($c) => (string) $c['name'], $topCast);
    $castJson = array_map(static fn ($c) => [
        'name' => (string) $c['name'],
        'character' => (string) ($c['character'] ?? ''),
        'profile_path' => $c['profile_path'] ?? null,
    ], $topCast);
    $crewJson = $director !== null ? [['name' => $director, 'role' => 'Sutradara']] : [];

    // Trailer — prioritaskan yang official=true, type=Trailer, site=YouTube.
    $trailerKey = null;
    $trailerFallback = null;
    foreach ($videos['results'] ?? [] as $v) {
        if (($v['site'] ?? '') === 'YouTube' && ($v['type'] ?? '') === 'Trailer') {
            if (!empty($v['official'])) {
                $trailerKey = (string) $v['key'];
                break;
            }
            if ($trailerFallback === null) {
                $trailerFallback = (string) $v['key'];
            }
        }
    }
    $trailerKey = $trailerKey ?? $trailerFallback;

    $posterPath = $detail['poster_path'] ?? null;
    $featuredImage = $posterPath !== null ? 'https://image.tmdb.org/t/p/w780' . $posterPath : null;

    $content = build_editorial_content($title, $overview, $director, $castNames, $releaseYear, $genreNames);

    $pdo->beginTransaction();
    try {
        $insertPage->execute([
            'title' => $title,
            'slug' => $slug,
            'featured_image' => $featuredImage,
            'content' => $content,
            'excerpt' => $overview !== '' ? mb_substr($overview, 0, 300) : ('Review dan detail film ' . $title . '.'),
            'meta_title' => $title . ' — ZonaSinema',
            'meta_description' => $overview !== '' ? mb_substr($overview, 0, 155) : ('Review, rating, dan detail lengkap film ' . $title . '.'),
            'status' => 'published',
            'category_id' => $categoryId ?: null,
            'author_id' => $authorId ?: null,
        ]);
        $pageId = (int) $pdo->lastInsertId();

        $insertFilm->execute([
            'page_id' => $pageId,
            'tmdb_id' => $tmdbId,
            'original_title' => (string) ($detail['original_title'] ?? $title),
            'release_date' => $releaseDate !== '' ? $releaseDate : null,
            'runtime_minutes' => $runtime !== null ? (int) $runtime : null,
            'vote_average' => $detail['vote_average'] ?? null,
            'vote_count' => $detail['vote_count'] ?? null,
            'popularity' => $detail['popularity'] ?? null,
            'poster_path' => $posterPath,
            'backdrop_path' => $detail['backdrop_path'] ?? null,
            'trailer_youtube_key' => $trailerKey,
            'cast_json' => json_encode($castJson, JSON_UNESCAPED_UNICODE),
            'crew_json' => json_encode($crewJson, JSON_UNESCAPED_UNICODE),
        ]);
        $filmId = (int) $pdo->lastInsertId();

        foreach ($matchedGenreIds as $gid) {
            $insertGenreMap->execute(['film_id' => $filmId, 'genre_id' => $genreIdMap[$gid]]);
        }

        $pdo->commit();
        $imported++;
        echo "[{$i}/{$total}] Imported: \"{$title}\" (tmdb_id: {$tmdbId})\n";
    } catch (Throwable $e) {
        $pdo->rollBack();
        echo "[{$i}/{$total}] GAGAL insert \"{$title}\": " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "== Selesai ==\n";
echo "Imported: {$imported}\n";
echo "Skip (duplikat): {$skippedDuplicate}\n";
echo "Skip (nol genre match): {$skippedNoGenre}\n";
echo "Gagal: {$failed}\n";
