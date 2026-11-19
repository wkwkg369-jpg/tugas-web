<?php
require_once __DIR__ . '/includes/auth.php';
logout_user();
set_flash_message('info', 'Anda telah berhasil keluar dari akun.');
header('Location: masuk.php');
exit;
