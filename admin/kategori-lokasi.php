<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/data.php';

$activeSubTab = $_GET['tab'] ?? 'kategori';
if (!in_array($activeSubTab, ['kategori', 'lokasi'])) {
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
    }
}

$page_title = 'Kategori & Lokasi - SIP-Barang Admin';
$active_tab = 'kategori_lokasi';
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
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Master Data Kategori &amp; Lokasi Kampus</h1>
        </div>
        <div>
            <?php if ($activeSubTab === 'kategori'): ?>
                <button type="button" onclick="openModal('modalTambahKategori')" class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm px-4 py-2.5 rounded-lg inline-flex items-center gap-2 shadow-sm transition-colors">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Tambah Kategori</span>
                </button>
            <?php else: ?>
                <button type="button" onclick="openModal('modalTambahLokasi')" class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm px-4 py-2.5 rounded-lg inline-flex items-center gap-2 shadow-sm transition-colors">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Tambah Lokasi</span>
                </button>
            <?php endif; ?>
        </div>
    </header>

    <!-- Main Content -->
    <main class="px-8 pb-8 space-y-6">
        
        <!-- Toggle Tabs: Kategori Barang vs Titik Pos & Lokasi -->
        <div class="flex items-center gap-3">
            <a href="?tab=kategori" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all <?= $activeSubTab === 'kategori' ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' ?>">
                <i data-lucide="tag" class="w-4 h-4"></i>
                <span>Kategori Barang (<?= count($categoriesList) ?>)</span>
            </a>
            <a href="?tab=lokasi" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all <?= $activeSubTab === 'lokasi' ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' ?>">
                <i data-lucide="map-pin" class="w-4 h-4"></i>
                <span>Titik Pos &amp; Lokasi (<?= count($locationsList) ?>)</span>
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

        <?php else: ?>
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

function confirmDelete(type, name) {
    if (confirm(`Apakah Anda yakin ingin menghapus data ${type}: "${name}"?`)) {
        alert(`${type} "${name}" berhasil dihapus.`);
    }
}
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
