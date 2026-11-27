#!/usr/bin/env bash
set -euo pipefail

# ==============================================================================
# SIPETA Kejaksaan - Automated Database (& Optional Storage) Backup to Google Drive
# Aman disimpan di GitHub: tidak mengandung token, password, atau kredensial.
# ==============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
TMP_DIR="${BACKUP_TMP_DIR:-${APP_DIR}/storage/app/backups}"
GDRIVE_REMOTE="${GDRIVE_REMOTE:-gdrive:Backup-SIPETA}"
RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-90}"
INCLUDE_STORAGE="${BACKUP_INCLUDE_STORAGE:-false}"

for arg in "$@"; do
    case "$arg" in
        --with-storage)
            INCLUDE_STORAGE="true"
            ;;
        --help|-h)
            echo "Penggunaan: $0 [--with-storage]"
            echo "Env opsional:"
            echo "  GDRIVE_REMOTE          Remote tujuan rclone (default: gdrive:Backup-SIPETA)"
            echo "  BACKUP_RETENTION_DAYS  Hapus backup Drive lebih lama dari N hari (default: 90, 0 = nonaktif)"
            echo "  BACKUP_INCLUDE_STORAGE true/false untuk ikut backup file upload public (default: false)"
            exit 0
            ;;
        *)
            echo "Argumen tidak dikenal: $arg" >&2
            exit 1
            ;;
    esac
done

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"
}

if ! command -v docker >/dev/null 2>&1; then
    echo "ERROR: docker tidak ditemukan di PATH." >&2
    exit 1
fi

if ! command -v rclone >/dev/null 2>&1; then
    echo "ERROR: rclone tidak ditemukan. Install terlebih dahulu (sudo apt install -y rclone)." >&2
    exit 1
fi

cd "$APP_DIR"

if [ ! -f ".env" ]; then
    echo "ERROR: File .env tidak ditemukan di ${APP_DIR}." >&2
    exit 1
fi

# Baca variabel DB dari .env secara aman (tahan CRLF Windows & spasi pada baris lain)
read_env_var() {
    local key="$1"
    local line
    line="$(grep -E "^${key}=" .env | tail -n 1 || true)"
    line="${line%$'\r'}"
    local val="${line#*=}"
    if [[ "$val" =~ ^\"(.*)\"$ ]] || [[ "$val" =~ ^\'(.*)\'$ ]]; then
        val="${BASH_REMATCH[1]}"
    fi
    printf '%s' "$val"
}

DB_DATABASE="$(read_env_var DB_DATABASE)"
DB_USERNAME="$(read_env_var DB_USERNAME)"
DB_PASSWORD="$(read_env_var DB_PASSWORD)"

if [ -z "${DB_DATABASE:-}" ] || [ -z "${DB_USERNAME:-}" ] || [ -z "${DB_PASSWORD:-}" ]; then
    echo "ERROR: DB_DATABASE, DB_USERNAME, atau DB_PASSWORD belum diatur di .env." >&2
    exit 1
fi

mkdir -p "$TMP_DIR"

DATE_STAMP="$(date +%Y%m%d-%H%M%S)"
MONTH_FOLDER="$(date +%Y-%m)"
DB_FILE="sipeta-db-${DATE_STAMP}.sql.gz"
DB_PATH="${TMP_DIR}/${DB_FILE}"
STORAGE_FILE="sipeta-storage-${DATE_STAMP}.tar.gz"
STORAGE_PATH="${TMP_DIR}/${STORAGE_FILE}"

cleanup() {
    rm -f "$DB_PATH" "$STORAGE_PATH"
}
trap cleanup EXIT

log "Memulai backup database '${DB_DATABASE}' dari container db..."

docker compose exec -T db mariadb-dump \
    -u"${DB_USERNAME}" \
    -p"${DB_PASSWORD}" \
    --single-transaction \
    --routines \
    --triggers \
    "${DB_DATABASE}" | gzip -9 > "$DB_PATH"

if [ ! -s "$DB_PATH" ] || ! gzip -t "$DB_PATH" 2>/dev/null; then
    echo "ERROR: File backup database kosong atau korup. Upload dibatalkan." >&2
    exit 1
fi

log "Mengunggah backup database ke ${GDRIVE_REMOTE}/database/${MONTH_FOLDER}/${DB_FILE}..."
rclone move "$DB_PATH" "${GDRIVE_REMOTE}/database/${MONTH_FOLDER}/" --transfers 1

if [ "$INCLUDE_STORAGE" = "true" ]; then
    log "Mengarsipkan file upload (storage/app/public) dari container app..."
    docker compose exec -T app tar czf - -C /var/www/storage/app/public . > "$STORAGE_PATH"

    if [ ! -s "$STORAGE_PATH" ] || ! gzip -t "$STORAGE_PATH" 2>/dev/null; then
        echo "ERROR: File arsip storage kosong atau korup. Upload storage dibatalkan." >&2
        exit 1
    fi

    log "Mengunggah backup storage ke ${GDRIVE_REMOTE}/storage/${MONTH_FOLDER}/${STORAGE_FILE}..."
    rclone move "$STORAGE_PATH" "${GDRIVE_REMOTE}/storage/${MONTH_FOLDER}/" --transfers 1
fi

if [ "${RETENTION_DAYS}" -gt 0 ] 2>/dev/null; then
    log "Membersihkan backup Google Drive yang lebih lama dari ${RETENTION_DAYS} hari..."
    rclone delete "${GDRIVE_REMOTE}/database/" --min-age "${RETENTION_DAYS}d" --rmdirs || true
    if [ "$INCLUDE_STORAGE" = "true" ]; then
        rclone delete "${GDRIVE_REMOTE}/storage/" --min-age "${RETENTION_DAYS}d" --rmdirs || true
    fi
fi

log "Backup ke Google Drive selesai. File sementara lokal telah dibersihkan."

