#!/usr/bin/env bash
set -euo pipefail

if [[ $# -lt 1 ]]; then
  echo "Usage: DB_PASS=... $0 /path/to/xl_migration_YYYYmmdd_HHMMSS" >&2
  exit 1
fi

BACKUP_DIR="$1"
APP_ROOT="${APP_ROOT:-/www/wwwroot/lt/lottery_xl/xl}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_NAME="${DB_NAME:-stock_datas}"
DB_USER="${DB_USER:-stock_datas}"
DB_PASS="${DB_PASS:-}"
RESTORE_CODE="${RESTORE_CODE:-1}"

if [[ -z "$DB_PASS" ]]; then
  echo "DB_PASS is required" >&2
  exit 1
fi

if [[ "$RESTORE_CODE" == "1" ]]; then
  mkdir -p "$APP_ROOT"
  tar -xzf "$BACKUP_DIR/app-files.tgz" -C "$APP_ROOT"
fi

gzip -dc "$BACKUP_DIR/${DB_NAME}.sql.gz" | mysql \
  --host="$DB_HOST" \
  --user="$DB_USER" \
  --password="$DB_PASS" \
  --default-character-set=utf8mb4 \
  "$DB_NAME"

cd "$APP_ROOT"

if command -v composer >/dev/null 2>&1; then
  composer install --no-dev --optimize-autoloader
fi

bash deploy/scripts/install-runtime-dirs.sh

if command -v supervisorctl >/dev/null 2>&1; then
  supervisorctl reread || true
  supervisorctl update || true
  supervisorctl restart xl_queue:* xl_queue_fast:* xl_queue_open:* || true
fi

echo "restore completed"
