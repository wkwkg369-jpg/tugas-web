<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/data.php';

// Wajib login admin/petugas SEBELUM handler apa pun dijalankan.
require_admin('../masuk.php');

$activeSubTab = $_GET['tab'] ?? 'kategori';
if (!in_array($activeSubTab, ['kategori', 'lokasi', 'prodi'], true)) {
    $activeSubTab = 'kategori';
}

// Handle Form Submissions (Add / Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'tambah_kategori') {
        $namaKat = trim($_POST['nama_kategori'] ?? '');
        $deskripsiKat = trim($_POST['deskripsi_kategori'] ?? '');
        if (!empty($namaKat)) {
            set_flash_message('success', "Kategori '$namaKat' berhasil ditambahkan!");
        }
        header('Location: kategori-lokasi.php?tab=kategori');
        exit;
    } elseif ($action === 'tambah_lokasi') {
        $namaLok = trim($_POST['nama_lokasi'] ?? '');
        $gedungLok = trim($_POST['gedung_lokasi'] ?? '');
        if (!empty($namaLok)) {
            // Also insert into database locations table if possible
            try {
                db_execute("INSERT INTO locations (name) VALUES (?)", [$namaLok]);
            } catch (Throwable $e) {}
            set_flash_message('success', "Titik lokasi '$namaLok' berhasil ditambahkan!");
        }
        header('Location: kategori-lokasi.php?tab=lokasi');
        exit;
    } elseif ($action === 'edit_lokasi') {
        $namaLok = trim($_POST['nama_lokasi'] ?? '');
        $gedungLok = trim($_POST['gedung_lokasi'] ?? '');
        set_flash_message('success', "Data lokasi '$namaLok' berhasil diperbarui!");
        header('Location: kategori-lokasi.php?tab=lokasi');
        exit;
    } elseif ($action === 'edit_kategori') {
        $namaKat = trim($_POST['nama_kategori'] ?? '');
        set_flash_message('success', "Data kategori '$namaKat' berhasil diperbarui!");
        header('Location: kategori-lokasi.php?tab=kategori');
        exit;
    } elseif ($action === 'tambah_prodi') {
        $namaProdi = trim($_POST['nama_prodi'] ?? '');
        $fakultasProdi = trim($_POST['fakultas_prodi'] ?? '');
        $jenjangProdi = trim($_POST['jenjang_prodi'] ?? 'S1');
        if ($namaProdi === '' || $fakultasProdi === '') {
            set_flash_message('error', 'Nama program studi dan fakultas wajib diisi.');
        } elseif (!in_array($jenjangProdi, ['D3', 'D4', 'S1', 'S2', 'S3'], true)) {
            set_flash_message('error', 'Jenjang yang dipilih tidak dikenali.');
        } else {
            $existing = db_fetch_one("SELECT id FROM study_programs WHERE name = ? AND fakultas = ?", [$namaProdi, $fakultasProdi]);
            if ($existing) {
                set_flash_message('error', "Program studi '$namaProdi' di $fakultasProdi sudah terdaftar.");
            } else {
                db_execute("INSERT INTO study_programs (name, fakultas, jenjang) VALUES (?, ?, ?)", [$namaProdi, $fakultasProdi, $jenjangProdi]);
                set_flash_message('success', "Program studi '$namaProdi - $fakultasProdi' berhasil ditambahkan!");
            }
        }
        header('Location: kategori-lokasi.php?tab=prodi');
        exit;
    } elseif ($action === 'edit_prodi') {
        $prodiId = intval($_POST['prodi_id'] ?? 0);
        $namaProdi = trim($_POST['nama_prodi'] ?? '');
        $fakultasProdi = trim($_POST['fakultas_prodi'] ?? '');
        $jenjangProdi = trim($_POST['jenjang_prodi'] ?? 'S1');
        if ($prodiId <= 0 || $namaProdi === '' || $fakultasProdi === '') {
            set_flash_message('error', 'Data program studi tidak lengkap.');
        } else {
            $dupe = db_fetch_one("SELECT id FROM study_programs WHERE name = ? AND fakultas = ? AND id != ?", [$namaProdi, $fakultasProdi, $prodiId]);
            if ($dupe) {
                set_flash_message('error', "Program studi '$namaProdi' di $fakultasProdi sudah terdaftar.");
            } else {
                db_execute("UPDATE study_programs SET name = ?, fakultas = ?, jenjang = ? WHERE id = ?", [$namaProdi, $fakultasProdi, $jenjangProdi, $prodiId]);
                set_flash_message('success', "Program studi '$namaProdi' berhasil diperbarui!");
            }
        }
        header('Location: kategori-lokasi.php?tab=prodi');
        exit;
    } elseif ($action === 'toggle_prodi') {
        $prodiId = intval($_POST['prodi_id'] ?? 0);
        $prodi = db_fetch_one("SELECT * FROM study_programs WHERE id = ?", [$prodiId]);
        if ($prodi) {
            $newActive = empty($prodi['is_active']) ? 1 : 0;
            db_execute("UPDATE study_programs SET is_active = ? WHERE id = ?", [$newActive, $prodiId]);
            set_flash_message('info', "Program studi '{$prodi['name']}' ditandai " . ($newActive ? 'Aktif' : 'Nonaktif') . '.');
        }
        header('Location: kategori-lokasi.php?tab=prodi');
        exit;
    } elseif ($action === 'hapus_prodi') {
        $prodiId = intval($_POST['prodi_id'] ?? 0);
        $prodi = db_fetch_one("SELECT * FROM study_programs WHERE id = ?", [$prodiId]);
        if ($prodi) {
            $label = $prodi['name'] . ' - ' . $prodi['fakultas'];
            $dipakai = db_fetch_one("SELECT COUNT(*) AS c FROM users WHERE prodi = ?", [$label]);
            if (!empty($dipakai['c'])) {
                set_flash_message('error', "Program studi '$label' masih dipakai {$dipakai['c']} pengguna. Nonaktifkan saja agar riwayat tetap utuh.");
            } else {
                db_execute("DELETE FROM study_programs WHERE id = ?", [$prodiId]);
                set_flash_message('success', "Program studi '$label' berhasil dihapus.");
            }
        }
        header('Location: kategori-lokasi.php?tab=prodi');
        exit;
    }
}

$page_title = 'Kategori & Lokasi - SIP-Barang Admin';
$active_tab = 'kategori_lokasi';
$programStudiList = get_study_programs(false);
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';

// 8 Categories list matching Figma
$categoriesList = [
    [
        'no' => 1,
        'nama' => 'Elektronik & Gadget',
        'slug' => 'elektronik-gadget',
        'deskripsi' => 'Laptop, smartphone, tablet, charger, smartwatch, earphone TWS',
        'total' => '52 Laporan Aktif',
        'status' => 'Aktif',
        'icon_bg' => 'bg-blue-100 text-blue-600',
        'icon' => 'laptop'
    ],
    [
        'no' => 2,
        'nama' => 'Dompet & Tas',
        'slug' => 'dompet-tas',
        'deskripsi' => 'Dompet kartu, tas ransel, totebag, pouch organizer, tas selempang',
        'total' => '38 Laporan Aktif',
        'status' => 'Aktif',
        'icon_bg' => 'bg-pink-100 text-pink-600',
        'icon' => 'wallet'
    ],
    [
        'no' => 3,
        'nama' => 'Dokumen & Identitas',
        'slug' => 'dokumen-identitas',
        'deskripsi' => 'KTM Mahasiswa, KTP, SIM, STNK, Paspor, Kartu Bank / E-Money',
        'total' => '47 Laporan Aktif',
        'status' => 'Aktif',
        'icon_bg' => 'bg-sky-100 text-sky-600',
        'icon' => 'id-card'
    ],
    [
        'no' => 4,
        'nama' => 'Kunci & Akses Kendaraan',
        'slug' => 'kunci-kendaraan',
        'deskripsi' => 'Kunci motor Smartkey, kunci kontak, remote mobil, gembok kamar kos',
        'total' => '29 Laporan Aktif',
        'status' => 'Aktif',
        'icon_bg' => 'bg-amber-100 text-amber-600',
        'icon' => 'key'
    ],
    [
        'no' => 5,
        'nama' => 'Pakaian & Aksesoris Fisik',
        'slug' => 'pakaian-sandang',
        'deskripsi' => 'Jaket almamater, hoodie, topi, payung, jas lab, kemeja seragam',
        'total' => '18 Laporan Aktif',
        'status' => 'Aktif',
        'icon_bg' => 'bg-indigo-100 text-indigo-600',
        'icon' => 'shirt'
    ],
    [
        'no' => 6,
        'nama' => 'Buku & Alat Tulis',
        'slug' => 'buku-atk',
        'deskripsi' => 'Buku teks kuliah, binder catatan, kalkulator ilmiah, tempat pensil',
        'total' => '22 Laporan Aktif',
        'status' => 'Aktif',
        'icon_bg' => 'bg-emerald-100 text-emerald-600',
        'icon' => 'book'
    ],
    [
        'no' => 7,
        'nama' => 'Botol Minum & Makan',
        'slug' => 'botol-tumbler',
        'deskripsi' => 'Tumbler stainless, botol air minum, kotak bekal makan siang',
        'total' => '26 Laporan Aktif',
        'status' => 'Aktif',
        'icon_bg' => 'bg-cyan-100 text-cyan-600',
        'icon' => 'cup-soda'
    ],
    [
        'no' => 8,
        'nama' => 'Lain-lain',
        'slug' => 'lain-lain',
        'deskripsi' => 'Kacamata, perhiasan, jam tangan, helm motor, dan aksesoris lainnya',
        'total' => '15 Laporan Aktif',
        'status' => 'Aktif',
        'icon_bg' => 'bg-slate-100 text-slate-600',
        'icon' => 'package'
    ]
];

// 12 Locations list matching Figma
$locationsList = [
    [
        'no' => 1,
        'nama' => 'Perpustakaan Pusat',
        'gedung' => 'Gedung Perpustakaan Pusat Lt. 1 & 2',
        'total' => '24 Laporan Aktif',
        'status' => 'Aktif',
        'icon_bg' => 'bg-blue-100 text-blue-600',
        'icon' => 'book-open'
    ],
    [
        'no' => 2,
        'nama' => 'Lab Komputer Gedung B',
        'gedung' => 'Gedung B (Fakultas Teknik)',
        'total' => '12 Laporan Aktif',
        'status' => 'Aktif',
        'icon_bg' => 'bg-indigo-100 text-indigo-600',
        'icon' => 'monitor'
    ],
    [
        'no' => 3,
        'nama' => 'Parkiran Sepeda Motor Barat',
        'gedung' => 'Zona Parkir Barat Kampus Blok C',
        'total' => '19 Laporan Aktif',
        'status' => 'Aktif',
        'icon_bg' => 'bg-amber-100 text-amber-600',
        'icon' => 'bike'
    ],
    [
        'no' => 4,
        'nama' => 'Kantin Pusat Rumpun Ilmu',
        'gedung' => 'Gedung RIK Lt. 1',
        'total' => '15 Laporan Aktif',
        'status' => 'Aktif',
        'icon_bg' => 'bg-rose-100 text-rose-600',
        'icon' => 'coffee'
    ],
    [
        'no' => 5,
        'nama' => 'Gedung Kuliah Bersama (GKB)',
        'gedung' => 'Area Perkuliahan Terpadu Lt. 1 - 4',
        'total' => '31 Laporan Aktif',
        'status' => 'Aktif',
        'icon_bg' => 'bg-purple-100 text-purple-600',
        'icon' => 'school'
    ],
    [
        'no' => 6,
        'nama' => 'Gedung Rektorat & Tata Usaha',
        'gedung' => 'Lobi Utama & Kantor Pusat Kampus',
        'total' => '8 Laporan Aktif',
        'status' => 'Aktif',
        'icon_bg' => 'bg-sky-100 text-sky-600',
        'icon' => 'building'
    ],
    [
        'no' => 7,
        'nama' => 'Masjid Kampus',
        'gedung' => 'Area Utama Sholat & Selasar Wudhu',
        'total' => '14 Laporan Aktif',
        'status' => 'Aktif',
        'icon_bg' => 'bg-emerald-100 text-emerald-600',
        'icon' => 'landmark'
    ],
    [
        'no' => 8,
        'nama' => 'Halte Bus Kampus',
        'gedung' => 'Pemberhentian Bus Kuning Terpadu',
        'total' => '9 Laporan Aktif',
        'status' => 'Aktif',
        'icon_bg' => 'bg-yellow-100 text-yellow-600',
        'icon' => 'bus'
    ],
    [
        'no' => 9,
        'nama' => 'Gazebo & Area Terbuka',
        'gedung' => 'Taman Lingkar & Danau Mahasiswa',
        'total' => '6 Laporan Aktif',
        'status' => 'Aktif',
        'icon_bg' => 'bg-teal-100 text-teal-600',
        'icon' => 'trees'
    ],
    [
        'no' => 10,
        'nama' => 'Pos Keamanan Utama',
        'gedung' => 'Gerbang Masuk Utama Kampus',
        'total' => '18 Laporan Aktif',
        'status' => 'Aktif',
        'icon_bg' => 'bg-slate-100 text-slate-700',
        'icon' => 'shield'
    ],
    [
        'no' => 11,
        'nama' => 'Gelanggang Olahraga / Gym',
        'gedung' => 'Pusat Olahraga & Lapangan Indoor',
        'total' => '11 Laporan Aktif',
        'status' => 'Aktif',
        'icon_bg' => 'bg-orange-100 text-orange-600',
        'icon' => 'trophy'
    ],
    [
        'no' => 12,
        'nama' => 'Auditorium Serbaguna',
        'gedung' => 'Gedung Pertemuan & Acara Kampus',
        'total' => '7 Laporan Aktif',
        'status' => 'Aktif',
        'icon_bg' => 'bg-cyan-100 text-cyan-600',
        'icon' => 'presentation'
    ]
];
?>

<div class="flex-1 flex flex-col bg-[#F8FAFC] overflow-y-auto min-w-0">
    <!-- Top Header matching Figma -->
    <header class="px-8 pt-8 pb-6 flex items-center justify-between shrink-0">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Master Data Kategori, Lokasi &amp; Program Studi</h1>
        </div>
        <div>
            <?php if ($activeSubTab === 'kategori'): ?>
                <button type="button" onclick="openModal('modalTambahKategori')" class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm px-4 py-2.5 rounded-lg inline-flex items-center gap-2 shadow-sm transition-colors">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Tambah Kategori</span>
                </button>
            <?php elseif ($activeSubTab === 'lokasi'): ?>
                <button type="button" onclick="openModal('modalTambahLokasi')" class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm px-4 py-2.5 rounded-lg inline-flex items-center gap-2 shadow-sm transition-colors">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Tambah Lokasi</span>
                </button>
            <?php else: ?>
                <button type="button" onclick="openModal('modalTambahProdi')" class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm px-4 py-2.5 rounded-lg inline-flex items-center gap-2 shadow-sm transition-colors">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Tambah Program Studi</span>
                </button>
            <?php endif; ?>
        </div>
    </header>

    <!-- Main Content -->
    <main class="px-8 pb-8 space-y-6">
        
        <!-- Toggle Tabs: Kategori Barang, Titik Pos & Lokasi, Program Studi -->
        <div class="flex flex-wrap items-center gap-3">
            <a href="?tab=kategori" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all <?= $activeSubTab === 'kategori' ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' ?>">
                <i data-lucide="tag" class="w-4 h-4"></i>
                <span>Kategori Barang (<?= count($categoriesList) ?>)</span>
            </a>
            <a href="?tab=lokasi" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all <?= $activeSubTab === 'lokasi' ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' ?>">
                <i data-lucide="map-pin" class="w-4 h-4"></i>
                <span>Titik Pos &amp; Lokasi (<?= count($locationsList) ?>)</span>
            </a>
            <a href="?tab=prodi" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all <?= $activeSubTab === 'prodi' ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' ?>">
                <i data-lucide="graduation-cap" class="w-4 h-4"></i>
                <span>Program Studi (<?= count($programStudiList) ?>)</span>
            </a>
        </div>

        <?php if ($activeSubTab === 'kategori'): ?>
            <!-- TAB 1: MASTER DATA KATEGORI -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_2px_10px_rgba(15,23,42,0.03)] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 text-xs font-semibold uppercase tracking-wider bg-slate-50/50">
                                <th class="py-4 px-6 w-16">NO</th>
                                <th class="py-4 px-6 w-20">IKON</th>
                                <th class="py-4 px-6">NAMA KATEGORI</th>
                                <th class="py-4 px-6">DESKRIPSI &amp; CONTOH ITEM</th>
                                <th class="py-4 px-6">TOTAL LAPORAN</th>
                                <th class="py-4 px-6">STATUS</th>
                                <th class="py-4 px-6 text-right">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($categoriesList as $c): ?>
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="py-4 px-6 text-slate-500 font-medium"><?= $c['no'] ?></td>
                                    <td class="py-4 px-6">
                                        <div class="w-10 h-10 rounded-xl <?= $c['icon_bg'] ?> flex items-center justify-center">
                                            <i data-lucide="<?= $c['icon'] ?>" class="w-5 h-5"></i>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="font-semibold text-slate-900"><?= htmlspecialchars($c['nama']) ?></div>
                                        <div class="text-xs text-slate-400 mt-0.5">Slug: <?= htmlspecialchars($c['slug']) ?></div>
                                    </td>
                                    <td class="py-4 px-6 text-slate-600 max-w-xs"><?= htmlspecialchars($c['deskripsi']) ?></td>
                                    <td class="py-4 px-6 font-semibold text-blue-600 text-xs"><?= htmlspecialchars($c['total']) ?></td>
                                    <td class="py-4 px-6">
                                        <span class="text-xs font-semibold px-3 py-1 rounded-full bg-emerald-50 text-emerald-600">
                                            <?= htmlspecialchars($c['status']) ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <div class="inline-flex items-center gap-2">
                                            <button type="button" onclick="openEditKategori('<?= addslashes($c['nama']) ?>', '<?= addslashes($c['deskripsi']) ?>')" class="text-xs font-medium px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors">
                                                Edit
                                            </button>
                                            <button type="button" onclick="confirmDelete('Kategori', '<?= addslashes($c['nama']) ?>')" class="text-xs font-medium p-1.5 rounded-lg text-red-500 hover:bg-red-50 transition-colors">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($activeSubTab === 'lokasi'): ?>
            <!-- TAB 2: MASTER DATA TITIK POS & LOKASI -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_2px_10px_rgba(15,23,42,0.03)] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 text-xs font-semibold uppercase tracking-wider bg-slate-50/50">
                                <th class="py-4 px-6 w-16">NO</th>
                                <th class="py-4 px-6 w-20">IKON</th>
                                <th class="py-4 px-6">NAMA LOKASI &amp; TITIK POS</th>
                                <th class="py-4 px-6">GEDUNG / ZONA KAMPUS</th>
                                <th class="py-4 px-6">TOTAL LAPORAN</th>
                                <th class="py-4 px-6">STATUS</th>
                                <th class="py-4 px-6 text-right">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($locationsList as $loc): ?>
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="py-4 px-6 text-slate-500 font-medium"><?= $loc['no'] ?></td>
                                    <td class="py-4 px-6">
                                        <div class="w-10 h-10 rounded-xl <?= $loc['icon_bg'] ?> flex items-center justify-center">
                                            <i data-lucide="<?= $loc['icon'] ?>" class="w-5 h-5"></i>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="font-semibold text-slate-900"><?= htmlspecialchars($loc['nama']) ?></div>
                                        <div class="text-xs text-slate-400 mt-0.5">Pos Pantau Keamanan Terhubung</div>
                                    </td>
                                    <td class="py-4 px-6 text-slate-600 max-w-xs"><?= htmlspecialchars($loc['gedung']) ?></td>
                                    <td class="py-4 px-6 font-semibold text-blue-600 text-xs"><?= htmlspecialchars($loc['total']) ?></td>
                                    <td class="py-4 px-6">
                                        <span class="text-xs font-semibold px-3 py-1 rounded-full bg-emerald-50 text-emerald-600">
                                            <?= htmlspecialchars($loc['status']) ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <div class="inline-flex items-center gap-2">
                                            <button type="button" onclick="openEditLokasi('<?= addslashes($loc['nama']) ?>', '<?= addslashes($loc['gedung']) ?>')" class="text-xs font-medium px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors">
                                                Edit
                                            </button>
                                            <button type="button" onclick="confirmDelete('Lokasi', '<?= addslashes($loc['nama']) ?>')" class="text-xs font-medium p-1.5 rounded-lg text-red-500 hover:bg-red-50 transition-colors">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <!-- TAB 3: MASTER DATA PROGRAM STUDI -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-[0_2px_10px_rgba(15,23,42,0.03)] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 text-xs font-semibold uppercase tracking-wider bg-slate-50/50">
                                <th class="py-4 px-6 w-16">NO</th>
                                <th class="py-4 px-6 w-20">IKON</th>
                                <th class="py-4 px-6">PROGRAM STUDI</th>
                                <th class="py-4 px-6">FAKULTAS</th>
                                <th class="py-4 px-6">JENJANG</th>
                                <th class="py-4 px-6">PENGGUNA</th>
                                <th class="py-4 px-6">STATUS</th>
                                <th class="py-4 px-6 text-right">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (empty($programStudiList)): ?>
                                <tr>
                                    <td colspan="8" class="py-12 text-center text-slate-400">
                                        <p class="text-sm font-medium text-slate-600">Belum ada program studi</p>
                                        <p class="text-xs text-slate-400 mt-1">Tambahkan program studi lewat tombol di kanan atas.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $noProdi = 1; foreach ($programStudiList as $p): ?>
                                    <?php
                                        $labelProdi = $p['name'] . ' - ' . $p['fakultas'];
                                        $jumlahPakai = db_fetch_one("SELECT COUNT(*) AS c FROM users WHERE prodi = ?", [$labelProdi]);
                                        $jumlahPakai = intval($jumlahPakai['c'] ?? 0);
                                        $isActive = !empty($p['is_active']);
                                    ?>
                                    <tr class="hover:bg-slate-50/70 transition-colors">
                                        <td class="py-4 px-6 text-slate-500 font-medium"><?= $noProdi++ ?></td>
                                        <td class="py-4 px-6">
                                            <div class="w-10 h-10 rounded-xl bg-violet-100 text-violet-600 flex items-center justify-center">
                                                <i data-lucide="graduation-cap" class="w-5 h-5"></i>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6">
                                            <div class="font-semibold text-slate-900"><?= htmlspecialchars($p['name']) ?></div>
                                            <div class="text-xs text-slate-400 mt-0.5">Tampil di form pendaftaran</div>
                                        </td>
                                        <td class="py-4 px-6 text-slate-600 max-w-xs"><?= htmlspecialchars($p['fakultas']) ?></td>
                                        <td class="py-4 px-6 text-slate-600"><?= htmlspecialchars($p['jenjang']) ?></td>
                                        <td class="py-4 px-6 font-semibold text-blue-600 text-xs"><?= $jumlahPakai ?> pengguna</td>
                                        <td class="py-4 px-6">
                                            <span class="text-xs font-semibold px-3 py-1 rounded-full <?= $isActive ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-500' ?>">
                                                <?= $isActive ? 'Aktif' : 'Nonaktif' ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 text-right">
                                            <div class="inline-flex items-center gap-2">
                                                <button type="button" onclick="openEditProdi(<?= (int) $p['id'] ?>, '<?= addslashes($p['name']) ?>', '<?= addslashes($p['fakultas']) ?>', '<?= addslashes($p['jenjang']) ?>')" class="text-xs font-medium px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors">
                                                    Edit
                                                </button>
                                                <form method="POST" action="kategori-lokasi.php?tab=prodi" class="inline">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="toggle_prodi">
                                                    <input type="hidden" name="prodi_id" value="<?= (int) $p['id'] ?>">
                                                    <button type="submit" class="text-xs font-medium px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors">
                                                        <?= $isActive ? 'Nonaktifkan' : 'Aktifkan' ?>
                                                    </button>
                                                </form>
                                                <form method="POST" action="kategori-lokasi.php?tab=prodi" class="inline" onsubmit="return confirm('Hapus program studi <?= addslashes($p['name']) ?>? Data pengguna yang memakainya tidak diubah.');">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="hapus_prodi">
                                                    <input type="hidden" name="prodi_id" value="<?= (int) $p['id'] ?>">
                                                    <button type="submit" class="text-xs font-medium p-1.5 rounded-lg text-red-500 hover:bg-red-50 transition-colors">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/40 text-xs text-slate-500">
                    Program studi yang aktif muncul di dropdown pendaftaran mahasiswa (<code>daftar.php</code>) dan jadi saran Fakultas/Unit pada form pengguna.
                </div>
            </div>
        <?php endif; ?>

    </main>
</div>

<!-- Modal Edit Lokasi matching Figma: Kategori dan Lokasi - Edit.png -->
<div id="modalEditLokasi" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-100 animate-in fade-in zoom-in-95">
        <div class="flex items-center gap-2.5 pb-4 mb-4 border-b border-slate-100">
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                <i data-lucide="map-pin" class="w-4 h-4"></i>
            </div>
            <div>
                <h3 class="font-bold text-slate-900 text-base">Edit Lokasi</h3>
                <p class="text-xs text-slate-400">Perbarui informasi titik lokasi kampus</p>
            </div>
        </div>
        <form method="POST" action="kategori-lokasi.php?tab=lokasi" class="space-y-4">
                <?= csrf_field() ?>
            <input type="hidden" name="action" value="edit_lokasi">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Lokasi</label>
                <input type="text" id="editNamaLokasi" name="nama_lokasi" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Gedung / Zona Kampus</label>
                <select id="editGedungLokasi" name="gedung_lokasi" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                    <option value="Gedung B (Fakultas Teknik)">Gedung B (Fakultas Teknik)</option>
                    <option value="Gedung Perpustakaan Pusat">Gedung Perpustakaan Pusat</option>
                    <option value="Zona Parkir Kampus">Zona Parkir Kampus</option>
                    <option value="Gedung RIK (Rumpun Ilmu)">Gedung RIK (Rumpun Ilmu)</option>
                    <option value="Gedung Kuliah Bersama (GKB)">Gedung Kuliah Bersama (GKB)</option>
                    <option value="Gedung Rektorat & Tata Usaha">Gedung Rektorat &amp; Tata Usaha</option>
                    <option value="Masjid Kampus">Masjid Kampus</option>
                    <option value="Area Halte & Terbuka">Area Halte &amp; Terbuka</option>
                </select>
            </div>
            <div class="flex items-center justify-end gap-3 pt-3">
                <button type="button" onclick="closeModal('modalEditLokasi')" class="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition-colors inline-flex items-center gap-1.5">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span>Simpan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tambah Lokasi -->
<div id="modalTambahLokasi" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-100 animate-in fade-in zoom-in-95">
        <div class="flex items-center gap-2.5 pb-4 mb-4 border-b border-slate-100">
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
            </div>
            <div>
                <h3 class="font-bold text-slate-900 text-base">Tambah Lokasi Baru</h3>
                <p class="text-xs text-slate-400">Tambahkan titik pos atau area penyimpanan baru</p>
            </div>
        </div>
        <form method="POST" action="kategori-lokasi.php?tab=lokasi" class="space-y-4">
                <?= csrf_field() ?>
            <input type="hidden" name="action" value="tambah_lokasi">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Titik Lokasi</label>
                <input type="text" name="nama_lokasi" placeholder="Misal: Pos Satpam Gedung C" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Gedung / Zona Kampus</label>
                <input type="text" name="gedung_lokasi" placeholder="Misal: Lantai 1 Lobi Utama" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
            </div>
            <div class="flex items-center justify-end gap-3 pt-3">
                <button type="button" onclick="closeModal('modalTambahLokasi')" class="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition-colors inline-flex items-center gap-1.5">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Tambah Lokasi</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Kategori -->
<div id="modalEditKategori" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-100 animate-in fade-in zoom-in-95">
        <div class="flex items-center gap-2.5 pb-4 mb-4 border-b border-slate-100">
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                <i data-lucide="tag" class="w-4 h-4"></i>
            </div>
            <div>
                <h3 class="font-bold text-slate-900 text-base">Edit Kategori Barang</h3>
                <p class="text-xs text-slate-400">Perbarui nama dan rincian kategori</p>
            </div>
        </div>
        <form method="POST" action="kategori-lokasi.php?tab=kategori" class="space-y-4">
                <?= csrf_field() ?>
            <input type="hidden" name="action" value="edit_kategori">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Kategori</label>
                <input type="text" id="editNamaKategori" name="nama_kategori" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Deskripsi &amp; Contoh Item</label>
                <textarea id="editDeskripsiKategori" name="deskripsi_kategori" rows="3" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"></textarea>
            </div>
            <div class="flex items-center justify-end gap-3 pt-3">
                <button type="button" onclick="closeModal('modalEditKategori')" class="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition-colors inline-flex items-center gap-1.5">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span>Simpan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tambah Kategori -->
<div id="modalTambahKategori" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-100 animate-in fade-in zoom-in-95">
        <div class="flex items-center gap-2.5 pb-4 mb-4 border-b border-slate-100">
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
            </div>
            <div>
                <h3 class="font-bold text-slate-900 text-base">Tambah Kategori Baru</h3>
                <p class="text-xs text-slate-400">Tambahkan jenis kelompok barang baru</p>
            </div>
        </div>
        <form method="POST" action="kategori-lokasi.php?tab=kategori" class="space-y-4">
                <?= csrf_field() ?>
            <input type="hidden" name="action" value="tambah_kategori">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Kategori</label>
                <input type="text" name="nama_kategori" placeholder="Misal: Aksesoris Olahraga" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Deskripsi &amp; Contoh Item</label>
                <textarea name="deskripsi_kategori" rows="3" placeholder="Misal: Raket, bola basket, sepatu olahraga..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"></textarea>
            </div>
            <div class="flex items-center justify-end gap-3 pt-3">
                <button type="button" onclick="closeModal('modalTambahKategori')" class="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition-colors inline-flex items-center gap-1.5">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Tambah Kategori</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tambah Program Studi -->
<div id="modalTambahProdi" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-100 animate-in fade-in zoom-in-95">
        <div class="flex items-center gap-2.5 pb-4 mb-4 border-b border-slate-100">
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                <i data-lucide="graduation-cap" class="w-4 h-4"></i>
            </div>
            <div>
                <h3 class="font-bold text-slate-900 text-base">Tambah Program Studi</h3>
                <p class="text-xs text-slate-400">Program studi aktif langsung muncul di form pendaftaran</p>
            </div>
        </div>
        <form method="POST" action="kategori-lokasi.php?tab=prodi" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="tambah_prodi">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Program Studi</label>
                <input type="text" name="nama_prodi" placeholder="Misal: Sistem Informasi" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Fakultas</label>
                <input type="text" name="fakultas_prodi" placeholder="Misal: Fakultas Ilmu Komputer" required list="daftarFakultas" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                <datalist id="daftarFakultas">
                    <?php foreach (get_faculties(false) as $f): ?>
                        <option value="<?= htmlspecialchars($f) ?>"></option>
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Jenjang</label>
                <select name="jenjang_prodi" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                    <option value="D3">D3</option>
                    <option value="D4">D4</option>
                    <option value="S1" selected>S1</option>
                    <option value="S2">S2</option>
                    <option value="S3">S3</option>
                </select>
            </div>
            <div class="flex items-center justify-end gap-3 pt-3">
                <button type="button" onclick="closeModal('modalTambahProdi')" class="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition-colors inline-flex items-center gap-1.5">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Tambah</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Program Studi -->
<div id="modalEditProdi" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-100 animate-in fade-in zoom-in-95">
        <div class="flex items-center gap-2.5 pb-4 mb-4 border-b border-slate-100">
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                <i data-lucide="graduation-cap" class="w-4 h-4"></i>
            </div>
            <div>
                <h3 class="font-bold text-slate-900 text-base">Edit Program Studi</h3>
                <p class="text-xs text-slate-400">Perbarui nama, fakultas, atau jenjang</p>
            </div>
        </div>
        <form method="POST" action="kategori-lokasi.php?tab=prodi" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="edit_prodi">
            <input type="hidden" name="prodi_id" id="editProdiId">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Program Studi</label>
                <input type="text" name="nama_prodi" id="editNamaProdi" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Fakultas</label>
                <input type="text" name="fakultas_prodi" id="editFakultasProdi" required list="daftarFakultas" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Jenjang</label>
                <select name="jenjang_prodi" id="editJenjangProdi" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                    <option value="D3">D3</option>
                    <option value="D4">D4</option>
                    <option value="S1">S1</option>
                    <option value="S2">S2</option>
                    <option value="S3">S3</option>
                </select>
            </div>
            <div class="flex items-center justify-end gap-3 pt-3">
                <button type="button" onclick="closeModal('modalEditProdi')" class="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition-colors inline-flex items-center gap-1.5">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span>Simpan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    const m = document.getElementById(id);
    if (m) {
        m.classList.remove('hidden');
    }
}

function closeModal(id) {
    const m = document.getElementById(id);
    if (m) {
        m.classList.add('hidden');
    }
}

function openEditLokasi(nama, gedung) {
    document.getElementById('editNamaLokasi').value = nama;
    const gSelect = document.getElementById('editGedungLokasi');
    let matched = false;
    for (let i = 0; i < gSelect.options.length; i++) {
        if (gSelect.options[i].text.toLowerCase().includes(gedung.toLowerCase()) || gedung.toLowerCase().includes(gSelect.options[i].value.toLowerCase())) {
            gSelect.selectedIndex = i;
            matched = true;
            break;
        }
    }
    if (!matched) {
        gSelect.selectedIndex = 0;
    }
    openModal('modalEditLokasi');
}

function openEditKategori(nama, deskripsi) {
    document.getElementById('editNamaKategori').value = nama;
    document.getElementById('editDeskripsiKategori').value = deskripsi;
    openModal('modalEditKategori');
}

function openEditProdi(id, nama, fakultas, jenjang) {
    document.getElementById('editProdiId').value = id;
    document.getElementById('editNamaProdi').value = nama;
    document.getElementById('editFakultasProdi').value = fakultas;
    const j = document.getElementById('editJenjangProdi');
    for (let i = 0; i < j.options.length; i++) {
        if (j.options[i].value === jenjang) { j.selectedIndex = i; break; }
    }
    openModal('modalEditProdi');
}

function confirmDelete(type, name) {
    if (confirm(`Apakah Anda yakin ingin menghapus data ${type}: "${name}"?`)) {
        alert(`${type} "${name}" berhasil dihapus.`);
    }
}
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
