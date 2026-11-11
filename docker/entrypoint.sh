#!/bin/sh
set -e

# Fungsi tunggu koneksi database siap
wait_for_db() {
    echo "Menunggu koneksi database ($DB_HOST:$DB_PORT)..."
    until php -r "
        try {
            \$pdo = new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));
            exit(0);
        } catch (\Throwable \$e) {
            exit(1);
        }
    " 2>/dev/null; do
        echo "Database belum siap. Menunggu 2 detik..."
        sleep 2
    done
    echo "Koneksi database berhasil tersambung!"
}

# Tunggu database jika variabel DB_HOST ada
if [ -n "$DB_HOST" ]; then
    wait_for_db
fi

# Jika container adalah app (menjalankan php-fpm)
if [ "$1" = "php-fpm" ]; then
    echo "Menjalankan inisialisasi aplikasi Laravel SIPETA..."

    # Pastikan direktori storage dan cache berizin www-data
    chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

    # Pastikan storage link tersedia
    php artisan storage:link || true

    # Jalankan database migrations
    echo "Menjalankan migrasi database..."
    php artisan migrate --force

    # Cache konfigurasi untuk performa produksi maksimal
    echo "Mengoptimasi cache Laravel (config, route, view)..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    echo "Aplikasi SIPETA siap menerima request!"
fi

exec "$@"

