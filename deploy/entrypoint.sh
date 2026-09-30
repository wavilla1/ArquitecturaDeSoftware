#!/bin/sh
set -eu
php artisan package:discover --ansi
php artisan config:cache
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache
exec "$@"
