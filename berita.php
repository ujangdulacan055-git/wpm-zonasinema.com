<?php
declare(strict_types=1);

/**
 * ZonaSinema — "Berita & Tips" placeholder (24 Agu 2026, brief "Dropdown
 * Genre & Tahun Rilis + Rename Populer"). Ganti slot nav "Populer"
 * (redundant sama pill "Terpopuler" di genre bar homepage) — rencana ke
 * depan jadi wadah artikel/tips seputar film, TAPI itu fitur/brief
 * terpisah nanti. Halaman ini SENGAJA cuma placeholder "segera hadir",
 * bukan sistem artikel/CMS baru — di luar scope brief ini.
 */

require_once __DIR__ . '/includes/site-bootstrap.php';

$pageTitle = 'Berita & Tips — ZonaSinema';
$pageDescription = 'Artikel dan tips seputar film dari ZonaSinema — segera hadir.';
$activeNav = 'berita-tips';
$canonicalUrl = wpm_site_url('berita.php');

require __DIR__ . '/includes/site-header.php';
?>

<section class="page-hero">
    <div class="crypto-container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= wpm_esc(wpm_site_url('')) ?>">Beranda</a> <span>/</span> Berita &amp; Tips
        </nav>
        <span class="section-kicker">Segera Hadir</span>
        <h1>Berita &amp; Tips</h1>
        <p>Artikel dan tips seputar film — rekomendasi tontonan, trivia, dan pembahasan ringan lainnya.</p>
    </div>
</section>

<section class="crypto-section--tight">
    <div class="crypto-container">
        <div class="empty-state">
            <?= wpm_icon('film') ?>
            <p>Segera hadir — artikel &amp; tips seputar film. Sementara ini, jelajahi review dan database film kami dulu.</p>
            <a class="crypto-btn crypto-btn--primary" href="<?= wpm_esc(wpm_site_url('')) ?>" style="margin-top:16px;display:inline-flex;">Kembali ke Beranda</a>
        </div>
    </div>
</section>

</main>
<?php require __DIR__ . '/includes/site-footer.php'; ?>
