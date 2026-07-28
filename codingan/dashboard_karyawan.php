<?php
// ============================================================
// dashboard_karyawan.php - Portal Karyawan
// IT Helpdesk & Ticketing System
// ============================================================
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Karyawan') {
    header('Location: login.php'); exit;
}

$id_user = (int) $_SESSION['user_id'];
$nama    = htmlspecialchars($_SESSION['nama']);
$alert   = ['type' => '', 'message' => '', 'kode_tiket' => ''];
$tab     = $_GET['tab'] ?? 'tiket';

// ── PROSES: Buat Tiket Baru ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'buat_tiket') {
    $id_kategori = (int)   ($_POST['id_kategori'] ?? 0);
    $id_aset     = !empty($_POST['id_aset']) ? (int)$_POST['id_aset'] : null;
    $judul       = trim(   $_POST['judul']       ?? '');
    $deskripsi   = trim(   $_POST['deskripsi']   ?? '');
    $prioritas   = trim(   $_POST['prioritas']   ?? 'Medium');
    $valid_p     = ['Low','Medium','High','Urgent'];

    if ($id_kategori <= 0 || empty($judul) || empty($deskripsi) || !in_array($prioritas, $valid_p)) {
        $alert = ['type'=>'error','message'=>'Harap Lengkapi Data! Semua field wajib diisi dengan benar.','kode_tiket'=>''];
    } else {
        $rc  = $koneksi->query("SELECT COUNT(*) AS total FROM Tiket")->fetch_assoc();
        $num = (int)$rc['total'] + 1;
        $kode = '#TCK-' . str_pad($num, 3, '0', STR_PAD_LEFT);

        $cek = $koneksi->prepare("SELECT id_tiket FROM Tiket WHERE kode_tiket = ?");
        $cek->bind_param('s', $kode); $cek->execute(); $cek->store_result();
        while ($cek->num_rows > 0) {
            $num++; $kode = '#TCK-' . str_pad($num, 3, '0', STR_PAD_LEFT);
            $cek->bind_param('s', $kode); $cek->execute(); $cek->store_result();
        }
        $cek->close();

        $stmt = $koneksi->prepare("INSERT INTO Tiket (id_pelapor,id_kategori,id_aset,kode_tiket,judul_masalah,deskripsi,tingkat_prioritas,status_tiket,tanggal_dibuat) VALUES (?,?,?,?,?,?,?,'Open',NOW())");
        $stmt->bind_param('iiissss', $id_user, $id_kategori, $id_aset, $kode, $judul, $deskripsi, $prioritas);
        if ($stmt->execute()) {
            $alert = ['type'=>'success','message'=>"Tiket berhasil dikirim! Kode tiket Anda: <strong>{$kode}</strong>. Tim IT akan segera menangani.",'kode_tiket'=>$kode];
        } else {
            $alert = ['type'=>'error','message'=>'Gagal membuat tiket. Silakan coba lagi.','kode_tiket'=>''];
        }
        $stmt->close();
    }
    $tab = 'tiket';
}

// ── PROSES: Kirim Ulasan ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'kirim_ulasan') {
    $id_tiket = (int)($_POST['id_tiket'] ?? 0);
    $rating   = (int)($_POST['rating']   ?? 0);
    $komentar = trim($_POST['komentar']  ?? '');
    if ($id_tiket > 0 && $rating >= 1 && $rating <= 5) {
        $cek = $koneksi->prepare("SELECT id_tiket FROM Tiket WHERE id_tiket=? AND id_pelapor=? AND status_tiket='Resolved'");
        $cek->bind_param('ii', $id_tiket, $id_user); $cek->execute(); $cek->store_result();
        if ($cek->num_rows > 0) {
            $ins = $koneksi->prepare("INSERT IGNORE INTO Ulasan_Layanan (id_tiket,id_user,rating,komentar_kepuasan) VALUES (?,?,?,?)");
            $ins->bind_param('iiis', $id_tiket, $id_user, $rating, $komentar);
            $ins->execute(); $ins->close();
            $alert = ['type'=>'success','message'=>'Terima kasih atas ulasan Anda! Feedback sangat berarti bagi kami.','kode_tiket'=>''];
        }
        $cek->close();
    }
    $tab = 'riwayat';
}

// ── AMBIL DATA ────────────────────────────────────────────────
$kategori_list = $koneksi->query("SELECT * FROM Kategori_Masalah ORDER BY nama_kategori ASC");
$aset_stmt     = $koneksi->prepare("SELECT * FROM Aset_Perangkat WHERE id_user=? ORDER BY nama_aset ASC");
$aset_stmt->bind_param('i', $id_user); $aset_stmt->execute();
$aset_result = $aset_stmt->get_result();

$stat_q = $koneksi->prepare("SELECT COUNT(*) AS total, SUM(status_tiket='Open') AS open_count, SUM(status_tiket='In Progress') AS inprogress_count, SUM(status_tiket='Resolved') AS resolved_count FROM Tiket WHERE id_pelapor=?");
$stat_q->bind_param('i', $id_user); $stat_q->execute();
$stats = $stat_q->get_result()->fetch_assoc();
$stat_q->close();

$tiket_q = $koneksi->prepare("
    SELECT t.id_tiket, t.kode_tiket, t.judul_masalah, t.tingkat_prioritas, t.status_tiket, t.tanggal_dibuat,
           k.nama_kategori, p.nama AS nama_teknisi, u.id_ulasan
    FROM Tiket t
    LEFT JOIN Kategori_Masalah k ON t.id_kategori=k.id_kategori
    LEFT JOIN Pengguna p ON t.id_teknisi=p.id_user
    LEFT JOIN Ulasan_Layanan u ON t.id_tiket=u.id_tiket
    WHERE t.id_pelapor=? ORDER BY t.tanggal_dibuat DESC");
$tiket_q->bind_param('i', $id_user); $tiket_q->execute();
$tiket_list = $tiket_q->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Karyawan — IT Helpdesk System</title>
    <meta name="description" content="Portal karyawan untuk membuat tiket kendala IT dan memantau status penanganan.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Karyawan accent = Blue */
        .nav-link.active { background: rgba(59,130,246,0.1); color: var(--blue-300); }
        .nav-link.active i { color: var(--blue-400); }
        .user-badge.karyawan { background: rgba(59,130,246,0.12); color: var(--blue-400); }
        .input-field:focus { border-color: rgba(59,130,246,0.6); box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        
        [data-theme="light"] .nav-link.active { color: var(--blue-500); }
        [data-theme="light"] .nav-link.active i { color: var(--blue-500); }
        [data-theme="light"] .user-badge.karyawan { color: var(--blue-500); }

        /* Stat cards Karyawan */
        .stat-kary { display: grid; grid-template-columns: repeat(4,1fr); gap: 14px; margin-bottom: 24px; }

        /* Form tiket */
        .form-section {
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            overflow: hidden;
            margin-bottom: 0;
        }
        .form-section-header {
            display: flex; align-items: center; gap: 12px;
            padding: 16px 20px;
            background: var(--bg-elevated);
            border-bottom: 1px solid var(--border);
        }
        .form-section-icon {
            width: 32px; height: 32px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px;
        }
        .form-section-body { padding: 20px; background: var(--bg-surface); }

        /* Priority selector */
        .priority-selector { display: grid; grid-template-columns: repeat(4,1fr); gap: 8px; }
        .priority-opt { position: relative; }
        .priority-opt input { position: absolute; opacity: 0; width: 0; height: 0; }
        .priority-opt label {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 4px; padding: 10px 6px;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            cursor: pointer; transition: all 0.18s;
            text-align: center;
        }
        .priority-opt label:hover { border-color: var(--border-hover); background: var(--bg-elevated); }
        .priority-opt input:checked + label { font-weight: 600; }
        .priority-opt.urgent input:checked + label { border-color: var(--priority-urgent); background: var(--priority-urgent-bg); color: var(--priority-urgent); }
        .priority-opt.high   input:checked + label { border-color: var(--priority-high);   background: var(--priority-high-bg);   color: var(--priority-high); }
        .priority-opt.medium input:checked + label { border-color: var(--priority-medium); background: var(--priority-medium-bg); color: var(--priority-medium); }
        .priority-opt.low    input:checked + label { border-color: var(--priority-low);    background: var(--priority-low-bg);    color: var(--priority-low); }

        /* Riwayat table */
        .ticket-code-blue { color: var(--blue-400); background: rgba(59,130,246,0.08); }
    </style>
    </script>
    <script>
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
        <div class="sidebar-brand-icon" style="background:linear-gradient(135deg,#3b82f6,#60a5fa);">
            <i class="fas fa-user" style="color:#fff;font-size:15px;"></i>
        </div>
        <div>
            <div class="sidebar-brand-name">IT Helpdesk</div>
            <div class="sidebar-brand-sub">Portal Karyawan</div>
        </div>
    </div>

    <div class="sidebar-user">
        <div class="sidebar-user-inner">
            <div class="user-avatar" style="background:linear-gradient(135deg,#3b82f6,#06b6d4);">
                <?= strtoupper(substr($nama,0,1)) ?>
            </div>
            <div style="overflow:hidden;">
                <div class="user-name truncate"><?= $nama ?></div>
                <span class="user-badge karyawan">Karyawan</span>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-section-label">Menu</div>
        <a href="?tab=tiket" class="nav-link <?= $tab==='tiket'?'active active-blue':'' ?>">
            <i class="fas fa-ticket"></i> Buat Tiket Baru
        </a>
        <a href="?tab=riwayat" class="nav-link <?= $tab==='riwayat'?'active active-blue':'' ?>">
            <i class="fas fa-list-check"></i> Riwayat Tiket
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
            <h1 class="topbar-title"><?= $tab==='riwayat' ? 'Riwayat Tiket Saya' : 'Buat Tiket Kendala' ?></h1>
            <div class="topbar-subtitle"><?= $tab==='riwayat' ? 'Pantau status penanganan tiket IT Anda.' : 'Laporkan kendala IT Anda kepada tim helpdesk.' ?></div>
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

    <!-- Stat Cards -->
    <div class="stat-kary anim-stagger">
        <?php
        $sc = [
            ['Total',      $stats['total']??0,             'fa-ticket',        'rgba(59,130,246,0.08)',  '#60a5fa'],
            ['Open',       $stats['open_count']??0,        'fa-door-open',     'rgba(245,158,11,0.08)',  '#fbbf24'],
            ['In Progress',$stats['inprogress_count']??0,  'fa-gear',          'rgba(99,102,241,0.08)',  '#818cf8'],
            ['Resolved',   $stats['resolved_count']??0,    'fa-circle-check',  'rgba(16,185,129,0.08)',  '#34d399'],
        ];
        foreach ($sc as $s): ?>
        <div class="kpi-card">
            <div class="flex items-center justify-between mb-2">
                <div class="kpi-label"><?= $s[0] ?></div>
                <div style="width:30px;height:30px;border-radius:8px;background:<?= $s[3] ?>;display:flex;align-items:center;justify-content:center;">
                    <i class="fas <?= $s[2] ?>" style="font-size:12px;color:<?= $s[4] ?>;"></i>
                </div>
            </div>
            <div class="kpi-value"><?= $s[1] ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ════ TAB: BUAT TIKET ════ -->
    <?php if ($tab === 'tiket'): ?>
    <div class="card anim-slide" style="overflow:hidden;">
        <div class="card-header">
            <div>
                <div class="card-title">Form Tiket Kendala IT</div>
                <div class="card-subtitle">Isi semua informasi berikut untuk membuat tiket</div>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="dashboard_karyawan.php" id="formTiket" novalidate>
                <input type="hidden" name="action" value="buat_tiket">

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                    <!-- Judul -->
                    <div style="grid-column:span 2;">
                        <label class="input-label"><i class="fas fa-heading" style="margin-right:5px;color:var(--blue-400);"></i>Judul Masalah <span style="color:#f87171;">*</span></label>
                        <input type="text" name="judul" id="judul" required class="input-field"
                            placeholder="Contoh: Laptop tidak bisa menyala"
                            value="<?= htmlspecialchars($_POST['judul'] ?? '') ?>">
                    </div>

                    <!-- Kategori -->
                    <div>
                        <label class="input-label"><i class="fas fa-tag" style="margin-right:5px;color:var(--blue-400);"></i>Kategori Masalah <span style="color:#f87171;">*</span></label>
                        <select name="id_kategori" id="id_kategori" required class="input-field">
                            <option value="">— Pilih Kategori —</option>
                            <?php while ($kat = $kategori_list->fetch_assoc()): ?>
                            <option value="<?= $kat['id_kategori'] ?>"
                                <?= (isset($_POST['id_kategori']) && $_POST['id_kategori'] == $kat['id_kategori']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($kat['nama_kategori']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Aset -->
                    <div>
                        <label class="input-label"><i class="fas fa-laptop" style="margin-right:5px;color:var(--blue-400);"></i>Perangkat Terkait <span style="color:var(--text-muted);font-weight:400;">(Opsional)</span></label>
                        <select name="id_aset" id="id_aset" class="input-field">
                            <option value="">— Pilih Perangkat —</option>
                            <?php while ($aset = $aset_result->fetch_assoc()): ?>
                            <option value="<?= $aset['id_aset'] ?>"
                                <?= (isset($_POST['id_aset']) && $_POST['id_aset'] == $aset['id_aset']) ? 'selected' : '' ?>>
                                [<?= htmlspecialchars($aset['kode_aset']) ?>] <?= htmlspecialchars($aset['nama_aset']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Deskripsi -->
                    <div style="grid-column:span 2;">
                        <label class="input-label"><i class="fas fa-align-left" style="margin-right:5px;color:var(--blue-400);"></i>Deskripsi Kendala <span style="color:#f87171;">*</span></label>
                        <textarea name="deskripsi" id="deskripsi" rows="4" required class="input-field" style="resize:vertical;"
                            placeholder="Jelaskan kendala secara detail: kapan terjadi, langkah yang sudah dicoba, pesan error yang muncul, dll."><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
                        <div class="input-hint">Semakin detail deskripsi, semakin cepat tim IT dapat membantu Anda.</div>
                    </div>

                    <!-- Prioritas -->
                    <div style="grid-column:span 2;">
                        <label class="input-label" style="margin-bottom:10px;"><i class="fas fa-flag" style="margin-right:5px;color:var(--blue-400);"></i>Tingkat Prioritas <span style="color:#f87171;">*</span></label>
                        <div class="priority-selector">
                            <?php
                            $opts = [
                                ['Low',    'Tidak Mendesak', 'fa-circle',           'low',    '🟢'],
                                ['Medium', 'Normal',         'fa-circle-half-stroke','medium', '🟡'],
                                ['High',   'Mendesak',       'fa-circle-exclamation','high',   '🟠'],
                                ['Urgent', 'Sangat Mendesak','fa-triangle-exclamation','urgent','🔴'],
                            ];
                            foreach ($opts as $o):
                                $sel = (($_POST['prioritas'] ?? 'Medium') === $o[0]);
                            ?>
                            <div class="priority-opt <?= strtolower($o[0]) ?>">
                                <input type="radio" name="prioritas" id="p_<?= $o[0] ?>" value="<?= $o[0] ?>" <?= $sel?'checked':'' ?>>
                                <label for="p_<?= $o[0] ?>">
                                    <span style="font-size:18px;"><?= $o[4] ?></span>
                                    <span style="font-size:12px;font-weight:600;"><?= $o[0] ?></span>
                                    <span style="font-size:10px;color:var(--text-muted);"><?= $o[1] ?></span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between" style="padding-top:16px;border-top:1px solid var(--border);">
                    <span style="font-size:11px;color:var(--text-muted);"><span style="color:#f87171;">*</span> Field wajib diisi</span>
                    <button type="submit" id="btnKirim" class="btn btn-blue">
                        <i class="fas fa-paper-plane"></i>
                        <span id="btnText">Kirim Tiket</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ════ TAB: RIWAYAT ════ -->
    <?php else: ?>
    <div class="card anim-slide" style="overflow:hidden;">
        <div class="card-header">
            <div>
                <div class="card-title">Riwayat Tiket Saya</div>
                <div class="card-subtitle">Semua tiket yang pernah Anda buat</div>
            </div>
            <a href="?tab=tiket" class="btn btn-blue btn-sm"><i class="fas fa-plus"></i> Tiket Baru</a>
        </div>
        <div class="overflow-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Kode</th><th>Judul Masalah</th><th>Kategori</th>
                        <th>Prioritas</th><th>Teknisi</th><th>Status</th>
                        <th>Tanggal</th><th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($tiket_list->num_rows === 0): ?>
                <tr><td colspan="8" style="text-align:center;padding:48px;">
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-ticket"></i></div>
                        <div class="empty-title">Belum Ada Tiket</div>
                        <div class="empty-desc"><a href="?tab=tiket" style="color:var(--blue-400);">Buat tiket pertama Anda</a></div>
                    </div>
                </td></tr>
                <?php else: ?>
                <?php while ($tk = $tiket_list->fetch_assoc()):
                    $pm = ['Urgent'=>'badge-urgent','High'=>'badge-high','Medium'=>'badge-medium','Low'=>'badge-low'];
                    $sm = ['Open'=>'badge-open','In Progress'=>'badge-progress','Resolved'=>'badge-resolved'];
                ?>
                <tr>
                    <td><span class="ticket-code ticket-code-blue"><?= htmlspecialchars($tk['kode_tiket']) ?></span></td>
                    <td><span style="font-size:13px;font-weight:500;color:var(--text-primary);max-width:200px;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($tk['judul_masalah']) ?></span></td>
                    <td><?= htmlspecialchars($tk['nama_kategori'] ?? '—') ?></td>
                    <td><span class="badge <?= $pm[$tk['tingkat_prioritas']] ?? '' ?>"><?= $tk['tingkat_prioritas'] ?></span></td>
                    <td><?= $tk['nama_teknisi'] ? htmlspecialchars($tk['nama_teknisi']) : '<span style="color:var(--text-faint);">Belum ditugaskan</span>' ?></td>
                    <td><span class="badge <?= $sm[$tk['status_tiket']] ?? '' ?>"><?= $tk['status_tiket'] ?></span></td>
                    <td>
                        <div style="font-size:12px;color:var(--text-secondary);"><?= date('d M Y', strtotime($tk['tanggal_dibuat'])) ?></div>
                        <div style="font-size:11px;color:var(--text-muted);"><?= date('H:i', strtotime($tk['tanggal_dibuat'])) ?></div>
                    </td>
                    <td>
                        <?php if ($tk['status_tiket'] === 'Resolved' && !$tk['id_ulasan']): ?>
                        <button onclick="openUlasan(<?= $tk['id_tiket'] ?>, '<?= htmlspecialchars($tk['kode_tiket']) ?>')"
                            class="btn btn-sm" style="background:rgba(245,158,11,0.1);color:#fbbf24;border:1px solid rgba(245,158,11,0.2);">
                            <i class="fas fa-star"></i> Beri Ulasan
                        </button>
                        <?php elseif ($tk['id_ulasan']): ?>
                        <span style="font-size:11px;color:#34d399;display:flex;align-items:center;gap:4px;">
                            <i class="fas fa-check-circle"></i> Sudah Diulas
                        </span>
                        <?php else: ?>
                        <span style="color:var(--text-faint);font-size:13px;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</main>
</div>

<!-- ══════════ MODAL ULASAN ══════════ -->
<div id="modalUlasan" style="display:none;" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <div>
                <div class="modal-title">Beri Ulasan Layanan</div>
                <div style="font-size:12px;color:var(--text-muted);margin-top:2px;" id="modalLabel"></div>
            </div>
            <button class="modal-close" onclick="closeUlasan()"><i class="fas fa-xmark"></i></button>
        </div>

        <form method="POST" action="dashboard_karyawan.php">
            <input type="hidden" name="action" value="kirim_ulasan">
            <input type="hidden" name="id_tiket" id="modalIdTiket">

            <div style="margin-bottom:20px;">
                <label class="input-label" style="margin-bottom:10px;">Rating Kepuasan</label>
                <div class="star-rating" id="starRating">
                    <?php for ($i=5;$i>=1;$i--): ?>
                    <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" <?= $i===5?'checked':'' ?>>
                    <label for="star<?= $i ?>" title="<?= $i ?> Bintang">&#9733;</label>
                    <?php endfor; ?>
                </div>
                <div class="input-hint" style="margin-top:8px;">Klik bintang untuk memberikan rating (1–5)</div>
            </div>

            <div style="margin-bottom:20px;">
                <label class="input-label">Komentar <span style="font-weight:400;color:var(--text-muted);">(Opsional)</span></label>
                <textarea name="komentar" rows="3" class="input-field" style="resize:none;"
                    placeholder="Bagikan pengalaman Anda tentang pelayanan yang diterima..."></textarea>
            </div>

            <button type="submit" class="btn btn-full" style="background:linear-gradient(135deg,#f59e0b,#f97316);color:#fff;">
                <i class="fas fa-paper-plane"></i> Kirim Ulasan
            </button>
        </form>
    </div>
</div>

<script>
// ── Clock ──────────────────────────────────────────────────────
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

// ── Greeting ───────────────────────────────────────────────────
(function(){
    const h = new Date().getHours();
    const g = h<11?'Selamat Pagi':h<15?'Selamat Siang':h<18?'Selamat Sore':'Selamat Malam';
    document.getElementById('greeting').textContent = `${g}, <?= addslashes($nama) ?> 👋`;
})();

// ── Toast ──────────────────────────────────────────────────────
function showToast(type, msg) {
    const c   = document.getElementById('toastContainer');
    const div = document.createElement('div');
    div.className = `toast toast-${type}`;
    div.innerHTML = `<span class="toast-icon"><i class="fas ${type==='success'?'fa-circle-check':'fa-circle-exclamation'}"></i></span><div class="toast-body">${msg}</div><button class="toast-close" onclick="dismissToast(this.parentElement)"><i class="fas fa-xmark"></i></button>`;
    c.appendChild(div);
    setTimeout(() => dismissToast(div), 7000);
}
function dismissToast(el) {
    if (!el||el._removing) return; el._removing=true;
    el.style.animation='toastOut 0.35s ease forwards';
    setTimeout(()=>el.remove(),350);
}

// ── Modal Ulasan ───────────────────────────────────────────────
function openUlasan(id, kode) {
    document.getElementById('modalIdTiket').value = id;
    document.getElementById('modalLabel').textContent = `Tiket: ${kode}`;
    document.getElementById('modalUlasan').style.display = 'flex';
}
function closeUlasan() {
    document.getElementById('modalUlasan').style.display = 'none';
}
document.getElementById('modalUlasan').addEventListener('click', function(e) {
    if (e.target === this) closeUlasan();
});

// ── Form submit ────────────────────────────────────────────────
document.getElementById('formTiket')?.addEventListener('submit', function(e) {
    const judul = document.getElementById('judul').value.trim();
    const kat   = document.getElementById('id_kategori').value;
    const desk  = document.getElementById('deskripsi').value.trim();
    if (!judul || !kat || !desk) { e.preventDefault(); return; }
    const btn = document.getElementById('btnKirim');
    btn.disabled = true;
    document.getElementById('btnText').textContent = 'Memproses...';
    btn.insertAdjacentHTML('afterbegin','<i class="fas fa-spinner fa-spin"></i>');
});
</script>
</body>
</html>
