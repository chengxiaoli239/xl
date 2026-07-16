# XL Lottery Site Migration

This directory keeps the deployment files needed to move the site to a new
server. Commands assume the app root is:

```bash
/www/wwwroot/lt/lottery_xl/xl
```

## Services

- PHP: 7.4 or newer, with `curl`, `json`, `mbstring`, `openssl`, `pdo_mysql`,
  `redis`, `fileinfo`, and `gd`/`imagick` if image features are used.
- MySQL: database `stock_datas`, table prefix `lt_`.
- Redis: DB 0 for Yii cache/queues, DB 1 for common Redis cache.
- Web root: `backend/web`.
- Queue workers: `yii queue/listen`, `yii queue-fast/listen`,
  `yii queue-open/listen`.

## Source Server

1. Commit and push code.
2. Create a migration backup:

```bash
cd /www/wwwroot/lt/lottery_xl/xl
DB_PASS='replace-with-current-db-password' bash deploy/scripts/backup-production.sh
```

The backup script writes a timestamped folder under `/www/backup` by default.
Move that folder to the target server with `rsync` or `scp`.

## Target Server

1. Install PHP, MySQL, Redis, Composer, Nginx, and Supervisor.
2. Clone or unpack the project to `/www/wwwroot/lt/lottery_xl/xl`.
3. Install dependencies:

```bash
cd /www/wwwroot/lt/lottery_xl/xl
composer install --no-dev --optimize-autoloader
```

4. Copy and edit local config templates if needed:

```bash
cp deploy/env/common-main-local.php.example common/config/main-local.php
```

5. Restore the backup:

```bash
cd /www/wwwroot/lt/lottery_xl/xl
DB_PASS='replace-with-target-db-password' bash deploy/scripts/restore-target.sh /path/to/xl_migration_YYYYmmdd_HHMMSS
```

6. Install Nginx and Supervisor templates:

```bash
cp deploy/nginx/xl-backend.conf.example /www/server/panel/vhost/nginx/xl-backend.conf
cp deploy/supervisor/supervisord.conf /www/server/supervisor/supervisord.conf
cp deploy/supervisor/xl-lottery-queues.ini /www/server/supervisor/conf.d/xl-lottery-queues.ini
cp deploy/systemd/xl-supervisord.service /etc/systemd/system/xl-supervisord.service
```

Edit server name, PHP socket, app user, and paths before reload.

7. Enable services:

```bash
systemctl daemon-reload
systemctl enable --now xl-supervisord
nginx -t && systemctl reload nginx
```

8. Restore crontab from `deploy/crontab/xl-crontab.example` after confirming
   each job is still needed on the target server.

## Post-Migration Checks

```bash
cd /www/wwwroot/lt/lottery_xl/xl
/www/server/php/74/bin/php yii migrate --interactive=0
/www/server/php/74/bin/php yii queue/info
/www/server/php/74/bin/php yii queue-fast/info
/www/server/php/74/bin/php yii queue-open/info
```

Then verify:

- Backend login opens correctly.
- Redis queue workers are running in Supervisor.
- `/www/log/lottery_xl` is writable.
- `backend/runtime`, `console/runtime`, and web asset directories are writable.
- Active盘口 accounts can sync balance and auto-login.
