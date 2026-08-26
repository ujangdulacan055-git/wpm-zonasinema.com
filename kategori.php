<?php
declare(strict_types=1);

/**
 * Sagagoal — article listing page. Handles four modes via query string:
 *   kategori.php                → all published articles, paginated
 *   kategori.php?slug=bitcoin   → articles in one category
 *   kategori.php?tag=defi       → articles with one tag
 *   kategori.php?league=3       → articles linked to one league (pages.league_id)
 * This is also what the "Berita" nav item, article tag chips, and the news
 * homepage's league filter row link to.
 */

require_once __DIR__ . '/includes/site-bootstrap.php';

$categorySlug = trim((string) ($_GET['slug'] ?? ''));
$tagSlug = trim((string) ($_GET['tag'] ?? ''));
$leagueId = (int) ($_GET['league'] ?? 0);
$genreSlug = trim((string) ($_GET['genre'] ?? ''));
$filmYear = trim((string) ($_GET['year'] ?? ''));
$filmPopular = isset($_GET['popular']);
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 50; // 24 Agu 2026: naik dari 9 -> 50, biar "Semua Film" (35 film
                // sekarang) muat 1 halaman tanpa pagination gak perlu.

$category = null;
$tag = null;
$league = null;
$genre = null;
$where = "p.status = 'published'";
$params = [];

/* ── Mode genre/tahun/terpopuler (23 Agu 2026) — filter beneran ke tabel
 * films/film_genres, dipakai genre bar di includes/site-header.php. Beda
 * dari mode kategori/tag/league di atas (yang query article_categories/
 * article_tags/leagues) — sengaja dipisah karena films JOIN pages, bukan
 * kolom pages langsung. ── */
$isFilmFilterMode = $genreSlug !== '' || $filmYear !== '' || $filmPopular;
/* ZonaSinema murni film — kartu selalu render sebagai poster (bukan artikel),
 * TAPI ini dipisah dari $isFilmFilterMode di atas karena variabel itu juga
 * menggerbang WHERE clause (baris di bawah) yang membatasi query ke
 * page_id yang ada di tabel films. Kalau dipaksa selalu true, "Semua Film"
 * (tanpa filter genre/tahun/populer) jadi ikut nge-block artikel yang belum
 * ke-link ke films. $useFilmCardLayout aman selalu true karena cuma
 * ngatur badge/grid/renderer kartu, bukan query. (23 Agu 2026, Bagian B) */
$useFilmCardLayout = true;
if ($isFilmFilterMode) {
    $where .= ' AND p.page_id IN (SELECT page_id FROM films)';
    if ($genreSlug !== '') {
        $genreStmt = $pdo->prepare('SELECT * FROM film_genres WHERE slug = :slug LIMIT 1');
        $genreStmt->execute(['slug' => $genreSlug]);
        $genre = $genreStmt->fetch() ?: null;
        if ($genre === null) {
            http_response_code(404);
        } else {
            $where .= ' AND p.page_id IN (
                SELECT f.page_id FROM films f
                JOIN film_genre_map fgm ON fgm.film_id = f.id
                WHERE fgm.genre_id = :genreId
            )';
            $params['genreId'] = (int) $genre['id'];
        }
    }
    if ($filmYear !== '' && ctype_digit($filmYear)) {
        $where .= ' AND p.page_id IN (SELECT page_id FROM films WHERE YEAR(release_date) = :filmYear)';
        $params['filmYear'] = (int) $filmYear;
    }
}

if ($categorySlug !== '') {
    $catStmt = $pdo->prepare('SELECT * FROM article_categories WHERE slug = :slug LIMIT 1');
    $catStmt->execute(['slug' => $categorySlug]);
    $category = $catStmt->fetch() ?: null;
    if ($category === null) {
        http_response_code(404);
    } else {
        $where .= ' AND p.category_id = :catId';
        $params['catId'] = (int) $category['id'];
    }
} elseif ($tagSlug !== '') {
    $tagStmt = $pdo->prepare('SELECT * FROM article_tags WHERE slug = :slug LIMIT 1');
    $tagStmt->execute(['slug' => $tagSlug]);
    $tag = $tagStmt->fetch() ?: null;
    if ($tag === null) {
        http_response_code(404);
    } else {
        $where .= ' AND p.page_id IN (SELECT page_id FROM article_tag_map WHERE tag_id = :tagId)';
        $params['tagId'] = (int) $tag['id'];
    }
} elseif ($leagueId > 0) {
    $leagueStmt = $pdo->prepare('SELECT * FROM leagues WHERE id = :id AND is_active = 1 LIMIT 1');
    $leagueStmt->execute(['id' => $leagueId]);
    $league = $leagueStmt->fetch() ?: null;
    if ($league === null) {
        http_response_code(404);
    } else {
        $where .= ' AND p.league_id = :leagueId';
        $params['leagueId'] = (int) $league['id'];
    }
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM pages p WHERE $where");
$countStmt->execute($params);
$totalArticles = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalArticles / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$orderBy = ($isFilmFilterMode && $filmPopular)
    ? '(SELECT f.popularity FROM films f WHERE f.page_id = p.page_id) DESC'
    : 'p.published_at DESC';

$listStmt = $pdo->prepare(
    "SELECT p.*, c.name AS category_name, c.slug AS category_slug, f.vote_average, f.release_date
     FROM pages p
     LEFT JOIN article_categories c ON c.id = p.category_id
     LEFT JOIN films f ON f.page_id = p.page_id
     WHERE $where
     ORDER BY $orderBy
     LIMIT $perPage OFFSET $offset"
);
$listStmt->execute($params);
$articles = $listStmt->fetchAll();

$allCategories = [];
try {
    $allCategories = $pdo->query(
        "SELECT c.*, COUNT(p.page_id) AS article_count
         FROM article_categories c
         LEFT JOIN pages p ON p.category_id = c.id AND p.status = 'published'
         GROUP BY c.id ORDER BY c.name ASC"
    )->fetchAll();
} catch (Throwable $e) {
    $allCategories = [];
}

$paginateUrl = static function (int $p) use ($categorySlug, $tagSlug, $leagueId, $genreSlug, $filmYear, $filmPopular): string {
    if ($tagSlug !== '') {
        return wpm_url_tag($tagSlug) . '?page=' . $p;
    }
    if ($leagueId > 0) {
        return wpm_url_kategori() . '?league=' . $leagueId . '&page=' . $p;
    }
    if ($genreSlug !== '') {
        return 'kategori.php?genre=' . rawurlencode($genreSlug) . '&page=' . $p;
    }
    if ($filmYear !== '') {
        return 'kategori.php?year=' . rawurlencode($filmYear) . '&page=' . $p;
    }
    if ($filmPopular) {
        return 'kategori.php?popular=1&page=' . $p;
    }
    return wpm_url_kategori($categorySlug !== '' ? $categorySlug : null) . '?page=' . $p;
};

if ($genre !== null) {
    $pageTitle = 'Film ' . $genre['label_id'] . ' — ZonaSinema';
    $pageDescription = 'Kumpulan review dan database film genre ' . $genre['label_id'] . ' di ZonaSinema.';
    $heroTitle = 'Film ' . $genre['label_id'];
    $heroSubtitle = 'Kumpulan film genre ' . $genre['label_id'] . '.';
} elseif ($filmYear !== '') {
    $pageTitle = 'Film Rilis ' . $filmYear . ' — ZonaSinema';
    $pageDescription = 'Kumpulan film yang rilis tahun ' . $filmYear . ' di ZonaSinema.';
    $heroTitle = 'Film Rilis ' . $filmYear;
    $heroSubtitle = 'Kumpulan film dengan tahun rilis ' . $filmYear . '.';
} elseif ($filmPopular) {
    $pageTitle = 'Film Terpopuler — ZonaSinema';
    $pageDescription = 'Film paling populer menurut data TMDb di ZonaSinema.';
    $heroTitle = 'Film Terpopuler';
    $heroSubtitle = 'Diurutkan berdasar popularitas.';
} elseif ($category !== null) {
    $pageTitle = 'Berita ' . $category['name'] . ' — ZonaSinema';
    $pageDescription = 'Kumpulan berita dan artikel kategori ' . $category['name'] . ' di ZonaSinema.';
    $heroTitle = $category['name'];
    $heroSubtitle = 'Kumpulan berita & artikel kategori ini.';
} elseif ($tag !== null) {
    $pageTitle = 'Tag: ' . $tag['name'] . ' — ZonaSinema';
    $pageDescription = 'Kumpulan artikel dengan tag ' . $tag['name'] . ' di ZonaSinema.';
    $heroTitle = '#' . $tag['name'];
    $heroSubtitle = 'Artikel dengan tag ini.';
} elseif ($league !== null) {
    $pageTitle = 'Berita ' . $league['name'] . ' — ZonaSinema';
    $pageDescription = 'Kumpulan berita seputar ' . $league['name'] . ' di ZonaSinema.';
    $heroTitle = $league['name'];
    $heroSubtitle = 'Kumpulan berita terkait liga ini.';
} else {
    $pageTitle = 'Semua Film — ZonaSinema';
    $pageDescription = 'Kumpulan seluruh review dan artikel film dari ZonaSinema.';
    $heroTitle = 'Semua Film';
    $heroSubtitle = 'Kumpulan seluruh artikel yang sudah diterbitkan.';
}
$activeNav = 'berita';
$canonicalUrl = wpm_site_url(
    $tagSlug !== ''
        ? wpm_url_tag($tagSlug)
        : ($leagueId > 0 ? wpm_url_kategori() . '?league=' . $leagueId : wpm_url_kategori($categorySlug !== '' ? $categorySlug : null))
);

/* ── Sidebar ad slot (right) — same pattern as artikel.php: rendered once
 * here so the resulting HTML doubles as the "is an ad actually active"
 * check and the markup to print. Empty slot => no <aside>, no reserved
 * column (see .news-grid-layout variants in site.css). ── */
$sidebarAdHtml = wpm_render_ad_slot($pdo, 'sidebar-right', 'category', $category['id'] ?? null);
$hasSidebarAd = $sidebarAdHtml !== '';
$newsGridLayoutClass = 'news-grid-layout' . ($hasSidebarAd ? ' news-grid-layout--right-only' : '');

require __DIR__ . '/includes/site-header.php';
?>

<section class="page-hero">
    <div class="crypto-container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= wpm_esc(wpm_site_url('')) ?>">Beranda</a> <span>/</span> <a href="<?= wpm_esc(wpm_url_kategori()) ?>">Film</a>
            <?php if ($category !== null) : ?><span>/</span> <?= wpm_esc($category['name']) ?><?php endif; ?>
            <?php if ($league !== null) : ?><span>/</span> <?= wpm_esc($league['name']) ?><?php endif; ?>
        </nav>
        <span class="section-kicker"><?= $useFilmCardLayout ? 'Film' : 'Berita' ?></span>
        <h1><?= wpm_esc($heroTitle) ?></h1>
        <p><?= wpm_esc(strip_tags($heroSubtitle)) ?> <?= wpm_esc((string) $totalArticles) ?> <?= $useFilmCardLayout ? 'film' : 'artikel' ?>.</p>
    </div>
</section>

<?php if (!$useFilmCardLayout && $allCategories !== []) : ?>
<div class="crypto-container" style="margin-bottom:8px;">
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <a class="crypto-btn crypto-btn--ghost" style="padding:8px 16px;font-size:13px;<?= $category === null && $tag === null ? 'background:var(--grad-brand);color:#fff;' : '' ?>" href="<?= wpm_esc(wpm_url_kategori()) ?>">Semua</a>
        <?php foreach ($allCategories as $cat) : ?>
            <a class="crypto-btn crypto-btn--ghost" style="padding:8px 16px;font-size:13px;<?= $category !== null && $category['id'] == $cat['id'] ? 'background:var(--grad-brand);color:#fff;' : '' ?>" href="<?= wpm_esc(wpm_url_kategori((string) $cat['slug'])) ?>"><?= wpm_esc((string) $cat['name']) ?> (<?= (int) $cat['article_count'] ?>)</a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<section class="crypto-section--tight">
    <div class="crypto-container">
        <?= wpm_render_ad_slot($pdo, 'above-article', 'category', $category['id'] ?? null) ?>

        <div class="<?= wpm_esc($newsGridLayoutClass) ?>">
            <div class="news-grid-layout__main">
                <?php if ($articles !== []) : ?>
                    <?php
                    $betweenCardsAdHtml = wpm_render_ad_slot($pdo, 'between-article-cards', 'category', $category['id'] ?? null);
                    $adInsertAfter = min(5, count($articles) - 1);
                    ?>
                    <div class="<?= $useFilmCardLayout ? 'poster-grid' : 'crypto-grid crypto-grid--3' ?>">
                        <?php foreach ($articles as $i => $article) : ?>
                            <?= $useFilmCardLayout ? wpm_poster_card($article) : wpm_article_card($article) ?>
                            <?php if ($betweenCardsAdHtml !== '' && $i === $adInsertAfter) : ?>
                                <div class="news-grid__ad"><?= $betweenCardsAdHtml ?></div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($totalPages > 1) : ?>
                    <nav class="pagination" aria-label="Pagination">
                        <a class="<?= $page <= 1 ? 'is-disabled' : '' ?>" href="<?= wpm_esc($paginateUrl(max(1, $page - 1))) ?>">&larr;</a>
                        <?php for ($p = 1; $p <= $totalPages; $p++) : ?>
                            <a class="<?= $p === $page ? 'is-current' : '' ?>" href="<?= wpm_esc($paginateUrl($p)) ?>"><?= $p ?></a>
                        <?php endfor; ?>
                        <a class="<?= $page >= $totalPages ? 'is-disabled' : '' ?>" href="<?= wpm_esc($paginateUrl(min($totalPages, $page + 1))) ?>">&rarr;</a>
                    </nav>
                    <?php endif; ?>
                <?php else : ?>
                    <div class="empty-state"><?= wpm_icon('news') ?><p>Belum ada artikel untuk ditampilkan.</p></div>
                <?php endif; ?>
            </div>

            <?php if ($hasSidebarAd) : ?>
            <aside class="news-grid-layout__sidebar">
                <?= $sidebarAdHtml ?>
            </aside>
            <?php endif; ?>
        </div>
    </div>
</section>

</main>
<?php require __DIR__ . '/includes/site-footer.php'; ?>
