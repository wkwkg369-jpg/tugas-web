<?php
$page_title = 'Klaim Berhasil Diajukan - SIP-Barang Kampus';
$current_page = 'riwayat';
require_once __DIR__ . '/includes/header.php';

$user = get_logged_in_user();

/* Klaim wajib login: tanpa akun, status klaim tidak akan pernah terlihat pemiliknya. */
if (!$user) {
    set_flash_message('error', 'Silakan masuk terlebih dahulu untuk mengajukan klaim kepemilikan.');
    header('Location: masuk.php');
    exit;
}

$userId = (int) $user['id'];
$item = null;
$kodeKlaim = '';
$namaBarang = '';
$waktuKlaim = date('d M Y, H:i') . ' WIB';
$statusKlaim = 'Dalam Verifikasi';
$errorKlaim = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemId = (int) ($_POST['item_id'] ?? 0);
    $item = $itemId ? get_item_by_id($itemId) : null;
    $jawaban = trim($_POST['jawaban_verifikasi'] ?? '');

    if (!$item) {
        $errorKlaim = 'Barang yang Anda klaim tidak ditemukan.';
    } elseif (mb_strlen($jawaban) < 10) {
        $errorKlaim = 'Jawaban verifikasi minimal 10 karakter agar petugas dapat menilai kepemilikan.';
    } elseif (get_user_claim_for_item($userId, $itemId)) {
        $errorKlaim = 'Anda sudah mengajukan klaim untuk barang ini. Pantau statusnya di Riwayat Saya.';
    } elseif ((int) ($item['pelapor_id'] ?? 0) === $userId) {
        $errorKlaim = 'Anda tidak dapat mengklaim laporan milik sendiri.';
    } elseif (!in_array($item['status'], ['Ditemukan', 'Hilang', 'Dalam Verifikasi'], true)
              || in_array($item['status'], ['Dikembalikan', 'Ditutup'], true)) {
        $errorKlaim = 'Barang ini tidak lagi dapat diklaim (status: ' . $item['status'] . ').';
    }

    // Upload bukti pendukung (opsional, divalidasi ekstensi + isi berkas)
    $buktiFilename = null;
    if ($errorKlaim === '' && isset($_FILES['bukti_klaim']) && $_FILES['bukti_klaim']['error'] !== UPLOAD_ERR_NO_FILE) {
        $cekBukti = validate_upload($_FILES['bukti_klaim'], ['jpg', 'jpeg', 'png', 'webp', 'pdf'], 5 * 1024 * 1024);
        if ($cekBukti['ok']) {
            $buktiFilename = 'proof_' . date('Ymd_His') . '_' . random_int(100, 999) . '.' . $cekBukti['ext'];
            if (!move_uploaded_file($_FILES['bukti_klaim']['tmp_name'], ensure_upload_dir() . '/' . $buktiFilename)) {
                $buktiFilename = null;
                $errorKlaim = 'Bukti pendukung gagal disimpan. Silakan coba lagi tanpa lampiran.';
            }
        } else {
            $errorKlaim = $cekBukti['error'];
        }
    }

    if ($errorKlaim === '') {
        $hasil = create_claim([
            'item_id' => (int) $item['id'],
            'user_id' => $userId,
            'nama' => $user['nama'],
            'email' => $user['email'],
            'phone' => $user['phone'] ?? null,
            'jawaban' => $jawaban,
            'bukti' => $buktiFilename,
            'item_nama' => $item['nama'],
        ]);

        if ($hasil['ok']) {
            $kodeKlaim = $hasil['kode'];
            $namaBarang = $item['nama'];
        } else {
            $errorKlaim = $hasil['error'];
        }
    }

    if ($errorKlaim !== '') {
        // Tampilkan status klaim terakhir milik pengguna bila ada, supaya halaman tidak kosong.
        $terakhir = db_fetch_one(
            "SELECT c.*, i.nama AS item_nama FROM claims c JOIN items i ON i.id = c.item_id
             WHERE c.user_id = ? ORDER BY c.id DESC LIMIT 1",
            [$userId]
        );
        if ($terakhir) {
            $kodeKlaim = $terakhir['kode_klaim'];
            $namaBarang = $terakhir['item_nama'];
            $statusKlaim = $terakhir['status'];
        }
    }
} else {
    // Akses GET: tampilkan klaim terakhir milik pengguna (bukan klaim orang lain).
    $terakhir = db_fetch_one(
        "SELECT c.*, i.nama AS item_nama FROM claims c JOIN items i ON i.id = c.item_id
         WHERE c.user_id = ? ORDER BY c.id DESC LIMIT 1",
        [$userId]
    );
    if (!$terakhir) {
        set_flash_message('info', 'Belum ada klaim yang diajukan. Pilih barang temuan di katalog lalu ajukan klaim.');
        header('Location: pencarian.php');
        exit;
    }
    $kodeKlaim = $terakhir['kode_klaim'];
    $namaBarang = $terakhir['item_nama'];
    $statusKlaim = $terakhir['status'];
    $waktuKlaim = date('d M Y, H:i', strtotime($terakhir['created_at'])) . ' WIB';
}

require_once __DIR__ . '/includes/navbar.php';
?>

<main class="flex-1 py-14 flex items-center justify-center px-4">
    <!-- Card Klaim Berhasil (Figma: Card Klaim Berhasil 520x490) -->
    <div class="card-default w-full max-w-[520px] p-8 sm:p-10 rounded-card bg-white border border-slate-200 shadow-card text-center">

        <?php if ($errorKlaim !== ''): ?>
            <!-- Keadaan gagal: jelaskan alasannya, jangan pura-pura sukses -->
            <div class="w-14 h-14 rounded-full bg-red-100 text-red-600 mx-auto flex items-center justify-center mb-6">
                <i data-lucide="alert-circle" class="w-8 h-8"></i>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                Klaim Belum Dapat Diproses
            </h1>
            <div class="mt-4 p-4 rounded-xl bg-red-50 border border-red-200 text-sm text-red-700 text-left leading-relaxed">
                <?= htmlspecialchars($errorKlaim) ?>
            </div>

            <?php if ($kodeKlaim !== ''): ?>
                <div class="mt-5 p-4 rounded-xl bg-slate-50 border border-slate-200 text-left space-y-2">
                    <div class="text-xs text-slate-400">Klaim terakhir Anda:</div>
                    <div class="font-mono text-xs text-slate-600">#<?= htmlspecialchars($kodeKlaim) ?></div>
                    <div class="text-sm font-semibold text-slate-900 truncate"><?= htmlspecialchars($namaBarang) ?></div>
                    <div class="pt-1 flex items-center justify-between">
                        <span class="text-xs text-slate-500">Status Klaim:</span>
                        <?= render_status_badge($statusKlaim, 'sm') ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="mt-7 space-y-3">
                <a href="pencarian.php" class="w-full btn-primary h-12 rounded-btn text-sm font-semibold shadow-sm flex items-center justify-center gap-2">
                    <i data-lucide="search" class="w-4 h-4"></i>
                    <span>Kembali ke Pencarian</span>
                </a>
                <a href="riwayat.php?tab=klaim" class="w-full btn-secondary h-11 rounded-btn text-sm font-medium flex items-center justify-center gap-2">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                    <span>Lihat Riwayat Klaim</span>
                </a>
            </div>

        <?php else: ?>
            <!-- Icon Circle Sukses (56x56, soft green) -->
            <div class="w-14 h-14 rounded-full bg-emerald-100 text-emerald-600 mx-auto flex items-center justify-center mb-6">
                <i data-lucide="check-circle" class="w-8 h-8"></i>
            </div>

            <!-- Judul H2 & Deskripsi -->
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                Klaim Berhasil Diajukan!
            </h1>
            <p class="mt-3 text-sm text-slate-500 leading-relaxed max-w-md mx-auto">
                Bukti kepemilikan Anda telah kami terima dan diteruskan ke petugas keamanan. Kami akan memverifikasi klaim ini dalam 1x24 jam kerja.
            </p>

            <!-- Box Ringkasan Klaim (Figma: Box Ringkasan Klaim 440x110) -->
            <div class="mt-6 p-4 rounded-xl bg-slate-50 border border-slate-200 text-left space-y-2">
                <div class="flex items-center justify-between text-xs text-slate-400">
                    <span>ID Pengajuan: <strong class="text-slate-600 font-mono">#<?= htmlspecialchars($kodeKlaim) ?></strong></span>
                    <span><?= htmlspecialchars($waktuKlaim) ?></span>
                </div>
                <div class="text-base font-bold text-slate-900 truncate">
                    <?= htmlspecialchars($namaBarang) ?>
                </div>
                <div class="pt-1 flex items-center justify-between">
                    <span class="text-xs text-slate-500">Status Klaim Saat Ini:</span>
                    <?= render_status_badge($statusKlaim, 'sm') ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-8 space-y-3">
                <a href="riwayat.php?tab=klaim" class="w-full btn-primary h-12 rounded-btn text-sm font-semibold shadow-sm flex items-center justify-center gap-2">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                    <span>Pantau Status di Riwayat Saya</span>
                </a>
                <div>
                    <a href="index.php" class="text-sm text-slate-500 hover:text-slate-800 font-medium inline-block py-2 transition-colors">
                        Kembali ke Halaman Beranda
                    </a>
                </div>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
