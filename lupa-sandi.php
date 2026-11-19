<?php
$page_title = 'Lupa Kata Sandi - SIP-Barang Kampus';
$current_page = 'masuk';
require_once __DIR__ . '/includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!empty($email)) {
        $otp = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        db_execute("INSERT INTO otp_codes (email, code, type) VALUES (?, ?, 'reset_password')", [$email, $otp]);
        $_SESSION['pending_otp'] = [
            'email' => $email,
            'code' => $otp
        ];
        set_flash_message('success', 'Kode reset kata sandi telah dikirim ke ' . $email . '. (Kode demo: ' . $otp . ')');
        header('Location: verifikasi-otp.php?email=' . urlencode($email));
        exit;
    }
}

require_once __DIR__ . '/includes/navbar.php';
?>

<main class="flex-1 py-14 flex items-center justify-center px-4">
    <!-- Card Lupa Sandi (Figma: Card Lupa Sandi 440x440) -->
    <div class="card-default w-full max-w-[440px] p-8 sm:p-10 rounded-card bg-white border border-slate-200 shadow-sm text-center">
        
        <!-- Icon Circle Key (56x56) -->
        <div class="w-14 h-14 rounded-full bg-blue-100 text-primary mx-auto flex items-center justify-center mb-6">
            <i data-lucide="key-round" class="w-7 h-7"></i>
        </div>

        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
            Lupa Kata Sandi?
        </h1>
        <p class="mt-2 text-xs sm:text-sm text-slate-500 leading-relaxed">
            Masukkan email kampusmu, kami akan kirim kode untuk mengatur ulang kata sandi.
        </p>

        <form action="lupa-sandi.php" method="POST" class="mt-6 space-y-4 text-left">
            <div>
                <label class="block text-xs md:text-sm font-bold text-slate-900 mb-1.5">
                    Email Kampus
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <i data-lucide="mail" class="w-4 h-4"></i>
                    </div>
                    <input 
                        type="email" 
                        name="email" 
                        required
                        placeholder="budi.pratama@ui.ac.id" 
                        class="input-default pl-10"
                    >
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full btn-primary h-12 rounded-btn text-base font-semibold shadow-sm flex items-center justify-center gap-2">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    <span>Kirim Kode Verifikasi</span>
                </button>
            </div>

            <div class="text-center pt-2">
                <a href="masuk.php" class="inline-flex items-center gap-2 text-xs sm:text-sm font-semibold text-primary hover:text-blue-700 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span>Kembali ke halaman masuk</span>
                </a>
            </div>
        </form>

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
