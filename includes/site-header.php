<?php
declare(strict_types=1);

if (!defined('WPM_BOOTSTRAPPED')) {
    header('Location: ../index.php', true, 302);
    exit;
}

/**
 * Shared <head> + top nav for every public page. Caller sets these
 * variables before requiring this file:
 *   $pageTitle       (required)
 *   $pageDescription (required)
 *   $activeNav       (optional) — id from wpm_nav_menu() to highlight
 *   $canonicalUrl    (optional)
 *   $ogImage         (optional)
 *   $ogType          (optional) — og:type value, defaults to "website"; artikel.php sets "article"
 *   $pageNoindex     (optional) — true emits <meta name="robots" content="noindex, nofollow">
 *   $extraHead       (optional) — raw HTML injected before </head>
 * Opens <main> at the end — the caller closes it and includes
 * includes/site-footer.php.
 */

$activeNav = $activeNav ?? '';
$currentYear = date('Y');
$wpmMenu = wpm_nav_menu($pdo);

// Dropdown nav "Genre" & "Tahun Rilis" (24 Agu 2026) — dihitung sekali di
// sini, dipakai ulang di site-footer.php buat mobile drawer (sama pola
// kayak $wpmSiteSettings), biar query film_genres/films gak dobel.
$wpmNavGenres = wpm_film_genres_for_nav($pdo);
$wpmNavYears = wpm_film_years_for_nav($pdo);

// Site branding — falls back to the original hardcoded defaults whenever
// the admin hasn't filled in Site Settings yet (or a field is left blank),
// so the site never shows an empty name/tagline.
$wpmSiteSettings  = wpm_site_settings($pdo);
$wpmSiteName      = trim((string) ($wpmSiteSettings['site_name'] ?? '')) !== ''
    ? (string) $wpmSiteSettings['site_name'] : 'ZonaSinema';
$wpmSiteTagline   = trim((string) ($wpmSiteSettings['site_tagline'] ?? '')) !== ''
    ? (string) $wpmSiteSettings['site_tagline'] : 'Review & Database Film';
$wpmSiteLogoUrl   = wpm_image((string) ($wpmSiteSettings['logo_path'] ?? ''));
// Dedicated favicon first; fall back to the main site logo so the tab
// icon still shows the brand even if a separate favicon was never
// uploaded in Site Settings.
$wpmFaviconUrl    = wpm_image((string) ($wpmSiteSettings['favicon_path'] ?? '')) ?? $wpmSiteLogoUrl;

$cssPath = __DIR__ . '/../assets/css/site.css';
$jsPath  = __DIR__ . '/../assets/js/site.js';
$cssVer  = @filemtime($cssPath) ?: 1;
$jsVer   = @filemtime($jsPath) ?: 1;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <script>
      document.documentElement.setAttribute('data-theme', 'dark');
    </script>
    <meta charset="UTF-8">
    <!-- Must come first: makes every relative asset/link on this page
         resolve against the site root, not the current URL's own depth
         (needed since clean URLs like /film/<slug> sit one segment
         "deeper" than the old /artikel.php ever did). -->
    <base href="<?= wpm_esc(wpm_base_href()) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= wpm_esc($pageTitle) ?></title>
    <meta name="description" content="<?= wpm_esc($pageDescription) ?>">
    <?php if (!empty($wpmFaviconUrl)) : ?>
        <link rel="icon" href="<?= wpm_esc($wpmFaviconUrl) ?>">
        <link rel="shortcut icon" href="<?= wpm_esc($wpmFaviconUrl) ?>">
        <link rel="apple-touch-icon" href="<?= wpm_esc($wpmFaviconUrl) ?>">
    <?php endif; ?>
    <!-- PWA (added 20 Aug 2026) — manifest.json/sw.js live at the site
         root (not a subfolder) so the service worker's scope covers the
         whole origin; wpm_site_url() (same helper canonical/OG tags use)
         keeps this correct whether the app is mounted at the domain root
         (production) or nested under a subfolder (local dev). -->
    <link rel="manifest" href="<?= wpm_esc(wpm_site_url('manifest.json')) ?>">
    <meta name="theme-color" content="#fac864">
    <?php if (!empty($canonicalUrl)) : ?>
        <link rel="canonical" href="<?= wpm_esc($canonicalUrl) ?>">
    <?php endif; ?>
    <?php if (!empty($pageNoindex)) : ?>
        <meta name="robots" content="noindex, nofollow">
    <?php endif; ?>
    <meta property="og:title" content="<?= wpm_esc($pageTitle) ?>">
    <meta property="og:description" content="<?= wpm_esc($pageDescription) ?>">
    <meta property="og:type" content="<?= wpm_esc($ogType ?? 'website') ?>">
    <?php if (!empty($ogImage)) : ?>
        <meta property="og:image" content="<?= wpm_esc($ogImage) ?>">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- ZonaSinema redesign (21 Agu 2026) — Bebas Neue (display) + Manrope
         (body), replacing the old Space Grotesk/Inter sport-theme pair. -->
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/site.css?v=<?= (int) $cssVer ?>">
    <?= $extraHead ?? '' ?>

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-HZ988K60RH"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'G-HZ988K60RH');
    </script>

    <!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-PSSVTMGS');</script>
<!-- End Google Tag Manager -->
</head>
<body class="crypto-theme">
<div class="crypto-bg" aria-hidden="true"></div>

<header class="zc-nav">
    <div class="crypto-nav__inner zc-nav__inner">
        <div class="zc-nav__left">
            <!-- Logo permanen/hardcode (24 Agu 2026, keputusan final operator) —
                 BUKAN lagi dari site_settings.logo_path, murni file di
                 assets/img/branding/. Nol teks/tagline terpisah, logo doang. -->
            <a href="<?= wpm_esc(wpm_site_url('')) ?>" class="crypto-logo">
                <img class="crypto-logo__mark crypto-logo__mark--wide" src="<?= wpm_esc(wpm_site_url('assets/img/branding/logo-zonasinema-white-transparent.png')) ?>" alt="ZonaSinema">
            </a>

            <form class="zc-search" method="get" action="<?= wpm_esc(wpm_url_pencarian()) ?>">
                <?= wpm_icon('search') ?>
                <input type="text" name="q" class="zc-search__input" placeholder="Cari judul film...">
                <button type="submit" class="zc-search__btn" aria-label="Cari"><?= wpm_icon('search') ?></button>
            </form>
        </div>

        <div class="zc-nav__right">
            <nav class="zc-nav-links" aria-label="Menu utama">
                <!-- Dropdown Genre (24 Agu 2026) — <details>/<summary> native,
                     toggle jalan di desktop & mobile tanpa JS custom. Isi dari
                     $wpmNavGenres (query film_genres, lihat site-header.php atas). -->
                <details class="nav-dropdown">
                    <summary class="<?= $activeNav === 'berita' ? 'is-active' : '' ?>">Genre
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M6 9l6 6 6-6"/></svg>
                    </summary>
                    <div class="nav-dropdown__panel">
                        <a href="kategori.php" class="<?= ($_GET['genre'] ?? '') === '' ? 'is-active' : '' ?>">Semua Genre</a>
                        <?php foreach ($wpmNavGenres as $g) : ?>
                            <a href="kategori.php?genre=<?= wpm_esc((string) $g['slug']) ?>" class="<?= ($_GET['genre'] ?? '') === $g['slug'] ? 'is-active' : '' ?>"><?= wpm_esc((string) $g['label_id']) ?></a>
                        <?php endforeach; ?>
                    </div>
                </details>
                <!-- Dropdown Tahun Rilis (24 Agu 2026) — isi dari $wpmNavYears
                     (query DISTINCT YEAR(release_date) dari films, dinamis
                     sesuai data yang beneran ada, bukan hardcode range). -->
                <details class="nav-dropdown">
                    <summary>Tahun Rilis
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M6 9l6 6 6-6"/></svg>
                    </summary>
                    <div class="nav-dropdown__panel nav-dropdown__panel--years">
                        <?php if ($wpmNavYears === []) : ?>
                            <span class="nav-dropdown__empty">Belum ada data tahun</span>
                        <?php endif; ?>
                        <?php foreach ($wpmNavYears as $year) : ?>
                            <a href="kategori.php?year=<?= (int) $year ?>" class="<?= (string) ($_GET['year'] ?? '') === (string) $year ? 'is-active' : '' ?>"><?= (int) $year ?></a>
                        <?php endforeach; ?>
                    </div>
                </details>
                <a href="berita.php" class="<?= ($activeNav ?? '') === 'berita-tips' ? 'is-active' : '' ?>">Berita &amp; Tips</a>
                <!-- Ganti dari toggle Mode Gelap (24 Agu 2026, brief
                     GANTI-DARKMODE-KE-REQUEST-MOVIE) — tema gelap/terang
                     tetap jalan otomatis (localStorage/prefers-color-scheme,
                     lihat <script> di <head>), cuma kontrol manual switch-nya
                     yang dicabut, slotnya diganti link fitur ini. -->
                <a href="request-film.php" class="nav-link--request<?= ($activeNav ?? '') === 'request-film' ? ' is-active' : '' ?>">
                    <?= wpm_icon('film') ?>
                    <span>Request Movie</span>
                </a>
            </nav>
            <button type="button" class="crypto-nav__toggle" id="crypto-nav-toggle" aria-label="Buka menu">
                <span></span>
            </button>
        </div>
    </div>
</header>

<!-- Genre bar — wired ke tabel films/film_genres (23 Agu 2026). Tiap link
     genre/tahun/terpopuler sekarang beneran filter berbeda di kategori.php,
     bukan semua ke halaman yang sama lagi. -->
<?php $wpmGenreBarActive = trim((string) ($_GET['genre'] ?? '')); ?>
<div class="zc-genre-bar"><div class="zc-genre-bar__inner">
    <a href="kategori.php" class="<?= $wpmGenreBarActive === '' && !isset($_GET['year']) && !isset($_GET['popular']) ? 'is-active' : '' ?>">Semua</a>
    <a href="kategori.php?genre=aksi" class="<?= $wpmGenreBarActive === 'aksi' ? 'is-active' : '' ?>">Aksi</a>
    <a href="kategori.php?genre=horror" class="<?= $wpmGenreBarActive === 'horror' ? 'is-active' : '' ?>">Horror</a>
    <a href="kategori.php?genre=komedi" class="<?= $wpmGenreBarActive === 'komedi' ? 'is-active' : '' ?>">Komedi</a>
    <a href="kategori.php?genre=sci-fi" class="<?= $wpmGenreBarActive === 'sci-fi' ? 'is-active' : '' ?>">Sci-Fi</a>
    <a href="kategori.php?genre=romansa" class="<?= $wpmGenreBarActive === 'romansa' ? 'is-active' : '' ?>">Romansa</a>
    <a href="kategori.php?genre=drama" class="<?= $wpmGenreBarActive === 'drama' ? 'is-active' : '' ?>">Drama</a>
    <a href="kategori.php?genre=animasi" class="<?= $wpmGenreBarActive === 'animasi' ? 'is-active' : '' ?>">Animasi</a>
    <a href="kategori.php?genre=thriller" class="<?= $wpmGenreBarActive === 'thriller' ? 'is-active' : '' ?>">Thriller</a>
    <a href="kategori.php?year=2025" class="<?= ($_GET['year'] ?? '') === '2025' ? 'is-active' : '' ?>">2025</a>
    <a href="kategori.php?year=2026" class="<?= ($_GET['year'] ?? '') === '2026' ? 'is-active' : '' ?>">2026</a>
    <a href="kategori.php?popular=1" class="<?= isset($_GET['popular']) ? 'is-active' : '' ?>">Terpopuler</a>
</div></div>

<?php
$wpmBreakingNewsHtml = wpm_breaking_news_markup($pdo);
?>
<?php if ($wpmBreakingNewsHtml !== '') : ?>
<div class="site-ticker-row">
    <div class="site-ticker-row__inner">
        <?= $wpmBreakingNewsHtml ?>
    </div>
</div>
<?php endif; ?>

<?= wpm_render_ad_slot($pdo, 'header') ?>
<?= wpm_render_ad_slot($pdo, 'below-main-menu') ?>

<main>
