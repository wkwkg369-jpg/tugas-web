@echo off
title Menjalankan SIP-Barang Kampus (PHP Server)
echo ========================================================
echo   SIP-Barang Kampus - Lost and Found Web Application
echo ========================================================
echo.
echo Mencari instalasi PHP...

:: Cek Laragon PHP
if exist "C:\laragon\bin\php\php-8.3.36-Win32-vs16-x64\php.exe" (
    set PHP_BIN="C:\laragon\bin\php\php-8.3.36-Win32-vs16-x64\php.exe"
    goto RUN_SERVER
)

:: Cek PHP di PATH
where php >nul 2>nul
if %ERRORLEVEL% equ 0 (
    set PHP_BIN=php
    goto RUN_SERVER
)

:: Cek XAMPP PHP
if exist "C:\xampp\php\php.exe" (
    set PHP_BIN="C:\xampp\php\php.exe"
    goto RUN_SERVER
)

echo [ERROR] PHP tidak ditemukan di Laragon, XAMPP, atau PATH!
echo Silakan buka via web server Laragon/XAMPP Anda.
pause
exit /b 1

:RUN_SERVER
echo Server PHP berjalan di http://localhost:8000
echo Tekan Ctrl+C untuk menghentikan server.
echo.
start http://localhost:8000
%PHP_BIN% -S localhost:8000
