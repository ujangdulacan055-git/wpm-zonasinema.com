<?php
declare(strict_types=1);

/**
 * ZonaSinema — halaman publik "Request Movie" (24 Agu 2026, brief
 * GANTI-DARKMODE-KE-REQUEST-MOVIE). Pengunjung minta judul film buat
 * ditambah/direview ke katalog — MURNI request metadata, BUKAN request
 * nonton/streaming apa pun (nol player/embed/link video di halaman ini).
 *
 * Disimpan ke tabel `movie_requests` (lihat
 * docs/db-migrations/2026-08-24-movie_requests.sql), direview manual oleh
 * admin — belum ada halaman admin listing-nya di cms-admin (di luar
 * scope brief ini, follow-up terpisah kalau operator mau).
 */

require_once __DIR__ . '/includes/site-bootstrap.php';

$formStatus = '';
$formErrors = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    // Honeypot — pola sama kayak form kontak (page.php): bot yang ngisi
    // field tersembunyi ini dikasih redirect sukses palsu, nol data disimpan.
    if (trim((string) ($_POST['website'] ?? '')) !== '') {
        header('Location: request-film.php?sent=1', true, 302);
        exit;
    }

    if (!wpm_csrf_verify()) {
        $formErrors[] = 'Sesi form udah expired, coba submit ulang.';
    }

    $title = trim((string) ($_POST['title'] ?? ''));
    $year = trim((string) ($_POST['year'] ?? ''));
    $note = trim((string) ($_POST['note'] ?? ''));
    $requesterName = trim((string) ($_POST['requester_name'] ?? ''));
    $requesterContact = trim((string) ($_POST['requester_contact'] ?? ''));

    if ($title === '') {
        $formErrors[] = 'Judul film wajib diisi.';
    } elseif (mb_strlen($title) > 255) {
        $formErrors[] = 'Judul film kepanjangan (maksimal 255 karakter).';
    }
    if ($year !== '' && !preg_match('/^\d{4}$/', $year)) {
        $formErrors[] = 'Tahun rilis harus 4 digit angka (misal 2024), atau dikosongin aja kalau gak yakin.';
    }
    if (mb_strlen($note) > 2000) {
        $formErrors[] = 'Catatan kepanjangan (maksimal 2000 karakter).';
    }

    // Rate-limit sederhana berbasis hash IP (bukan IP mentah) — maksimal
    // 5 request per jam per IP, biar gak gampang di-spam tapi masih wajar
    // buat pengunjung yang mau request beberapa judul sekaligus.
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $ipHash = $ip !== '' ? hash('sha256', $ip) : null;
    if ($formErrors === [] && $ipHash !== null) {
        $rateStmt = $pdo->prepare(
            'SELECT COUNT(*) FROM movie_requests WHERE ip_hash = :ip_hash AND created_at >= (NOW() - INTERVAL 1 HOUR)'
        );
        $rateStmt->execute(['ip_hash' => $ipHash]);
        if ((int) $rateStmt->fetchColumn() >= 5) {
            $formErrors[] = 'Kamu udah kirim beberapa request barusan — coba lagi sejam lagi ya.';
        }
    }

    if ($formErrors === []) {
        try {
            $pdo->prepare(
                'INSERT INTO movie_requests (title, year, note, requester_name, requester_contact, status, created_at, ip_hash)
                 VALUES (:title, :year, :note, :requester_name, :requester_contact, :status, NOW(), :ip_hash)'
            )->execute([
                'title' => mb_substr($title, 0, 255),
                'year' => $year !== '' ? $year : null,
                'note' => $note !== '' ? mb_substr($note, 0, 2000) : null,
                'requester_name' => $requesterName !== '' ? mb_substr($requesterName, 0, 100) : null,
                'requester_contact' => $requesterContact !== '' ? mb_substr($requesterContact, 0, 150) : null,
                'status' => 'baru',
                'ip_hash' => $ipHash,
            ]);
            header('Location: request-film.php?sent=1', true, 302);
            exit;
        } catch (Throwable $e) {
            $formErrors[] = 'Gagal nyimpen request, coba lagi sebentar.';
        }
    }
}

$formStatus = isset($_GET['sent']) ? 'sent' : '';

$pageTitle = 'Request Movie — ZonaSinema';
$pageDescription = 'Gak nemu film yang kamu cari di ZonaSinema? Kirim judulnya di sini, kami review buat ditambahkan ke katalog.';
$activeNav = 'request-film';
$canonicalUrl = wpm_site_url('request-film.php');

require __DIR__ . '/includes/site-header.php';
?>

<section class="page-hero">
    <div class="crypto-container">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= wpm_esc(wpm_site_url('')) ?>">Beranda</a> <span>/</span> Request Movie
        </nav>
        <span class="section-kicker">Film</span>
        <h1>Request Movie</h1>
        <p>Gak nemu film yang kamu cari? Kasih tau judulnya, tim kami review buat ditambahkan ke katalog ZonaSinema.</p>
    </div>
</section>

<section class="crypto-section--tight">
    <div class="crypto-container" style="max-width:640px;">

        <?php if ($formStatus === 'sent') : ?>
            <div class="form-alert form-alert--success" style="margin-bottom:24px;">
                Request kamu udah kami terima, makasih! Tim kami bakal review buat ditambahkan ke katalog.
            </div>
        <?php endif; ?>

        <?php if ($formErrors !== []) : ?>
            <div class="form-alert form-alert--error" style="margin-bottom:24px;">
                <ul style="margin:0;padding-left:18px;">
                    <?php foreach ($formErrors as $error) : ?>
                        <li><?= wpm_esc($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="glass-card contact-form-card">
        <form method="post" action="request-film.php" novalidate>
            <div class="form-row">
                <label class="form-label" for="title">Judul Film *</label>
                <input class="form-input" type="text" id="title" name="title" required maxlength="255" placeholder="Contoh: Dune: Part Three" value="<?= wpm_esc((string) ($_POST['title'] ?? '')) ?>">
            </div>

            <div class="form-row form-row--2col">
                <div class="form-row">
                    <label class="form-label" for="year">Tahun Rilis (opsional)</label>
                    <input class="form-input" type="text" id="year" name="year" maxlength="4" placeholder="Contoh: 2024" value="<?= wpm_esc((string) ($_POST['year'] ?? '')) ?>">
                </div>
                <div class="form-row">
                    <label class="form-label" for="requester_contact">Email / WhatsApp (opsional)</label>
                    <input class="form-input" type="text" id="requester_contact" name="requester_contact" maxlength="150" placeholder="Kalau mau dikabarin" value="<?= wpm_esc((string) ($_POST['requester_contact'] ?? '')) ?>">
                </div>
            </div>

            <div class="form-row">
                <label class="form-label" for="requester_name">Nama Kamu (opsional)</label>
                <input class="form-input" type="text" id="requester_name" name="requester_name" maxlength="100" placeholder="Boleh dikosongin kalau mau anonim" value="<?= wpm_esc((string) ($_POST['requester_name'] ?? '')) ?>">
            </div>

            <div class="form-row">
                <label class="form-label" for="note">Catatan (opsional)</label>
                <textarea class="form-textarea" id="note" name="note" maxlength="2000" placeholder="Info tambahan biar gampang dicari, misal sutradara atau negara asal."><?= wpm_esc((string) ($_POST['note'] ?? '')) ?></textarea>
            </div>

            <!-- Honeypot anti-spam field — stays empty for real users, pola sama kayak form kontak (page.php) -->
            <div class="hp-field" aria-hidden="true">
                <label for="website">Website</label>
                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
            </div>

            <?= wpm_csrf_field() ?>

            <button type="submit" class="crypto-btn crypto-btn--primary">Kirim Request</button>
        </form>
        </div>
    </div>
</section>

</main>
<?php require __DIR__ . '/includes/site-footer.php'; ?>
