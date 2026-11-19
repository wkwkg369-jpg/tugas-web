<?php
/**
 * Data Service & Helper Functions untuk SIP-Barang Kampus
 * Terintegrasi langsung dengan Database (MySQL / SQLite).
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

// Kategori Barang
function get_categories(): array {
    $rows = db_fetch_all("SELECT id, name, slug, icon FROM categories ORDER BY id ASC");
    $result = [
        ['id' => 'all', 'name' => 'Semua Kategori', 'slug' => 'all', 'icon' => 'grid']
    ];
    foreach ($rows as $r) {
        $result[] = [
            'id' => $r['slug'],
            'name' => $r['name'],
            'slug' => $r['slug'],
            'icon' => $r['icon']
        ];
    }
    return $result;
}

// Lokasi Kampus
function get_locations(): array {
    $rows = db_fetch_all("SELECT name FROM locations ORDER BY id ASC");
    $locs = ['Semua Lokasi Kampus'];
    foreach ($rows as $r) {
        $locs[] = $r['name'];
    }
    return $locs;
}

// Data Semua Barang (Katalog & Beranda)
function get_mock_items(): array {
    return get_items();
}

function get_items(array $filters = []): array {
    $sql = "SELECT * FROM items WHERE 1=1";
    $params = [];

    if (!empty($filters['q'])) {
        $sql .= " AND (nama LIKE ? OR deskripsi LIKE ? OR ciri_umum LIKE ? OR lokasi LIKE ? OR kode LIKE ?)";
        $term = '%' . $filters['q'] . '%';
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }

    if (!empty($filters['status']) && $filters['status'] !== 'all') {
        $sql .= " AND status = ?";
        $params[] = $filters['status'];
    }

    if (!empty($filters['kategori']) && $filters['kategori'] !== 'all') {
        $sql .= " AND (kategori_slug = ? OR kategori = ?)";
        $params[] = $filters['kategori'];
        $params[] = $filters['kategori'];
    }

    if (!empty($filters['lokasi']) && $filters['lokasi'] !== 'Semua Lokasi Kampus') {
        $sql .= " AND lokasi LIKE ?";
        $params[] = '%' . $filters['lokasi'] . '%';
    }

    if (!empty($filters['jenis'])) {
        $sql .= " AND jenis_laporan = ?";
        $params[] = $filters['jenis'];
    }

    $sql .= " ORDER BY id DESC";

    if (!empty($filters['limit'])) {
        $sql .= " LIMIT " . intval($filters['limit']);
    }

    $items = db_fetch_all($sql, $params);

    // Format item fields to guarantee expected keys exist
    return array_map(function($it) {
        return format_item_data($it);
    }, $items);
}

function get_item_by_id($id): ?array {
    $item = db_fetch_one("SELECT * FROM items WHERE id = ?", [intval($id)]);
    return $item ? format_item_data($item) : null;
}

function get_item_by_kode(string $kode): ?array {
    $item = db_fetch_one("SELECT * FROM items WHERE kode = ?", [$kode]);
    return $item ? format_item_data($item) : null;
}

function format_item_data(array $it): array {
    $it['id'] = intval($it['id']);
    $it['lokasi_singkat'] = $it['lokasi_singkat'] ?: $it['lokasi'];
    $it['waktu_singkat'] = $it['waktu_kejadian'];
    $it['waktu'] = $it['waktu_kejadian'];
    $it['pelapor'] = $it['pelapor_nama'] ?? 'Civitas Kampus';
    $it['icon'] = $it['icon'] ?: 'package';
    $it['warna_placeholder'] = $it['warna_placeholder'] ?: '#dbeafe';
    return $it;
}

// Data Notifikasi
function get_mock_notifications(?int $userId = null): array {
    if ($userId === null) {
        $user = get_logged_in_user();
        $userId = $user ? $user['id'] : null;
    }

    if (!$userId) {
        return [];
    }

    $rows = db_fetch_all("SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC", [$userId]);
    return array_map(function($n) {
        return [
            'id' => intval($n['id']),
            'judul' => $n['judul'],
            'pesan' => $n['pesan'],
            'waktu' => format_relative_time($n['created_at']),
            'unread' => (bool)$n['is_unread'],
            'icon' => $n['icon'] ?: 'bell',
            'link' => $n['link'] ?? '#'
        ];
    }, $rows);
}

function get_unread_notifications_count(?int $userId = null): int {
    if ($userId === null) {
        $user = get_logged_in_user();
        if (!$user) return 0;
        $userId = $user['id'];
    }

    $res = db_fetch_one("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_unread = 1", [$userId]);
    return intval($res['unread_count'] ?? 0);
}

function mark_all_notifications_read(?int $userId = null): void {
    if ($userId === null) {
        $user = get_logged_in_user();
        if (!$user) return;
        $userId = $user['id'];
    }
    db_execute("UPDATE notifications SET is_unread = 0 WHERE user_id = ?", [$userId]);
}

// User Reports & Claims
function get_user_reports(?int $userId = null): array {
    if ($userId === null) {
        $user = get_logged_in_user();
        $userId = $user ? $user['id'] : null;
    }

    if ($userId) {
        $rows = db_fetch_all("SELECT * FROM items WHERE pelapor_id = ? ORDER BY id DESC", [$userId]);
        return array_map('format_item_data', $rows);
    }

    return [];
}

function get_user_claims(?int $userId = null): array {
    if ($userId === null) {
        $user = get_logged_in_user();
        $userId = $user ? $user['id'] : null;
    }

    if (!$userId) {
        return [];
    }

    $sql = "SELECT c.*, i.nama as item_nama, i.kategori as item_kategori, i.kode as item_kode, i.icon as item_icon 
            FROM claims c 
            JOIN items i ON c.item_id = i.id
            WHERE c.user_id = ?
            ORDER BY c.id DESC";

    return db_fetch_all($sql, [$userId]);
}

// Relative time formatting helper
function format_relative_time(string $datetimeStr): string {
    $time = strtotime($datetimeStr);
    if (!$time) return $datetimeStr;
    $diff = time() - $time;
    if ($diff < 60) return 'Baru saja';
    if ($diff < 3600) return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
    if ($diff < 172800) return 'Kemarin';
    return date('d M Y', $time);
}

// Helper Render Badge Status
function render_status_badge($status, $size = 'normal'): string {
    $classes = [
        'Hilang' => 'bg-red-50 text-red-600 border border-red-200',
        'Ditemukan' => 'bg-emerald-50 text-emerald-600 border border-emerald-200',
        'Dalam Verifikasi' => 'bg-amber-50 text-amber-600 border border-amber-200',
        'Dikembalikan' => 'bg-indigo-50 text-indigo-600 border border-indigo-200',
        'Ditutup' => 'bg-slate-100 text-slate-500 border border-slate-200'
    ];
    
    $dotClasses = [
        'Hilang' => 'bg-red-500',
        'Ditemukan' => 'bg-emerald-500',
        'Dalam Verifikasi' => 'bg-amber-500',
        'Dikembalikan' => 'bg-indigo-500',
        'Ditutup' => 'bg-slate-400'
    ];
    
    $cls = $classes[$status] ?? 'bg-slate-100 text-slate-600 border border-slate-200';
    $dotCls = $dotClasses[$status] ?? 'bg-slate-400';
    $padding = $size === 'sm' ? 'px-2 py-0.5 text-xs' : 'px-2.5 py-1 text-xs md:text-sm';

    return '<span class="inline-flex items-center gap-1.5 font-medium rounded-full ' . $padding . ' ' . $cls . '">
        <span class="w-1.5 h-1.5 rounded-full ' . $dotCls . '"></span>
        ' . htmlspecialchars($status) . '
    </span>';
}
