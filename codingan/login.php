<?php
// ============================================================
// login.php - Halaman Login
// IT Helpdesk & Ticketing System
// ============================================================
session_start();
require_once 'koneksi.php';

$error = '';

if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'Admin':    header('Location: dashboard_admin.php');    exit;
        case 'Teknisi':  header('Location: dashboard_teknisi.php');  exit;
        case 'Karyawan': header('Location: dashboard_karyawan.php'); exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email        = trim($_POST['email']    ?? '');
    $password     = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Email dan password tidak boleh kosong.';
    } else {
        $password_md5 = md5($password);
        $stmt = $koneksi->prepare("SELECT id_user, nama, role FROM Pengguna WHERE email = ? AND password = ? LIMIT 1");
        $stmt->bind_param('ss', $email, $password_md5);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $_SESSION['user_id']  = $user['id_user'];
            $_SESSION['nama']     = $user['nama'];
            $_SESSION['role']     = $user['role'];
            $_SESSION['email']    = $email;
            $_SESSION['login_at'] = date('Y-m-d H:i:s');

            switch ($user['role']) {
                case 'Admin':    header('Location: dashboard_admin.php');    exit;
                case 'Teknisi':  header('Location: dashboard_teknisi.php');  exit;
                case 'Karyawan': header('Location: dashboard_karyawan.php'); exit;
                default:
                    session_destroy();
                    $error = 'Role tidak dikenali. Hubungi administrator.';
            }
        } else {
            $error = 'Email atau password salah. Silakan coba lagi.';
        }
        $stmt->close();
    }
}

$koneksi->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — IT Helpdesk & Ticketing System</title>
    <meta name="description" content="Portal login Sistem IT Helpdesk. Masuk sebagai Karyawan, Teknisi IT, atau Admin IT.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        body { background: var(--bg-base); }

        .login-page {
            min-height: 100vh;
            display: flex;
        }

        /* ── Left Panel ── */
        .login-left {
            width: 440px;
            flex-shrink: 0;
            background: var(--login-panel-bg);
            border-right: 1px solid var(--login-panel-border);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px 40px;
            position: relative;
            overflow: hidden;
        }

        /* decorative mesh */
        .login-left::before {
            content: '';
            position: absolute;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(99,102,241,0.08) 0%, transparent 70%);
            top: -100px; left: -100px;
            pointer-events: none;
        }
        .login-left::after {
            content: '';
            position: absolute;
            width: 350px; height: 350px;
            background: radial-gradient(circle, rgba(139,92,246,0.06) 0%, transparent 70%);
            bottom: -60px; right: -60px;
            pointer-events: none;
        }

        .brand-logo {
            width: 48px; height: 48px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; color: #fff;
            box-shadow: 0 8px 24px rgba(99,102,241,0.35);
            margin-bottom: 20px;
        }
        .brand-title {
            font-size: 26px; font-weight: 800;
            color: var(--text-primary);
            line-height: 1.2;
            margin-bottom: 8px;
        }
        .brand-desc {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.7;
            max-width: 300px;
        }

        .feature-list { margin-top: 48px; display: flex; flex-direction: column; gap: 16px; }
        .feature-item { display: flex; align-items: flex-start; gap: 12px; }
        .feature-icon {
            width: 32px; height: 32px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; flex-shrink: 0; margin-top: 1px;
        }
        .feature-icon.indigo { background: rgba(99,102,241,0.12); color: #818cf8; }
        .feature-icon.teal   { background: rgba(20,184,166,0.12);  color: #2dd4bf; }
        .feature-icon.blue   { background: rgba(59,130,246,0.12);  color: #60a5fa; }
        .feature-text-title  { font-size: 13px; font-weight: 600; color: var(--text-primary); }
        .feature-text-desc   { font-size: 12px; color: var(--text-muted); margin-top: 1px; }

        .brand-footer { font-size: 11px; color: var(--text-faint); }

        /* ── Right Panel ── */
        .login-right {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 40px;
        }

        .login-form-wrap {
            width: 100%;
            max-width: 380px;
        }

        .login-form-title {
            font-size: 22px; font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 6px;
        }
        .login-form-sub {
            font-size: 13px; color: var(--text-muted);
            margin-bottom: 32px;
        }

        .form-group { margin-bottom: 16px; }

        .input-icon-wrap { position: relative; }
        .input-icon-wrap .icon-left {
            position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
            color: var(--text-muted); font-size: 13px; pointer-events: none;
        }
        .input-icon-wrap .icon-right {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            color: var(--text-muted); font-size: 13px; cursor: pointer;
            transition: color 0.15s;
        }
        .input-icon-wrap .icon-right:hover { color: var(--text-primary); }
        .input-icon-wrap .input-field { padding-left: 36px; }
        .input-icon-wrap .input-field.has-right { padding-right: 36px; }

        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: #fff;
            border: none;
            border-radius: var(--radius-md);
            padding: 11px 20px;
            font-size: 14px; font-weight: 600;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            transition: all 0.2s ease;
            margin-top: 24px;
            position: relative; overflow: hidden;
        }
        .btn-login:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(99,102,241,0.4); }
        .btn-login:active { transform: translateY(0); }

        /* Progress bar loader */
        .btn-login .progress-bar {
            position: absolute; bottom: 0; left: 0; height: 2px;
            background: rgba(255,255,255,0.5);
            width: 0; transition: width 0.3s linear;
        }

        /* Error alert */
        .alert-error {
            display: flex; align-items: flex-start; gap: 10px;
            background: rgba(239,68,68,0.08);
            border: 1px solid rgba(239,68,68,0.2);
            border-radius: var(--radius-md);
            padding: 12px 14px;
            font-size: 13px; color: #f87171;
            margin-bottom: 16px;
            animation: slideUp 0.3s ease;
        }
        .alert-error i { flex-shrink: 0; margin-top: 1px; }

        /* Demo accounts */
        .demo-divider {
            display: flex; align-items: center; gap: 12px;
            margin: 24px 0 16px;
        }
        .demo-divider-line { flex: 1; height: 1px; background: var(--border); }
        .demo-divider-text { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; white-space: nowrap; }

        .demo-accounts { display: flex; gap: 8px; }
        .demo-btn {
            flex: 1;
            background: var(--bg-elevated);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 9px 8px;
            cursor: pointer;
            transition: all 0.18s ease;
            text-align: center;
        }
        .demo-btn:hover { border-color: var(--border-hover); background: var(--bg-overlay); }
        .demo-btn-icon { font-size: 16px; margin-bottom: 4px; }
        .demo-btn-label { font-size: 11px; font-weight: 600; color: var(--text-secondary); display: block; }
        .demo-btn-role  { font-size: 10px; color: var(--text-muted); display: block; margin-top: 1px; }
        .demo-btn:hover .demo-btn-label { color: var(--text-primary); }
    </style>
    <script>
        // Apply saved theme BEFORE render to prevent flash
        (function() {
            const t = localStorage.getItem('helpdesk_theme') || 'dark';
            if (t === 'light') document.documentElement.setAttribute('data-theme', 'light');
        })();

        function toggleTheme() {
            const html = document.documentElement;
            const isLight = html.getAttribute('data-theme') === 'light';
            // Add transition class for smooth animation
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
<div class="login-page">

    <!-- ══ LEFT PANEL ══ -->
    <div class="login-left">
        <div style="position: relative; z-index: 1;">
            <div class="brand-logo"><i class="fas fa-headset"></i></div>
            <div class="brand-title">IT Helpdesk<br>&amp; Ticketing</div>
            <p class="brand-desc">Platform terpadu untuk pengelolaan kendala IT, dari pelaporan hingga resolusi.</p>

            <div class="feature-list">
                <div class="feature-item">
                    <div class="feature-icon indigo"><i class="fas fa-ticket"></i></div>
                    <div>
                        <div class="feature-text-title">Manajemen Tiket</div>
                        <div class="feature-text-desc">Buat, pantau, dan selesaikan tiket kendala IT dengan mudah</div>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon teal"><i class="fas fa-paper-plane"></i></div>
                    <div>
                        <div class="feature-text-title">Disposisi Otomatis</div>
                        <div class="feature-text-desc">Admin dapat mendisposisikan tiket ke teknisi yang tepat</div>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon blue"><i class="fas fa-chart-pie"></i></div>
                    <div>
                        <div class="feature-text-title">Analitik Real-time</div>
                        <div class="feature-text-desc">Dashboard statistik dan laporan kinerja layanan IT</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="brand-footer" style="position: relative; z-index: 1;">© 2025 IT Helpdesk System. All rights reserved.</div>
    </div>

    <!-- ══ RIGHT PANEL ══ -->
    <div class="login-right" style="position:relative;">
        <button class="theme-toggle" onclick="toggleTheme()" title="Toggle Tema" style="position:absolute; top:24px; right:24px;"><i class="fas fa-sun" id="themeIcon"></i></button>
        <div class="login-form-wrap anim-slide">

            <h1 class="login-form-title">Selamat Datang</h1>
            <p class="login-form-sub">Masuk ke portal Anda untuk melanjutkan</p>

            <?php if ($error): ?>
            <div class="alert-error" id="alertError">
                <i class="fas fa-circle-exclamation"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" action="login.php" id="loginForm" novalidate>

                <div class="form-group">
                    <label class="input-label" for="email">Alamat Email</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-envelope icon-left"></i>
                        <input type="email" id="email" name="email"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            placeholder="nama@perusahaan.com"
                            class="input-field"
                            autocomplete="email" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="input-label" for="password">Password</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-lock icon-left"></i>
                        <input type="password" id="password" name="password"
                            placeholder="Masukkan password Anda"
                            class="input-field has-right"
                            autocomplete="current-password" required>
                        <span class="icon-right" onclick="togglePwd()" title="Tampilkan/sembunyikan password">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </span>
                    </div>
                </div>

                <button type="submit" class="btn-login" id="btnLogin">
                    <i class="fas fa-right-to-bracket"></i>
                    <span id="btnText">Masuk ke Sistem</span>
                    <div class="progress-bar" id="progressBar"></div>
                </button>
            </form>

            <div class="demo-divider">
                <div class="demo-divider-line"></div>
                <span class="demo-divider-text">Akun Demo</span>
                <div class="demo-divider-line"></div>
            </div>

            <div class="demo-accounts">
                <button class="demo-btn" onclick="fillCredential('admin@helpdesk.com','admin123')" title="Login sebagai Admin">
                    <div class="demo-btn-icon" style="color:#818cf8;">&#9673;</div>
                    <span class="demo-btn-label">Admin IT</span>
                    <span class="demo-btn-role">admin123</span>
                </button>
                <button class="demo-btn" onclick="fillCredential('nesya@helpdesk.com','teknisi123')" title="Login sebagai Teknisi">
                    <div class="demo-btn-icon" style="color:#2dd4bf;">&#9673;</div>
                    <span class="demo-btn-label">Nesya</span>
                    <span class="demo-btn-role">teknisi123</span>
                </button>
                <button class="demo-btn" onclick="fillCredential('risky@helpdesk.com','karyawan123')" title="Login sebagai Karyawan">
                    <div class="demo-btn-icon" style="color:#60a5fa;">&#9673;</div>
                    <span class="demo-btn-label">Risky</span>
                    <span class="demo-btn-role">karyawan123</span>
                </button>
            </div>

        </div>
    </div>
</div>

<script>
    function togglePwd() {
        const input = document.getElementById('password');
        const icon  = document.getElementById('eyeIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye','fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash','fa-eye');
        }
    }

    function fillCredential(email, pass) {
        document.getElementById('email').value    = email;
        document.getElementById('password').value = pass;
        document.getElementById('email').focus();
    }

    document.getElementById('loginForm').addEventListener('submit', function() {
        const btn  = document.getElementById('btnLogin');
        const text = document.getElementById('btnText');
        const bar  = document.getElementById('progressBar');
        btn.disabled = true;
        text.textContent = 'Memverifikasi...';
        bar.style.width = '85%';
    });

    // Auto dismiss error after 6s
    const alertErr = document.getElementById('alertError');
    if (alertErr) {
        setTimeout(() => {
            alertErr.style.transition = 'opacity 0.4s';
            alertErr.style.opacity = '0';
            setTimeout(() => alertErr.remove(), 400);
        }, 6000);
    }
</script>
</body>
</html>
