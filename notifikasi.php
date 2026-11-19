<?php
$page_title = 'Pusat Notifikasi - SIP-Barang Kampus';
$current_page = 'notifikasi';
require_once __DIR__ . '/includes/header.php';

// Handle mark all as read
if (isset($_GET['action']) && $_GET['action'] === 'read_all') {
    require_csrf();
    mark_all_notifications_read();
    set_flash_message('success', 'Semua notifikasi telah ditandai sudah dibaca.');
    header('Location: notifikasi.php');
    exit;
}

$notifications = get_mock_notifications();
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="flex-1 py-10">
    <div class="max-w-[800px] mx-auto px-4 sm:px-6">
        
        <!-- Header Section -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                    Pusat Notifikasi
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">
                    Informasi terkini mengenai perkembangan laporan dan klaim barang Anda.
                </p>
            </div>
            <a href="<?= csrf_link("notifikasi.php?action=read_all") ?>" class="text-xs text-primary font-semibold hover:underline flex items-center gap-1">
                <i data-lucide="check-check" class="w-4 h-4"></i>
                <span>Tandai semua dibaca</span>
            </a>
        </div>

        <!-- Container Notifikasi (Figma: Container Notifikasi 800x520) -->
        <div class="card-default rounded-card bg-white border border-slate-200 shadow-sm overflow-hidden divide-y divide-slate-100">
            <?php if (empty($notifications)): ?>
                <div class="p-12 text-center text-slate-500">
                    <i data-lucide="bell-off" class="w-12 h-12 mx-auto text-slate-300 mb-3"></i>
                    <p class="font-medium text-slate-700">Belum ada notifikasi baru</p>
                    <p class="text-xs text-slate-400 mt-1">Pemberitahuan terkait laporan Anda akan muncul di sini.</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notif): ?>
                    <?php $tautan = $notif['link'] ?? ''; $adaTautan = $tautan !== '' && $tautan !== '#'; ?>
                    <div class="p-5 flex items-start gap-4 transition-colors <?= $notif['unread'] ? 'bg-blue-50/50 hover:bg-blue-50' : 'hover:bg-slate-50' ?>">
                        <!-- Icon Box -->
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 <?= $notif['unread'] ? 'bg-blue-100 text-primary' : 'bg-slate-100 text-slate-500' ?>">
                            <i data-lucide="<?= htmlspecialchars($notif['icon']) ?>" class="w-5 h-5"></i>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <?php if ($notif['unread']): ?>
                                        <span class="w-2 h-2 rounded-full bg-primary shrink-0"></span>
                                    <?php endif; ?>
                                    <h3 class="text-sm font-bold <?= $notif['unread'] ? 'text-slate-900' : 'text-slate-700' ?>">
                                        <?= htmlspecialchars($notif['judul']) ?>
                                    </h3>
                                </div>
                                <span class="text-xs whitespace-nowrap shrink-0 <?= $notif['unread'] ? 'text-primary font-semibold' : 'text-slate-400' ?>">
                                    <?= htmlspecialchars($notif['waktu']) ?>
                                </span>
                            </div>
                            <p class="mt-1 text-xs sm:text-sm leading-relaxed <?= $notif['unread'] ? 'text-slate-700' : 'text-slate-500' ?>">
                                <?= htmlspecialchars($notif['pesan']) ?>
                            </p>
                            <?php if ($adaTautan): ?>
                                <a href="<?= htmlspecialchars($tautan) ?>" class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-primary hover:underline">
                                    <span>Lihat detail</span>
                                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
