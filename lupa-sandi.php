<?php
$page_title = 'Atur Ulang Kata Sandi - SIP-Barang Kampus';
$current_page = 'masuk';
require_once __DIR__ . '/includes/header.php';

$otpType = 'reset_password';
$step = (string) ($_GET['step'] ?? 'minta');
$email = strtolower(trim((string) ($_GET['email'] ?? '')));
$errorMessage = '';

// Sesi verifikasi reset: hanya berlaku setelah kode benar.
$verifiedEmail = (string) ($_SESSION['reset_verified_email'] ?? '');

// Langkah 1 — minta kode
if ($step === 'minta' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Masukkan alamat email yang valid.';
    } else {
        $user = db_fetch_one("SELECT id, nama FROM users WHERE LOWER(email) = ? LIMIT 1", [$email]);
        if ($user) {
            $issued = sip_otp_issue($email, $otpType);
            $sent = sip_otp_send($email, $issued['code'], $otpType, $user['nama'] ?? '');
            $_SESSION['pending_otp'] = ['email' => $email, 'code' => $issued['code']];
            if (!$sent['ok']) {
                set_flash_message('warning', 'Kode sudah dibuat, tetapi email gagal dikirim. Hubungi petugas keamanan kampus.');
            }
        }
        // Pesan seragam: jangan bocorkan email mana yang terdaftar.
        set_flash_message('success', 'Jika email tersebut terdaftar, kode atur ulang kata sandi sudah dikirim. Kode berlaku 10 menit.');
        header('Location: lupa-sandi.php?step=kode&email=' . urlencode($email));
        exit;
    }
}

// Langkah 2 — verifikasi kode
if ($step === 'kode') {
    if ($email === '') {
        $email = (string) ($_SESSION['pending_otp']['email'] ?? '');
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $digits = $_POST['digits'] ?? [];
        $enteredCode = is_array($digits) ? implode('', $digits) : trim((string) ($_POST['otp_code'] ?? ''));
        $res = sip_otp_verify($email, $enteredCode, $otpType);
        if ($res['ok']) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                @session_regenerate_id(true);
            }
            $_SESSION['reset_verified_email'] = $email;
            unset($_SESSION['pending_otp']);
            header('Location: lupa-sandi.php?step=sandi&email=' . urlencode($email));
            exit;
        }
        $errorMessage = $res['error'];
    }
}

// Langkah 3 — kata sandi baru
if ($step === 'sandi') {
    if ($verifiedEmail === '' || ($email !== '' && $verifiedEmail !== $email)) {
        set_flash_message('warning', 'Sesi atur ulang sudah berakhir. Minta kode baru.');
        header('Location: lupa-sandi.php');
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pass1 = (string) ($_POST['password'] ?? '');
        $pass2 = (string) ($_POST['password_confirm'] ?? '');
        if (strlen($pass1) < 8) {
            $errorMessage = 'Kata sandi minimal 8 karakter.';
        } elseif ($pass1 !== $pass2) {
            $errorMessage = 'Konfirmasi kata sandi tidak sama.';
        } else {
            $hash = password_hash($pass1, PASSWORD_DEFAULT);
            db_execute("UPDATE users SET password = ? WHERE LOWER(email) = ?", [$hash, $verifiedEmail]);
            // Kode reset sudah terpakai; bersihkan sesi terkait.
            unset($_SESSION['reset_verified_email'], $_SESSION['pending_otp']);
            set_flash_message('success', 'Kata sandi berhasil diubah. Silakan masuk dengan kata sandi baru.');
            header('Location: masuk.php');
            exit;
        }
    }
}

require_once __DIR__ . '/includes/navbar.php';
$resendWait = ($email !== '') ? sip_otp_resend_blocked($email, $otpType) : 0;
?>

<main class="flex-1 py-14 flex items-center justify-center px-4">
    <div class="card-default w-full max-w-[440px] p-8 sm:p-10 rounded-card bg-white border border-slate-200 shadow-sm">
        <div class="w-14 h-14 rounded-full bg-blue-100 text-primary mx-auto flex items-center justify-center mb-6">
            <i data-lucide="<?= $step === 'sandi' ? 'lock' : 'key-round' ?>" class="w-7 h-7"></i>
        </div>

        <?php if (!empty($errorMessage)): ?>
            <div class="mb-4 p-3 rounded-btn bg-red-50 border border-red-200 text-xs text-red-700 flex items-center gap-2">
                <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
                <span><?= htmlspecialchars($errorMessage) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($step === 'sandi'): ?>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight text-center">Kata Sandi Baru</h1>
            <p class="mt-2 text-xs sm:text-sm text-slate-500 leading-relaxed text-center">
                Buat kata sandi baru untuk <strong class="text-slate-800"><?= htmlspecialchars($verifiedEmail) ?></strong>.
            </p>
            <form action="lupa-sandi.php?step=sandi&email=<?= urlencode($verifiedEmail) ?>" method="POST" class="mt-6 space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label class="block text-xs md:text-sm font-bold text-slate-900 mb-1.5">Kata Sandi Baru</label>
                    <input type="password" name="password" required minlength="8" autocomplete="new-password" class="input-default" placeholder="Minimal 8 karakter">
                </div>
                <div>
                    <label class="block text-xs md:text-sm font-bold text-slate-900 mb-1.5">Ulangi Kata Sandi</label>
                    <input type="password" name="password_confirm" required minlength="8" autocomplete="new-password" class="input-default" placeholder="Ulangi kata sandi">
                </div>
                <button type="submit" class="w-full btn-primary h-12 rounded-btn text-base font-semibold shadow-sm">Simpan Kata Sandi</button>
            </form>

        <?php elseif ($step === 'kode'): ?>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight text-center">Masukkan Kode</h1>
            <p class="mt-2 text-xs sm:text-sm text-slate-500 leading-relaxed text-center">
                Kode 6 digit dikirim ke<br>
                <strong class="text-slate-800"><?= htmlspecialchars($email !== '' ? $email : '(email tidak diketahui)') ?></strong>
            </p>
            <form action="lupa-sandi.php?step=kode&email=<?= urlencode($email) ?>" method="POST" class="mt-6 space-y-4">
                <?= csrf_field() ?>
                <div class="flex items-center justify-center gap-2">
                    <?php for ($i = 0; $i < 6; $i++): ?>
                        <input type="text" name="digits[]" maxlength="1" inputmode="numeric" <?= $i === 0 ? 'autocomplete="one-time-code" autofocus' : '' ?> class="otp-digit w-11 h-12 text-center text-lg font-bold border border-slate-300 rounded-btn">
                    <?php endfor; ?>
                </div>
                <button type="submit" class="w-full btn-primary h-12 rounded-btn text-base font-semibold shadow-sm">Verifikasi Kode</button>
                <div class="text-xs text-slate-500 text-center">
                    <?php if ($resendWait > 0): ?>
                        Tunggu <strong><?= (int) $resendWait ?></strong> detik untuk minta kode baru.
                    <?php else: ?>
                        <a href="lupa-sandi.php" class="text-primary font-bold hover:underline">Minta kode baru</a>
                    <?php endif; ?>
                </div>
            </form>

        <?php else: ?>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight text-center">Lupa Kata Sandi</h1>
            <p class="mt-2 text-xs sm:text-sm text-slate-500 leading-relaxed text-center">
                Masukkan email kampus yang terdaftar. Kami kirim kode 6 digit untuk mengatur ulang kata sandi.
            </p>
            <form action="lupa-sandi.php?step=minta" method="POST" class="mt-6 space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label class="block text-xs md:text-sm font-bold text-slate-900 mb-1.5">Email Kampus</label>
                    <input type="email" name="email" required value="<?= htmlspecialchars($email) ?>" placeholder="nama@ui.ac.id" class="input-default">
                </div>
                <button type="submit" class="w-full btn-primary h-12 rounded-btn text-base font-semibold shadow-sm">Kirim Kode</button>
                <div class="text-xs text-slate-500 text-center">
                    Ingat kata sandi? <a href="masuk.php" class="text-primary font-bold hover:underline">Masuk di sini</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.otp-digit');
    inputs.forEach((input, index) => {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 1);
            if (this.value && index < inputs.length - 1) inputs[index + 1].focus();
        });
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !this.value && index > 0) inputs[index - 1].focus();
        });
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
            for (let i = 0; i < inputs.length; i++) inputs[i].value = text[i] || '';
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>