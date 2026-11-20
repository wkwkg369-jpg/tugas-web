<?php
/**
 * Contoh konfigurasi email. SALIN file ini menjadi includes/mail-config.php
 * di server, lalu isi kredensialnya. File mail-config.php TIDAK boleh masuk
 * repo publik (sudah diblokir lewat .gitignore).
 *
 * Untuk Gmail:
 *   1. Aktifkan verifikasi 2 langkah pada akun Google.
 *   2. Buka https://myaccount.google.com/apppasswords
 *   3. Buat "App password" (16 karakter). Itu yang dipakai di 'password'.
 *   4. Isi 'username' dengan alamat Gmail lengkap.
 */

return [
    // 'smtp' untuk benar-benar mengirim, 'demo' untuk mencatat ke logs/mail-demo.log
    'mode'       => 'smtp',

    'host'       => 'smtp.gmail.com',
    'port'       => 587,
    'secure'     => 'tls',   // 'tls' = STARTTLS 587, 'ssl' = TLS langsung 465

    'username'   => 'akun@gmail.com',
    'password'   => 'app-password-16-karakter',

    // Harus sama dengan username pada Gmail. Untuk layanan lain (Brevo,
    // Mailgun SMTP, SMTP kampus) isi alamat pengirim yang diizinkan.
    'from_email' => 'akun@gmail.com',
    'from_name'  => 'SIP-Barang Kampus',

    'timeout'    => 20,

    // Domain email yang boleh mendaftar. Default: ui.ac.id
    'allowed_email_domains' => ['ui.ac.id'],
];