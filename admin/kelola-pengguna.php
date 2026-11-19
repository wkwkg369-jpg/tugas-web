<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/data.php';

// Handle AJAX Status Toggle
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'toggle_status') {
    header('Content-Type: application/json');
    $userId = intval($_POST['user_id'] ?? 0);
    $user = db_fetch_one("SELECT * FROM users WHERE id = ?", [$userId]);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Pengguna tidak ditemukan.']);
        exit;
    }

    $newActive = empty($user['is_active']) ? 1 : 0;
    $newStatus = ($newActive === 1) ? 'Aktif' : 'Nonaktif';

    db_execute("UPDATE users SET is_active = ?, status = ? WHERE id = ?", [$newActive, $newStatus, $userId]);

    echo json_encode([
        'success' => true,
        'user_id' => $userId,
        'user_name' => $user['nama'],
        'new_status' => $newStatus,
        'new_active' => $newActive
    ]);
    exit;
}

// Handle Form Submissions (Add / Edit / Delete / Status Toggle fallback)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Tambah Pengguna / Petugas
    if ($action === 'tambah_pengguna') {
        $nama = trim($_POST['nama'] ?? '');
        $nim = trim($_POST['nim'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $prodi = trim($_POST['prodi'] ?? '');
        $role = trim($_POST['role'] ?? 'petugas');
        $status = trim($_POST['status'] ?? 'Aktif');
        $isActive = ($status === 'Aktif') ? 1 : 0;
        $rawPass = trim($_POST['password'] ?? 'password123');
        if (empty($rawPass)) {
            $rawPass = 'password123';
        }
        $passHash = password_hash($rawPass, PASSWORD_DEFAULT);

        if (!empty($nama) && !empty($email)) {
            // Check uniqueness
            $existing = db_fetch_one("SELECT id FROM users WHERE email = ? OR (nim IS NOT NULL AND nim != '' AND nim = ?)", [$email, $nim]);
            if ($existing) {
                set_flash_message('error', "Email '$email' atau NIM/NIP '$nim' sudah terdaftar!");
            } else {
                db_execute(
                    "INSERT INTO users (nama, nim, prodi, email, phone, password, role, status, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$nama, $nim, $prodi, $email, $phone, $passHash, $role, $status, $isActive]
                );
                set_flash_message('success', "Pengguna '$nama' berhasil ditambahkan!");
            }
        }
        header('Location: kelola-pengguna.php');
        exit;
    }

    // 2. Edit Pengguna
    if ($action === 'edit_pengguna') {
        $userId = intval($_POST['user_id'] ?? 0);
        $nama = trim($_POST['nama'] ?? '');
        $nim = trim($_POST['nim'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $prodi = trim($_POST['prodi'] ?? '');
        $role = trim($_POST['role'] ?? 'petugas');
        $status = trim($_POST['status'] ?? 'Aktif');
        $isActive = ($status === 'Aktif') ? 1 : 0;
        $newPass = trim($_POST['new_password'] ?? '');

        if ($userId > 0 && !empty($nama) && !empty($email)) {
            if (!empty($newPass)) {
                $passHash = password_hash($newPass, PASSWORD_DEFAULT);
                db_execute(
                    "UPDATE users SET nama = ?, nim = ?, email = ?, phone = ?, prodi = ?, role = ?, status = ?, is_active = ?, password = ? WHERE id = ?",
                    [$nama, $nim, $email, $phone, $prodi, $role, $status, $isActive, $passHash, $userId]
                );
            } else {
                db_execute(
                    "UPDATE users SET nama = ?, nim = ?, email = ?, phone = ?, prodi = ?, role = ?, status = ?, is_active = ? WHERE id = ?",
                    [$nama, $nim, $email, $phone, $prodi, $role, $status, $isActive, $userId]
                );
            }
            set_flash_message('success', "Data pengguna '$nama' berhasil diperbarui!");
        }
        header('Location: kelola-pengguna.php');
        exit;
    }

    // 3. Reset Password User
    if ($action === 'reset_password') {
        $userId = intval($_POST['user_id'] ?? 0);
        $defaultPass = password_hash('password123', PASSWORD_DEFAULT);
        db_execute("UPDATE users SET password = ? WHERE id = ?", [$defaultPass, $userId]);
        set_flash_message('success', "Kata sandi pengguna berhasil di-reset menjadi 'password123'.");
        header('Location: kelola-pengguna.php');
        exit;
    }

    // 4. Hapus Pengguna
    if ($action === 'hapus_pengguna') {
        $userId = intval($_POST['user_id'] ?? 0);
        $currentLoggedIn = get_logged_in_user();
        if ($currentLoggedIn && $currentLoggedIn['id'] == $userId) {
            set_flash_message('error', "Anda tidak dapat menghapus akun Anda sendiri saat sedang login!");
        } else {
            db_execute("DELETE FROM users WHERE id = ?", [$userId]);
            set_flash_message('info', "Akun pengguna berhasil dihapus.");
        }
        header('Location: kelola-pengguna.php');
        exit;
    }
}

// Handle GET Toggle Status Fallback
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $userId = intval($_GET['id']);
    $user = db_fetch_one("SELECT * FROM users WHERE id = ?", [$userId]);
    if ($user) {
        $newActive = empty($user['is_active']) ? 1 : 0;
        $newStatus = ($newActive === 1) ? 'Aktif' : 'Nonaktif';
        db_execute("UPDATE users SET is_active = ?, status = ? WHERE id = ?", [$newActive, $newStatus, $userId]);
        set_flash_message('success', "Status akun '{$user['nama']}' berhasil diubah menjadi {$newStatus}.");
    }
    header('Location: kelola-pengguna.php');
    exit;
}

$page_title = 'Manajemen Pengguna - SIP-Barang Admin';
$active_tab = 'pengguna';
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';

// Search & Filtering
$search = trim($_GET['q'] ?? '');
$filterPeran = trim($_GET['peran'] ?? '');
$filterFakultas = trim($_GET['fakultas'] ?? '');

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (nama LIKE ? OR nim LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($filterPeran)) {
    if ($filterPeran === 'Mahasiswa') {
        $sql .= " AND role = 'mahasiswa'";
    } elseif ($filterPeran === 'Petugas Pos') {
        $sql .= " AND role = 'petugas'";
    } elseif ($filterPeran === 'Admin Pusat') {
        $sql .= " AND role = 'admin'";
    }
}

if (!empty($filterFakultas)) {
    $sql .= " AND prodi LIKE ?";
    $params[] = "%$filterFakultas%";
}

$sql .= " ORDER BY id ASC";
$usersList = db_fetch_all($sql, $params);
?>

<div class="flex-1 flex flex-col bg-[#F8FAFC] overflow-y-auto min-w-0">
    <!-- Top Header matching Figma -->
    <header class="px-8 pt-8 pb-6 flex items-center justify-between shrink-0">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Manajemen Pengguna Kampus</h1>
        </div>
        <div>
            <button type="button" onclick="openModal('modalTambahPengguna')"
                class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm px-4 py-2.5 rounded-lg inline-flex items-center gap-2 shadow-sm transition-colors cursor-pointer">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                <span>Tambah Petugas</span>
            </button>
        </div>
    </header>

    <!-- Filters & Table -->
    <main class="px-8 pb-8 space-y-6">

        <!-- Search & Filters Bar Form -->
        <form method="GET" action="kelola-pengguna.php" class="flex flex-wrap items-center gap-4">
            <div class="relative flex-1 min-w-[280px]">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                    placeholder="Cari nama, NIM, atau email..."
                    class="w-full bg-white border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
            </div>

            <div class="relative">
                <select name="peran" onchange="this.form.submit()"
                    class="appearance-none bg-white border border-slate-200 rounded-xl pl-4 pr-10 py-2.5 text-sm text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all cursor-pointer">
                    <option value="">Semua Peran</option>
                    <option value="Mahasiswa" <?= $filterPeran === 'Mahasiswa' ? 'selected' : '' ?>>Mahasiswa</option>
                    <option value="Petugas Pos" <?= $filterPeran === 'Petugas Pos' ? 'selected' : '' ?>>Petugas Pos
                    </option>
                    <option value="Admin Pusat" <?= $filterPeran === 'Admin Pusat' ? 'selected' : '' ?>>Admin Pusat
                    </option>
                </select>
                <i data-lucide="chevron-down"
                    class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
            </div>

            <div class="relative">
                <select name="fakultas" onchange="this.form.submit()"
                    class="appearance-none bg-white border border-slate-200 rounded-xl pl-4 pr-10 py-2.5 text-sm text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all cursor-pointer">
                    <option value="">Semua Fakultas / Unit</option>
                    <option value="Ilmu Komputer" <?= $filterFakultas === 'Ilmu Komputer' ? 'selected' : '' ?>>Fakultas
                        Ilmu Komputer</option>
                    <option value="Ekonomi" <?= $filterFakultas === 'Ekonomi' ? 'selected' : '' ?>>Fakultas Ekonomi &amp;
                        Bisnis</option>
                    <option value="Teknik" <?= $filterFakultas === 'Teknik' ? 'selected' : '' ?>>Fakultas Teknik</option>
                    <option value="Keamanan" <?= $filterFakultas === 'Keamanan' ? 'selected' : '' ?>>Biro Pengamanan Kampus
                    </option>
                    <option value="Direktorat" <?= $filterFakultas === 'Direktorat' ? 'selected' : '' ?>>Direktorat
                        Kemahasiswaan</option>
                </select>
                <i data-lucide="chevron-down"
                    class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
            </div>

            <?php if (!empty($search) || !empty($filterPeran) || !empty($filterFakultas)): ?>
                <a href="kelola-pengguna.php"
                    class="px-3.5 py-2.5 text-xs font-semibold text-slate-500 hover:text-slate-800 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
                    Reset Filter
                </a>
            <?php endif; ?>
        </form>

        <!-- Master Table Card matching Figma -->
        <div
            class="bg-white rounded-2xl border border-slate-100 shadow-[0_2px_10px_rgba(15,23,42,0.03)] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr
                            class="border-b border-slate-100 text-slate-400 text-xs font-semibold uppercase tracking-wider bg-slate-50/50">
                            <th class="py-4 px-6 w-16">NO</th>
                            <th class="py-4 px-6">NAMA LENGKAP &amp; EMAIL</th>
                            <th class="py-4 px-6">NIM / NIP</th>
                            <th class="py-4 px-6">FAKULTAS / UNIT</th>
                            <th class="py-4 px-6">PERAN</th>
                            <th class="py-4 px-6">STATUS AKUN</th>
                            <th class="py-4 px-6 text-right">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($usersList)): ?>
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    <div
                                        class="w-12 h-12 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 mx-auto mb-3">
                                        <i data-lucide="users" class="w-6 h-6"></i>
                                    </div>
                                    <p class="text-sm font-medium text-slate-600">Tidak ada data pengguna yang cocok</p>
                                    <p class="text-xs text-slate-400 mt-1">Coba ubah kata kunci pencarian atau reset filter.
                                    </p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1;
                            foreach ($usersList as $u):
                                $isActive = !empty($u['is_active']);
                                $statusLabel = $isActive ? 'Aktif' : 'Nonaktif';

                                // Role labels & colors matching Figma
                                $roleLabel = 'Mahasiswa';
                                $roleClass = 'bg-blue-50 text-blue-600';
                                if ($u['role'] === 'petugas') {
                                    $roleLabel = 'Petugas Pos';
                                    $roleClass = 'bg-amber-50 text-amber-600';
                                } elseif ($u['role'] === 'admin') {
                                    $roleLabel = 'Admin Pusat';
                                    $roleClass = 'bg-indigo-50 text-indigo-600';
                                }

                                // User JSON for JS modal inspection
                                $userDataJson = htmlspecialchars(json_encode([
                                    'id' => $u['id'],
                                    'nama' => $u['nama'],
                                    'nim' => $u['nim'] ?? '',
                                    'email' => $u['email'],
                                    'phone' => $u['phone'] ?? '',
                                    'prodi' => $u['prodi'] ?? '',
                                    'role' => $u['role'],
                                    'role_label' => $roleLabel,
                                    'role_class' => $roleClass,
                                    'status' => $statusLabel,
                                    'is_active' => $isActive ? 1 : 0,
                                    'created_at' => $u['created_at'] ?? date('Y-m-d')
                                ]), ENT_QUOTES, 'UTF-8');
                                ?>
                                <tr class="hover:bg-slate-50/70 transition-colors" id="user-row-<?= $u['id'] ?>">
                                    <td class="py-4 px-6 text-slate-500 font-medium"><?= $no++ ?></td>
                                    <td class="py-4 px-6">
                                        <div class="font-semibold text-slate-900"><?= htmlspecialchars($u['nama']) ?></div>
                                        <div class="text-xs text-slate-400 mt-0.5"><?= htmlspecialchars($u['email']) ?></div>
                                    </td>
                                    <td class="py-4 px-6 font-mono text-slate-600 text-xs">
                                        <?= htmlspecialchars($u['nim'] ?? '-') ?>
                                    </td>
                                    <td class="py-4 px-6 text-slate-600">
                                        <?= htmlspecialchars($u['prodi'] ?? 'Civitas Akademika') ?>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="text-xs font-semibold px-3 py-1 rounded-full <?= $roleClass ?>">
                                            <?= $roleLabel ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <!-- Interactive Clickable Status Toggle Switch -->
                                        <div class="flex items-center gap-2.5">
                                            <button type="button" onclick="toggleStatus(<?= $u['id'] ?>, this)"
                                                title="Klik untuk mengubah status akun"
                                                class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none <?= $isActive ? 'bg-emerald-500' : 'bg-slate-300' ?>"
                                                id="toggle-btn-<?= $u['id'] ?>" data-active="<?= $isActive ? '1' : '0' ?>">
                                                <span
                                                    class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out <?= $isActive ? 'translate-x-4' : 'translate-x-0' ?>"
                                                    id="toggle-knob-<?= $u['id'] ?>"></span>
                                            </button>
                                            <span
                                                class="text-xs font-medium <?= $isActive ? 'text-emerald-600' : 'text-slate-400' ?> min-w-[50px]"
                                                id="status-label-<?= $u['id'] ?>">
                                                <?= $statusLabel ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <?php if (!$isActive && $u['role'] === 'mahasiswa'): ?>
                                            <!-- Green "Aktifkan" button matching Figma Farhan row -->
                                            <button type="button" onclick="activateUser(<?= $u['id'] ?>)"
                                                id="action-btn-<?= $u['id'] ?>"
                                                class="text-xs font-medium px-3.5 py-1.5 rounded-lg bg-emerald-100 hover:bg-emerald-200 text-emerald-700 transition-colors cursor-pointer">
                                                Aktifkan
                                            </button>
                                        <?php elseif ($u['role'] === 'mahasiswa'): ?>
                                            <!-- Detail Button for Mahasiswa -->
                                            <button type="button" onclick="openDetailMahasiswa(<?= $userDataJson ?>)"
                                                id="action-btn-<?= $u['id'] ?>"
                                                class="text-xs font-medium px-3.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors cursor-pointer">
                                                Detail
                                            </button>
                                        <?php else: ?>
                                            <!-- Kelola Button for Petugas & Admin -->
                                            <button type="button" onclick="openKelolaPetugas(<?= $userDataJson ?>)"
                                                id="action-btn-<?= $u['id'] ?>"
                                                class="text-xs font-medium px-3.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors cursor-pointer">
                                                Kelola
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<!-- ============================================================== -->
<!-- MODAL 1: TAMBAH PETUGAS / PENGGUNA BARU -->
<!-- ============================================================== -->
<div id="modalTambahPengguna"
    class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div
        class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-slate-100 animate-in fade-in zoom-in-95">
        <div class="flex items-center gap-3 pb-4 mb-4 border-b border-slate-100">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <i data-lucide="user-plus" class="w-5 h-5"></i>
            </div>
            <div>
                <h3 class="font-bold text-slate-900 text-base">Tambah Petugas / Pengguna Baru</h3>
                <p class="text-xs text-slate-400">Tambahkan akun petugas pos, staf admin, atau mahasiswa</p>
            </div>
        </div>

        <form method="POST" action="kelola-pengguna.php" class="space-y-4">
            <input type="hidden" name="action" value="tambah_pengguna">

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Lengkap</label>
                <input type="text" name="nama" placeholder="Contoh: Budi Prasetyo" required
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">NIM / NIP</label>
                    <input type="text" name="nim" placeholder="Contoh: 198005122005011002" required
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Peran Akun (Role)</label>
                    <select name="role" required
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                        <option value="petugas" selected>Petugas Pos Keamanan</option>
                        <option value="admin">Admin Pusat</option>
                        <option value="mahasiswa">Mahasiswa</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Email Kampus</label>
                    <input type="email" name="email" placeholder="budi@ui.ac.id" required
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nomor WhatsApp</label>
                    <input type="text" name="phone" placeholder="081234567890"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Fakultas / Unit Kerja</label>
                <input type="text" name="prodi" placeholder="Misal: Biro Pengamanan Kampus / Fasilkom" required
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kata Sandi Awal</label>
                    <input type="text" name="password" value="password123" required
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Status Akun</label>
                    <select name="status"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                        <option value="Aktif" selected>Aktif</option>
                        <option value="Nonaktif">Nonaktif</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeModal('modalTambahPengguna')"
                    class="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                    Batal
                </button>
                <button type="submit"
                    class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition-colors inline-flex items-center gap-1.5">
                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                    <span>Tambah Pengguna</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL 2: DETAIL PENGGUNA (MAHASISWA) -->
<!-- ============================================================== -->
<div id="modalDetailMahasiswa"
    class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div
        class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-100 animate-in fade-in zoom-in-95">
        <div class="flex items-start justify-between pb-4 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-base shadow-sm shrink-0"
                    id="detailAvatar">
                    M
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base" id="detailNama">-</h3>
                    <p class="text-xs text-slate-400 mt-0.5" id="detailEmail">-</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('modalDetailMahasiswa')"
                class="text-slate-400 hover:text-slate-600 p-1">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <div class="py-4 space-y-3 text-xs">
            <div class="flex justify-between py-1.5 border-b border-slate-50">
                <span class="text-slate-400 font-medium">NIM / Identitas</span>
                <span class="text-slate-900 font-semibold font-mono" id="detailNim">-</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-slate-50">
                <span class="text-slate-400 font-medium">Fakultas / Program Studi</span>
                <span class="text-slate-900 font-semibold text-right" id="detailProdi">-</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-slate-50">
                <span class="text-slate-400 font-medium">Nomor WhatsApp</span>
                <span class="text-slate-900 font-semibold" id="detailPhone">-</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-slate-50">
                <span class="text-slate-400 font-medium">Peran Pengguna</span>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-600"
                    id="detailRole">Mahasiswa</span>
            </div>
            <div class="flex justify-between py-1.5 border-b border-slate-50">
                <span class="text-slate-400 font-medium">Status Akun</span>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold" id="detailStatusBadge">Aktif</span>
            </div>
            <div class="flex justify-between py-1.5">
                <span class="text-slate-400 font-medium">Terdaftar Sejak</span>
                <span class="text-slate-600" id="detailJoined">-</span>
            </div>
        </div>

        <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
            <form method="POST" action="kelola-pengguna.php"
                onsubmit="return confirm('Reset kata sandi pengguna ini menjadi: password123?')">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="detailUserIdReset" value="">
                <button type="submit"
                    class="px-3 py-2 text-xs font-medium text-amber-700 bg-amber-50 hover:bg-amber-100 rounded-xl transition-colors inline-flex items-center gap-1.5">
                    <i data-lucide="key" class="w-3.5 h-3.5"></i>
                    <span>Reset Sandi</span>
                </button>
            </form>
            <div class="flex items-center gap-2">
                <button type="button" onclick="toggleStatusFromModal()"
                    class="px-3 py-2 text-xs font-semibold rounded-xl transition-colors" id="detailToggleActionBtn">
                    Nonaktifkan
                </button>
                <button type="button" onclick="closeModal('modalDetailMahasiswa')"
                    class="px-4 py-2 text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL 3: KELOLA PETUGAS & ADMIN -->
<!-- ============================================================== -->
<div id="modalKelolaPetugas"
    class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div
        class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-slate-100 animate-in fade-in zoom-in-95">
        <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                    <i data-lucide="shield" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base">Kelola Akun Petugas &amp; Admin</h3>
                    <p class="text-xs text-slate-400">Edit hak akses, status, dan informasi petugas</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('modalKelolaPetugas')"
                class="text-slate-400 hover:text-slate-600 p-1">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form method="POST" action="kelola-pengguna.php" class="space-y-4">
            <input type="hidden" name="action" value="edit_pengguna">
            <input type="hidden" name="user_id" id="editUserId" value="">

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Lengkap</label>
                <input type="text" name="nama" id="editNama" required
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">NIP / NIM</label>
                    <input type="text" name="nim" id="editNim" required
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Peran Akun</label>
                    <select name="role" id="editRole"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                        <option value="petugas">Petugas Pos Keamanan</option>
                        <option value="admin">Admin Pusat</option>
                        <option value="mahasiswa">Mahasiswa</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Email</label>
                    <input type="email" name="email" id="editEmail" required
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">No. Telepon</label>
                    <input type="text" name="phone" id="editPhone"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Unit Kerja / Fakultas</label>
                <input type="text" name="prodi" id="editProdi" required
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Ganti Kata Sandi (Opsional)</label>
                    <input type="password" name="new_password" placeholder="Kosongkan jika tidak diubah"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Status Akun</label>
                    <select name="status" id="editStatus"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                        <option value="Aktif">Aktif</option>
                        <option value="Nonaktif">Nonaktif</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                <button type="button" onclick="deleteUserFromEditModal()"
                    class="px-3.5 py-2 text-xs font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-xl transition-colors inline-flex items-center gap-1.5">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    <span>Hapus Akun</span>
                </button>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="closeModal('modalKelolaPetugas')"
                        class="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit"
                        class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition-colors inline-flex items-center gap-1.5">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Hidden Delete Form -->
<form id="deleteUserForm" method="POST" action="kelola-pengguna.php" style="display:none;">
    <input type="hidden" name="action" value="hapus_pengguna">
    <input type="hidden" name="user_id" id="deleteUserIdInput" value="">
</form>

<!-- JavaScript Interactions -->
<script>
    let currentDetailUser = null;

    function openModal(id) {
        const m = document.getElementById(id);
        if (m) {
            m.classList.remove('hidden');
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    }

    function closeModal(id) {
        const m = document.getElementById(id);
        if (m) {
            m.classList.add('hidden');
        }
    }

    // 1. Toggle Status via AJAX
    function toggleStatus(userId, btnElement) {
        const currentActive = btnElement.getAttribute('data-active') === '1';

        // Optimistic UI toggle immediately
        const nextActive = !currentActive;
        const knob = document.getElementById(`toggle-knob-${userId}`);
        const label = document.getElementById(`status-label-${userId}`);
        const actionBtn = document.getElementById(`action-btn-${userId}`);

        applyToggleUI(userId, nextActive);

        // Send AJAX request
        const formData = new FormData();
        formData.append('ajax_action', 'toggle_status');
        formData.append('user_id', userId);

        fetch('kelola-pengguna.php', {
            method: 'POST',
            body: formData
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    applyToggleUI(userId, data.new_active === 1);
                    showToast(`Status akun ${data.user_name} berhasil diubah menjadi ${data.new_status}!`, 'success');
                } else {
                    // Revert on failure
                    applyToggleUI(userId, currentActive);
                    alert(data.message || 'Gagal mengubah status');
                }
            })
            .catch(err => {
                // Fallback to GET redirection
                window.location.href = `kelola-pengguna.php?action=toggle&id=${userId}`;
            });
    }

    function applyToggleUI(userId, isActive) {
        const btn = document.getElementById(`toggle-btn-${userId}`);
        const knob = document.getElementById(`toggle-knob-${userId}`);
        const label = document.getElementById(`status-label-${userId}`);
        const actionBtn = document.getElementById(`action-btn-${userId}`);

        if (btn) {
            btn.setAttribute('data-active', isActive ? '1' : '0');
            if (isActive) {
                btn.className = btn.className.replace('bg-slate-300', 'bg-emerald-500');
                if (knob) {
                    knob.className = knob.className.replace('translate-x-0', 'translate-x-4');
                }
                if (label) {
                    label.textContent = 'Aktif';
                    label.className = 'text-xs font-medium text-emerald-600 min-w-[50px]';
                }
                if (actionBtn && actionBtn.textContent.trim() === 'Aktifkan') {
                    actionBtn.textContent = 'Detail';
                    actionBtn.className = 'text-xs font-medium px-3.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors cursor-pointer';
                }
            } else {
                btn.className = btn.className.replace('bg-emerald-500', 'bg-slate-300');
                if (knob) {
                    knob.className = knob.className.replace('translate-x-4', 'translate-x-0');
                }
                if (label) {
                    label.textContent = 'Nonaktif';
                    label.className = 'text-xs font-medium text-slate-400 min-w-[50px]';
                }
                if (actionBtn && actionBtn.textContent.trim() === 'Detail') {
                    actionBtn.textContent = 'Aktifkan';
                    actionBtn.className = 'text-xs font-medium px-3.5 py-1.5 rounded-lg bg-emerald-100 hover:bg-emerald-200 text-emerald-700 transition-colors cursor-pointer';
                    actionBtn.setAttribute('onclick', `activateUser(${userId})`);
                }
            }
        }
    }

    // 2. Quick Activate
    function activateUser(userId) {
        const btn = document.getElementById(`toggle-btn-${userId}`);
        if (btn) {
            toggleStatus(userId, btn);
        } else {
            window.location.href = `kelola-pengguna.php?action=toggle&id=${userId}`;
        }
    }

    // 3. Open Detail Mahasiswa Modal
    function openDetailMahasiswa(user) {
        currentDetailUser = user;
        document.getElementById('detailNama').textContent = user.nama;
        document.getElementById('detailEmail').textContent = user.email;
        document.getElementById('detailNim').textContent = user.nim || '-';
        document.getElementById('detailProdi').textContent = user.prodi || '-';
        document.getElementById('detailPhone').textContent = user.phone || '-';
        document.getElementById('detailJoined').textContent = user.created_at || '-';
        document.getElementById('detailUserIdReset').value = user.id;

        // Initials Avatar
        const initials = user.nama.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
        document.getElementById('detailAvatar').textContent = initials || 'M';

        // Status Badge
        const statusBadge = document.getElementById('detailStatusBadge');
        const toggleActionBtn = document.getElementById('detailToggleActionBtn');

        if (user.is_active == 1) {
            statusBadge.textContent = 'Aktif';
            statusBadge.className = 'px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-600';
            toggleActionBtn.textContent = 'Nonaktifkan Akun';
            toggleActionBtn.className = 'px-3 py-2 text-xs font-semibold text-red-600 bg-red-50 hover:bg-red-100 rounded-xl transition-colors';
        } else {
            statusBadge.textContent = 'Nonaktif';
            statusBadge.className = 'px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-500';
            toggleActionBtn.textContent = 'Aktifkan Akun';
            toggleActionBtn.className = 'px-3 py-2 text-xs font-semibold text-emerald-600 bg-emerald-50 hover:bg-emerald-100 rounded-xl transition-colors';
        }

        openModal('modalDetailMahasiswa');
    }

    function toggleStatusFromModal() {
        if (currentDetailUser) {
            const btn = document.getElementById(`toggle-btn-${currentDetailUser.id}`);
            closeModal('modalDetailMahasiswa');
            if (btn) {
                toggleStatus(currentDetailUser.id, btn);
            } else {
                window.location.href = `kelola-pengguna.php?action=toggle&id=${currentDetailUser.id}`;
            }
        }
    }

    // 4. Open Kelola Petugas / Admin Modal
    function openKelolaPetugas(user) {
        document.getElementById('editUserId').value = user.id;
        document.getElementById('editNama').value = user.nama;
        document.getElementById('editNim').value = user.nim || '';
        document.getElementById('editEmail').value = user.email;
        document.getElementById('editPhone').value = user.phone || '';
        document.getElementById('editProdi').value = user.prodi || '';
        document.getElementById('editRole').value = user.role;
        document.getElementById('editStatus').value = user.status;

        openModal('modalKelolaPetugas');
    }

    function deleteUserFromEditModal() {
        const userId = document.getElementById('editUserId').value;
        const userName = document.getElementById('editNama').value;
        if (confirm(`Apakah Anda yakin ingin menghapus akun petugas "${userName}"? Tindakan ini tidak dapat dibatalkan.`)) {
            document.getElementById('deleteUserIdInput').value = userId;
            document.getElementById('deleteUserForm').submit();
        }
    }

    // Toast Helper
    function showToast(message, type = 'success') {
        const existing = document.getElementById('ajaxToast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.id = 'ajaxToast';
        toast.className = `fixed top-6 right-6 z-50 max-w-sm w-full bg-white rounded-xl shadow-modal border p-4 flex items-center gap-3 transition-all duration-300 animate-in fade-in slide-in-from-top-4 ${type === 'success' ? 'border-emerald-300' : 'border-red-300'}`;
        toast.innerHTML = `
        <div class="p-1 rounded-full ${type === 'success' ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600'}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        </div>
        <div class="flex-1 text-xs text-slate-700 font-medium">${message}</div>
    `;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    }
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>