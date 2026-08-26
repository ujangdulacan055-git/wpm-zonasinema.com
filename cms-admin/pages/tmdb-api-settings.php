<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once dirname(__DIR__) . '/config/database.php';

/**
 * ZonaSinema — TMDB API Settings (26 Agu 2026). Ganti "Livescore API
 * Settings" — leftover dari project sibling sagagoal.com (situs livescore)
 * yang ke-bawa pas fork jadi WPM2, tapi halamannya (livescore-api-settings.php)
 * gak pernah ada di project ini — link mati di sidebar (404 kalau diklik).
 * ZonaSinema 100% situs film, gak ada livescore, jadi diganti jadi
 * halaman status TMDB API (satu-satunya API eksternal yang beneran
 * dipakai situs ini — scripts/import-tmdb.php).
 *
 * SENGAJA read-only/status doang (keputusan operator 26 Agu 2026) — token
 * TETAP cuma di .env (root project), TIDAK disimpan lagi di database.
 * Alasan: .env udah jadi satu sumber kebenaran yang dipakai
 * scripts/import-tmdb.php, dan operator sengaja bikin .env manual lewat
 * cPanel File Manager (bukan lewat chat/AI) demi keamanan — nyimpen token
 * kedua kalinya di DB cuma bikin 2 sumber kebenaran yang bisa beda-beda,
 * gak nambah keamanan. Halaman ini cuma BACA .env buat ditampilin
 * (masked) + test koneksi ke TMDB API, TIDAK PERNAH nulis/ubah .env.
 */
cms_require_role(['superadmin']);

$pageTitle = 'TMDB API Settings';
$currentNav = 'tmdb-api-settings';
$breadcrumbs = [
    ['label' => 'Dashboard', 'href' => cms_dashboard_href()],
    ['label' => 'Integrations', 'href' => ''],
    ['label' => 'TMDB API Settings', 'href' => ''],
];

$selfUrl = 'tmdb-api-settings.php';

// ── Baca .env (root project) — READ ONLY, gak pernah ditulis dari sini ──
$envPath = dirname(__DIR__, 2) . '/.env';
$envExists = is_file($envPath);
$tmdbToken = '';
if ($envExists) {
    $envParsed = parse_ini_file($envPath) ?: [];
    $tmdbToken = trim((string) ($envParsed['TMDB_API_TOKEN'] ?? ''));
}
$tokenConfigured = $tmdbToken !== '';
$tokenMasked = $tokenConfigured
    ? (mb_strlen($tmdbToken) > 12
        ? mb_substr($tmdbToken, 0, 6) . str_repeat('•', 18) . mb_substr($tmdbToken, -4)
        : str_repeat('•', 12))
    : '';

// ── Test koneksi ke TMDB API (tombol manual, POST) ──────────────────────
$testResult = null;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'test_connection') {
    cms_verify_csrf();

    if (!$tokenConfigured) {
        $testResult = ['ok' => false, 'message' => 'TMDB_API_TOKEN kosong/tidak ada di .env — isi dulu sebelum test koneksi.'];
    } else {
        $ch = curl_init('https://api.themoviedb.org/3/authentication');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $tmdbToken,
                'Accept: application/json',
            ],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            $testResult = ['ok' => false, 'message' => 'Gagal connect ke TMDB API: ' . $curlError];
        } elseif ($httpCode === 200) {
            $decoded = json_decode((string) $response, true);
            $success = is_array($decoded) && !empty($decoded['success']);
            $testResult = $success
                ? ['ok' => true, 'message' => 'Token valid — berhasil autentikasi ke TMDB API.']
                : ['ok' => false, 'message' => 'TMDB API merespons tapi status success=false. Cek token-nya lagi.'];
        } elseif ($httpCode === 401) {
            $testResult = ['ok' => false, 'message' => 'Token ditolak TMDB (HTTP 401) — token salah/expired/dicabut.'];
        } else {
            $testResult = ['ok' => false, 'message' => 'TMDB API merespons HTTP ' . $httpCode . ' — cek lagi beberapa saat.'];
        }
    }
}

// ── Statistik import film (dari database, bukan dari TMDB langsung) ────
$totalFilms = (int) $pdo->query('SELECT COUNT(*) FROM films')->fetchColumn();
$lastSyncedAt = $pdo->query('SELECT MAX(synced_at) FROM films')->fetchColumn();
$totalPublishedPages = (int) $pdo->query(
    "SELECT COUNT(*) FROM pages p JOIN films f ON f.page_id = p.page_id WHERE p.status = 'published'"
)->fetchColumn();

$fmtDt = static function (?string $value): string {
    if ($value === null || $value === '') {
        return '—';
    }
    $ts = strtotime($value);
    return $ts !== false ? date('d M Y, H:i', $ts) : $value;
};

require dirname(__DIR__) . '/includes/header.php';
require dirname(__DIR__) . '/includes/sidebar.php';
require dirname(__DIR__) . '/includes/navbar.php';
require dirname(__DIR__) . '/includes/breadcrumb.php';
require dirname(__DIR__) . '/includes/alerts.php';
?>
<section class="admin-stack">
    <div class="toolbar">
        <div class="toolbar__left">
            <h2 class="section-title">TMDB API Settings</h2>
            <p class="section-lead">Status koneksi ke The Movie Database (TMDB) API — sumber data film ZonaSinema.</p>
        </div>
    </div>

    <?php if ($testResult !== null) : ?>
        <div class="admin-alert admin-alert--<?= $testResult['ok'] ? 'success' : 'error' ?>">
            <span class="admin-alert__text"><?= cms_esc($testResult['message']) ?></span>
        </div>
    <?php endif; ?>

    <div class="admin-grid admin-grid--stats">
        <article class="stat-card">
            <div class="stat-card__label">Total film di database</div>
            <div class="stat-card__value"><?= number_format($totalFilms) ?></div>
            <div class="stat-card__hint"><?= number_format($totalPublishedPages) ?> published</div>
        </article>
        <article class="stat-card">
            <div class="stat-card__label">Sync terakhir</div>
            <div class="stat-card__value" style="font-size:18px;"><?= cms_esc($fmtDt($lastSyncedAt !== false ? (string) $lastSyncedAt : null)) ?></div>
            <div class="stat-card__hint">films.synced_at terbaru</div>
        </article>
        <article class="stat-card">
            <div class="stat-card__label">Status token</div>
            <div class="stat-card__value" style="font-size:18px;color:<?= $tokenConfigured ? 'var(--admin-success, #22c55e)' : 'var(--admin-danger, #ef4444)' ?>;">
                <?= $tokenConfigured ? 'Terkonfigurasi' : 'Belum ada' ?>
            </div>
            <div class="stat-card__hint">dari .env root project</div>
        </article>
    </div>

    <div class="panel">
        <h3 style="margin-top:0;">Token TMDB_API_TOKEN</h3>
        <?php if (!$envExists) : ?>
            <p style="color:var(--admin-danger, #ef4444);">File <code>.env</code> tidak ditemukan di root project. Buat manual lewat cPanel File Manager (bukan lewat chat/AI, demi keamanan) — isi <code>TMDB_API_TOKEN=xxxxx</code>.</p>
        <?php elseif (!$tokenConfigured) : ?>
            <p style="color:var(--admin-danger, #ef4444);"><code>.env</code> ada tapi <code>TMDB_API_TOKEN</code> kosong. Isi tokennya langsung di file <code>.env</code> lewat cPanel File Manager.</p>
        <?php else : ?>
            <p>Token terbaca dari <code>.env</code> (masked, ditampilin sebagian doang buat konfirmasi):</p>
            <p style="font-family:monospace;font-size:15px;background:var(--admin-surface-2, rgba(255,255,255,.04));padding:10px 14px;border-radius:8px;display:inline-block;"><?= cms_esc($tokenMasked) ?></p>
        <?php endif; ?>

        <p style="margin-top:18px;color:var(--admin-text-muted, rgba(255,255,255,.6));font-size:13px;">
            Halaman ini <strong>read-only</strong> — token TMDB tetap cuma disimpan di file <code>.env</code> (root project), bukan di database. Buat ganti token, edit langsung <code>.env</code> lewat cPanel File Manager, bukan dari sini.
        </p>

        <form method="post" action="<?= cms_esc($selfUrl) ?>" style="margin-top:16px;">
            <?= cms_csrf_field() ?>
            <input type="hidden" name="action" value="test_connection">
            <button type="submit" class="admin-btn admin-btn--primary">🔌 Test Koneksi ke TMDB API</button>
        </form>
    </div>

    <div class="panel">
        <h3 style="margin-top:0;">Import film baru</h3>
        <p style="color:var(--admin-text-muted, rgba(255,255,255,.6));font-size:13px;">
            Import film baru dari TMDB dijalankan lewat CLI (bukan dari admin panel ini) — jalankan di server:
        </p>
        <p style="font-family:monospace;font-size:13px;background:var(--admin-surface-2, rgba(255,255,255,.04));padding:10px 14px;border-radius:8px;">
            php scripts/import-tmdb.php --source=popular --pages=5
        </p>
        <p style="color:var(--admin-text-muted, rgba(255,255,255,.6));font-size:13px;">
            <code>--source</code>: popular | top_rated | now_playing | upcoming. Idempotent — aman dijalankan berkali-kali, film yang udah ada (tmdb_id sama) otomatis di-skip.
        </p>
    </div>
</section>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
