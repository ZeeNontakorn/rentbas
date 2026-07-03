#!/bin/bash
set -e

# ตรวจสอบว่ามี APP_KEY หรือไม่ ถ้าไม่มีจะหยุดทำงานทันที
if [ -z "$APP_KEY" ]; then
    echo "ERROR: APP_KEY is not set. Container cannot start without APP_KEY."
    echo "Generate one with: docker compose run --rm app php artisan key:generate --show"
    echo "Then set APP_KEY=<generated-key> in your .env file."
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

# =============================================================================
#
# หลังจาก deploy ให้รัน migration ด้วยคำสั่ง:
#   docker compose exec app php artisan migrate --force
#
# =============================================================================

exec apache2-foreground
