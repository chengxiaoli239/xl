#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_ROOT="${APP_ROOT:-$(cd "$SCRIPT_DIR/../.." && pwd)}"
BACKUP_BASE="${BACKUP_BASE:-/www/backup}"
STAMP="$(date +%Y%m%d_%H%M%S)"
OUT_DIR="${OUT_DIR:-$BACKUP_BASE/xl_migration_$STAMP}"

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_NAME="${DB_NAME:-stock_datas}"
DB_USER="${DB_USER:-stock_datas}"
DB_PASS="${DB_PASS:-}"

if [[ -z "$DB_PASS" ]]; then
  echo "DB_PASS is required" >&2
  exit 1
fi

mkdir -p "$OUT_DIR"

mysqldump \
  --host="$DB_HOST" \
  --user="$DB_USER" \
  --password="$DB_PASS" \
  --default-character-set=utf8mb4 \
  --single-transaction \
  --routines \
  --events \
  --triggers \
  --hex-blob \
  "$DB_NAME" | gzip > "$OUT_DIR/${DB_NAME}.sql.gz"

tar \
  --exclude='.git' \
  --exclude='vendor' \
  --exclude='backend/runtime/cache/*' \
  --exclude='backend/runtime/debug/*' \
  --exclude='backend/runtime/logs/*' \
  --exclude='console/runtime/*' \
  --exclude='frontend/runtime/*' \
  --exclude='backend/web/assets/*' \
  --exclude='frontend/web/assets/*' \
  --exclude='deploy/backups/*' \
  -czf "$OUT_DIR/app-files.tgz" \
  -C "$APP_ROOT" .

if command -v redis-cli >/dev/null 2>&1; then
  redis-cli --rdb "$OUT_DIR/redis-dump.rdb" >/dev/null 2>&1 || true
fi

{
  echo "created_at=$(date '+%F %T %z')"
  echo "app_root=$APP_ROOT"
  echo "db_host=$DB_HOST"
  echo "db_name=$DB_NAME"
  echo "db_user=$DB_USER"
  git -C "$APP_ROOT" rev-parse --short HEAD 2>/dev/null | sed 's/^/git_commit=/'
} > "$OUT_DIR/manifest.txt"

echo "$OUT_DIR"
