#!/bin/sh
set -eu
# Les médias, plugins et contenus WP vivent dans un volume persistant.
# Mettre à jour uniquement notre code source à chaque nouveau déploiement.
mkdir -p /var/www/html/wp-content/themes/celestin
mkdir -p /var/www/html/wp-content/mu-plugins
cp -a /usr/src/wordpress/wp-content/themes/celestin/. /var/www/html/wp-content/themes/celestin/
cp -a /usr/src/wordpress/wp-content/mu-plugins/. /var/www/html/wp-content/mu-plugins/
exec docker-entrypoint.sh "$@"
