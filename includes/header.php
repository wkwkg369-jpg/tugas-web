<?php
if (!isset($page_title)) {
    $page_title = 'SIP-Barang Kampus - Layanan Kehilangan & Temuan Barang';
}
if (!isset($current_page)) {
    $current_page = '';
}
require_once __DIR__ . '/data.php';
?>
<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    
    <!-- Favicon SVG -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%232563eb' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z'/></svg>">
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS (Offline-ready with Local Vendor & CDN Fallback) -->
    <script src="assets/vendor/tailwind.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            DEFAULT: '#2563eb',
                            hover: '#1d4ed8',
                            pressed: '#1e40af',
                            soft: '#dbeafe',
                        },
                        status: {
                            hilang: '#ef4444',
                            'hilang-soft': '#fee2e2',
                            ditemukan: '#10b981',
                            'ditemukan-soft': '#d1fae5',
                            verifikasi: '#f59e0b',
                            'verifikasi-soft': '#fef3c7',
                            dikembalikan: '#6366f1',
                            'dikembalikan-soft': '#e0e7ff',
                            ditutup: '#94a3b8',
                            'ditutup-soft': '#f1f5f9'
                        }
                    },
                    borderRadius: {
                        'btn': '10px',
                        'input': '10px',
                        'card': '16px',
                        'pill': '999px',
                    },
                    boxShadow: {
                        'card': '0 1px 3px rgba(15, 23, 42, 0.08), 0 1px 2px rgba(15, 23, 42, 0.04)',
                    }
                }
            }
        }
    </script>

    <!-- Custom Design System Styles -->
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- Lucide Icons (Offline-ready) -->
    <script src="assets/vendor/lucide.min.js"></script>
</head>
<body class="min-h-full flex flex-col font-sans bg-slate-50 text-slate-900 antialiased">
