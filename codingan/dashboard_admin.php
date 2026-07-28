<?php
// ============================================================
// dashboard_admin.php - Panel Admin IT
// IT Helpdesk & Ticketing System
// ============================================================
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: login.php'); exit;
}

$id_admin = (int) $_SESSION['user_id'];
$nama     = htmlspecialchars($_SESSION['nama']);
$alert    = ['type' => '', 'message' => ''];
$tab      = $_GET['tab'] ?? 'dashboard';

// ── PROSES POST ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'disposisi') {
        $id_tiket  = (int) ($_POST['id_tiket']  ?? 0);
        $id_teknis = (int) ($_POST['id_teknisi'] ?? 0);
        if ($id_tiket > 0 && $id_teknis > 0) {
            $upd = $koneksi->prepare("UPDATE Tiket SET id_teknisi = ?, status_tiket = 'In Progress' WHERE id_tiket = ? AND status_tiket = 'Open'");
            $upd->bind_param('ii', $id_teknis, $id_tiket);
            $upd->execute();
            $alert = $upd->affected_rows > 0
                ? ['type'=>'success','message'=>'Tiket berhasil didisposisikan. Status diubah menjadi <strong>In Progress</strong>.']
                : ['type'=>'error',  'message'=>'Tiket tidak dapat didisposisikan. Tiket mungkin sudah ditugaskan.'];
            $upd->close();
        } else {
            $alert = ['type'=>'error','message'=>'Pilih tiket dan teknisi terlebih dahulu.'];
        }
    }

    if ($_POST['action'] === 'tambah_kategori') {
        $nm = trim($_POST['nama_kategori'] ?? '');
        if (!empty($nm)) {
            $ins = $koneksi->prepare("INSERT INTO Kategori_Masalah (nama_kategori) VALUES (?)");
            $ins->bind_param('s', $nm); $ins->execute(); $ins->close();
            $alert = ['type'=>'success','message'=>"Kategori <strong>".htmlspecialchars($nm)."</strong> berhasil ditambahkan."];
        } else { $alert = ['type'=>'error','message'=>'Nama kategori tidak boleh kosong.']; }
        $tab = 'master';
    }

    if ($_POST['action'] === 'hapus_kategori') {
        $idk = (int) ($_POST['id_kategori'] ?? 0);
        if ($idk > 0) {
            $del = $koneksi->prepare("DELETE FROM Kategori_Masalah WHERE id_kategori = ?");
            $del->bind_param('i', $idk); $del->execute(); $del->close();
            $alert = ['type'=>'success','message'=>'Kategori berhasil dihapus.'];
        }
        $tab = 'master';
    }
}

// ── AMBIL DATA ───────────────────────────────────────────────
$gs = $koneksi->query("
    SELECT COUNT(*) AS total_tiket,
           SUM(status_tiket='Open') AS open_count,
           SUM(status_tiket='In Progress') AS inprogress_count,
           SUM(status_tiket='Resolved') AS resolved_count,
           SUM(tingkat_prioritas='Urgent') AS urgent_count
    FROM Tiket")->fetch_assoc();

$total_pengguna = (int)$koneksi->query("SELECT COUNT(*) AS c FROM Pengguna")->fetch_assoc()['c'];
$total_teknisi  = (int)$koneksi->query("SELECT COUNT(*) AS c FROM Pengguna WHERE role='Teknisi'")->fetch_assoc()['c'];
$total_karyawan = (int)$koneksi->query("SELECT COUNT(*) AS c FROM Pengguna WHERE role='Karyawan'")->fetch_assoc()['c'];
$avg_rating     = $koneksi->query("SELECT ROUND(AVG(rating),1) AS r FROM Ulasan_Layanan")->fetch_assoc()['r'] ?? null;
$open_badge     = (int)$koneksi->query("SELECT COUNT(*) AS c FROM Tiket WHERE status_tiket='Open' AND id_teknisi IS NULL")->fetch_assoc()['c'];

$tiket_open_q = $koneksi->query("
    SELECT t.id_tiket, t.kode_tiket, t.judul_masalah, t.tingkat_prioritas, t.tanggal_dibuat,
           k.nama_kategori, p.nama AS nama_pelapor
    FROM Tiket t
    LEFT JOIN Kategori_Masalah k ON t.id_kategori=k.id_kategori
    LEFT JOIN Pengguna p ON t.id_pelapor=p.id_user
    WHERE t.status_tiket='Open' AND t.id_teknisi IS NULL
    ORDER BY FIELD(t.tingkat_prioritas,'Urgent','High','Medium','Low'), t.tanggal_dibuat ASC");

$all_tiket_q = $koneksi->query("
    SELECT t.id_tiket, t.kode_tiket, t.judul_masalah, t.tingkat_prioritas, t.status_tiket, t.tanggal_dibuat,
           k.nama_kategori, p.nama AS nama_pelapor, tek.nama AS nama_teknisi
    FROM Tiket t
    LEFT JOIN Kategori_Masalah k ON t.id_kategori=k.id_kategori
    LEFT JOIN Pengguna p ON t.id_pelapor=p.id_user
    LEFT JOIN Pengguna tek ON t.id_teknisi=tek.id_user
    ORDER BY t.tanggal_dibuat DESC LIMIT 50");

$teknisi_q = $koneksi->query("SELECT id_user, nama FROM Pengguna WHERE role='Teknisi' ORDER BY nama ASC");
$teknisi_list = [];
while ($r = $teknisi_q->fetch_assoc()) $teknisi_list[] = $r;

$kategori_q = $koneksi->query("
    SELECT km.*, COUNT(t.id_tiket) AS jumlah_tiket
    FROM Kategori_Masalah km
    LEFT JOIN Tiket t ON km.id_kategori=t.id_kategori
    GROUP BY km.id_kategori ORDER BY km.nama_kategori ASC");

$ulasan_q = $koneksi->query("
    SELECT ul.rating, ul.komentar_kepuasan, t.kode_tiket, t.judul_masalah,
           p.nama AS nama_user, tek.nama AS nama_teknisi
    FROM Ulasan_Layanan ul
    JOIN Tiket t ON ul.id_tiket=t.id_tiket
    JOIN Pengguna p ON ul.id_user=p.id_user
    LEFT JOIN Pengguna tek ON t.id_teknisi=tek.id_user
    ORDER BY ul.id_ulasan DESC");

$koneksi->close();

// ── COMPUTED VALUES ──────────────────────────────────────────
$total   = max(1, (int)($gs['total_tiket'] ?? 0));
$open_p  = round((($gs['open_count'] ?? 0)       / $total) * 100);
$prog_p  = round((($gs['inprogress_count'] ?? 0) / $total) * 100);
$res_p   = round((($gs['resolved_count'] ?? 0)   / $total) * 100);

// SVG Donut: r=42, circ=263.9
$circ = 263.9;
$res_dash  = round(($res_p  / 100) * $circ, 1);
$prog_dash = round(($prog_p / 100) * $circ, 1);
$open_dash = round(($open_p / 100) * $circ, 1);
// offsets (draw resolved first, then progress, then open — rotate -90)
$res_offset  = 0;
$prog_offset = -$res_dash;
$open_offset = -$res_dash - $prog_dash;

$page_info = [
    'dashboard' => ['Dashboard Analitik',    'Ringkasan statistik sistem IT Helpdesk secara real-time.'],
    'disposisi' => ['Disposisi Tiket',        'Tugaskan tiket Open kepada Teknisi IT yang tersedia.'],
    'rekap'     => ['Rekap Semua Tiket',      'Rekapitulasi seluruh tiket yang masuk ke sistem.'],
    'ulasan'    => ['Ulasan & Rating',        'Feedback kepuasan karyawan terhadap layanan IT.'],
    'master'    => ['Master Kategori',        'Kelola kategori masalah untuk klasifikasi tiket.'],
];
$pi = $page_info[$tab] ?? $page_info['dashboard'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin — IT Helpdesk System</title>
    <meta name="description" content="Panel Admin IT — Rekapitulasi, disposisi tiket, dan master data.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Admin accent overrides */
        .nav-link.active { background: rgba(99,102,241,0.1); color: var(--admin-300); }
        .nav-link.active i { color: var(--admin-400); }
        .user-badge.admin { background: rgba(99,102,241,0.12); color: var(--admin-400); }
        .brand-logo-admin { background: linear-gradient(135deg, #6366f1, #8b5cf6); }
        .kpi-icon.indigo  { background: rgba(99,102,241,0.1);  color: var(--admin-400); }
        .kpi-icon.red     { background: rgba(239,68,68,0.1);   color: #f87171; }
        .kpi-icon.amber   { background: rgba(245,158,11,0.1);  color: #fbbf24; }
        .kpi-icon.green   { background: rgba(16,185,129,0.1);  color: #34d399; }
        
        [data-theme="light"] .nav-link.active { color: var(--admin-500); }
        [data-theme="light"] .nav-link.active i { color: var(--admin-500); }
        [data-theme="light"] .user-badge.admin { color: var(--admin-500); }
        [data-theme="light"] .kpi-icon.indigo { color: var(--admin-500); }

        /* Disposisi card accent */
        .disposisi-card { border-left: 3px solid var(--border); }
        .disposisi-card:hover { border-left-color: var(--admin-500); }

        /* Stat row cards */
        .stat-row-card {
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 18px 20px;
        }

        /* Donut legend */
        .legend-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }

        /* Rekap filter bar */
        .filter-bar { display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-bottom: 1px solid var(--border); }

        /* Testimonial card */
        .testimonial-card { border-left: 3px solid rgba(99,102,241,0.3); }

        /* Status stripe Teknisi karyawan */
        .stripe-open     { background: var(--status-open); }
        .stripe-progress { background: var(--status-progress); }
        .stripe-resolved { background: var(--status-resolved); }

        /* Radio pill */
        .radio-pill { display: flex; gap: 8px; }
        .radio-pill label {
            flex: 1; display: flex; align-items: center; gap: 8px;
            padding: 10px 12px; border-radius: var(--radius-md);
            border: 1px solid var(--border); cursor: pointer; transition: all 0.18s;
        }
        .radio-pill label:hover { border-color: var(--border-hover); }
        .radio-pill input[type=radio] { accent-color: var(--admin-400); }
        .radio-pill input:checked + label,
        .radio-pill label:has(input:checked) { border-color: var(--admin-400); background: var(--admin-glow); }

        /* Quick stat inline */
        .inline-stats { display: flex; gap: 20px; }
        .inline-stat-val { font-size: 22px; font-weight: 700; color: var(--text-primary); line-height: 1; }
        .inline-stat-label { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
    </style>
    </script>
    <script>
        // Apply saved theme BEFORE render to prevent flash
        (function() {
            const t = localStorage.getItem('helpdesk_theme') || 'dark';
            if (t === 'light') document.documentElement.setAttribute('data-theme', 'light');
        })();

        function toggleTheme() {
            const html = document.documentElement;
            const isLight = html.getAttribute('data-theme') === 'light';
            html.classList.add('theme-animating');
            if (isLight) {
                html.removeAttribute('data-theme');
                localStorage.setItem('helpdesk_theme', 'dark');
            } else {
                html.setAttribute('data-theme', 'light');
                localStorage.setItem('helpdesk_theme', 'light');
            }
            setTimeout(() => html.classList.remove('theme-animating'), 400);
            updateThemeIcon();
        }

        function updateThemeIcon() {
            const icon = document.getElementById('themeIcon');
            if (!icon) return;
            const isLight = document.documentElement.getAttribute('data-theme') === 'light';
            icon.className = isLight ? 'fas fa-moon' : 'fas fa-sun';
            const btn = icon.closest('.theme-toggle');
            if (btn) btn.title = isLight ? 'Switch ke Dark Mode' : 'Switch ke Light Mode';
        }
        document.addEventListener('DOMContentLoaded', updateThemeIcon);
    </script>
</head>
<body>
<div class="page-wrapper">

<!-- ══════════ SIDEBAR ══════════ -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon brand-logo-admin"><i class="fas fa-headset" style="color:#fff;font-size:16px;"></i></div>
        <div>
            <div class="sidebar-brand-name">IT Helpdesk</div>
            <div class="sidebar-brand-sub">Admin Panel</div>
        </div>
    </div>

    <div class="sidebar-user">
        <div class="sidebar-user-inner">
            <div class="user-avatar" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">
                <?= strtoupper(substr($nama,0,1)) ?>
            </div>
            <div style="overflow:hidden;">
                <div class="user-name truncate"><?= $nama ?></div>
                <span class="user-badge admin">Admin IT</span>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-section-label">Menu Utama</div>
        <a href="?tab=dashboard" class="nav-link <?= $tab==='dashboard'?'active active-admin':'' ?>">
            <i class="fas fa-chart-pie"></i> Dashboard Analitik
        </a>
        <a href="?tab=disposisi" class="nav-link <?= $tab==='disposisi'?'active active-admin':'' ?>">
            <i class="fas fa-paper-plane"></i> Disposisi Tiket
            <?php if ($open_badge > 0): ?>
            <span class="nav-badge"><?= $open_badge ?></span>
            <?php endif; ?>
        </a>
        <a href="?tab=rekap" class="nav-link <?= $tab==='rekap'?'active active-admin':'' ?>">
            <i class="fas fa-table-list"></i> Rekap Semua Tiket
        </a>
        <a href="?tab=ulasan" class="nav-link <?= $tab==='ulasan'?'active active-admin':'' ?>">
            <i class="fas fa-star"></i> Ulasan Layanan
        </a>

        <div class="sidebar-section-label" style="margin-top:12px;">Master Data</div>
        <a href="?tab=master" class="nav-link <?= $tab==='master'?'active active-admin':'' ?>">
            <i class="fas fa-database"></i> Kategori Masalah
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="logout.php" class="logout-link">
            <i class="fas fa-right-from-bracket"></i> Keluar dari Sistem
        </a>
    </div>
</aside>

<!-- ══════════ MAIN ══════════ -->
<main class="main-content">

    <!-- Top Bar -->
    <div class="topbar">
        <div class="topbar-left">
            <div class="topbar-greeting" id="greeting">Halo, <?= $nama ?></div>
            <h1 class="topbar-title"><?= $pi[0] ?></h1>
            <div class="topbar-subtitle"><?= $pi[1] ?></div>
        </div>
        <div style="display:flex;align-items:center;gap:12px;">
            <button class="theme-toggle" onclick="toggleTheme()" title="Toggle Tema"><i class="fas fa-sun" id="themeIcon"></i></button>
            <div class="topbar-clock">
                <div class="clock-time" id="clock">00:00:00</div>
                <div class="clock-date" id="clockDate">Memuat...</div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <?php if ($alert['type']): ?>
    <script>
    document.addEventListener('DOMContentLoaded', () => showToast('<?= $alert['type'] ?>', `<?= addslashes($alert['message']) ?>`));
    </script>
    <?php endif; ?>

    <!-- ════════════════════════════════════════════
         TAB: DASHBOARD ANALITIK
    ════════════════════════════════════════════ -->
    <?php if ($tab === 'dashboard'): ?>

    <!-- KPI Cards -->
    <div class="kpi-grid anim-stagger">
        <?php
        $kpis = [
            ['Total Tiket',    $gs['total_tiket']??0,      'fa-ticket',       'indigo'],
            ['Perlu Ditangani',$gs['open_count']??0,        'fa-envelope-open','red'],
            ['In Progress',    $gs['inprogress_count']??0,  'fa-gear',         'amber'],
            ['Terselesaikan',  $gs['resolved_count']??0,    'fa-check-double', 'green'],
        ];
        foreach ($kpis as $k): ?>
        <div class="kpi-card">
            <div class="kpi-icon <?= $k[3] ?>"><i class="fas <?= $k[2] ?>"></i></div>
            <div class="kpi-label"><?= $k[0] ?></div>
            <div class="kpi-value"><?= $k[1] ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Stats + Donut Row -->
    <div style="display:grid;grid-template-columns:1fr 340px;gap:16px;margin-bottom:16px;">

        <!-- Stat cards -->
        <div style="display:flex;flex-direction:column;gap:12px;">
            <div class="stat-row-card flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="kpi-icon" style="background:rgba(139,92,246,0.1);color:#c084fc;width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:13px;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <div style="font-size:13px;font-weight:600;color:var(--text-primary);">Total Pengguna</div>
                        <div style="font-size:11px;color:var(--text-muted);">Dalam sistem</div>
                    </div>
                </div>
                <div class="inline-stats">
                    <div style="text-align:center;">
                        <div class="inline-stat-val"><?= $total_teknisi ?></div>
                        <div class="inline-stat-label">Teknisi</div>
                    </div>
                    <div style="text-align:center;">
                        <div class="inline-stat-val"><?= $total_karyawan ?></div>
                        <div class="inline-stat-label">Karyawan</div>
                    </div>
                    <div style="text-align:center;">
                        <div class="inline-stat-val"><?= $total_pengguna ?></div>
                        <div class="inline-stat-label">Total</div>
                    </div>
                </div>
            </div>

            <div class="stat-row-card flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="kpi-icon" style="background:rgba(239,68,68,0.1);color:#f87171;width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:13px;">
                        <i class="fas fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <div style="font-size:13px;font-weight:600;color:var(--text-primary);">Tiket Urgent</div>
                        <div style="font-size:11px;color:var(--text-muted);">Memerlukan penanganan segera</div>
                    </div>
                </div>
                <div style="font-size:32px;font-weight:700;color:var(--text-primary);"><?= $gs['urgent_count']??0 ?></div>
            </div>

            <div class="stat-row-card flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="kpi-icon" style="background:rgba(234,179,8,0.1);color:#fbbf24;width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:13px;">
                        <i class="fas fa-star"></i>
                    </div>
                    <div>
                        <div style="font-size:13px;font-weight:600;color:var(--text-primary);">Rata-rata Rating</div>
                        <div style="font-size:11px;color:var(--text-muted);">Dari skala 1–5 ulasan masuk</div>
                    </div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:32px;font-weight:700;color:var(--text-primary);"><?= $avg_rating ?? '-' ?></div>
                    <?php if ($avg_rating): ?>
                    <div style="display:flex;gap:2px;justify-content:flex-end;margin-top:2px;">
                        <?php for ($i=1;$i<=5;$i++): ?>
                        <i class="fas fa-star" style="font-size:10px;color:<?= $i<=round($avg_rating)?'#f59e0b':'var(--text-faint)' ?>;"></i>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- SVG Donut Chart -->
        <div class="card" style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:28px;">
            <div style="font-size:13px;font-weight:600;color:var(--text-secondary);margin-bottom:20px;text-align:center;">Distribusi Status Tiket</div>
            <div class="donut-wrap" style="width:140px;height:140px;">
                <svg viewBox="0 0 100 100" width="140" height="140">
                    <!-- track -->
                    <circle cx="50" cy="50" r="42" fill="none" stroke="var(--bg-elevated)" stroke-width="10"/>
                    <?php if ($total > 0): ?>
                    <!-- resolved (green) -->
                    <circle cx="50" cy="50" r="42" fill="none" stroke="#10b981" stroke-width="10"
                        stroke-dasharray="<?= $res_dash ?> <?= $circ ?>"
                        stroke-dashoffset="<?= -$res_offset ?>"
                        stroke-linecap="round"
                        transform="rotate(-90 50 50)"/>
                    <!-- in progress (amber) -->
                    <?php if ($prog_dash > 0): ?>
                    <circle cx="50" cy="50" r="42" fill="none" stroke="#f59e0b" stroke-width="10"
                        stroke-dasharray="<?= $prog_dash ?> <?= $circ ?>"
                        stroke-dashoffset="<?= $prog_offset ?>"
                        stroke-linecap="round"
                        transform="rotate(-90 50 50)"/>
                    <?php endif; ?>
                    <!-- open (blue) -->
                    <?php if ($open_dash > 0): ?>
                    <circle cx="50" cy="50" r="42" fill="none" stroke="#3b82f6" stroke-width="10"
                        stroke-dasharray="<?= $open_dash ?> <?= $circ ?>"
                        stroke-dashoffset="<?= $open_offset ?>"
                        stroke-linecap="round"
                        transform="rotate(-90 50 50)"/>
                    <?php endif; ?>
                    <?php endif; ?>
                </svg>
                <div class="donut-center">
                    <div class="donut-center-val"><?= $total ?></div>
                    <div class="donut-center-label">Tiket</div>
                </div>
            </div>
            <!-- Legend -->
            <div style="display:flex;flex-direction:column;gap:8px;margin-top:20px;width:100%;">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2"><div class="legend-dot" style="background:#3b82f6;"></div><span style="font-size:12px;color:var(--text-secondary);">Open</span></div>
                    <span style="font-size:12px;font-weight:600;color:var(--text-primary);"><?= $gs['open_count']??0 ?> <span style="color:var(--text-muted);font-weight:400;">(<?= $open_p ?>%)</span></span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2"><div class="legend-dot" style="background:#f59e0b;"></div><span style="font-size:12px;color:var(--text-secondary);">In Progress</span></div>
                    <span style="font-size:12px;font-weight:600;color:var(--text-primary);"><?= $gs['inprogress_count']??0 ?> <span style="color:var(--text-muted);font-weight:400;">(<?= $prog_p ?>%)</span></span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2"><div class="legend-dot" style="background:#10b981;"></div><span style="font-size:12px;color:var(--text-secondary);">Resolved</span></div>
                    <span style="font-size:12px;font-weight:600;color:var(--text-primary);"><?= $gs['resolved_count']??0 ?> <span style="color:var(--text-muted);font-weight:400;">(<?= $res_p ?>%)</span></span>
                </div>
            </div>
        </div>
    </div>


    <!-- ════════════════════════════════════════════
         TAB: DISPOSISI TIKET
    ════════════════════════════════════════════ -->
    <?php elseif ($tab === 'disposisi'): ?>

    <?php if ($tiket_open_q->num_rows === 0): ?>
    <div class="card anim-fade" style="padding:64px;text-align:center;">
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-check-double"></i></div>
            <div class="empty-title">Semua Tiket Sudah Ditugaskan</div>
            <div class="empty-desc">Tidak ada tiket Open yang menunggu disposisi saat ini.</div>
        </div>
    </div>
    <?php else: ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;" class="anim-stagger">
        <?php while ($tk = $tiket_open_q->fetch_assoc()):
            $p_map = ['Urgent'=>'badge-urgent','High'=>'badge-high','Medium'=>'badge-medium','Low'=>'badge-low'];
        ?>
        <div class="card card-hover disposisi-card" style="padding:18px;">
            <div class="flex items-center justify-between mb-3">
                <span class="ticket-code"><?= htmlspecialchars($tk['kode_tiket']) ?></span>
                <span class="badge <?= $p_map[$tk['tingkat_prioritas']] ?? 'badge-medium' ?>"><?= $tk['tingkat_prioritas'] ?></span>
            </div>
            <div style="font-size:14px;font-weight:600;color:var(--text-primary);margin-bottom:8px;line-height:1.4;">
                <?= htmlspecialchars($tk['judul_masalah']) ?>
            </div>
            <div class="flex gap-3 mb-4" style="flex-wrap:wrap;">
                <span style="font-size:11px;color:var(--text-muted);"><i class="fas fa-user" style="margin-right:4px;"></i><?= htmlspecialchars($tk['nama_pelapor']) ?></span>
                <span style="font-size:11px;color:var(--text-muted);"><i class="fas fa-tag" style="margin-right:4px;"></i><?= htmlspecialchars($tk['nama_kategori']) ?></span>
                <span style="font-size:11px;color:var(--text-muted);"><i class="fas fa-clock" style="margin-right:4px;"></i><?= date('d M Y, H:i', strtotime($tk['tanggal_dibuat'])) ?></span>
            </div>
            <form method="POST" action="dashboard_admin.php?tab=disposisi" style="display:flex;gap:8px;align-items:center;">
                <input type="hidden" name="action" value="disposisi">
                <input type="hidden" name="id_tiket" value="<?= $tk['id_tiket'] ?>">
                <select name="id_teknisi" required class="input-field flex-1" style="font-size:12px;padding:7px 10px;">
                    <option value="">— Pilih Teknisi —</option>
                    <?php foreach ($teknisi_list as $tek): ?>
                    <option value="<?= $tek['id_user'] ?>"><?= htmlspecialchars($tek['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary btn-sm" style="flex-shrink:0;">
                    <i class="fas fa-paper-plane"></i> Disposisi
                </button>
            </form>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>


    <!-- ════════════════════════════════════════════
         TAB: REKAP SEMUA TIKET
    ════════════════════════════════════════════ -->
    <?php elseif ($tab === 'rekap'): ?>
    <div class="card anim-slide" style="overflow:hidden;">
        <div class="filter-bar">
            <div class="search-wrap flex-1" style="max-width:320px;">
                <i class="fas fa-magnifying-glass"></i>
                <input type="text" id="searchInput" class="input-field search-input"
                    placeholder="Cari tiket, pelapor, teknisi..." style="font-size:12px;padding:7px 10px 7px 32px;">
            </div>
            <span style="font-size:11px;color:var(--text-muted);" id="rowCount"><?= $all_tiket_q->num_rows ?> tiket ditemukan</span>
        </div>
        <div class="overflow-auto">
            <table class="data-table" id="rekapTable">
                <thead>
                    <tr>
                        <th>Kode</th><th>Judul Masalah</th><th>Pelapor</th>
                        <th>Teknisi</th><th>Kategori</th><th>Prioritas</th>
                        <th>Status</th><th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($all_tiket_q->num_rows === 0): ?>
                    <tr><td colspan="8" style="text-align:center;padding:48px;">
                        <div class="empty-state"><div class="empty-icon"><i class="fas fa-inbox"></i></div>
                        <div class="empty-desc">Belum ada tiket dalam sistem.</div></div>
                    </td></tr>
                    <?php else: ?>
                    <?php while ($tk = $all_tiket_q->fetch_assoc()):
                        $sm = ['Open'=>'badge-open','In Progress'=>'badge-progress','Resolved'=>'badge-resolved'];
                        $pm = ['Urgent'=>'badge-urgent','High'=>'badge-high','Medium'=>'badge-medium','Low'=>'badge-low'];
                    ?>
                    <tr>
                        <td><span class="ticket-code"><?= htmlspecialchars($tk['kode_tiket']) ?></span></td>
                        <td><span style="font-size:13px;font-weight:500;color:var(--text-primary);max-width:180px;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($tk['judul_masalah']) ?></span></td>
                        <td><?= htmlspecialchars($tk['nama_pelapor'] ?? '—') ?></td>
                        <td><?= $tk['nama_teknisi'] ? '<span style="color:#34d399;">'.htmlspecialchars($tk['nama_teknisi']).'</span>' : '<span style="color:var(--text-faint);">Belum</span>' ?></td>
                        <td><?= htmlspecialchars($tk['nama_kategori'] ?? '—') ?></td>
                        <td><span class="badge <?= $pm[$tk['tingkat_prioritas']] ?? '' ?>"><?= $tk['tingkat_prioritas'] ?></span></td>
                        <td><span class="badge <?= $sm[$tk['status_tiket']] ?? '' ?>"><?= $tk['status_tiket'] ?></span></td>
                        <td><span style="font-size:11px;"><?= date('d M Y', strtotime($tk['tanggal_dibuat'])) ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>


    <!-- ════════════════════════════════════════════
         TAB: ULASAN LAYANAN
    ════════════════════════════════════════════ -->
    <?php elseif ($tab === 'ulasan'): ?>
    <div style="display:flex;flex-direction:column;gap:12px;" class="anim-stagger">
        <?php if ($ulasan_q->num_rows === 0): ?>
        <div class="card" style="padding:64px;text-align:center;">
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-star"></i></div>
                <div class="empty-title">Belum Ada Ulasan</div>
                <div class="empty-desc">Ulasan akan muncul setelah tiket diselesaikan dan karyawan memberikan rating.</div>
            </div>
        </div>
        <?php else: ?>
        <?php while ($ul = $ulasan_q->fetch_assoc()): ?>
        <div class="card testimonial-card" style="padding:18px;">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <span class="ticket-code" style="margin-right:8px;"><?= htmlspecialchars($ul['kode_tiket']) ?></span>
                    <span style="font-size:13px;font-weight:500;color:var(--text-primary);"><?= htmlspecialchars($ul['judul_masalah']) ?></span>
                </div>
                <div class="flex gap-1">
                    <?php for ($i=1;$i<=5;$i++): ?>
                    <i class="fas fa-star" style="font-size:13px;color:<?= $i<=$ul['rating']?'#f59e0b':'var(--text-faint)' ?>;"></i>
                    <?php endfor; ?>
                </div>
            </div>
            <?php if ($ul['komentar_kepuasan']): ?>
            <p style="font-size:13px;color:var(--text-secondary);font-style:italic;line-height:1.7;padding-left:12px;border-left:2px solid rgba(99,102,241,0.3);margin-bottom:10px;">
                "<?= htmlspecialchars($ul['komentar_kepuasan']) ?>"
            </p>
            <?php endif; ?>
            <div class="flex gap-4" style="flex-wrap:wrap;">
                <span style="font-size:11px;color:var(--text-muted);"><i class="fas fa-user" style="margin-right:4px;"></i><?= htmlspecialchars($ul['nama_user']) ?></span>
                <?php if ($ul['nama_teknisi']): ?>
                <span style="font-size:11px;color:var(--text-muted);"><i class="fas fa-screwdriver-wrench" style="margin-right:4px;"></i><?= htmlspecialchars($ul['nama_teknisi']) ?></span>
                <?php endif; ?>
                <span style="font-size:11px;font-weight:600;color:#f59e0b;"><?= $ul['rating'] ?>/5 bintang</span>
            </div>
        </div>
        <?php endwhile; ?>
        <?php endif; ?>
    </div>


    <!-- ════════════════════════════════════════════
         TAB: MASTER DATA KATEGORI
    ════════════════════════════════════════════ -->
    <?php elseif ($tab === 'master'): ?>
    <div style="display:grid;grid-template-columns:1fr 1.6fr;gap:16px;">

        <!-- Form tambah -->
        <div class="card anim-slide" style="padding:20px;align-self:start;">
            <div class="card-header" style="padding:0 0 16px 0;border:none;margin-bottom:16px;border-bottom:1px solid var(--border);">
                <div>
                    <div class="card-title">Tambah Kategori</div>
                    <div class="card-subtitle">Tambah kategori masalah baru</div>
                </div>
            </div>
            <form method="POST" action="dashboard_admin.php?tab=master">
                <input type="hidden" name="action" value="tambah_kategori">
                <div class="form-group" style="margin-bottom:14px;">
                    <label class="input-label">Nama Kategori</label>
                    <input type="text" name="nama_kategori" class="input-field" placeholder="Contoh: Keamanan Siber" required>
                </div>
                <button type="submit" class="btn btn-primary btn-full">
                    <i class="fas fa-plus"></i> Tambah Kategori
                </button>
            </form>
        </div>

        <!-- Daftar kategori -->
        <div class="card anim-slide" style="overflow:hidden;">
            <div class="card-header">
                <div>
                    <div class="card-title">Daftar Kategori Masalah</div>
                    <div class="card-subtitle"><?= $kategori_q->num_rows ?> kategori terdaftar</div>
                </div>
            </div>
            <?php if ($kategori_q->num_rows === 0): ?>
            <div style="padding:40px;text-align:center;color:var(--text-muted);font-size:13px;">Belum ada kategori.</div>
            <?php else: ?>
            <div style="divide-y:1px solid var(--border);">
                <?php while ($kat = $kategori_q->fetch_assoc()): ?>
                <div class="flex items-center justify-between" style="padding:12px 18px;border-bottom:1px solid rgba(48,54,61,0.5);">
                    <div class="flex items-center gap-3">
                        <div style="width:30px;height:30px;border-radius:8px;background:rgba(99,102,241,0.08);display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-tag" style="font-size:12px;color:var(--admin-400);"></i>
                        </div>
                        <div>
                            <div style="font-size:13px;font-weight:500;color:var(--text-primary);"><?= htmlspecialchars($kat['nama_kategori']) ?></div>
                            <div style="font-size:11px;color:var(--text-muted);"><?= $kat['jumlah_tiket'] ?> tiket terkait</div>
                        </div>
                    </div>
                    <?php if ($kat['jumlah_tiket'] == 0): ?>
                    <form method="POST" action="dashboard_admin.php?tab=master" onsubmit="return confirm('Hapus kategori ini?')">
                        <input type="hidden" name="action" value="hapus_kategori">
                        <input type="hidden" name="id_kategori" value="<?= $kat['id_kategori'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                    </form>
                    <?php else: ?>
                    <span style="font-size:11px;color:var(--text-faint);font-style:italic;">Tidak dapat dihapus</span>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</main>
</div>

<script>
// ── Clock ─────────────────────────────────────────────────────
const HARI  = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
const BULAN = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
function updateClock() {
    const n = new Date();
    document.getElementById('clock').textContent =
        [n.getHours(),n.getMinutes(),n.getSeconds()].map(v=>String(v).padStart(2,'0')).join(':');
    document.getElementById('clockDate').textContent =
        `${HARI[n.getDay()]}, ${String(n.getDate()).padStart(2,'0')} ${BULAN[n.getMonth()]} ${n.getFullYear()}`;
}
updateClock(); setInterval(updateClock, 1000);

// ── Greeting ──────────────────────────────────────────────────
(function(){
    const h = new Date().getHours();
    const g = h < 11 ? 'Selamat Pagi' : h < 15 ? 'Selamat Siang' : h < 18 ? 'Selamat Sore' : 'Selamat Malam';
    document.getElementById('greeting').textContent = `${g}, <?= addslashes($nama) ?> 👋`;
})();

// ── Toast ─────────────────────────────────────────────────────
function showToast(type, msg) {
    const c   = document.getElementById('toastContainer');
    const div = document.createElement('div');
    div.className = `toast toast-${type}`;
    div.innerHTML = `
        <span class="toast-icon"><i class="fas ${type==='success'?'fa-circle-check':'fa-circle-exclamation'}"></i></span>
        <div class="toast-body">${msg}</div>
        <button class="toast-close" onclick="dismissToast(this.parentElement)"><i class="fas fa-xmark"></i></button>`;
    c.appendChild(div);
    setTimeout(() => dismissToast(div), 6000);
}
function dismissToast(el) {
    if (!el || el._removing) return;
    el._removing = true;
    el.style.animation = 'toastOut 0.35s ease forwards';
    setTimeout(() => el.remove(), 350);
}

// ── Search Filter (Rekap) ──────────────────────────────────────
const searchInput = document.getElementById('searchInput');
if (searchInput) {
    searchInput.addEventListener('input', function() {
        const q = this.value.toLowerCase();
        const rows = document.querySelectorAll('#rekapTable tbody tr');
        let visible = 0;
        rows.forEach(tr => {
            const match = tr.textContent.toLowerCase().includes(q);
            tr.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        const rc = document.getElementById('rowCount');
        if (rc) rc.textContent = `${visible} tiket ditemukan`;
    });
}
</script>
</body>
</html>
