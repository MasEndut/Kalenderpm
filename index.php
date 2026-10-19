<?php
session_start();
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');
$today = date('Y-m-d');
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f8f4">
    <meta name="csrf-token" content="<?= $csrfToken ?>">
    <title>Kalender Penerima Manfaat</title>
    <link rel="stylesheet" href="assets/app.css?v=20261019">
</head>
<body data-today="<?= htmlspecialchars($today, ENT_QUOTES, 'UTF-8') ?>">
    <main class="app-shell">
        <header class="topbar">
            <a class="brand" href="./" aria-label="Kalender Penerima Manfaat">
                <span class="brand-mark" aria-hidden="true">pm</span>
                <span class="brand-copy"><strong>Kalender PM</strong><small>Laporan operasional</small></span>
            </a>
            <div class="topbar-actions">
                <div class="topbar-note"><span class="live-dot"></span> Pencatatan harian</div>
                <button class="theme-toggle icon-button" id="theme-toggle" type="button" aria-label="Aktifkan mode malam" title="Aktifkan mode malam" aria-pressed="false">
                    <svg class="theme-icon-moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.2 15.4A8.5 8.5 0 0 1 8.6 3.8 8.6 8.6 0 1 0 20.2 15.4Z"/></svg>
                    <svg class="theme-icon-sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
                </button>
            </div>
        </header>

        <div class="workspace">
            <section class="calendar-section" aria-labelledby="calendar-title">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow">OPERASIONAL HARIAN</p>
                        <h1 id="calendar-title">Kalender penerima manfaat</h1>
                        <p class="section-intro">Pilih tanggal untuk melihat atau mengisi laporan.</p>
                    </div>
                    <span class="year-stamp" id="year-stamp" aria-hidden="true"></span>
                </div>

                <div class="calendar-toolbar" aria-label="Navigasi kalender">
                    <div class="month-controls">
                        <button class="icon-button" id="previous-month" type="button" aria-label="Bulan sebelumnya" title="Bulan sebelumnya">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                        </button>
                        <label class="visually-hidden" for="month-select">Bulan</label>
                        <select id="month-select" class="month-select"></select>
                        <label class="visually-hidden" for="year-select">Tahun</label>
                        <select id="year-select" class="year-select"></select>
                        <button class="icon-button" id="next-month" type="button" aria-label="Bulan berikutnya" title="Bulan berikutnya">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                        </button>
                    </div>
                    <button class="today-button" id="today-button" type="button">Hari ini</button>
                </div>

                <div class="calendar-card">
                    <div class="weekday-row" aria-hidden="true">
                        <span>Min</span><span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span>
                    </div>
                    <div class="calendar-grid" id="calendar-grid" role="grid" aria-label="Tanggal pada bulan terpilih" aria-live="polite"></div>
                    <div class="calendar-footer">
                        <div class="legend" aria-label="Keterangan status">
                            <span><i class="legend-dot open"></i>Buka</span>
                            <span><i class="legend-dot closed"></i>Tutup</span>
                            <span><i class="legend-dot empty"></i>Belum diisi</span>
                        </div>
                        <span class="month-summary" id="month-summary"></span>
                    </div>
                </div>
                <div class="calendar-error" id="calendar-error" role="status" hidden></div>
            </section>

            <div class="drawer-backdrop" id="drawer-backdrop"></div>
            <aside class="detail-panel" id="detail-panel" aria-labelledby="detail-title" aria-hidden="true">
                <div class="empty-detail" id="empty-detail">
                    <div class="empty-mark" aria-hidden="true">
                        <svg viewBox="0 0 48 48"><rect x="8" y="11" width="32" height="29" rx="5"/><path d="M16 7v8m16-8v8M8 20h32M17 27h5m5 0h5m-15 7h5"/></svg>
                    </div>
                    <p class="eyebrow">RINCIAN TANGGAL</p>
                    <h2>Pilih tanggal kalender</h2>
                    <p>Laporan dan jumlah penerima manfaat akan tampil di sini.</p>
                </div>

                <div class="detail-content" id="detail-content" hidden>
                    <div class="detail-header">
                        <div>
                            <p class="eyebrow">RINCIAN TANGGAL</p>
                            <h2 id="detail-title">Detail Laporan Operasional</h2>
                        </div>
                        <button class="icon-button close-drawer" id="close-detail-icon" type="button" aria-label="Tutup detail" title="Tutup detail">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m18 6-12 12M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="date-banner">
                        <span class="date-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><rect x="3.5" y="5" width="17" height="16" rx="3"/><path d="M8 3v4m8-4v4M4 10h16"/></svg>
                        </span>
                        <div><span class="muted-label">Tanggal laporan</span><strong id="selected-date"></strong></div>
                        <span class="saved-pill" id="saved-pill"></span>
                    </div>

                    <div class="status-field">
                        <label for="status-select">Status operasional</label>
                        <select id="status-select" disabled>
                            <option value="">Belum Diisi</option>
                            <option value="Buka">Buka</option>
                            <option value="Tutup">Tutup</option>
                        </select>
                    </div>

                    <div class="table-heading">
                        <div><h3>Rincian penerima</h3><span class="muted-label">Sekolah, lembaga, atau kategori</span></div>
                        <div class="table-heading-tools">
                            <span class="row-count" id="row-count">0 baris</span>
                            <button class="reset-draft-button" id="reset-draft" type="button" aria-label="Reset isian ke kategori awal" title="Reset isian ke kategori awal" hidden>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12a9 9 0 1 0 2.64-6.36L3 8m0-5v5h5"/></svg>
                                Reset
                            </button>
                        </div>
                    </div>

                    <div class="beneficiary-table-wrap">
                        <table class="beneficiary-table">
                            <thead><tr><th scope="col">Nama sekolah / lembaga / kategori</th><th scope="col">Jumlah PM</th><th scope="col"><span class="visually-hidden">Aksi</span></th></tr></thead>
                            <tbody id="beneficiary-rows"></tbody>
                        </table>
                        <div class="table-empty" id="table-empty">Belum ada rincian penerima.</div>
                    </div>

                    <button class="add-row-button" id="add-row" type="button" hidden>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>Tambah Baris
                    </button>

                    <div class="total-bar"><span>TOTAL PM</span><strong id="total-pm">0</strong></div>
                    <p class="detail-error" id="detail-error" role="alert" hidden></p>
                    <div class="detail-actions" id="detail-actions"></div>
                </div>
            </aside>
        </div>
        <div class="toast" id="toast" role="status" aria-live="polite"></div>
        <footer class="page-footer"><span>Kalender Penerima Manfaat</span><span>Data tersimpan per tanggal</span></footer>
    </main>
    <script src="assets/app.js?v=20261019.2"></script>
</body>
</html>