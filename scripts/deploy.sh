#!/bin/sh
# Déploiement autonome du projet Docker celestin.
# Aucune commande sur Factify, ubuntu-edge ou leurs volumes.
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

# Génère les secrets SQL s'ils n'existent pas; ne les affiche jamais.
sh scripts/ensure-wordpress-secrets.sh "$PWD"

docker network inspect celestin_proxy >/dev/null 2>&1 || docker network create celestin_proxy >/dev/null
compose() { docker compose -p celestin -f compose.yml "$@"; }
compose config --quiet

# Les nouveaux volumes ne contiennent ni anciennes pages, ni thème Astra,
# ni plugin Célestin. Les anciens volumes sont conservés, jamais effacés.
compose build --pull web
if ! compose up -d --remove-orphans --wait --wait-timeout 240; then
  compose ps >&2 || true
  compose logs --tail=60 web wordpress db >&2 || true
  exit 1
fi

# Teste la page d'installation de WordPress *vierge* et l'absence
# des modules Célestin dans la nouvelle installation.
compose exec -T wordpress sh -c 'test ! -e /var/www/html/wp-content/mu-plugins/celestin-content.php && test ! -e /var/www/html/wp-content/themes/celestin'
compose exec -T web wget -q -O /dev/null http://127.0.0.1:8080/wp-admin/install.php
# Le frontal HTTPS ubuntu-edge partage Nginx avec Factify. Ne modifier que
# le fichier du domaine Célestin, vérifier nginx -t, puis recharger sans restart.
sh scripts/enable-celestin-proxy-uploads.sh
compose ps
echo "WordPress vierge en ligne sur celestin-web:8080. Anciennes données conservées dans leurs volumes inactifs."
