<?php
$page_title = 'Klaim Berhasil Diajukan - SIP-Barang Kampus';
$current_page = 'riwayat';
require_once __DIR__ . '/includes/header.php';

$user = get_logged_in_user();
$userId = $user ? $user['id'] : 1;
$userName = $user ? $user['nama'] : 'Budi Pratama';
$userEmail = $user ? $user['email'] : 'budi.pratama@ui.ac.id';
$userPhone = $user ? $user['phone'] : '081234567890';

$item = null;
$kodeKlaim = 'KLM-2026-' . random_int(8820, 8999);
$waktuKlaim = date('d M Y, H:i') . ' WIB';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemId = intval($_POST['item_id'] ?? 1);
    $item = get_item_by_id($itemId);
    $jawaban = trim($_POST['jawaban_verifikasi'] ?? '');

    // Handle Upload Bukti
    $buktiFilename = null;
    if (isset($_FILES['bukti_klaim']) && $_FILES['bukti_klaim']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['bukti_klaim']['tmp_name'];
        $origName = basename($_FILES['bukti_klaim']['name']);
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'])) {
            $buktiFilename = 'proof_' . time() . '_' . random_int(100, 999) . '.' . $ext;
            move_uploaded_file($tmpName, __DIR__ . '/assets/uploads/' . $buktiFilename);
        }
    }

    if ($item) {
        // Insert Claim
        db_execute("INSERT INTO claims (kode_klaim, item_id, user_id, nama_pengklaim, email_pengklaim, phone_pengklaim, jawaban_verifikasi, bukti_foto, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Dalam Verifikasi')", [
            $kodeKlaim,
            $item['id'],
            $userId,
            $userName,
            $userEmail,
            $userPhone,
            $jawaban,
            $buktiFilename
        ]);

        // Update item status to 'Dalam Verifikasi'
        db_execute("UPDATE items SET status = 'Dalam Verifikasi' WHERE id = ?", [$item['id']]);

        // Add user notification
        db_execute("INSERT INTO notifications (user_id, judul, pesan, icon, is_unread) VALUES (?, ?, ?, 'check-circle', 1)", [
            $userId,
            'Pengajuan Klaim Berhasil Diterima!',
            "Klaim Anda (#{$kodeKlaim}) untuk barang '{$item['nama']}' telah masuk ke antrean verifikasi petugas keamanan kampus."
        ]);
    }
}

if (!$item) {
    // If accessed directly via GET, load recent claim or item
    $claims = get_user_claims($userId);
    $recentClaim = !empty($claims) ? $claims[0] : null;
    $namaBarang = $recentClaim ? $recentClaim['item_nama'] : 'Tumbler Hydro Flask 32oz Biru Dongker';
    $kodeKlaim = $recentClaim ? $recentClaim['kode_klaim'] : 'KLM-2026-8821';
} else {
    $namaBarang = $item['nama'];
}

require_once __DIR__ . '/includes/navbar.php';
?>

<main class="flex-1 py-14 flex items-center justify-center px-4">
    <!-- Card Klaim Berhasil (Figma: Card Klaim Berhasil 520x490) -->
    <div class="card-default w-full max-w-[520px] p-8 sm:p-10 rounded-card bg-white border border-slate-200 shadow-card text-center">
        
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
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    <span>Dalam Verifikasi</span>
                </span>
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

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
