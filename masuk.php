<?php
$page_title = 'Masuk ke Akun - SIP-Barang Kampus';
$current_page = 'masuk';
require_once __DIR__ . '/includes/header.php';

// If already logged in, redirect
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$errorMessage = '';
$inputIdentifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputIdentifier = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $res = login_user($inputIdentifier, $password);
    if ($res['success']) {
        set_flash_message('success', 'Selamat datang kembali, ' . htmlspecialchars($res['user']['nama']) . '!');
        if ($res['user']['role'] === 'admin' || $res['user']['role'] === 'petugas') {
            header('Location: admin/index.php');
        } else {
            header('Location: index.php');
        }
        exit;
    } elseif (!empty($res['verify_email'])) {
        // Email belum terverifikasi: lanjutkan ke halaman kode, bukan gagal total.
        set_flash_message('warning', $res['message']);
        header('Location: verifikasi-otp.php?email=' . urlencode((string) $res['verify_email']));
        exit;
    } else {
        $errorMessage = $res['message'];
    }
}

require_once __DIR__ . '/includes/navbar.php';
?>

<main class="flex-1 py-10 flex items-center">
    <div class="max-w-[1200px] mx-auto px-4 sm:px-6 w-full">
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
            
            <!-- Left: Ilustrasi Kampus (Figma: Placeholder Ilustrasi 560x520) -->
            <div class="hidden lg:flex lg:col-span-5 flex-col items-center justify-center p-8 rounded-card bg-gradient-to-br from-blue-50 to-indigo-50 border border-slate-200 text-center min-h-[480px]">
                <div class="w-24 h-24 rounded-3xl bg-blue-600/10 text-primary flex items-center justify-center mb-6">
                    <i data-lucide="sparkles" class="w-12 h-12 text-primary"></i>
                </div>
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">
                    SIP-Barang Kampus
                </h2>
                <p class="mt-3 text-sm text-slate-600 max-w-sm leading-relaxed">
                    Sistem informasi terintegrasi untuk pelaporan, pencarian, dan pengembalian barang hilang antar civitas akademika kampus.
                </p>
                <div class="mt-8 grid grid-cols-2 gap-3 w-full max-w-xs text-left">
                    <div class="p-3 bg-white/80 backdrop-blur rounded-xl border border-slate-200/60 text-xs">
                        <span class="font-bold text-primary block">Cepat & Aman</span>
                        <span class="text-slate-500">Verifikasi berlapis oleh satpam</span>
                    </div>
                    <div class="p-3 bg-white/80 backdrop-blur rounded-xl border border-slate-200/60 text-xs">
                        <span class="font-bold text-primary block">Real-time</span>
                        <span class="text-slate-500">Notifikasi status langsung</span>
                    </div>
                </div>
            </div>

            <!-- Right: Card Form Masuk (Figma: Card Form Masuk 600x520) -->
            <div class="lg:col-span-7 max-w-[600px] mx-auto w-full">
                <div class="card-default p-8 sm:p-10 rounded-card bg-white border border-slate-200 shadow-sm">
                    
                    <div class="mb-6">
                        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                            Masuk ke Akunmu
                        </h1>
                        <p class="text-sm text-slate-500 mt-1">
                            Gunakan email kampus atau NIM dan kata sandi.
                        </p>
                    </div>

                    <?php if (!empty($errorMessage)): ?>
                        <div class="mb-5 p-3.5 rounded-btn bg-red-50 border border-red-200 text-xs sm:text-sm text-red-700 flex items-center gap-2.5">
                            <i data-lucide="alert-circle" class="w-4 h-4 text-red-600 shrink-0"></i>
                            <span><?= htmlspecialchars($errorMessage) ?></span>
                        </div>
                    <?php endif; ?>

                    <form action="masuk.php" method="POST" class="space-y-4">
                <?= csrf_field() ?>
                        
                        <!-- Input User (Email / NIM) -->
                        <div>
                            <label class="block text-xs md:text-sm font-bold text-slate-900 mb-1.5">
                                Email Kampus atau NIM
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <i data-lucide="user" class="w-4 h-4"></i>
                                </div>
                                <input 
                                    type="text" 
                                    name="username" 
                                    id="loginUsername"
                                    required
                                    value="<?= htmlspecialchars($inputIdentifier) ?>"
                                    placeholder="nama@ui.ac.id atau 2024080123" 
                                    class="input-default pl-10"
                                >
                            </div>
                        </div>

                        <!-- Input Kata Sandi -->
                        <div>
                            <label class="block text-xs md:text-sm font-bold text-slate-900 mb-1.5">
                                Kata Sandi
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <i data-lucide="lock" class="w-4 h-4"></i>
                                </div>
                                <input 
                                    type="password" 
                                    name="password" 
                                    id="loginPassword"
                                    required
                                    placeholder="••••••••••••" 
                                    class="input-default pl-10 pr-10"
                                >
                                <button type="button" class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Remember Me & Forgot Password -->
                        <div class="flex items-center justify-between text-xs sm:text-sm">
                            <label class="flex items-center gap-2 cursor-pointer text-slate-600 hover:text-slate-900">
                                <input type="checkbox" name="remember" class="rounded text-primary focus:ring-blue-500 w-4 h-4">
                                <span>Ingat saya</span>
                            </label>
                            <a href="lupa-sandi.php" class="text-primary hover:text-blue-700 font-semibold transition-colors">
                                Lupa kata sandi?
                            </a>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <button type="submit" class="w-full btn-primary h-12 rounded-btn text-base font-semibold shadow-sm flex items-center justify-center gap-2">
                                <i data-lucide="log-in" class="w-4 h-4"></i>
                                <span>Masuk</span>
                            </button>
                        </div>

                        <!-- Register Redirect Link -->
                        <div class="text-center pt-2 text-xs sm:text-sm text-slate-500">
                            Belum punya akun? 
                            <a href="daftar.php" class="text-primary font-bold hover:underline">
                                Daftar di sini
                            </a>
                        </div>

                    </form>

                </div>
            </div>

        </div>

    </div>
</main>



<?php require_once __DIR__ . '/includes/footer.php'; ?>
