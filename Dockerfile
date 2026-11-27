# Home Hub: Laravel served by nginx + PHP-FPM (serversideup/php), listening on 8080.

# --- Build: install PHP dependencies without dev packages ---
FROM serversideup/php:8.4-cli AS vendor
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist
COPY . .
RUN composer dump-autoload --no-dev --no-scripts --optimize --classmap-authoritative

# --- Runtime ---
FROM serversideup/php:8.4-fpm-nginx
ENV PHP_OPCACHE_ENABLE=1 \
    AUTORUN_ENABLED=true \
    AUTORUN_LARAVEL_MIGRATION=true \
    AUTORUN_LARAVEL_STORAGE_LINK=false
COPY --chown=www-data:www-data --from=vendor /var/www/html /var/www/html
USER www-data
# Writable dirs; storage/app is a volume in compose.yaml (the SQLite database lives there).
RUN mkdir -p storage/app/private storage/app/public storage/framework/cache/data \
        storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && rm -f bootstrap/cache/*.php \
    && php artisan package:discover
