<?php
$page_title = 'Daftar Akun Mahasiswa - SIP-Barang Kampus';
$current_page = 'daftar';
require_once __DIR__ . '/includes/header.php';

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $res = register_user($_POST);
    if ($res['success']) {
        if (!empty($res['email_sent'])) {
            set_flash_message('success', 'Pendaftaran berhasil. Kode verifikasi sudah dikirim ke email kampus Anda.');
        } elseif (!sip_mail_ready()) {
            set_flash_message('warning', 'Akun dibuat, tetapi pengiriman email belum aktif di server ini. Hubungi petugas keamanan kampus untuk verifikasi.');
        } else {
            set_flash_message('error', 'Akun dibuat, tetapi email verifikasi gagal terkirim. Buka halaman verifikasi lalu pilih "Kirim Ulang Kode".');
        }
        header('Location: verifikasi-otp.php?email=' . urlencode((string) $_POST['email']));
        exit;
    } else {
        $errorMessage = $res['message'];
    }
}

$programStudi = get_study_programs(true);
$prodiLabels = [];
foreach ($programStudi as $p) {
    $prodiLabels[] = $p['name'] . ' - ' . $p['fakultas'];
}

// Pendaftaran hanya menerima email kampus. Domain ditulis di konfigurasi agar
// mudah diubah tanpa menyentuh kode.
$allowedDomains = sip_allowed_email_domains();
$domainText = implode(', ', array_map(fn($d) => '@' . $d, $allowedDomains));

require_once __DIR__ . '/includes/navbar.php';
?>

<main class="flex-1 py-10 flex items-center">
    <div class="max-w-[1200px] mx-auto px-4 sm:px-6 w-full">
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
            
            <!-- Left: Ilustrasi Kampus (Figma: Placeholder Ilustrasi 560x640) -->
            <div class="hidden lg:flex lg:col-span-5 flex-col items-center justify-center p-8 rounded-card bg-gradient-to-br from-blue-50 to-indigo-50 border border-slate-200 text-center min-h-[560px]">
                <div class="w-20 h-20 rounded-2xl bg-primary/10 text-primary flex items-center justify-center mb-6">
                    <i data-lucide="user-plus" class="w-10 h-10"></i>
                </div>
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">
                    Bergabung dengan Komunitas Kampus
                </h2>
                <p class="mt-3 text-sm text-slate-600 max-w-sm leading-relaxed">
                    Daftarkan akun Anda dengan identitas kampus untuk kemudahan melacak, mengklaim, dan mengembalikan barang temuan.
                </p>
                <div class="mt-8 space-y-3 w-full max-w-xs text-left">
                    <div class="flex items-center gap-3 p-3 bg-white/80 rounded-xl border border-slate-200/60 text-xs">
                        <i data-lucide="check" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                        <span class="text-slate-700">Verifikasi otomatis via email kampus</span>
                    </div>
                    <div class="flex items-center gap-3 p-3 bg-white/80 rounded-xl border border-slate-200/60 text-xs">
                        <i data-lucide="check" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                        <span class="text-slate-700">Notifikasi status real-time WhatsApp</span>
                    </div>
                </div>
            </div>

            <!-- Right: Form Container (Figma: Form Container 600x640) -->
            <div class="lg:col-span-7 max-w-[620px] mx-auto w-full">
                <div class="card-default p-6 sm:p-10 rounded-card bg-white border border-slate-200 shadow-sm">
                    
                    <div class="mb-6">
                        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                            Daftar Akun Mahasiswa
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-500 mt-1">
                            Gunakan akun SSO / email resmi kampus untuk verifikasi otomatis.
                        </p>
                    </div>

                    <?php if (!empty($errorMessage)): ?>
                        <div class="mb-5 p-3.5 rounded-btn bg-red-50 border border-red-200 text-xs sm:text-sm text-red-700 flex items-center gap-2.5">
                            <i data-lucide="alert-circle" class="w-4 h-4 text-red-600 shrink-0"></i>
                            <span><?= htmlspecialchars($errorMessage) ?></span>
                        </div>
                    <?php endif; ?>

                    <form action="daftar.php" method="POST" class="space-y-4">
                <?= csrf_field() ?>
                        
                        <!-- 2 Kolom: Nama Lengkap & NIM -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs md:text-sm font-bold text-slate-900 mb-1.5">
                                    Nama Lengkap <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                        <i data-lucide="user" class="w-4 h-4"></i>
                                    </div>
                                    <input 
                                        type="text" 
                                        name="nama" 
                                        required
                                        value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>"
                                        placeholder="Budi Pratama" 
                                        class="input-default pl-10"
                                    >
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs md:text-sm font-bold text-slate-900 mb-1.5">
                                    NIM <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                        <i data-lucide="credit-card" class="w-4 h-4"></i>
                                    </div>
                                    <input 
                                        type="text" 
                                        name="nim" 
                                        required
                                        value="<?= htmlspecialchars($_POST['nim'] ?? '') ?>"
                                        placeholder="2024080123" 
                                        class="input-default pl-10"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Dropdown Program Studi (Prodi) -->
                        <div>
                            <label class="block text-xs md:text-sm font-bold text-slate-900 mb-1.5">
                                Program Studi (Prodi) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <i data-lucide="graduation-cap" class="w-4 h-4"></i>
                                </div>
                                <select name="prodi" required class="input-default pl-10 text-slate-700 bg-white">
                                    <option value="" disabled <?= empty($_POST['prodi']) ? 'selected' : '' ?>>Pilih Program Studi & Fakultas</option>
                                    <?php foreach ($prodiLabels as $label): ?>
                                        <option value="<?= htmlspecialchars($label) ?>" <?= ($_POST['prodi'] ?? '') === $label ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                    <?php if (!empty($_POST['prodi']) && !in_array($_POST['prodi'], $prodiLabels, true)): ?>
                                        <option value="<?= htmlspecialchars($_POST['prodi']) ?>" selected><?= htmlspecialchars($_POST['prodi']) ?></option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Email Kampus -->
                        <div>
                            <label class="block text-xs md:text-sm font-bold text-slate-900 mb-1.5">
                                Email Kampus <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <i data-lucide="mail" class="w-4 h-4"></i>
                                </div>
                                <input 
                                    type="email" 
                                    name="email" 
                                    required
                                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                    placeholder="budi.pratama@ui.ac.id" 
                                    class="input-default pl-10"
                                >
                            </div>
                            <p class="mt-1.5 text-[11px] text-slate-500">
                                Wajib email kampus (<?= htmlspecialchars($domainText) ?>). Kode verifikasi dikirim ke alamat ini.
                            </p>
                        </div>

                        <!-- Nomor HP WhatsApp -->
                        <div>
                            <label class="block text-xs md:text-sm font-bold text-slate-900 mb-1.5">
                                Nomor Handphone (WhatsApp) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <i data-lucide="phone" class="w-4 h-4"></i>
                                </div>
                                <input 
                                    type="tel" 
                                    name="phone" 
                                    required
                                    value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                                    placeholder="081234567890" 
                                    class="input-default pl-10"
                                >
                            </div>
                        </div>

                        <!-- Kata Sandi -->
                        <div>
                            <label class="block text-xs md:text-sm font-bold text-slate-900 mb-1.5">
                                Kata Sandi <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <i data-lucide="lock" class="w-4 h-4"></i>
                                </div>
                                <input 
                                    type="password" 
                                    name="password" 
                                    required
                                    placeholder="••••••••••••" 
                                    class="input-default pl-10 pr-10"
                                >
                                <button type="button" class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-3">
                            <button type="submit" class="w-full btn-primary h-12 rounded-btn text-base font-semibold shadow-sm flex items-center justify-center gap-2">
                                <span>Daftar Akun Sekarang</span>
                                <i data-lucide="arrow-right" class="w-4 h-4"></i>
                            </button>
                        </div>

                        <!-- Back to Login Link -->
                        <div class="text-center pt-2 text-xs sm:text-sm text-slate-500">
                            Sudah punya akun? 
                            <a href="masuk.php" class="text-primary font-bold hover:underline">
                                Masuk di sini
                            </a>
                        </div>

                    </form>

                </div>
            </div>

        </div>

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
