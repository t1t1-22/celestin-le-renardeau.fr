#!/bin/sh
# Déploiement entièrement automatisé du seul projet Docker "celestin".
# Ne touche jamais aux projets factcast, factify ou ubuntu-edge.
set -eu
umask 077

cd "${1:?Chemin du dépôt requis}"
tested_sha=${2:?SHA testé requis}

branch=$(git branch --show-current)
tracked_changes=$(git status --porcelain --untracked-files=no)
untracked_changes=$(git ls-files --others --exclude-standard -- . ':(exclude,top)cert/')
if [ "$branch" != main ] || [ -n "$tracked_changes$untracked_changes" ]; then
  echo "Le checkout Célestin doit être sur main et sans modifications locales suivies." >&2
  exit 1
fi

git fetch origin main
remote_sha=$(git rev-parse origin/main)
if [ "$tested_sha" != "$remote_sha" ]; then
  echo "Un commit plus récent existe : pas de déploiement d'un ancien commit."
  exit 0
fi
git merge --ff-only "$tested_sha"
test "$(git rev-parse HEAD)" = "$tested_sha"

# Les secrets SQL sont créés une fois sur Ubuntu et réutilisés aux déploiements suivants.
# Aucun secret ne transite dans les logs, GitHub ou la conversation.
sh scripts/ensure-wordpress-secrets.sh "$PWD"

# Réseau existant partagé avec le frontal HTTPS ubuntu-edge.
# On ne le recrée qu'en cas d'absence et on ne le supprime jamais.
docker network inspect celestin_proxy >/dev/null 2>&1 ||
  docker network create celestin_proxy >/dev/null

compose() { docker compose -p celestin -f compose.yml "$@"; }
compose config --quiet
# Une image valide est préparée avant toute suppression d'ancien conteneur.
compose build --pull web wordpress

marker=".celestin-wordpress-migrated"
if [ ! -f "$marker" ]; then
  # Docker Compose ajoute ce label à tous les anciens conteneurs Célestin.
  # Cette sélection stricte ne peut pas inclure Factify ni ubuntu-edge.
  obsolete=$(docker ps -aq --filter "label=com.docker.compose.project=celestin")
  if [ -n "$obsolete" ]; then
    echo "Retrait des anciens conteneurs du projet Docker celestin (volumes conservés)."
    for id in $obsolete; do
      project=$(docker inspect --format '{{index .Config.Labels "com.docker.compose.project"}}' "$id")
      if [ "$project" != "celestin" ]; then
        echo "Conteneur hors du projet Célestin : opération refusée." >&2
        exit 1
      fi
    done
    # Conteneurs seulement ; pas de docker volume rm, ni de down -v, ni de prune.
    docker rm -f $obsolete >/dev/null
  fi
fi

# --remove-orphans agit seulement sur le projet Compose explicitement ciblé.
if ! compose up -d --no-build --remove-orphans --wait --wait-timeout 240; then
  compose ps >&2 || true
  compose logs --tail=100 web wordpress db >&2 || true
  exit 1
fi

# Vérifier que le thème est lisible par l'utilisateur web, depuis le
# volume Docker persistant, et accessible depuis le frontal Nginx.
if ! compose exec -T wordpress sh -c 'su -s /bin/sh www-data -c "test -r /var/www/html/wp-content/themes/celestin/style.css"'; then
  echo "Theme Célestin : style.css illisible par le serveur web." >&2
  exit 1
fi
if ! compose exec -T web sh -c "wget -q -O - http://127.0.0.1:8080/wp-content/themes/celestin/style.css | grep -Fq 'Theme Name: Célestin'"; then
  echo "Le theme Célestin n'est pas servi correctement par HTTP." >&2
  exit 1
fi

# Valider WordPress lui-même et pas seulement la réponse statique de Nginx.
if ! compose exec -T web wget -q -O /dev/null http://127.0.0.1:8080/wp-admin/install.php; then
  compose ps >&2 || true
  compose logs --tail=100 web wordpress db >&2 || true
  echo "Le routage /wp-admin/install.php n'est pas fonctionnel." >&2
  exit 1
fi
echo "Vérification du moteur d'images WordPress et du dossier uploads."
if ! compose exec -T --user www-data wordpress php < scripts/check-wordpress-images.php; then
  echo "Impossible de traiter les images : voir les diagnostics ci-dessus." >&2
  exit 1
fi
touch "$marker"
chmod 600 "$marker"
compose ps
echo "WordPress Célestin et MariaDB fonctionnent sur celestin-web:8080 ($tested_sha)."
echo "Factify et ubuntu-edge n'ont pas été modifiés."
