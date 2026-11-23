#!/bin/sh
# Déploiement de l'infrastructure du site Célestin seulement.
# Les extensions WordPress sont désormais administrées via WP Pusher.
# Ne touche pas à Factify, DEPRAT, ubuntu-edge ou leurs volumes.
set -eu
umask 077
cd "${1:?Chemin du site requis}"
tested_sha=${2:?SHA GitHub requis}

test "$(git branch --show-current)" = "main" || { echo "Le checkout serveur doit être sur main." >&2; exit 1; }
test -z "$(git status --porcelain --untracked-files=no)" || { echo "Fichiers suivis modifiés localement." >&2; exit 1; }

git fetch origin main
head=$(git rev-parse origin/main)
if [ "$head" != "$tested_sha" ]; then
  echo "Un commit plus récent existe, déploiement précédent ignoré."
  exit 0
fi
git merge --ff-only "$tested_sha"
test "$(git rev-parse HEAD)" = "$tested_sha"

sh scripts/ensure-wordpress-secrets.sh "$PWD"
docker network inspect celestin_proxy >/dev/null 2>&1 || docker network create celestin_proxy >/dev/null
compose() { docker compose -p celestin -f compose.yml "$@"; }
compose config --quiet

# Recrée uniquement le conteneur WordPress afin de supprimer le bind-mount
# du MU-plugin obsolète. Ne supprime PAS les volumes persistants.
compose up -d --no-deps --force-recreate wordpress
if ! compose up -d --wait --wait-timeout 240; then
  compose ps >&2 || true
  compose logs --tail=60 web wordpress db >&2 || true
  exit 1
fi

# Un fichier laissé dans le volume persistant peut subsister après retrait du montage.
# Conserver la copie à l'écart de mu-plugins : aucun effacement de données.
compose exec -T wordpress sh -c '
set -eu
legacy=/var/www/html/wp-content/mu-plugins/celestin-site.php
if test -f "$legacy"; then
  mkdir -p /var/www/html/wp-content/celestin-legacy-backups
  cp "$legacy" /var/www/html/wp-content/celestin-legacy-backups/celestin-site-avant-wp-pusher.php.bak
  rm "$legacy"
  echo "Module Célestin archivé et désactivé."
fi
test ! -e "$legacy"
'
compose exec -T wordpress php -r 'require "/var/www/html/wp-load.php"; if (function_exists("cel_initialize_site")) { fwrite(STDERR,"Ancien module toujours actif\n"); exit(1); } echo "Ancien module Célestin désactivé ; installation WP Pusher possible.\n";'
compose exec -T web wget -q -O /dev/null http://127.0.0.1:8080/wp-admin/install.php

# Vérification sans mutation de l'infrastructure partagée.
compose ps
echo "Infrastructure Célestin opérationnelle. Volumes WordPress et Factify conservés. Module Docker retiré."
