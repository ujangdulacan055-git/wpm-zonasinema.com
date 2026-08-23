Project: Sagagoal.com — CMS berita olahraga (PHP, MySQL, cPanel hosting).
Bahasa komunikasi: Bahasa Indonesia santai (gaya WhatsApp), bukan formal.

Arsitektur:
- Default: semua AI agent (Growth Agent) menyarankan lewat Action Queue
  (tabel growth_agent_jobs) — tidak eksekusi/publish otomatis tanpa
  approval manusia, KECUALI fitur yang eksplisit ditandai "mode otonom"
  dan sudah dinyalakan manual di admin panel.
- Full Draft Automation (auto_draft_article) BOLEH auto-publish penuh
  tanpa approval manusia — keputusan operator, 9 Agu 2026 (lihat
  docs/DECISIONS.md). Toggle "mode otonom: auto-publish" di panel
  Growth Agent → tab Otomatisasi adalah satu-satunya saklar; kalau OFF,
  balik ke perilaku default (draft, nunggu approve). Tidak ada rate
  limit/gate warning yang menahan publish selama toggle ini ON — SEO-G0
  gate dan title-vs-headline check tetap JALAN dan tetap dicatat di
  job/output_json untuk keperluan audit, tapi hasilnya TIDAK memblokir
  publish.
- Baca docs/GROWTH_AGENT_V2_PROPOSAL.md dan docs/DECISIONS.md dulu sebelum
  mengerjakan fitur baru di area Growth Agent — jangan asumsi, banyak
  keputusan desain sudah didokumentasikan di sana.
- Prompt AI (system_prompt) untuk agent seo_agent/growth_agent/image_agent
  dikelola lewat Prompt Control (services/PromptLoader.php), bukan
  hardcode PHP — cek di situ dulu kalau ada masalah dengan output AI.

Workflow deploy:
- Saya (Claude/Cowork) TIDAK punya akses langsung ke production/cPanel.
- Kode diedit lokal (folder ini) → commit + push ke GitHub → saya kasih
  instruksi manual git pull + cp + diff untuk dijalankan user di terminal
  cPanel.
- Jangan pernah asumsikan sudah ter-deploy hanya karena sudah commit —
  selalu minta konfirmasi hasil diff dari user.

Dokumentasi:
- docs/GROWTH_AGENT_V2_PROPOSAL.md — proposal & roadmap fase-fase Growth Agent
- docs/DECISIONS.md — log keputusan teknis (append-only, jangan hapus history)
- docs/DEPLOY_WORKFLOW.md — cheat sheet deploy
- docs/GROWTH_AGENT_TESTING_GUIDE.md — panduan testing manual

Kalau ada perubahan kode yang perlu dikerjakan devs (bukan saya langsung),
siapkan brief teknis tertulis (bukan cuma instruksi lisan) sebelum
dikirim ke sesi Claude Code devs.