#!/bin/bash
set -e

echo "========================================================"
echo "  SIPETA Kejaksaan - Ubuntu Server Setup & Test Script  "
echo "========================================================"

# 1. Pastikan berkas .env tersedia
if [ ! -f .env ]; then
    echo "[1/7] Berkas .env belum ditemukan, membuat dari .env.example..."
    cp .env.example .env
    echo ">> PENTING: Silakan edit berkas .env untuk menyesuaikan DB_DATABASE, DB_USERNAME, dan DB_PASSWORD."
else
    echo "[1/7] Berkas .env terdeteksi."
fi

# 2. Pasang dependensi Composer
echo "[2/7] Memasang dependensi Composer..."
composer install --no-interaction --prefer-dist --optimize-autoloader

# 3. Generate APP_KEY jika belum ada
if ! grep -q "^APP_KEY=base64:" .env; then
    echo "[3/7] Menghasilkan APP_KEY baru..."
    php artisan key:generate --force
else
    echo "[3/7] APP_KEY sudah terpasang."
fi

# 4. Storage symlink
echo "[4/7] Menghubungkan symlink storage..."
php artisan storage:link || true

# 5. Permission storage & cache
echo "[5/7] Mengatur izin akses direktori storage dan bootstrap/cache..."
chmod -R 775 storage bootstrap/cache
if id -u www-data >/dev/null 2>&1; then
    sudo chown -R www-data:www-data storage bootstrap/cache || chown -R www-data:www-data storage bootstrap/cache || true
fi

# 6. Migrasi database
echo "[6/7] Menjalankan migrasi database..."
php artisan migrate --force

# 7. Optimasi cache Laravel
echo "[7/7] Mengoptimasi cache konfigurasi, rute, dan tampilan..."
php artisan optimize

echo "========================================================"
echo "  Setup Berhasil! Sistem SIPETA Siap Dijalankan.       "
echo "========================================================"
echo "Perintah untuk verifikasi & pengujian di Ubuntu:"
echo "1. php artisan test                   # Verifikasi seluruh 72 test suite"
echo "2. php artisan sipeta:run-sla         # Verifikasi eksekusi cron SLA Engine"
echo "3. php artisan db:seed                # Isi akun Admin & Jaksa default jika DB baru"
