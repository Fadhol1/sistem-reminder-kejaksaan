#!/bin/bash
set -e

echo "========================================================"
echo "  SIPETA Kejaksaan - Ubuntu Setup & Deployment Script  "
echo "========================================================"

# Cek apakah sistem menggunakan Docker Compose (Mode Rekomendasi)
if command -v docker >/dev/null 2>&1 && [ -f docker-compose.yml ]; then
    echo "[MODE DOCKER TERDETEKSI]"
    echo "Menggunakan docker-compose.yml untuk deployment..."

    # Pastikan .env tersedia
    if [ ! -f .env ]; then
        if [ -f .env.docker.example ]; then
            echo "Membuat berkas .env dari .env.docker.example..."
            cp .env.docker.example .env
        else
            echo "Membuat berkas .env dari .env.example..."
            cp .env.example .env
        fi
        echo ">> PENTING: Periksa dan sesuaikan variabel di .env jika diperlukan."
    fi

    # Build dan jalankan seluruh container (app, web, db, scheduler)
    echo "Membangun (rebuild) image dan menyalakan container..."
    docker compose up -d --build

    echo ""
    echo "Status Container:"
    docker compose ps

    echo ""
    echo "========================================================"
    echo "  Container Docker SIPETA Berhasil Dijalankan!         "
    echo "========================================================"
    echo "Perintah untuk pengujian di dalam container:"
    echo "1. docker compose exec app php artisan test           # Jalankan 72 unit & feature test"
    echo "2. docker compose exec app php artisan sipeta:run-sla # Jalankan SLA Engine manual"
    echo "3. docker compose logs -f app                         # Pantau log aplikasi"
    echo "4. docker compose logs -f scheduler                   # Pantau log background scheduler"
    exit 0
fi

# ========================================================
# Fallback Mode: Native Ubuntu (Tanpa Docker)
# ========================================================
echo "[MODE NATIVE HOST TERDETEKSI]"

if [ ! -f .env ]; then
    echo "[1/7] Berkas .env belum ditemukan, membuat dari .env.example..."
    cp .env.example .env
    echo ">> PENTING: Silakan sesuaikan konfigurasi database di .env."
else
    echo "[1/7] Berkas .env terdeteksi."
fi

echo "[2/7] Memasang dependensi Composer..."
composer install --no-interaction --prefer-dist --optimize-autoloader

if ! grep -q "^APP_KEY=base64:" .env; then
    echo "[3/7] Menghasilkan APP_KEY baru..."
    php artisan key:generate --force
else
    echo "[3/7] APP_KEY sudah terpasang."
fi

echo "[4/7] Menghubungkan symlink storage..."
php artisan storage:link || true

echo "[5/7] Mengatur izin akses direktori storage dan bootstrap/cache..."
chmod -R 775 storage bootstrap/cache
if id -u www-data >/dev/null 2>&1; then
    sudo chown -R www-data:www-data storage bootstrap/cache || chown -R www-data:www-data storage bootstrap/cache || true
fi

echo "[6/7] Menjalankan migrasi database..."
php artisan migrate --force

echo "[7/7] Mengoptimasi cache Laravel..."
php artisan optimize

echo "========================================================"
echo "  Setup Berhasil! Sistem SIPETA Siap Dijalankan.       "
echo "========================================================"
echo "Perintah pengujian:"
echo "1. php artisan test"
echo "2. php artisan sipeta:run-sla"
