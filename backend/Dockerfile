# Prise en main : Prudent et Moise ; validation de déploiement distincte des tests métier.
FROM php:8.5-apache-bookworm

RUN ["apt-get", "update"]
RUN ["apt-get", "install", "-y", "--no-install-recommends", "git", "unzip", "libpq-dev", "libzip-dev", "libonig-dev", "ca-certificates"]
RUN ["docker-php-ext-install", "-j2", "pdo_pgsql", "mbstring", "zip"]
RUN ["a2enmod", "rewrite"]

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN ["composer", "install", "--no-dev", "--prefer-dist", "--no-interaction", "--no-progress", "--no-scripts"]
COPY . .
RUN ["mkdir", "-p", "storage/framework/cache/data", "storage/framework/sessions", "storage/framework/views", "storage/logs", "bootstrap/cache"]
RUN ["composer", "dump-autoload", "--no-dev", "--classmap-authoritative", "--no-scripts"]
RUN ["php", "artisan", "package:discover", "--ansi"]
RUN ["chown", "-R", "www-data:www-data", "storage", "bootstrap/cache"]
COPY deploy/apache.conf /etc/apache2/sites-available/000-default.conf

ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr
ENV SESSION_DRIVER=database
ENV SESSION_SECURE_COOKIE=true
ENV SESSION_SAME_SITE=lax
ENV DB_CONNECTION=pgsql
ENV DB_SSLMODE=verify-full
ENV PGSSLROOTCERT=/etc/ssl/certs/ca-certificates.crt
ENV EASYPAY_ENABLED=false
ENV DEPLOY_RUN_MIGRATIONS=false
ENV PORT=10000

EXPOSE 10000
CMD ["/bin/sh", "deploy/start.sh"]
