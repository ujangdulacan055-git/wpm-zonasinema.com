<?php
declare(strict_types=1);

/**
 * ZonaSinema — "Kabar Film" (26 Agu 2026, brief "Bikin listing beneran
 * buat Kabar Film"). Awalnya placeholder statis (24 Agu 2026, "Berita &
 * Tips"), sekarang beneran nge-query artikel — operator minta kategori
 * dinamai "Artikel" (bukan "Kabar Film", itu cuma label nav/menu) biar
 * gak ketuker sama pola nama sebelumnya.
 *
 * Konten kategori "Film" (situs film/database, TMDb-synced) TETAP di
 * "Pages & Articles" yang sama — arsitektur satu tabel `pages` buat semua
 * konten, dibedain lewat category_id, JOIN opsional ke `films` cuma
 * kalau kategorinya "Film". Artikel kategori "Artikel" di sini TIDAK
 * di-JOIN films (murni teks, pakai wpm_article_card() bukan
 * wpm_poster_card()) — sama pola kayak kategori.php buat kategori
 * non-film.
 */

require_once __DIR__ . '/includes/site-bootstrap.php';

// Pastiin kategori "Artikel" ada (idempotent, INSERT IGNORE) — dibuat di
// sini (bukan cuma lewat admin panel) biar halaman ini selalu jalan
// walau admin belum sempat bikin kategorinya manual.
$pdo->prepare('INSERT IGNORE INTO article_categories (name, slug) VALUES (:name, :slug)')
    ->execute(['name' => 'Artikel', 'slug' => 'artikel']);
$artikelCategoryId = (int) $pdo->query("SELECT id FROM article_categories WHERE slug = 'artikel' LIMIT 1")->fetchColumn();

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM pages WHERE status = 'published' AND category_id = :cat");
$countStmt->execute(['cat' => $artikelCategoryId]);
$totalArticles = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalArticles / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$listStmt = $pdo->prepare(
    "SELECT p.*, c.name AS category_name
     FROM pages p
     LEFT JOIN article_categories c ON c.id = p.category_id
     WHERE p.status = 'published' AND p.category_id = :cat
     ORDER BY p.published_at DESC
     LIMIT $perPage OFFSET $offset"
);
$listStmt->execute(['cat' => $artikelCategoryId]);
$articles = $listStmt->fetchAll();

$paginateUrl = static function (int $p): string {
    return 'berita.php?page=' . $p;
};

$pageTitle = 'Kabar Film — ZonaSinema';
$pageDescription = 'Artikel dan kabar seputar film dari ZonaSinema — review, trivia, rekomendasi tontonan, dan pembahasan ringan lainnya.';
$activeNav = 'berita-tips';
$canonicalUrl = wpm_site_url('berita.php');

require __DIR__ . '/includes/site-header.php';
?>

<section class="page-hero">
    <div class="crypto-container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= wpm_esc(wpm_site_url('')) ?>">Beranda</a> <span>/</span> Kabar Film
        </nav>
        <span class="section-kicker">Kabar Film</span>
        <h1>Kabar Film</h1>
        <p>Artikel dan kabar seputar film — rekomendasi tontonan, trivia, dan pembahasan ringan lainnya.</p>
    </div>
</section>

<section class="crypto-section--tight">
    <div class="crypto-container">
        <?php if ($articles === []) : ?>
            <div class="empty-state">
                <?= wpm_icon('film') ?>
                <p>Belum ada artikel di sini — segera hadir. Sementara ini, jelajahi review dan database film kami dulu.</p>
                <a class="crypto-btn crypto-btn--primary" href="<?= wpm_esc(wpm_site_url('')) ?>" style="margin-top:16px;display:inline-flex;">Kembali ke Beranda</a>
            </div>
        <?php else : ?>
            <div class="crypto-grid crypto-grid--3">
                <?php foreach ($articles as $article) : ?>
                    <?= wpm_article_card($article) ?>
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
        <?php endif; ?>
    </div>
</section>

</main>
<?php require __DIR__ . '/includes/site-footer.php'; ?>
