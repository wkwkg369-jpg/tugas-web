<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/data.php';

// Halaman admin WAJIB login sebagai admin/petugas.
// (Versi repo melakukan auto-login admin demo; itu dimatikan karena situs ini
//  dapat diakses publik, sehingga siapa pun bisa mengubah data.)
require_admin('../masuk.php');

$currentUser = get_logged_in_user();
if (!isset($page_title)) {
    $page_title = 'SIP-Barang Admin - Portal Petugas Keamanan';
}
if (!isset($active_tab)) {
    $active_tab = 'dashboard';
}

$flash = get_flash_message();
?>
<!DOCTYPE html>
<html lang="id" class="h-full bg-[#F8FAFC]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    
    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%232563eb' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z'/></svg>">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="../assets/vendor/tailwind.js"></script>
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
                            soft: '#dbeafe',
                        }
                    },
                    borderRadius: {
                        'btn': '10px',
                        'card': '16px',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/vendor/lucide.min.js"></script>
</head>
<body class="min-h-full flex flex-col font-sans bg-[#F8FAFC] text-slate-900 antialiased">
<div class="flex h-screen overflow-hidden">
