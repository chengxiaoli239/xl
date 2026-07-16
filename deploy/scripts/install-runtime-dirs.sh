#!/usr/bin/env bash
set -euo pipefail

APP_ROOT="${APP_ROOT:-/www/wwwroot/lt/lottery_xl/xl}"
APP_USER="${APP_USER:-www}"
APP_GROUP="${APP_GROUP:-www}"

mkdir -p \
  "$APP_ROOT/backend/runtime/cache" \
  "$APP_ROOT/backend/runtime/captcha" \
  "$APP_ROOT/backend/runtime/logs" \
  "$APP_ROOT/backend/runtime/debug" \
  "$APP_ROOT/backend/web/assets" \
  "$APP_ROOT/console/runtime" \
  "$APP_ROOT/frontend/runtime" \
  "$APP_ROOT/frontend/web/assets" \
  /www/log/lottery_xl \
  /www/wwwlogs

chown -R "$APP_USER:$APP_GROUP" \
  "$APP_ROOT/backend/runtime" \
  "$APP_ROOT/backend/web/assets" \
  "$APP_ROOT/console/runtime" \
  "$APP_ROOT/frontend/runtime" \
  "$APP_ROOT/frontend/web/assets" \
  /www/log/lottery_xl \
  /www/wwwlogs

chmod -R ug+rwX \
  "$APP_ROOT/backend/runtime" \
  "$APP_ROOT/backend/web/assets" \
  "$APP_ROOT/console/runtime" \
  "$APP_ROOT/frontend/runtime" \
  "$APP_ROOT/frontend/web/assets" \
  /www/log/lottery_xl \
  /www/wwwlogs
