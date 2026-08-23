<?php
declare(strict_types=1);

if (!defined('WPM_BOOTSTRAPPED')) {
    header('Location: ../index.php', true, 302);
    exit;
}

/**
 * Shared footer + closing tags for every public page. Expects the caller
 * to have already closed </main>. Renders the Footer ad slot, popup ad,
 * and sticky-bottom-mobile ad (all optional — silently absent if inactive
 * or unconfigured), then the footer markup, mobile nav drawer, and JS.
 */

$wpmMenu = wpm_nav_menu($pdo);
$wpmFooterPages = wpm_special_pages_for_footer($pdo);
$adSettings = wpm_ad_settings($pdo);
$popupAd = (empty($adSettings) || (int) ($adSettings['ads_enabled'] ?? 1) === 1) ? wpm_ad_pick($pdo, 'popup') : null;
$stickyAd = (empty($adSettings) || ((int) ($adSettings['ads_enabled'] ?? 1) === 1 && (int) ($adSettings['sticky_mobile_enabled'] ?? 1) === 1))
    ? wpm_ad_pick($pdo, 'sticky-bottom-mobile', 'global', null, 'mobile')
    : null;
?>
<?= wpm_render_ad_slot($pdo, 'footer') ?>

<footer class="crypto-footer">
    <div class="crypto-container">
        <div class="footer-grid">
            <div class="footer-brand">
                <!-- Logo permanen/hardcode (24 Agu 2026, keputusan final operator) —
                     sama kayak header, nol teks/tagline terpisah. -->
                <a href="<?= wpm_esc(wpm_site_url('')) ?>" class="crypto-logo footer-brand__logo">
                    <img class="crypto-logo__mark crypto-logo__mark--wide footer-brand__logo-mark" src="<?= wpm_esc(wpm_site_url('assets/img/branding/logo-zonasinema-white-transparent.png')) ?>" alt="ZonaSinema">
                </a>
                <p>Portal review dan database film.</p>
            </div>
            <div>
                <p class="footer-heading">Jelajahi</p>
                <ul class="footer-links">
                    <?php foreach ($wpmMenu as $item) : ?>
                        <li><a href="<?= wpm_esc($item['href']) ?>"><?= wpm_esc($item['label']) ?></a></li>
                    <?php endforeach; ?>
                    <li><a href="request-film.php">Request Movie</a></li>
                    <?php foreach ($wpmFooterPages as $specialPage) : ?>
                        <li><a href="<?= wpm_esc((string) $specialPage['slug']) ?>"><?= wpm_esc((string) $specialPage['title']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div>
                <!-- Genre asli situs ini (film_genres, sama kayak genre bar
                     homepage) — BUKAN "Series"/"Crime" dst dari referensi
                     manapun, situs ini murni film, bukan series. -->
                <p class="footer-heading">Genre Populer</p>
                <ul class="footer-links">
                    <li><a href="kategori.php?genre=aksi">Aksi</a></li>
                    <li><a href="kategori.php?genre=horror">Horror</a></li>
                    <li><a href="kategori.php?genre=komedi">Komedi</a></li>
                    <li><a href="kategori.php?genre=sci-fi">Sci-Fi</a></li>
                    <li><a href="kategori.php?genre=drama">Drama</a></li>
                    <li><a href="kategori.php?genre=thriller">Thriller</a></li>
                </ul>
            </div>
        </div>

        <!-- Bar biru (24 Agu 2026, permintaan operator: samain warna sama
             header/navbar) — bungkus footer-bottom + atribusi TMDb jadi 1 blok. -->
        <div class="footer-bottom-bar">
            <div class="footer-bottom">
                <span>&copy; <?= wpm_esc($currentYear) ?> ZonaSinema. Seluruh hak cipta dilindungi.</span>
                <span>Portal review &amp; database film.</span>
            </div>

            <!-- Atribusi wajib TMDb (syarat pakai API mereka) — lihat
                 docs/BRIEF-INTEGRASI-TMDB-SKEMA-FILM.md Bagian A poin 4.
                 JANGAN DIHAPUS TOTAL selama situs masih pakai data TMDb —
                 bisa bikin akses API dicabut. Boleh dikecilin/ditaruh di
                 baris paling bawah (24 Agu 2026, permintaan operator), asal
                 tetap ada & link ke themoviedb.org tetap utuh. -->
            <p class="footer-disclaimer footer-disclaimer--tmdb">Powered by <a href="https://www.themoviedb.org/" target="_blank" rel="noopener">TMDb</a> — situs ini menggunakan TMDb API namun tidak didukung atau disertifikasi oleh TMDb.</p>
        </div>
    </div>
</footer>

<div class="crypto-nav__mobile" id="crypto-nav-mobile">
    <div class="crypto-nav__mobile-panel">
        <button type="button" class="crypto-nav__mobile-close" id="crypto-nav-mobile-close" aria-label="Tutup menu">&times;</button>
        <?php /* Sama persis dengan nav desktop (.zc-nav-links) di site-header.php — lihat CLAUDE.md soal placeholder genre film. */ ?>
        <a href="<?= wpm_esc(wpm_url_kategori()) ?>" class="<?= ($activeNav ?? '') === 'berita' ? 'is-active' : '' ?>">Genre</a>
        <a href="<?= wpm_esc(wpm_url_kategori()) ?>">Populer</a>
        <a href="<?= wpm_esc(wpm_url_kategori()) ?>">Tahun Rilis</a>
        <a href="request-film.php" class="<?= ($activeNav ?? '') === 'request-film' ? 'is-active' : '' ?>">Request Movie</a>
    </div>
</div>

<?php if ($popupAd !== null) : ?>
    <?php
    try {
        $pdo->prepare('UPDATE advertisements SET impressions = impressions + 1 WHERE id = :id')->execute(['id' => (int) $popupAd['id']]);
    } catch (Throwable $e) {
        // Best-effort.
    }
    ?>
    <div class="wpm-popup-ad" id="wpm-popup-ad">
        <div class="wpm-popup-ad__panel">
            <button type="button" class="wpm-popup-ad__close" id="wpm-popup-ad-close" aria-label="Tutup iklan">&times;</button>
            <?= wpm_ad_markup($popupAd, empty($adSettings) || (int) ($adSettings['show_ad_label'] ?? 1) === 1, 'popup') ?>
        </div>
    </div>
<?php endif; ?>

<?php if ($stickyAd !== null) : ?>
    <?php
    try {
        $pdo->prepare('UPDATE advertisements SET impressions = impressions + 1 WHERE id = :id')->execute(['id' => (int) $stickyAd['id']]);
    } catch (Throwable $e) {
        // Best-effort.
    }
    ?>
    <div class="wpm-sticky-ad" id="wpm-sticky-ad">
        <button type="button" class="wpm-sticky-ad__close" id="wpm-sticky-ad-close" aria-label="Tutup">&times;</button>
        <?= wpm_ad_markup($stickyAd, empty($adSettings) || (int) ($adSettings['show_ad_label'] ?? 1) === 1, 'sticky-bottom-mobile') ?>
    </div>
<?php endif; ?>

<?= wpm_floating_contact_buttons($wpmSiteSettings) ?>

<script src="assets/js/site.js?v=<?= (int) $jsVer ?>" defer></script>
<script>
  // PWA service worker registration (added 20 Aug 2026) — deliberately
  // here (end of body, after load) rather than in <head>, so it never
  // competes with the page's own render/paint for the main thread.
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register('<?= wpm_esc(wpm_site_url('sw.js')) ?>');
    });
  }
</script>
</body>
</html>
