<?php
declare(strict_types=1);

/**
 * ZonaSinema — public homepage. Tabbed film feed ("Terbaru" / "Terpopuler")
 * with a hero card + poster grid on the left, and a trending sidebar on
 * the right. "Tentang Kami" is its own page (tentang.php); "Kontak" is a
 * Special Page served via page.php (see includes/SpecialPages.php).
 */

require_once __DIR__ . '/includes/site-bootstrap.php';

// Default tab (10 Agu 2026, permintaan operator) — "Terbaru" adalah tab
// pertama/utama di homepage, jadi itu yang harus tampil default saat
// pertama buka index.php tanpa ?tab= sama sekali.
//
// Tab kedua diganti dari "Untuk Anda" jadi "Terpopuler" (24 Agu 2026) —
// "Untuk Anda" itu pola personalized-feed khas portal berita/livescore
// (butuh sistem rekomendasi yang gak ada), gak masuk akal buat situs
// database film. "Terpopuler" urut dari rating (films.vote_average) —
// direvisi (24 Agu 2026, permintaan operator) dari films.popularity
// (metrik TMDb, gak intuitif buat pengunjung) ke rating asli film,
// mirip pola IMDb/Letterboxd.
$tab = ($_GET['tab'] ?? '') === 'terpopuler' ? 'terpopuler' : 'terbaru';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;

$where = "p.status = 'published'";
$queryParams = [];
// "Terbaru" diurutkan dari films.release_date (tanggal rilis film
// beneran) — direvisi (24 Agu 2026) dari p.created_at (waktu row
// ke-insert/import ke DB kita, BUKAN kapan film-nya rilis — bikin
// urutan "Terbaru" gak nyambung sama tanggal rilis asli pas import
// TMDb baru dijalanin belakangan). Fallback ke p.created_at kalau film
// belum punya release_date (jaga-jaga data lama/non-TMDb).
$orderBy = $tab === 'terbaru'
    ? 'COALESCE((SELECT f.release_date FROM films f WHERE f.page_id = p.page_id), p.created_at) DESC'
    : '(SELECT f.vote_average FROM films f WHERE f.page_id = p.page_id) DESC';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM pages p WHERE $where");
$countStmt->execute($queryParams);
$totalArticles = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalArticles / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$listStmt = $pdo->prepare(
    "SELECT p.*, c.name AS category_name, a.name AS author_name, f.vote_average, f.backdrop_path
     FROM pages p
     LEFT JOIN article_categories c ON c.id = p.category_id
     LEFT JOIN admins a ON a.admin_id = p.author_id
     LEFT JOIN films f ON f.page_id = p.page_id
     WHERE $where
     ORDER BY $orderBy
     LIMIT $perPage OFFSET $offset"
);
$listStmt->execute($queryParams);
$feedArticles = $listStmt->fetchAll();

// Hero card is just the first result of page 1 — subsequent pages are a plain list.
$heroArticle = ($page === 1 && $feedArticles !== []) ? array_shift($feedArticles) : null;

/* ── Sidebar "Banyak Dicari": 10 film paling banyak dilihat (24 Agu 2026,
 * permintaan operator — sebelumnya cuma 4 dan dibatasi is_featured=1,
 * jadi cuma nongol 2 film karena baru 2 yang ke-flag featured dari import
 * TMDb. Sekarang semua film published ikut diurutkan, gak dibatasi
 * featured, biar listnya keliatan penuh 10 item. ── */
$trendingStmt = $pdo->query(
    "SELECT p.*, a.name AS author_name, f.vote_average
     FROM pages p
     LEFT JOIN admins a ON a.admin_id = p.author_id
     LEFT JOIN films f ON f.page_id = p.page_id
     WHERE p.status = 'published'
     ORDER BY p.views DESC, p.published_at DESC
     LIMIT 10"
);
$trendingArticles = $trendingStmt->fetchAll();

/* ── Promo banners (cms-admin/pages/banners.php), placement="home" ── */
$homeBanners = wpm_banners_active($pdo, 'home');

$paginateUrl = static function (int $p) use ($tab): string {
    return 'index.php?tab=' . $tab . '&page=' . $p;
};
$tabUrl = static function (string $t): string {
    return 'index.php?tab=' . $t;
};

$pageTitle = 'Review & Database Film Terkini';
$pageDescription = 'Portal review dan database film: sinopsis, rating, jadwal tayang bioskop, dan berita terbaru seputar film.';
$activeNav = 'beranda';
$canonicalUrl = wpm_site_url('');

require __DIR__ . '/includes/site-header.php';
?>

    <div class="crypto-container">
        <!-- ══════════ TABS: Terbaru / Terpopuler ══════════ -->
        <div class="news-tabs">
            <a class="news-tabs__item<?= $tab === 'terbaru' ? ' is-active' : '' ?>" href="<?= wpm_esc($tabUrl('terbaru')) ?>">Terbaru</a>
            <a class="news-tabs__item<?= $tab === 'terpopuler' ? ' is-active' : '' ?>" href="<?= wpm_esc($tabUrl('terpopuler')) ?>">Terpopuler</a>
        </div>

        <!-- ══════════ PROMO BANNERS (admin-configurable) ══════════ -->
        <?php if ($homeBanners !== []) : ?>
        <div class="banner-strip" style="margin-bottom:24px;">
            <div class="banner-strip__grid">
                <?php foreach ($homeBanners as $banner) : ?>
                    <?= wpm_banner_markup($banner) ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="news-layout">
            <!-- ══════════ MAIN COLUMN ══════════ -->
            <div class="news-layout__main">
                <?php if ($heroArticle !== null) : ?>
                    <?= wpm_news_hero_card($heroArticle) ?>
                    <?= wpm_render_ad_slot($pdo, 'homepage-hero', 'homepage') ?>
                <?php endif; ?>

                <?php if ($feedArticles !== []) : ?>
                    <div class="poster-slider__head">
                        <h2><?= $tab === 'terbaru' ? 'Film Terbaru' : 'Film Terpopuler' ?></h2>
                    </div>
                    <div class="poster-slider-wrap">
                        <button type="button" class="poster-slider__arrow poster-slider__arrow--prev" aria-label="Geser ke kiri" disabled>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
                        </button>
                        <div class="poster-slider">
                            <?php foreach ($feedArticles as $i => $article) : ?>
                                <?= wpm_poster_card($article) ?>
                                <?php if ($i === 4) : ?>
                                    <?= wpm_render_ad_slot($pdo, 'between-article-cards', 'homepage') ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="poster-slider__arrow poster-slider__arrow--next" aria-label="Geser ke kanan">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                        </button>
                    </div>
                <?php elseif ($heroArticle === null) : ?>
                    <div class="empty-state"><?= wpm_icon('news') ?><p>Belum ada artikel untuk ditampilkan.</p></div>
                <?php endif; ?>

                <?php if ($totalPages > 1) : ?>
                <nav class="pagination" aria-label="Pagination">
                    <a class="<?= $page <= 1 ? 'is-disabled' : '' ?>" href="<?= wpm_esc($paginateUrl(max(1, $page - 1))) ?>">&larr;</a>
                    <?php for ($p = 1; $p <= $totalPages; $p++) : ?>
                        <a class="<?= $p === $page ? 'is-current' : '' ?>" href="<?= wpm_esc($paginateUrl($p)) ?>"><?= $p ?></a>
                    <?php endfor; ?>
                    <a class="<?= $page >= $totalPages ? 'is-disabled' : '' ?>" href="<?= wpm_esc($paginateUrl(min($totalPages, $page + 1))) ?>">&rarr;</a>
                </nav>
                <?php endif; ?>
            </div>

            <!-- ══════════ SIDEBAR: SEDANG TREN + IKLAN ══════════ -->
            <aside class="news-layout__sidebar">
                <?php if ($trendingArticles !== []) : ?>
                <div class="trending-panel">
                    <h2 class="trending-panel__title">🔥 Banyak Dicari</h2>
                    <?php foreach ($trendingArticles as $i => $article) : ?>
                        <?= wpm_trending_item($article, $i + 1) ?>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Slot iklan sidebar homepage (10 Agu 2026, permintaan
                     operator) — sebelumnya iklan cuma ada di antara
                     konten berita (homepage-hero, between-article-cards).
                     Slug diperbaiki 11 Agu 2026: operator ternyata sudah
                     punya posisi 'sidebar-right' (bukan 'homepage-sidebar'
                     yang di-tebak awal) di ad_positions — 15 posisi lain
                     sudah lama ada (header, footer, popup, dst, lihat
                     ad-positions.php), jadi pakai slug yang sudah ada
                     itu, bukan bikin slug baru. Render function ini aman
                     dipanggil walau belum ada iklan aktif untuk slot ini
                     — otomatis render string kosong (lihat
                     wpm_render_ad_slot()). -->
                <?= wpm_render_ad_slot($pdo, 'sidebar-right', 'homepage') ?>
            </aside>
        </div>
    </div>

    <!-- ══════════ APLIKASI ZONASINEMA — SEGERA HADIR (shared w/ tentang.php) ══════════ -->
    <?= wpm_app_promo_section($pdo) ?>

<script>
(function () {
    document.querySelectorAll('.poster-slider-wrap').forEach(function (wrap) {
        var track = wrap.querySelector('.poster-slider');
        var prevBtn = wrap.querySelector('.poster-slider__arrow--prev');
        var nextBtn = wrap.querySelector('.poster-slider__arrow--next');
        if (!track || !prevBtn || !nextBtn) { return; }

        function step() {
            var card = track.querySelector('.poster-card');
            var cardWidth = card ? card.getBoundingClientRect().width + 16 : 300;
            return cardWidth * 2;
        }

        function updateArrows() {
            var maxScroll = track.scrollWidth - track.clientWidth;
            prevBtn.disabled = track.scrollLeft <= 4;
            nextBtn.disabled = track.scrollLeft >= maxScroll - 4;
        }

        prevBtn.addEventListener('click', function () {
            track.scrollBy({ left: -step(), behavior: 'smooth' });
        });
        nextBtn.addEventListener('click', function () {
            track.scrollBy({ left: step(), behavior: 'smooth' });
        });
        track.addEventListener('scroll', updateArrows, { passive: true });
        window.addEventListener('resize', updateArrows);
        updateArrows();
    });
})();
</script>

</main>
<?php require __DIR__ . '/includes/site-footer.php'; ?>
