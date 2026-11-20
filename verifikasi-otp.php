<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Verifikasi email (OTP) memakai layanan terpusat di includes/otp.php:
// kode berlaku 10 menit, hanya sekali pakai, kirim ulang membatalkan kode lama,
// dan batas percobaan disimpan di database (bukan sesi).
$otpType = 'register';

$state = isset($_GET['state']) ? (string) $_GET['state'] : 'default'; // 'default', 'salah', 'berhasil'
$email = strtolower(trim((string) ($_GET['email'] ?? ($_SESSION['pending_otp']['email'] ?? ''))));
$errorMessage = '';

// Halaman ini tidak lagi menyediakan pemilih "preview state" untuk umum;
// state hanya ditentukan oleh hasil verifikasi dan parameter ?state= dari redirect.
if (!in_array($state, ['default', 'salah', 'berhasil', 'kirim'], true)) {
    $state = 'default';
}

// ---------------------------------------------------------------- Kirim ulang
if ($state === 'kirim') {
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash_message('error', 'Alamat email tidak valid. Silakan daftar ulang.');
        header('Location: daftar.php');
        exit;
    }

    $wait = sip_otp_resend_blocked($email, $otpType);
    if ($wait > 0) {
        set_flash_message('warning', "Tunggu {$wait} detik lagi sebelum meminta kode baru.");
        header('Location: verifikasi-otp.php?email=' . urlencode($email));
        exit;
    }

    $issued = sip_otp_issue($email, $otpType);
    $user = db_fetch_one("SELECT nama FROM users WHERE LOWER(email) = ? LIMIT 1", [$email]);
    $sent = sip_otp_send($email, $issued['code'], $otpType, $user['nama'] ?? '');
    $_SESSION['pending_otp'] = ['email' => $email, 'code' => $issued['code']];

    if ($sent['ok']) {
        set_flash_message('success', "Kode verifikasi baru sudah dikirim ke {$email}.");
    } elseif (!sip_mail_ready()) {
        set_flash_message('warning', 'Pengiriman email belum aktif di server ini (mode demo). Hubungi petugas keamanan kampus.');
    } else {
        set_flash_message('error', 'Gagal mengirim email verifikasi. Coba lagi beberapa saat lagi atau hubungi petugas.');
    }

    header('Location: verifikasi-otp.php?email=' . urlencode($email));
    exit;
}

// ------------------------------------------------------------------ Verifikasi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $digits = $_POST['digits'] ?? [];
    $enteredCode = '';
    if (is_array($digits)) {
        $enteredCode = implode('', $digits);
    } elseif (isset($_POST['otp_code'])) {
        $enteredCode = trim((string) $_POST['otp_code']);
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $state = 'salah';
        $errorMessage = 'Sesi verifikasi tidak ditemukan. Silakan masuk lalu minta kode baru.';
    } else {
        $res = sip_otp_verify($email, $enteredCode, $otpType);
        if ($res['ok']) {
            sip_mark_email_verified($email);
            $user = db_fetch_one("SELECT * FROM users WHERE LOWER(email) = ? LIMIT 1", [$email]);

            // Login baru diberikan setelah email terbukti dikuasai pendaftar.
            if ($user && (!isset($user['is_active']) || (int) $user['is_active'] === 1)) {
                if (session_status() === PHP_SESSION_ACTIVE) {
                    @session_regenerate_id(true);
                }
                unset($user['password']);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user'] = $user;
            }

            unset($_SESSION['pending_otp'], $_SESSION['otp_attempts']);
            $state = 'berhasil';
        } else {
            $state = 'salah';
            $errorMessage = $res['error'];
        }
    }
}

$titles = [
    'default' => 'Verifikasi Email - SIP-Barang Kampus',
    'salah' => 'Kode Verifikasi Salah - SIP-Barang Kampus',
    'berhasil' => 'Email Terverifikasi - SIP-Barang Kampus'
];
$page_title = $titles[$state] ?? $titles['default'];
$current_page = 'otp';
require_once __DIR__ . '/includes/header.php';
$resendWait = ($email !== '') ? sip_otp_resend_blocked($email, $otpType) : 0;
$flash = get_flash_message();
?>

<!-- Minimal Navbar for OTP screen as per Figma (ID: 1084, 1113, 1145) -->
<nav class="h-[72px] bg-white border-b border-slate-200">
    <div class="max-w-[1200px] mx-auto h-full px-4 sm:px-6 flex items-center justify-between">
        <a href="index.php" class="flex items-center gap-2 text-primary font-bold text-lg">
            <i data-lucide="sparkles" class="w-5 h-5 text-primary"></i>
            <span>SIP-Barang Kampus</span>
        </a>
        <a href="masuk.php" class="btn-secondary h-10 px-4 text-sm font-medium rounded-btn flex items-center gap-1.5">
            <i data-lucide="log-out" class="w-4 h-4"></i>
            <span>Keluar</span>
        </a>
    </div>
</nav>

<main class="flex-1 py-14 flex items-center justify-center px-4">
    
    <?php if ($state === 'berhasil'): ?>
        <!-- ================= STATE 3: VERIFIKASI OTP BERHASIL (Figma ID: 1145) ================= -->
        <div class="card-default w-full max-w-[480px] p-8 sm:p-10 rounded-card bg-white border border-slate-200 shadow-sm text-center">
            <!-- Icon Circle Green -->
            <div class="w-14 h-14 rounded-full bg-emerald-100 text-emerald-600 mx-auto flex items-center justify-center mb-6">
                <i data-lucide="check-circle" class="w-8 h-8"></i>
            </div>

            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                Email Terverifikasi
            </h1>
            <p class="mt-3 text-xs sm:text-sm text-slate-500 leading-relaxed max-w-sm mx-auto">
                Akunmu sudah aktif. Sekarang kamu dapat melaporkan dan mencari barang hilang di seluruh lingkungan kampus.
            </p>

            <div class="mt-8">
                <a href="index.php" class="w-full btn-primary h-12 rounded-btn text-base font-semibold shadow-sm flex items-center justify-center gap-2">
                    <span>Lanjut ke Beranda</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>
        </div>

    <?php elseif ($state === 'salah'): ?>
        <!-- ================= STATE 2: VERIFIKASI OTP SALAH (Figma ID: 1113) ================= -->
        <div class="card-default w-full max-w-[480px] p-8 sm:p-10 rounded-card bg-white border border-slate-200 shadow-sm text-center">
            <!-- Icon Circle Red -->
            <div class="w-14 h-14 rounded-full bg-red-100 text-red-600 mx-auto flex items-center justify-center mb-6">
                <i data-lucide="alert-triangle" class="w-7 h-7"></i>
            </div>

            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                Verifikasi Email Kamu
            </h1>
            <p class="mt-2 text-xs sm:text-sm text-slate-500 leading-relaxed max-w-sm mx-auto">
                Kami sudah mengirim kode 6 digit ke<br>
                <strong class="text-slate-800 font-semibold"><?= htmlspecialchars($email !== '' ? $email : '(email tidak diketahui)') ?></strong>. Masukkan kode di bawah ini.
            </p>

            <!-- 6 Digit Error Inputs -->
            <form action="verifikasi-otp.php?email=<?= urlencode($email) ?>" method="POST" class="mt-6 space-y-4">
                <?= csrf_field() ?>
                <div class="flex items-center justify-center gap-2 sm:gap-3">
                    <input type="text" name="digits[]" maxlength="1" inputmode="numeric" autocomplete="one-time-code" class="otp-input otp-digit error" autofocus>
                    <input type="text" name="digits[]" maxlength="1" inputmode="numeric" class="otp-input otp-digit error">
                    <input type="text" name="digits[]" maxlength="1" inputmode="numeric" class="otp-input otp-digit error">
                    <input type="text" name="digits[]" maxlength="1" inputmode="numeric" class="otp-input otp-digit error">
                    <input type="text" name="digits[]" maxlength="1" inputmode="numeric" class="otp-input otp-digit error">
                    <input type="text" name="digits[]" maxlength="1" inputmode="numeric" class="otp-input otp-digit error">
                </div>

                <!-- Error Message Alert -->
                <div class="flex items-center justify-center gap-1.5 text-xs text-red-600 font-medium pt-1">
                    <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
                    <span><?= htmlspecialchars($errorMessage !== '' ? $errorMessage : 'Kode salah atau sudah kedaluwarsa. Periksa kembali email kamu atau minta kode baru.') ?></span>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full btn-primary h-12 rounded-btn text-base font-semibold shadow-sm flex items-center justify-center gap-2">
                        <span>Verifikasi Sekarang</span>
                    </button>
                </div>

                <div class="pt-2 text-xs text-slate-500">
                    Tidak menerima kode? 
                    <?php if ($resendWait > 0): ?>
                        <span class="font-medium">Tunggu <strong><?= (int) $resendWait ?></strong> detik untuk minta kode baru.</span>
                    <?php else: ?>
                        <a href="verifikasi-otp.php?state=kirim&email=<?= urlencode($email) ?>" class="text-primary font-bold hover:underline">
                            Kirim Ulang Kode
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

    <?php else: ?>
        <!-- ================= STATE 1: VERIFIKASI OTP NORMAL (Figma ID: 1084) ================= -->
        <div class="card-default w-full max-w-[480px] p-8 sm:p-10 rounded-card bg-white border border-slate-200 shadow-sm text-center">
            <!-- Icon Circle Blue -->
            <div class="w-14 h-14 rounded-full bg-blue-100 text-primary mx-auto flex items-center justify-center mb-6">
                <i data-lucide="mail-check" class="w-7 h-7"></i>
            </div>

            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                Verifikasi Email Kamu
            </h1>
            <p class="mt-2 text-xs sm:text-sm text-slate-500 leading-relaxed max-w-sm mx-auto">
                Kami sudah mengirim kode 6 digit ke<br>
                <strong class="text-slate-800 font-semibold"><?= htmlspecialchars($email !== '' ? $email : '(email tidak diketahui)') ?></strong>. Masukkan kode di bawah ini.
            </p>

            <div class="mt-3 p-2 bg-blue-50 text-blue-700 text-xs rounded-btn">
                Kode berlaku <strong>10 menit</strong> dan hanya bisa dipakai sekali.
            </div>

            <?php if ($flash): ?>
                <div class="mt-3 p-2.5 rounded-btn text-xs text-left flex items-start gap-2 <?= $flash['type'] === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : ($flash['type'] === 'error' ? 'bg-red-50 border border-red-200 text-red-800' : 'bg-amber-50 border border-amber-200 text-amber-800') ?>">
                    <span><?= htmlspecialchars((string) $flash['message']) ?></span>
                </div>
            <?php endif; ?>

            <!-- 6 Digit Inputs -->
            <form id="otpForm" action="verifikasi-otp.php?email=<?= urlencode($email) ?>" method="POST" class="mt-6 space-y-5">
                <?= csrf_field() ?>
                <div class="flex items-center justify-center gap-2 sm:gap-3">
                    <input type="text" name="digits[]" maxlength="1" inputmode="numeric" autocomplete="one-time-code" class="otp-input otp-digit" autofocus>
                    <input type="text" name="digits[]" maxlength="1" inputmode="numeric" class="otp-input otp-digit">
                    <input type="text" name="digits[]" maxlength="1" inputmode="numeric" class="otp-input otp-digit">
                    <input type="text" name="digits[]" maxlength="1" inputmode="numeric" class="otp-input otp-digit">
                    <input type="text" name="digits[]" maxlength="1" inputmode="numeric" class="otp-input otp-digit">
                    <input type="text" name="digits[]" maxlength="1" inputmode="numeric" class="otp-input otp-digit">
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full btn-primary h-12 rounded-btn text-base font-semibold shadow-sm flex items-center justify-center gap-2">
                        <span>Verifikasi Akun</span>
                    </button>
                </div>

                <!-- Resend -->
                <div class="text-xs text-slate-500 pt-1">
                    <?php if ($resendWait > 0): ?>
                        Kirim ulang kode dalam <span id="otpCountdown" class="font-bold text-slate-700" data-wait="<?= (int) $resendWait ?>"><?= sprintf('%02d:%02d', intdiv((int) $resendWait, 60), (int) $resendWait % 60) ?></span>
                    <?php else: ?>
                        Tidak menerima kode?
                        <a href="verifikasi-otp.php?state=kirim&email=<?= urlencode($email) ?>" class="text-primary font-bold hover:underline">
                            Kirim Ulang Kode
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    <?php endif; ?>

</main>

<script>
// Auto-focus OTP inputs progression
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.otp-digit');
    inputs.forEach((input, index) => {
        input.addEventListener('input', function(e) {
            if (this.value.length >= 1) {
                this.value = this.value[0].replace(/\D/, '');
                if (index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            }
        });

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !this.value && index > 0) {
                inputs[index - 1].focus();
            }
        });

        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text').trim();
            if (text.length >= 6) {
                for (let i = 0; i < 6; i++) {
                    if (inputs[i]) inputs[i].value = text[i];
                }
            }
        });
    });

    // Hitung mundur "kirim ulang" dari sisa waktu sebenarnya (bukan angka statis).
    const countdown = document.getElementById('otpCountdown');
    if (countdown) {
        let left = parseInt(countdown.dataset.wait || '0', 10);
        const tick = () => {
            if (left <= 0) {
                window.location.reload();
                return;
            }
            countdown.textContent = String(Math.floor(left / 60)).padStart(2, '0') + ':' + String(left % 60).padStart(2, '0');
            left -= 1;
        };
        tick();
        setInterval(tick, 1000);
    }
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
