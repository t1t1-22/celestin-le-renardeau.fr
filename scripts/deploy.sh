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

# Les données du site et Astra restent dans les volumes persistants.
# Le module Célestin est monté en lecture seule depuis ce dépôt.
compose build --pull web
# Git remplace l'inode du fichier PHP, tandis que le bind-mount Docker
# conserve l'ancien inode jusqu'à la recréation du conteneur.
# Recréer UNIQUEMENT wordpress, jamais la base DB ni Factify.
compose up -d --no-deps --force-recreate wordpress
if ! compose up -d --remove-orphans --wait --wait-timeout 240; then
  compose ps >&2 || true
  compose logs --tail=60 web wordpress db >&2 || true
  exit 1
fi

# Teste la page d'installation de WordPress *vierge* et l'absence
# des modules Célestin dans la nouvelle installation.
compose exec -T wordpress sh -c 'test -r /var/www/html/wp-content/mu-plugins/celestin-site.php && php -l /var/www/html/wp-content/mu-plugins/celestin-site.php'
# Initialise explicitement les pages et la page d’accueil après le démarrage de WordPress.
compose exec -T wordpress php -r 'require "/var/www/html/wp-load.php"; if (!function_exists("cel_initialize_site")) { fwrite(STDERR,"Module Célestin indisponible\\n"); exit(1); } cel_initialize_site(); $p=get_page_by_path("accueil"); if (!$p || get_option("show_on_front")!=="page" || (int)get_option("page_on_front")!==(int)$p->ID) { fwrite(STDERR,"Accueil Célestin non configuré\\n"); exit(1); } if (!function_exists("cel_setup_home_content") || !cel_setup_home_content()) { fwrite(STDERR,"Blocs accueil non initialises\\n"); exit(1); } $p=get_page_by_path("accueil"); echo "Accueil Célestin actif et éditable : ".$p->ID.PHP_EOL;'
compose exec -T web wget -q -O /dev/null http://127.0.0.1:8080/wp-admin/install.php
# Le frontal HTTPS ubuntu-edge partage Nginx avec Factify. Ne modifier que
# le fichier du domaine Célestin, vérifier nginx -t, puis recharger sans restart.
sh scripts/enable-celestin-proxy-uploads.sh

# Diagnostic non destructif du routage WordPress et de l'accueil.
echo '=== Diagnostic Célestin ==='
compose exec -T wordpress php -r 'require "/var/www/html/wp-load.php"; echo "home=".get_option("home")." show_on_front=".get_option("show_on_front")." page_on_front=".get_option("page_on_front")." initialized=".get_option("cel_site_initialized")." mu=".(function_exists("cel_front_page")?"yes":"no")." blog_installed=".(is_blog_installed()?"yes":"no")." wp_installing=".(wp_installing()?"yes":"no")." wp_loaded=".did_action("wp_loaded").PHP_EOL;'
if compose exec -T web sh -c 'wget -qO- --header="Host: celestin-le-renardeau.fr" --header="X-Forwarded-Proto: https" http://127.0.0.1:8080/ | grep -q "cel-hero"'; then
  echo 'DIAGNOSTIC: le template Célestin est servi par le conteneur web.'
else
  echo 'DIAGNOSTIC: réponse HTTP et début de page :' >&2
  compose exec -T web sh -c 'wget -S -O - --header="Host: celestin-le-renardeau.fr" --header="X-Forwarded-Proto: https" http://127.0.0.1:8080/ 2>&1 | head -c 1800' >&2 || true
  exit 1
fi
compose ps
echo "WordPress avec module Célestin en ligne sur celestin-web:8080. Données WordPress et Factify conservées."
