#!/bin/sh
set -e

php artisan config:cache
php artisan route:cache
php artisan event:cache

chown -R www-data:www-data storage bootstrap/cache

exec apache2-foreground