<?php
declare(strict_types=1);

/**
 * ZonaSinema — Halaman Detail Film.
 * Reached via the clean URL /film/<slug> (24 Agu 2026, ganti dari
 * /artikel/ — warisan Sagagoal; see root .htaccess), which rewrites to
 * this file as ?slug=<slug> under the hood — this file's own logic
 * never changed, only outgoing links now use wpm_url_artikel(). Still
 * directly reachable as artikel.php?slug=<slug> too, for old links.
 */

require_once __DIR__ . '/includes/site-bootstrap.php';

$slug = trim((string) ($_GET['slug'] ?? ''));

if ($slug === '') {
    header('Location: ' . wpm_url_kategori(), true, 302);
    exit;
}

$stmt = $pdo->prepare(
    "SELECT p.*, c.name AS category_name, c.slug AS category_slug, a.name AS author_name
     FROM pages p
     LEFT JOIN article_categories c ON c.id = p.category_id
     LEFT JOIN admins a ON a.admin_id = p.author_id
     WHERE p.slug = :slug AND p.status = 'published'
     LIMIT 1"
);
$stmt->execute(['slug' => $slug]);
$article = $stmt->fetch();

if (!$article) {
    http_response_code(404);
    $pageTitle = 'Artikel Tidak Ditemukan — ZonaSinema';
    $pageDescription = 'Artikel yang kamu cari tidak ditemukan atau belum diterbitkan.';
    $activeNav = 'berita';
    require __DIR__ . '/includes/site-header.php';
    ?>
    <section class="crypto-section">
        <div class="crypto-container">
            <div class="empty-state">
                <?= wpm_icon('news') ?>
                <p>Artikel tidak ditemukan atau belum diterbitkan.</p>
                <a class="crypto-btn crypto-btn--primary" href="<?= wpm_esc(wpm_url_kategori()) ?>" style="margin-top:16px;display:inline-flex;">Lihat Semua Berita</a>
            </div>
        </div>
    </section>
    </main>
    <?php require __DIR__ . '/includes/site-footer.php'; ?>
    <?php
    exit;
}

$pageId = (int) $article['page_id'];
wpm_increment_views($pdo, $pageId);

/* Tags */
$tagStmt = $pdo->prepare(
    'SELECT t.name, t.slug FROM article_tags t
     INNER JOIN article_tag_map m ON m.tag_id = t.id
     WHERE m.page_id = :id ORDER BY t.name ASC'
);
$tagStmt->execute(['id' => $pageId]);
$tags = $tagStmt->fetchAll();

/* Related articles — same category, excluding this one */
$related = [];
if (!empty($article['category_id'])) {
    $relStmt = $pdo->prepare(
        "SELECT p.*, c.name AS category_name, f.vote_average
         FROM pages p
         LEFT JOIN article_categories c ON c.id = p.category_id
         LEFT JOIN films f ON f.page_id = p.page_id
         WHERE p.status = 'published' AND p.category_id = :cat AND p.page_id != :id
         ORDER BY p.published_at DESC LIMIT 4"
    );
    $relStmt->execute(['cat' => (int) $article['category_id'], 'id' => $pageId]);
    $related = $relStmt->fetchAll();
}
if (count($related) < 4) {
    $need = 4 - count($related);
    $excludeIds = array_merge([$pageId], array_column($related, 'page_id'));
    $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
    $fallStmt = $pdo->prepare(
        "SELECT p.*, c.name AS category_name, f.vote_average
         FROM pages p
         LEFT JOIN article_categories c ON c.id = p.category_id
         LEFT JOIN films f ON f.page_id = p.page_id
         WHERE p.status = 'published' AND p.page_id NOT IN ($placeholders)
         ORDER BY p.published_at DESC LIMIT $need"
    );
    $fallStmt->execute($excludeIds);
    $related = array_merge($related, $fallStmt->fetchAll());
}

/* FAQ — generated via the admin "Generate FAQ" button (cms-admin/api/faq-generate.php),
 * stored as pages.faq_json. Renders nothing at all if that column is empty or the
 * admin never clicked Generate FAQ for this article — this section only exists
 * when there's real content to show. */
$faqItems = [];
$faqDecoded = json_decode((string) ($article['faq_json'] ?? ''), true);
if (is_array($faqDecoded)) {
    foreach ($faqDecoded as $faqItem) {
        if (!is_array($faqItem)) {
            continue;
        }
        $faqQuestion = trim((string) ($faqItem['question'] ?? ''));
        $faqAnswer = trim((string) ($faqItem['answer'] ?? ''));
        if ($faqQuestion === '' || $faqAnswer === '') {
            continue;
        }
        $faqItems[] = ['question' => $faqQuestion, 'answer' => $faqAnswer];
    }
}

$pageTitle = !empty($article['meta_title']) ? (string) $article['meta_title'] : (string) $article['title'] . ' — ZonaSinema';
$pageDescription = !empty($article['meta_description']) ? (string) $article['meta_description'] : wpm_excerpt((string) ($article['excerpt'] ?: $article['content']), 160);
$activeNav = 'berita';
$canonicalUrl = !empty($article['canonical_url']) ? (string) $article['canonical_url'] : wpm_site_url(wpm_url_artikel($slug));
$pageNoindex = (int) ($article['noindex'] ?? 0) === 1;
$ogImage = wpm_image($article['og_image'] ?? null) ?? wpm_image($article['featured_image'] ?? null);
if ($ogImage !== null) {
    $ogImage = wpm_site_url(ltrim($ogImage, '/'));
}

$shareUrl = wpm_site_url(wpm_url_artikel($slug));
$ogType = 'article';

/* ── Structured data (schema.org JSON-LD): NewsArticle + BreadcrumbList ──
 * GSC reports "URL has no enhancements" for articles — this site is a
 * sports news portal, so NewsArticle is the practical requirement for Top
 * Stories eligibility (plus richer date/thumbnail display in results).
 * Built here (before site-header.php is required) and injected via
 * $extraHead — see that file's own docblock for the convention. Separate
 * from the FAQPage block further down in the body; that one is untouched.
 */
// Asia/Jakarta-explicit (9 Aug 2026 fix, same class as wpm_format_date()
// in site-bootstrap.php) — pages.published_at/updated_at are WIB
// wall-clock strings; DATE_ATOM's offset suffix (+00:00 vs +07:00) must
// actually match what the naive timestamp means, or search engines read
// the wrong absolute moment for datePublished/dateModified.
$wpmIso8601 = static function (?string $value): ?string {
    if ($value === null || $value === '') {
        return null;
    }
    try {
        return (new DateTime($value, new DateTimeZone(WPM_MATCH_TZ)))->format(DATE_ATOM);
    } catch (Throwable $e) {
        return null;
    }
};

$schemaSiteSettings = wpm_site_settings($pdo);
$schemaSiteName = trim((string) ($schemaSiteSettings['site_name'] ?? '')) !== ''
    ? (string) $schemaSiteSettings['site_name'] : 'ZonaSinema';
$schemaLogoUrl = wpm_image((string) ($schemaSiteSettings['logo_path'] ?? ''));
if ($schemaLogoUrl !== null) {
    $schemaLogoUrl = wpm_site_url(ltrim($schemaLogoUrl, '/'));
}

$newsArticleSchema = array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'NewsArticle',
    'headline' => mb_substr((string) $article['title'], 0, 110),
    'description' => $pageDescription,
    'datePublished' => $wpmIso8601($article['published_at'] ?? null),
    'dateModified' => $wpmIso8601(!empty($article['updated_at']) ? (string) $article['updated_at'] : ($article['published_at'] ?? null)),
    'mainEntityOfPage' => $canonicalUrl,
    'author' => !empty($article['author_name'])
        ? ['@type' => 'Person', 'name' => (string) $article['author_name']]
        : ['@type' => 'Organization', 'name' => $schemaSiteName],
    'publisher' => array_filter([
        '@type' => 'Organization',
        'name' => $schemaSiteName,
        'logo' => $schemaLogoUrl !== null ? ['@type' => 'ImageObject', 'url' => $schemaLogoUrl] : null,
    ]),
], static fn ($value): bool => $value !== null);
if ($ogImage !== null) {
    $newsArticleSchema['image'] = [$ogImage];
}

// Mirrors the visual breadcrumb <nav> markup below exactly (Beranda →
// Berita → category, category skipped when there isn't one).
$breadcrumbItems = [
    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => wpm_site_url('')],
    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Berita', 'item' => wpm_site_url(wpm_url_kategori())],
];
if (!empty($article['category_name'])) {
    $breadcrumbItems[] = [
        '@type' => 'ListItem',
        'position' => 3,
        'name' => (string) $article['category_name'],
        'item' => wpm_site_url(wpm_url_kategori((string) $article['category_slug'])),
    ];
}
$breadcrumbSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => $breadcrumbItems,
];

// JSON_HEX_TAG escapes angle brackets as backslash-u-escaped hex
// codepoints instead of leaving them literal — without it, a title
// or description containing the substring "</script>" would close
// this script block early and let the rest execute as JavaScript
// (stored XSS via the editor role, the lowest-trust tier in the
// 3-tier RBAC — see docs/DECISIONS.md 2026-07-15). Doesn't affect
// JSON-LD validity: JSON parsers (Google's included) decode the
// escaped codepoints back to the literal characters.
$extraHead = ($extraHead ?? '')
    . '<script type="application/ld+json">' . json_encode($newsArticleSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . '</script>'
    . '<script type="application/ld+json">' . json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . '</script>';

/* ── Promo banners (cms-admin/pages/banners.php), placement="article" ── */
$articleBanners = wpm_banners_active($pdo, 'article');

/* ── Sidebar ad slots (25 Jul 2026) — rendered once here (not inside the
 * layout markup below) so the resulting HTML doubles as both the "is an ad
 * actually active in this slot" check AND the markup to print, instead of
 * querying/counting the impression twice. When a slot comes back empty the
 * <aside> for it is skipped entirely (not just hidden) so the grid column
 * it would have occupied is never reserved — see .article-layout's
 * variant classes in site.css for the column-count logic this drives.
 *
 * 21 Agu 2026 (film detail redesign): the RIGHT sidebar now always renders
 * regardless of $hasRightAd — it carries the "Info Film" + "Jadwal Tayang
 * Bioskop" cards (dummy, see placeholders below), with the ad (if any)
 * appended inside it. Only the LEFT sidebar still fully disappears when
 * there's no ad for it, same as before. */
$leftAdHtml = wpm_render_ad_slot($pdo, 'sidebar-left', 'article', $pageId);
$rightAdHtml = wpm_render_ad_slot($pdo, 'sidebar-right', 'article', $pageId);
$hasLeftAd = $leftAdHtml !== '';
$hasRightAd = $rightAdHtml !== '';
$articleLayoutClass = 'article-layout';
$articleLayoutClass .= $hasLeftAd ? ' article-layout--both' : ' article-layout--right-only';

/* ── Film metadata placeholders (dummy) ──────────────────────────────────
 * Situs ini belum punya tabel `films` terpisah — skema database film
 * di-hold, dibahas terpisah nanti (lihat docs/ROADMAP.md). Rating, genre,
 * cast, dan crew sekarang diambil dari tabel `films` (JOIN by page_id,
 * diisi via import TMDb 23 Agu 2026) — lihat docs/BRIEF-INTEGRASI-TMDB-SKEMA-FILM.md.
 * Kalau sebuah artikel BUKAN film (belum/nggak punya row di `films`,
 * misal artikel lama), semua fact ini fallback ke placeholder netral,
 * BUKAN di-fabricate. Jadwal tayang bioskop TETAP placeholder — TMDb
 * nggak nyediain data jadwal bioskop Indonesia (di luar scope API ini). */
$filmStmt = $pdo->prepare('SELECT * FROM films WHERE page_id = :id LIMIT 1');
$filmStmt->execute(['id' => $pageId]);
$film = $filmStmt->fetch() ?: null;

$filmGenreRows = [];
if ($film !== null) {
    $fgStmt = $pdo->prepare(
        'SELECT fg.label_id FROM film_genre_map fgm
         JOIN film_genres fg ON fg.id = fgm.genre_id
         WHERE fgm.film_id = :filmId ORDER BY fg.label_id ASC'
    );
    $fgStmt->execute(['filmId' => (int) $film['id']]);
    $filmGenreRows = $fgStmt->fetchAll(PDO::FETCH_COLUMN);
}
$filmDummyGenres = $filmGenreRows !== [] ? $filmGenreRows : ['Umum'];

$filmDummyRating = $film !== null && $film['vote_average'] !== null ? (float) $film['vote_average'] : null;
$filmDummyRatingCount = $film !== null && $film['vote_count'] !== null ? (int) $film['vote_count'] : null;
$filmDummyAgeRating = null; // TMDb basic endpoints nggak nyediain age rating Indonesia — badge ini disembunyikan kalau null, bukan di-fabricate
$filmDummyDuration = $film !== null && $film['runtime_minutes'] !== null ? ((int) $film['runtime_minutes'] . ' menit') : null;

$filmCrew = [];
if ($film !== null) {
    $decodedCrew = json_decode((string) $film['crew_json'], true);
    $filmCrew = is_array($decodedCrew) ? $decodedCrew : [];
}
$filmDummyDirector = null;
foreach ($filmCrew as $c) {
    if (($c['role'] ?? '') === 'Sutradara') {
        $filmDummyDirector = (string) $c['name'];
        break;
    }
}
$filmDummyWriter = null; // nggak ditarik dari TMDb credits di skema import ini — baris "Penulis Naskah" disembunyikan kalau null
$filmDummyStudio = null; // production_companies nggak ditarik di skema import ini — baris "Studio" disembunyikan kalau null

$filmDummyCast = [];
if ($film !== null) {
    $decodedCast = json_decode((string) $film['cast_json'], true);
    if (is_array($decodedCast)) {
        foreach ($decodedCast as $c) {
            $character = trim((string) ($c['character'] ?? ''));
            $filmDummyCast[] = ['name' => (string) $c['name'], 'role' => $character !== '' ? 'sebagai ' . $character : 'Pemeran'];
        }
    }
    if ($filmDummyDirector !== null) {
        $filmDummyCast[] = ['name' => $filmDummyDirector, 'role' => 'Sutradara'];
    }
}

// Jadwal tayang bioskop — TMDb nggak nyediain data ini (di luar scope API),
// sengaja dikosongin, jangan fabricate nama bioskop/jam tayang.
$filmDummyCinemas = [];

// Trailer — 24 Agu 2026, keputusan operator: REVISI pagar keras WO 003,
// khusus trailer YouTube RESMI (trailer_youtube_key dari TMDb, videos
// yang udah divalidasi) sekarang BOLEH dibuka lewat popup/modal embed,
// bukan cuma link-out. Cakupan ini SEMPIT & KETAT — modal ini CUMA buat
// trailer promosi durasi pendek, BUKAN pintu ke player film/streaming
// provider pihak ketiga (Hydrax/Vidstream/dll) dalam bentuk apapun. Iframe
// YouTube CUMA di-inject ke DOM pas modal dibuka (lihat script di bawah),
// jadi nol auto-load video pas halaman pertama kali diakses.
$filmTrailerKey = ($film !== null && !empty($film['trailer_youtube_key']))
    ? (string) $film['trailer_youtube_key']
    : null;

/** Inisial 1-2 huruf dari nama, buat avatar bulat dummy cast & crew. */
$wpmInitials = static function (string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
    }
    return $initials;
};

$filmHeroImg = wpm_image($article['featured_image'] ?? null);
// Prioritaskan films.release_date (tanggal rilis film beneran dari TMDb)
// di atas pages.published_at (tanggal artikel ini diterbitkan) — dua hal
// yang beda; fallback ke published_at cuma buat artikel non-film lama.
$filmReleaseSource = ($film !== null && $film['release_date'] !== null) ? $film['release_date'] : ($article['published_at'] ?? null);
$filmYear = wpm_format_date($filmReleaseSource, 'Y');
$filmReleaseDisplay = wpm_format_date($filmReleaseSource, 'd F Y');
$filmSummary = wpm_excerpt((string) ($article['excerpt'] ?: $article['content']), 220);

require __DIR__ . '/includes/site-header.php';
?>

<!-- Hero film: backdrop blur pakai featured_image artikel (bukan field khusus).
     Rating/genre/durasi/sutradara/rilis dari tabel films (JOIN by page_id) —
     lihat blok query di atas. Elemen disembunyikan (bukan di-fabricate)
     kalau field-nya null (artikel non-film, atau film tanpa data lengkap). -->
<section class="film-hero">
    <?php if ($filmHeroImg !== null) : ?>
        <div class="film-hero__backdrop" style="background-image:url('<?= wpm_esc($filmHeroImg) ?>');"></div>
    <?php endif; ?>
    <div class="film-hero__scrim"></div>
    <div class="film-hero__inner crypto-container">
        <div class="film-hero__poster">
            <?php if ($filmHeroImg !== null) : ?>
                <img src="<?= wpm_esc($filmHeroImg) ?>" alt="<?= wpm_esc((string) $article['title']) ?>">
            <?php else : ?>
                <?= wpm_icon('news') ?>
            <?php endif; ?>
        </div>
        <div class="film-hero__meta">
            <div class="film-hero__badges">
                <?php if ($filmDummyAgeRating !== null) : ?>
                    <span class="film-hero__age"><?= wpm_esc($filmDummyAgeRating) ?></span>
                <?php endif; ?>
                <span class="film-hero__note">Tayang di Bioskop<?= $filmYear !== '' ? ' · ' . wpm_esc($filmYear) : '' ?></span>
            </div>
            <h1><?= wpm_esc((string) $article['title']) ?></h1>

            <div class="film-hero__facts">
                <?php if ($filmDummyRating !== null) : ?>
                <div class="film-hero__rating">
                    <div class="film-hero__rating-circle"><span><?= wpm_esc(number_format($filmDummyRating, 1)) ?></span></div>
                    <div>
                        <div class="film-hero__rating-label">Skor TMDb</div>
                        <div class="film-hero__rating-count"><?= $filmDummyRatingCount !== null ? 'berdasar ' . (int) $filmDummyRatingCount . ' vote' : '' ?></div>
                    </div>
                </div>
                <span class="film-hero__fact-divider"></span>
                <?php endif; ?>
                <?php if ($filmDummyDirector !== null) : ?>
                    <div class="film-hero__fact"><span>Sutradara</span><strong><?= wpm_esc($filmDummyDirector) ?></strong></div>
                <?php endif; ?>
                <?php if ($filmDummyDuration !== null) : ?>
                    <div class="film-hero__fact"><span>Durasi</span><strong><?= wpm_esc($filmDummyDuration) ?></strong></div>
                <?php endif; ?>
                <?php if ($filmReleaseDisplay !== '') : ?>
                    <div class="film-hero__fact"><span>Rilis</span><strong><?= wpm_esc($filmReleaseDisplay) ?></strong></div>
                <?php endif; ?>
            </div>

            <?php if ($filmSummary !== '') : ?>
                <p class="film-hero__summary"><?= wpm_esc($filmSummary) ?></p>
            <?php endif; ?>

            <div class="film-hero__badges">
                <?php foreach ($filmDummyGenres as $genreLabel) : ?>
                    <span class="genre-pill"><?= wpm_esc($genreLabel) ?></span>
                <?php endforeach; ?>
            </div>

            <?php if ($filmTrailerKey !== null) : ?>
                <!-- Modal embed trailer YouTube RESMI doang (24 Agu 2026, revisi
                     operator) — iframe cuma di-inject pas dibuka, lihat #trailer-modal
                     di bawah. BUKAN player film/streaming provider pihak ketiga. -->
                <button type="button" class="btn-trailer" data-trailer-key="<?= wpm_esc($filmTrailerKey) ?>" data-trailer-open>
                    <svg viewBox="0 0 24 24" fill="currentColor" width="15" height="15"><path d="M8 5v14l11-7L8 5Z"/></svg>
                    Tonton Trailer di YouTube
                </button>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="crypto-section--tight">
    <div class="crypto-container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= wpm_esc(wpm_site_url('')) ?>">Beranda</a> <span>/</span>
            <a href="<?= wpm_esc(wpm_url_kategori()) ?>">Berita</a>
            <?php if (!empty($article['category_name'])) : ?>
                <span>/</span> <a href="<?= wpm_esc(wpm_url_kategori((string) $article['category_slug'])) ?>"><?= wpm_esc((string) $article['category_name']) ?></a>
            <?php endif; ?>
        </nav>

        <div class="<?= wpm_esc($articleLayoutClass) ?>">
            <?php if ($hasLeftAd) : ?>
            <!-- Sticky sidebar ad (left) -->
            <aside class="article-layout__sidebar">
                <?= $leftAdHtml ?>
            </aside>
            <?php endif; ?>

            <div class="article-layout__main film-body">
                <?= wpm_render_ad_slot($pdo, 'article-before-title', 'article', $pageId) ?>

                <div class="article-head__meta film-body__byline">
                    <?php if (!empty($article['category_name'])) : ?>
                        <a href="<?= wpm_esc(wpm_url_kategori((string) $article['category_slug'])) ?>" class="article-head__category"><?= wpm_esc((string) $article['category_name']) ?></a>
                    <?php endif; ?>
                    <?php if (!empty($article['author_name'])) : ?><span><?= wpm_icon('news') ?><?= wpm_esc((string) $article['author_name']) ?></span><?php endif; ?>
                    <span><?= wpm_icon('clock') ?><?= wpm_esc(wpm_format_date($article['published_at'] ?? null, 'd M Y, H:i')) ?></span>
                    <span><?= wpm_icon('eye') ?><?= (int) $article['views'] ?> views</span>
                </div>

                <?= wpm_render_ad_slot($pdo, 'article-after-title', 'article', $pageId) ?>
                <?= wpm_render_ad_slot($pdo, 'above-article', 'article', $pageId) ?>

                <div class="film-section film-synopsis">
                    <h2>Sinopsis</h2>
                    <div class="article-prose">
                        <?= wpm_inject_midpoint((string) $article['content'], wpm_render_ad_slot($pdo, 'middle-of-article', 'article', $pageId)) ?>
                    </div>
                </div>

                <!-- Cast & Crew — dari films.cast_json/crew_json (TMDb), lihat blok query di atas -->
                <div class="film-section">
                    <h2>Pemeran &amp; Kru</h2>
                    <div class="cast-grid">
                        <?php foreach ($filmDummyCast as $castMember) : ?>
                            <div class="cast-item">
                                <div class="cast-item__avatar"><span><?= wpm_esc($wpmInitials((string) $castMember['name'])) ?></span></div>
                                <div class="cast-item__name"><?= wpm_esc((string) $castMember['name']) ?></div>
                                <div class="cast-item__role"><?= wpm_esc((string) $castMember['role']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?= wpm_render_ad_slot($pdo, 'below-article', 'article', $pageId) ?>

                <?php if ($articleBanners !== []) : ?>
                <div class="banner-strip" style="margin:24px 0;">
                    <div class="banner-strip__grid">
                        <?php foreach ($articleBanners as $banner) : ?>
                            <?= wpm_banner_markup($banner) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($tags !== []) : ?>
                <div class="article-tags">
                    <?php foreach ($tags as $tag) : ?>
                        <a href="<?= wpm_esc(wpm_url_tag((string) $tag['slug'])) ?>"><?= wpm_icon('tag') ?><?= wpm_esc((string) $tag['name']) ?></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="article-share">
                    <span class="article-share__label">Bagikan:</span>
                    <a href="https://wa.me/?text=<?= urlencode((string) $article['title'] . ' ' . $shareUrl) ?>" target="_blank" rel="noopener" aria-label="Bagikan ke WhatsApp"><?= wpm_icon('chat') ?></a>
                    <a href="https://twitter.com/intent/tweet?text=<?= urlencode((string) $article['title']) ?>&amp;url=<?= urlencode($shareUrl) ?>" target="_blank" rel="noopener" aria-label="Bagikan ke Twitter/X"><?= wpm_icon('share') ?></a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($shareUrl) ?>" target="_blank" rel="noopener" aria-label="Bagikan ke Facebook"><?= wpm_icon('network') ?></a>
                    <button type="button" id="wpm-copy-link" title="Salin link" aria-label="Salin link"><?= wpm_icon('tag') ?></button>
                </div>

                <?php if ($faqItems !== []) : ?>
                <div class="article-faq">
                    <h2>Pertanyaan Umum</h2>
                    <?php foreach ($faqItems as $faqItem) : ?>
                        <details class="article-faq__item">
                            <summary><?= wpm_esc($faqItem['question']) ?></summary>
                            <div class="article-faq__answer"><?= wpm_esc($faqItem['answer']) ?></div>
                        </details>
                    <?php endforeach; ?>
                </div>
                <script type="application/ld+json"><?= json_encode([
                    '@context' => 'https://schema.org',
                    '@type' => 'FAQPage',
                    'mainEntity' => array_map(static fn (array $i): array => [
                        '@type' => 'Question',
                        'name' => $i['question'],
                        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $i['answer']],
                    ], $faqItems),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
                <?php endif; ?>

                <?php if ($related !== []) : ?>
                <div class="film-section related-articles">
                    <h2>Film Terkait</h2>
                    <div class="poster-slider">
                        <?php foreach ($related as $rel) : ?>
                            <?= wpm_poster_card($rel) ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar kanan: Info Film (data films/TMDb) + Jadwal Tayang Bioskop
                 (placeholder, TMDb nggak nyediain data ini) selalu tampil; ad slot
                 (kalau aktif) ikut di sini juga. -->
            <aside class="article-layout__sidebar">
                <div class="sidebar-block">
                    <h3>Info Film</h3>
                    <div class="sidebar-block__rows">
                        <?php if ($filmDummyDirector !== null) : ?>
                            <div class="sidebar-block__row"><span>Sutradara</span><strong><?= wpm_esc($filmDummyDirector) ?></strong></div>
                        <?php endif; ?>
                        <?php if ($filmDummyWriter !== null) : ?>
                            <div class="sidebar-block__row"><span>Penulis Naskah</span><strong><?= wpm_esc($filmDummyWriter) ?></strong></div>
                        <?php endif; ?>
                        <?php if ($filmDummyStudio !== null) : ?>
                            <div class="sidebar-block__row"><span>Studio</span><strong><?= wpm_esc($filmDummyStudio) ?></strong></div>
                        <?php endif; ?>
                        <div class="sidebar-block__row"><span>Genre</span><strong><?= wpm_esc(implode(', ', $filmDummyGenres)) ?></strong></div>
                        <?php if ($filmReleaseDisplay !== '') : ?>
                            <div class="sidebar-block__row"><span>Tanggal Rilis</span><strong><?= wpm_esc($filmReleaseDisplay) ?></strong></div>
                        <?php endif; ?>
                        <?php if ($filmDummyDuration !== null) : ?>
                            <div class="sidebar-block__row"><span>Durasi</span><strong><?= wpm_esc($filmDummyDuration) ?></strong></div>
                        <?php endif; ?>
                        <?php if ($filmDummyAgeRating !== null) : ?>
                            <div class="sidebar-block__row sidebar-block__row--last"><span>Rating Usia</span><strong><?= wpm_esc($filmDummyAgeRating) ?></strong></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="sidebar-block">
                    <h3><?= wpm_icon('calendar') ?> Jadwal Tayang Bioskop</h3>
                    <p class="sidebar-block__note">Info jadwal tayang, bukan tautan streaming.</p>
                    <?php if ($filmDummyCinemas !== []) : ?>
                    <div class="sidebar-block__cinemas">
                        <?php foreach ($filmDummyCinemas as $cinema) : ?>
                            <div class="cinema-item">
                                <div class="cinema-item__name"><?= wpm_icon('pin') ?><?= wpm_esc((string) $cinema['name']) ?></div>
                                <div class="cinema-item__city"><?= wpm_esc((string) $cinema['city']) ?></div>
                                <div class="cinema-item__times">
                                    <?php foreach ($cinema['times'] as $time) : ?>
                                        <span class="time-pill"><?= wpm_esc((string) $time) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else : ?>
                        <p class="sidebar-block__row">Cek jadwal di bioskop terdekat.</p>
                    <?php endif; ?>
                </div>

                <?php if ($hasRightAd) : ?>
                    <?= $rightAdHtml ?>
                <?php endif; ?>
            </aside>
        </div>
    </div>
</section>

<?php if ($filmTrailerKey !== null) : ?>
<!-- Modal trailer YouTube (24 Agu 2026, revisi operator — lihat komentar
     di atas dekat $filmTrailerKey). Iframe TIDAK ada di DOM sampai modal
     dibuka (JS inject src pas open, hapus pas close) — nol auto-load,
     nol elemen video nempel pas halaman pertama diakses. CUMA buat
     trailer YouTube resmi, bukan player film/streaming apapun. -->
<div class="trailer-modal" id="trailer-modal" role="dialog" aria-modal="true" aria-label="Trailer film" hidden>
    <div class="trailer-modal__backdrop" data-trailer-close></div>
    <div class="trailer-modal__panel">
        <button type="button" class="trailer-modal__close" data-trailer-close aria-label="Tutup trailer">&times;</button>
        <div class="trailer-modal__frame" id="trailer-modal-frame"></div>
    </div>
</div>
<script>
(function () {
    var modal = document.getElementById('trailer-modal');
    var frame = document.getElementById('trailer-modal-frame');
    if (!modal || !frame) { return; }

    function openModal(key) {
        // Src cuma di-set pas dibuka — sengaja gak taruh iframe statis di
        // markup, biar YouTube nol nge-load apapun sebelum user niat nonton.
        var iframe = document.createElement('iframe');
        iframe.src = 'https://www.youtube.com/embed/' + encodeURIComponent(key) + '?autoplay=1';
        iframe.title = 'Trailer YouTube';
        iframe.setAttribute('allow', 'autoplay; encrypted-media; picture-in-picture');
        iframe.setAttribute('allowfullscreen', '');
        iframe.setAttribute('frameborder', '0');
        frame.appendChild(iframe);
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.hidden = true;
        frame.innerHTML = ''; // hapus iframe -> video ke-stop, bukan cuma disembunyiin
        document.body.style.overflow = '';
    }

    document.querySelectorAll('[data-trailer-open]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var key = btn.getAttribute('data-trailer-key');
            if (key) { openModal(key); }
        });
    });
    document.querySelectorAll('[data-trailer-close]').forEach(function (el) {
        el.addEventListener('click', closeModal);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hidden) { closeModal(); }
    });
})();
</script>
<?php endif; ?>

</main>
<?php require __DIR__ . '/includes/site-footer.php'; ?>
