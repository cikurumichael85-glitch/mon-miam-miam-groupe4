#!/bin/sh
# Aucune clé créée ou base migrée implicitement au démarrage.
set -eu

: "${APP_KEY:?Renseigner APP_KEY dans les variables privées du service.}"
: "${DB_HOST:?Renseigner DB_HOST pour la nouvelle base hébergée.}"
: "${DB_DATABASE:?Renseigner DB_DATABASE pour la nouvelle base hébergée.}"
: "${DB_USERNAME:?Renseigner DB_USERNAME dans les variables privées.}"
: "${DB_PASSWORD:?Renseigner DB_PASSWORD dans les variables privées.}"

case "${PORT:-10000}" in
    ''|*[!0-9]*)
        echo 'PORT doit être un entier.' >&2
        exit 1
        ;;
esac

case "${DEPLOY_RUN_MIGRATIONS:-false}" in
    true|false)
        ;;
    *)
        echo 'DEPLOY_RUN_MIGRATIONS doit être true ou false.' >&2
        exit 1
        ;;
esac

sed -i "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
php artisan config:cache

if [ "${DEPLOY_RUN_MIGRATIONS:-false}" = 'true' ]; then
    # Activer seulement après accord pour la base cible, puis remettre false.
    php artisan migrate --force
fi

exec apache2-foreground
