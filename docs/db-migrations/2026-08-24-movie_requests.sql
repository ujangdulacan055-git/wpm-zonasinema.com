-- Migration: movie_requests
-- Ditambahkan: 24 Agu 2026, bagian dari brief
-- docs/BRIEF-GANTI-DARKMODE-KE-REQUEST-MOVIE.md (Bagian B).
--
-- Tabel penyimpanan request film dari halaman publik request-film.php.
-- Ini murni request METADATA film buat ditambah/direview ke katalog —
-- BUKAN request nonton/streaming apa pun.
--
-- Belum ada folder migrations/ standar di repo ini (dicek dulu — nol file
-- .sql eksisting), jadi file ini ditaruh di docs/db-migrations/ sebagai
-- referensi manual. Jalankan langsung lewat phpMyAdmin/CLI MySQL di
-- environment produksi (repo ini gak punya koneksi DB live buat migrasi
-- otomatis).
--
-- STATUS: sudah dieksekusi ke DB dev `wpm2` (24 Agu 2026, via devs
-- Claude Code session) — tabel `movie_requests` sudah ada & terverifikasi.
-- Masih perlu dijalankan manual ke DB production terpisah nanti pas
-- deploy.

CREATE TABLE IF NOT EXISTS movie_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  year VARCHAR(4) NULL,
  note TEXT NULL,
  requester_name VARCHAR(100) NULL,
  requester_contact VARCHAR(150) NULL,      -- email/WA, opsional
  status ENUM('baru','dilihat','ditambahkan','ditolak') NOT NULL DEFAULT 'baru',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ip_hash VARCHAR(64) NULL,                 -- hash IP buat rate-limit sederhana, JANGAN simpan IP mentah
  KEY idx_movie_requests_ip_hash_created_at (ip_hash, created_at),
  KEY idx_movie_requests_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
