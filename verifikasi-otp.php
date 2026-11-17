<?php
$state = isset($_GET['state']) ? $_GET['state'] : 'default'; // 'default', 'salah', 'berhasil'

$titles = [
    'default' => 'Verifikasi OTP - SIP-Barang Kampus',
    'salah' => 'Verifikasi OTP Salah - SIP-Barang Kampus',
    'berhasil' => 'Verifikasi OTP Berhasil - SIP-Barang Kampus'
];
$page_title = $titles[$state] ?? $titles['default'];
$current_page = 'otp';
require_once __DIR__ . '/includes/header.php';
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

<!-- State Switcher Bar for Developer Testing & Figma Verification -->
<div class="bg-slate-100 border-b border-slate-200 py-2 px-4 text-center">
    <div class="inline-flex items-center gap-2 text-xs text-slate-500">
        <span class="font-bold text-slate-700">Preview State Desain:</span>
        <a href="verifikasi-otp.php?state=default" class="px-2.5 py-1 rounded-full font-medium transition-colors <?= $state === 'default' ? 'bg-primary text-white font-semibold' : 'bg-white text-slate-600 hover:bg-slate-200' ?>">
            1. Normal (Input)
        </a>
        <a href="verifikasi-otp.php?state=salah" class="px-2.5 py-1 rounded-full font-medium transition-colors <?= $state === 'salah' ? 'bg-red-500 text-white font-semibold' : 'bg-white text-slate-600 hover:bg-slate-200' ?>">
            2. Salah (Error)
        </a>
        <a href="verifikasi-otp.php?state=berhasil" class="px-2.5 py-1 rounded-full font-medium transition-colors <?= $state === 'berhasil' ? 'bg-emerald-500 text-white font-semibold' : 'bg-white text-slate-600 hover:bg-slate-200' ?>">
            3. Berhasil (Success)
        </a>
    </div>
</div>

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
                <strong class="text-slate-800 font-semibold">budi.pratama@ui.ac.id</strong>. Masukkan kode di bawah ini.
            </p>

            <!-- 6 Digit Error Inputs -->
            <form action="verifikasi-otp.php?state=berhasil" method="POST" class="mt-6 space-y-4">
                <div class="flex items-center justify-center gap-2 sm:gap-3">
                    <input type="text" maxlength="1" value="4" class="otp-input otp-digit error" autofocus>
                    <input type="text" maxlength="1" value="8" class="otp-input otp-digit error">
                    <input type="text" maxlength="1" value="2" class="otp-input otp-digit error">
                    <input type="text" maxlength="1" value="9" class="otp-input otp-digit error">
                    <input type="text" maxlength="1" value="0" class="otp-input otp-digit error">
                    <input type="text" maxlength="1" value="1" class="otp-input otp-digit error">
                </div>

                <!-- Error Message Alert -->
                <div class="flex items-center justify-center gap-1.5 text-xs text-red-600 font-medium pt-1">
                    <i data-lucide="alert-circle" class="w-4 h-4"></i>
                    <span>Kode salah. Periksa kembali email kamu.</span>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full btn-primary h-12 rounded-btn text-base font-semibold shadow-sm flex items-center justify-center gap-2">
                        <span>Verifikasi Ulang</span>
                    </button>
                </div>
            </form>

            <!-- Footer Resend & Change Email -->
            <div class="mt-6 pt-4 border-t border-slate-100 space-y-2 text-xs sm:text-sm">
                <div class="flex items-center justify-center gap-1.5 text-slate-500">
                    <span>Tidak menerima kode?</span>
                    <a href="verifikasi-otp.php?state=default" class="text-primary font-bold hover:underline flex items-center gap-1">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                        <span>Kirim ulang</span>
                    </a>
                </div>
                <div>
                    <a href="daftar.php" class="text-slate-500 hover:text-slate-800 font-medium inline-flex items-center gap-1">
                        <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                        <span>Ganti email</span>
                    </a>
                </div>
            </div>
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
                <strong class="text-slate-800 font-semibold">budi.pratama@ui.ac.id</strong>. Masukkan kode di bawah ini.
            </p>

            <!-- 6 Digit Inputs -->
            <form action="verifikasi-otp.php?state=berhasil" method="POST" class="mt-6 space-y-4">
                <div class="flex items-center justify-center gap-2 sm:gap-3">
                    <input type="text" maxlength="1" value="4" class="otp-input otp-digit">
                    <input type="text" maxlength="1" value="8" class="otp-input otp-digit">
                    <input type="text" maxlength="1" value="2" class="otp-input otp-digit">
                    <input type="text" maxlength="1" value="" class="otp-input otp-digit" autofocus>
                    <input type="text" maxlength="1" value="" class="otp-input otp-digit">
                    <input type="text" maxlength="1" value="" class="otp-input otp-digit">
                </div>

                <p class="text-xs text-slate-400">Kode berlaku 10 menit</p>

                <div class="pt-2">
                    <button type="submit" class="w-full btn-primary h-12 rounded-btn text-base font-semibold shadow-sm flex items-center justify-center gap-2">
                        <span>Verifikasi</span>
                    </button>
                </div>
            </form>

            <!-- Footer Resend & Change Email -->
            <div class="mt-6 pt-4 border-t border-slate-100 space-y-2 text-xs sm:text-sm">
                <div class="flex items-center justify-center gap-1.5 text-slate-500">
                    <span>Tidak menerima kode?</span>
                    <span id="otpCountdown" class="text-slate-500 font-medium">Kirim ulang dalam 00:45</span>
                </div>
                <div>
                    <a href="daftar.php" class="text-primary hover:text-blue-700 font-medium inline-flex items-center gap-1">
                        <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                        <span>Ganti email</span>
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
