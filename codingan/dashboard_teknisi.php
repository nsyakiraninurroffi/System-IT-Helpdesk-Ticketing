<?php
// ============================================================
// dashboard_teknisi.php - Panel Teknisi IT
// IT Helpdesk & Ticketing System
// ============================================================
session_start();
require_once 'koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Teknisi') {
    header('Location: login.php'); exit;
}

$id_user = (int) $_SESSION['user_id'];
$nama    = htmlspecialchars($_SESSION['nama']);
$alert   = ['type' => '', 'message' => ''];
$tab     = $_GET['tab'] ?? 'tugas';

// ── PROSES UPDATE STATUS ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $id_tiket  = (int) ($_POST['id_tiket'] ?? 0);
    $sts_baru  = trim($_POST['status'] ?? '');
    $catatan   = trim($_POST['catatan_perbaikan'] ?? '');
    $valid_sts = ['In Progress', 'Resolved'];

    if ($id_tiket > 0 && in_array($sts_baru, $valid_sts)) {
        // Cek apakah tiket ini milik teknisi yang bersangkutan
        $cek = $koneksi->prepare("SELECT status_tiket FROM Tiket WHERE id_tiket = ? AND id_teknisi = ?");
        $cek->bind_param('ii', $id_tiket, $id_user);
        $cek->execute();
        $res = $cek->get_result();

        if ($res->num_rows === 1) {
            $t = $res->fetch_assoc();
            if ($t['status_tiket'] !== 'Resolved') {
                $upd = $koneksi->prepare("UPDATE Tiket SET status_tiket = ? WHERE id_tiket = ?");
                $upd->bind_param('si', $sts_baru, $id_tiket);

                if ($upd->execute()) {
                    // Tambah riwayat
                    $ins_r = $koneksi->prepare("INSERT INTO Riwayat_Penanganan (id_tiket, id_teknisi, status_update, catatan_perbaikan, tanggal_update) VALUES (?, ?, ?, ?, NOW())");
                    $ins_r->bind_param('iiss', $id_tiket, $id_user, $sts_baru, $catatan);
                    $ins_r->execute();
                    $ins_r->close();

                    $alert = ['type'=>'success','message'=>"Status tiket berhasil diperbarui menjadi <strong>{$sts_baru}</strong>."];
                } else {
                    $alert = ['type'=>'error','message'=>'Gagal memperbarui status.'];
                }
                $upd->close();
            } else {
                $alert = ['type'=>'error','message'=>'Tiket sudah terselesaikan (Resolved).'];
            }
        } else {
            $alert = ['type'=>'error','message'=>'Tiket tidak ditemukan atau bukan wewenang Anda.'];
        }
        $cek->close();
    }
    $tab = 'tugas';
}

// ── AMBIL DATA ────────────────────────────────────────────────
$stat_q = $koneksi->prepare("SELECT COUNT(*) AS total, SUM(status_tiket='In Progress') AS inprogress_count, SUM(status_tiket='Resolved') AS resolved_count FROM Tiket WHERE id_teknisi=?");
$stat_q->bind_param('i', $id_user); $stat_q->execute();
$stats = $stat_q->get_result()->fetch_assoc();
$stat_q->close();

// Tiket yang sedang ditangani (In Progress)
$tugas_q = $koneksi->prepare("
    SELECT t.id_tiket, t.kode_tiket, t.judul_masalah, t.deskripsi, t.tingkat_prioritas, t.tanggal_dibuat,
           k.nama_kategori, p.nama AS nama_pelapor, a.nama_aset
    FROM Tiket t
    LEFT JOIN Kategori_Masalah k ON t.id_kategori=k.id_kategori
    LEFT JOIN Pengguna p ON t.id_pelapor=p.id_user
    LEFT JOIN Aset_Perangkat a ON t.id_aset=a.id_aset
    WHERE t.id_teknisi=? AND t.status_tiket='In Progress'
    ORDER BY FIELD(t.tingkat_prioritas,'Urgent','High','Medium','Low'), t.tanggal_dibuat ASC");
$tugas_q->bind_param('i', $id_user); $tugas_q->execute();
$tugas_list = $tugas_q->get_result();

// Tiket yang sudah selesai (Resolved)
$selesai_q = $koneksi->prepare("
    SELECT t.id_tiket, t.kode_tiket, t.judul_masalah, t.tingkat_prioritas, t.tanggal_dibuat,
           k.nama_kategori, p.nama AS nama_pelapor
    FROM Tiket t
    LEFT JOIN Kategori_Masalah k ON t.id_kategori=k.id_kategori
    LEFT JOIN Pengguna p ON t.id_pelapor=p.id_user
    WHERE t.id_teknisi=? AND t.status_tiket='Resolved'
    ORDER BY t.tanggal_dibuat DESC");
$selesai_q->bind_param('i', $id_user); $selesai_q->execute();
$selesai_list = $selesai_q->get_result();
$koneksi->close();

$page_info = [
    'tugas'   => ['Tugas Aktif',      'Daftar tiket kendala yang harus Anda tangani saat ini.'],
    'selesai' => ['Tiket Selesai',    'Riwayat tiket kendala yang telah berhasil Anda selesaikan.'],
];
$pi = $page_info[$tab] ?? $page_info['tugas'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Teknisi — IT Helpdesk System</title>
    <meta name="description" content="Portal Teknisi IT untuk mengelola dan memperbarui status tiket.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Teknisi accent = Teal */
        .nav-link.active { background: rgba(20,184,166,0.1); color: var(--teal-300); }
        .nav-link.active i { color: var(--teal-400); }
        .user-badge.teknisi { background: rgba(20,184,166,0.12); color: var(--teal-400); }
        .brand-logo-tek { background: linear-gradient(135deg, #14b8a6, #0ea5e9); }
        
        [data-theme="light"] .nav-link.active { color: var(--teal-500); }
        [data-theme="light"] .nav-link.active i { color: var(--teal-500); }
        [data-theme="light"] .user-badge.teknisi { color: var(--teal-500); }
        [data-theme="light"] .kpi-icon.teal { color: var(--teal-500); }
        
        .kpi-icon.teal  { background: rgba(20,184,166,0.1);  color: var(--teal-400); }
        .kpi-icon.amber { background: rgba(245,158,11,0.1);  color: #fbbf24; }
        .kpi-icon.green { background: rgba(16,185,129,0.1);  color: #34d399; }

        .tiket-card-kanban { border-left: none; padding-left: 0; display: flex; }
        
        .timeline { border-left: 2px solid var(--border); margin-left: 10px; padding-left: 20px; position: relative; }
        .timeline-item { position: relative; margin-bottom: 20px; }
        .timeline-item::before { content: ''; position: absolute; left: -27px; top: 2px; width: 12px; height: 12px; border-radius: 50%; background: var(--teal-500); border: 3px solid var(--bg-surface); }
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
        <div class="sidebar-brand-icon brand-logo-tek"><i class="fas fa-screwdriver-wrench" style="color:#fff;font-size:15px;"></i></div>
        <div>
            <div class="sidebar-brand-name">IT Helpdesk</div>
            <div class="sidebar-brand-sub">Teknisi Panel</div>
        </div>
    </div>

    <div class="sidebar-user">
        <div class="sidebar-user-inner">
            <div class="user-avatar" style="background:linear-gradient(135deg,#14b8a6,#06b6d4);">
                <?= strtoupper(substr($nama,0,1)) ?>
            </div>
            <div style="overflow:hidden;">
                <div class="user-name truncate"><?= $nama ?></div>
                <span class="user-badge teknisi">Teknisi IT</span>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-section-label">Menu Pekerjaan</div>
        <a href="?tab=tugas" class="nav-link <?= $tab==='tugas'?'active active-teal':'' ?>">
            <i class="fas fa-list-check"></i> Tugas Aktif
            <?php if (($stats['inprogress_count']??0) > 0): ?>
            <span class="nav-badge" style="background:var(--teal-500);"><?= $stats['inprogress_count'] ?></span>
            <?php endif; ?>
        </a>
        <a href="?tab=selesai" class="nav-link <?= $tab==='selesai'?'active active-teal':'' ?>">
            <i class="fas fa-check-double"></i> Tiket Selesai
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

    <!-- KPI Cards -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:24px;" class="anim-stagger">
        <?php
        $kpis = [
            ['Total Ditugaskan', $stats['total']??0,            'fa-ticket',       'teal'],
            ['In Progress',      $stats['inprogress_count']??0, 'fa-gear',         'amber'],
            ['Terselesaikan',    $stats['resolved_count']??0,   'fa-check-double', 'green'],
        ];
        foreach ($kpis as $k): ?>
        <div class="kpi-card">
            <div class="kpi-icon <?= $k[3] ?>"><i class="fas <?= $k[2] ?>"></i></div>
            <div class="kpi-label"><?= $k[0] ?></div>
            <div class="kpi-value"><?= $k[1] ?></div>
        </div>
        <?php endforeach; ?>
    </div>


    <!-- ════ TAB: TUGAS AKTIF ════ -->
    <?php if ($tab === 'tugas'): ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;" class="anim-stagger">
        <?php if ($tugas_list->num_rows === 0): ?>
        <div class="card" style="grid-column:span 2;padding:64px;text-align:center;">
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-mug-hot"></i></div>
                <div class="empty-title">Kerja Bagus!</div>
                <div class="empty-desc">Tidak ada tiket aktif yang perlu Anda tangani saat ini. Silakan bersantai atau tunggu disposisi baru dari Admin.</div>
            </div>
        </div>
        <?php else: ?>
        <?php while ($tk = $tugas_list->fetch_assoc()):
            $p_map = ['Urgent'=>'badge-urgent','High'=>'badge-high','Medium'=>'badge-medium','Low'=>'badge-low'];
            $c_map = ['Urgent'=>'#ef4444','High'=>'#f97316','Medium'=>'#eab308','Low'=>'#22c55e'];
        ?>
        <div class="tiket-card-kanban">
            <div class="tiket-card-stripe" style="background:<?= $c_map[$tk['tingkat_prioritas']] ?? '#eab308' ?>;"></div>
            <div class="tiket-card-body flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="ticket-code" style="color:var(--teal-400);background:rgba(20,184,166,0.1);"><?= htmlspecialchars($tk['kode_tiket']) ?></span>
                        <span class="badge <?= $p_map[$tk['tingkat_prioritas']] ?? 'badge-medium' ?>"><?= $tk['tingkat_prioritas'] ?></span>
                    </div>
                    <div style="font-size:15px;font-weight:600;color:var(--text-primary);margin-bottom:6px;line-height:1.4;">
                        <?= htmlspecialchars($tk['judul_masalah']) ?>
                    </div>
                    <p style="font-size:12px;color:var(--text-secondary);line-height:1.6;margin-bottom:12px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">
                        <?= nl2br(htmlspecialchars($tk['deskripsi'])) ?>
                    </p>
                    <div class="flex flex-col gap-2 mb-4">
                        <div class="flex items-center gap-2" style="font-size:11px;color:var(--text-muted);">
                            <i class="fas fa-user w-4"></i> <span>Pelapor: <strong><?= htmlspecialchars($tk['nama_pelapor']) ?></strong></span>
                        </div>
                        <div class="flex items-center gap-2" style="font-size:11px;color:var(--text-muted);">
                            <i class="fas fa-tag w-4"></i> <span>Kategori: <?= htmlspecialchars($tk['nama_kategori'] ?? '—') ?></span>
                        </div>
                        <?php if ($tk['nama_aset']): ?>
                        <div class="flex items-center gap-2" style="font-size:11px;color:var(--text-muted);">
                            <i class="fas fa-laptop w-4"></i> <span>Aset: <?= htmlspecialchars($tk['nama_aset']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex items-center justify-between pt-3" style="border-top:1px solid var(--border);">
                    <span style="font-size:11px;color:var(--text-faint);"><i class="fas fa-clock mr-1"></i> <?= date('d M, H:i', strtotime($tk['tanggal_dibuat'])) ?></span>
                    <button onclick="openUpdateModal(<?= $tk['id_tiket'] ?>, '<?= htmlspecialchars($tk['kode_tiket']) ?>', '<?= htmlspecialchars(addslashes($tk['judul_masalah'])) ?>')" class="btn btn-teal btn-sm">
                        <i class="fas fa-pen-to-square"></i> Update Status
                    </button>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
        <?php endif; ?>
    </div>


    <!-- ════ TAB: TIKET SELESAI ════ -->
    <?php elseif ($tab === 'selesai'): ?>
    <div class="card anim-slide" style="overflow:hidden;">
        <div class="overflow-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Kode</th><th>Judul Masalah</th><th>Kategori</th>
                        <th>Pelapor</th><th>Prioritas</th><th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($selesai_list->num_rows === 0): ?>
                <tr><td colspan="6" style="text-align:center;padding:48px;">
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-box-open"></i></div>
                        <div class="empty-title">Belum Ada Tiket Selesai</div>
                        <div class="empty-desc">Selesaikan tugas aktif Anda untuk melihat riwayatnya di sini.</div>
                    </div>
                </td></tr>
                <?php else: ?>
                <?php while ($tk = $selesai_list->fetch_assoc()):
                    $pm = ['Urgent'=>'badge-urgent','High'=>'badge-high','Medium'=>'badge-medium','Low'=>'badge-low'];
                ?>
                <tr>
                    <td><span class="ticket-code" style="color:var(--teal-400);background:rgba(20,184,166,0.1);"><?= htmlspecialchars($tk['kode_tiket']) ?></span></td>
                    <td><span style="font-size:13px;font-weight:500;color:var(--text-primary);"><?= htmlspecialchars($tk['judul_masalah']) ?></span></td>
                    <td><?= htmlspecialchars($tk['nama_kategori'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($tk['nama_pelapor'] ?? '—') ?></td>
                    <td><span class="badge <?= $pm[$tk['tingkat_prioritas']] ?? '' ?>"><?= $tk['tingkat_prioritas'] ?></span></td>
                    <td><span style="font-size:11px;"><?= date('d M Y', strtotime($tk['tanggal_dibuat'])) ?></span></td>
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

<!-- ══════════ MODAL UPDATE STATUS ══════════ -->
<div id="modalUpdate" style="display:none;" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <div>
                <div class="modal-title">Update Status Penanganan</div>
                <div style="font-size:12px;color:var(--text-muted);margin-top:2px;" id="modalLabel"></div>
            </div>
            <button class="modal-close" onclick="closeUpdateModal()"><i class="fas fa-xmark"></i></button>
        </div>

        <form method="POST" action="dashboard_teknisi.php" id="formUpdate">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="id_tiket" id="modalIdTiket">

            <div style="margin-bottom:16px;">
                <label class="input-label" style="margin-bottom:10px;">Ubah Status Ke <span style="color:#f87171;">*</span></label>
                <div style="display:flex;gap:12px;">
                    <div style="flex:1;">
                        <input type="radio" name="status" id="sts_prog" value="In Progress" required style="display:none;" checked>
                        <label for="sts_prog" style="display:flex;align-items:center;justify-content:center;gap:8px;padding:10px;border:1px solid var(--border);border-radius:var(--radius-md);cursor:pointer;font-size:13px;font-weight:500;color:var(--text-secondary);transition:all 0.15s;" onmouseover="this.style.background='var(--bg-elevated)'" onmouseout="if(!document.getElementById('sts_prog').checked)this.style.background='transparent'">
                            <div style="width:14px;height:14px;border-radius:50%;border:2px solid #f59e0b;" class="radio-ind"></div> In Progress
                        </label>
                    </div>
                    <div style="flex:1;">
                        <input type="radio" name="status" id="sts_res" value="Resolved" required style="display:none;">
                        <label for="sts_res" style="display:flex;align-items:center;justify-content:center;gap:8px;padding:10px;border:1px solid var(--border);border-radius:var(--radius-md);cursor:pointer;font-size:13px;font-weight:500;color:var(--text-secondary);transition:all 0.15s;" onmouseover="this.style.background='var(--bg-elevated)'" onmouseout="if(!document.getElementById('sts_res').checked)this.style.background='transparent'">
                            <div style="width:14px;height:14px;border-radius:50%;border:2px solid #10b981;" class="radio-ind"></div> Resolved
                        </label>
                    </div>
                </div>
            </div>

            <div style="margin-bottom:20px;">
                <label class="input-label">Catatan Perbaikan <span style="color:#f87171;">*</span></label>
                <textarea name="catatan_perbaikan" rows="4" class="input-field" required style="resize:none;"
                    placeholder="Tuliskan tindakan yang dilakukan (misal: install ulang OS, ganti RAM, dll)"></textarea>
                <div class="input-hint">Catatan ini akan disimpan dalam riwayat penanganan tiket.</div>
            </div>

            <button type="submit" class="btn btn-teal btn-full" id="btnSimpanUpdate">
                <i class="fas fa-save"></i> Simpan Update
            </button>
        </form>
    </div>
</div>

<style>
/* CSS khusus untuk modal radio custom */
input[type="radio"]:checked + label { border-color: var(--teal-400) !important; background: rgba(20,184,166,0.08) !important; color: var(--text-primary) !important; }
input[type="radio"]:checked + label .radio-ind { background: var(--teal-400); border-color: var(--teal-400) !important; }
</style>

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
    setTimeout(() => dismissToast(div), 6000);
}
function dismissToast(el) {
    if (!el||el._removing) return; el._removing=true;
    el.style.animation='toastOut 0.35s ease forwards';
    setTimeout(()=>el.remove(),350);
}

// ── Modal Update ───────────────────────────────────────────────
function openUpdateModal(id, kode, judul) {
    document.getElementById('modalIdTiket').value = id;
    document.getElementById('modalLabel').innerHTML = `<strong>${kode}</strong> — ${judul}`;
    document.getElementById('modalUpdate').style.display = 'flex';
}
function closeUpdateModal() {
    document.getElementById('modalUpdate').style.display = 'none';
    document.getElementById('formUpdate').reset();
}
document.getElementById('modalUpdate').addEventListener('click', function(e) {
    if (e.target === this) closeUpdateModal();
});

document.getElementById('formUpdate')?.addEventListener('submit', function() {
    const btn = document.getElementById('btnSimpanUpdate');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
});
</script>
</body>
</html>
