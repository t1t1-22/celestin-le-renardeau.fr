#!/bin/sh
set -eu
cd "${1:?Chemin du dépôt requis}"
tested_sha=${2:?SHA testé requis}

branch=$(git branch --show-current)
tracked_changes=$(git status --porcelain --untracked-files=no)
untracked_changes=$(git ls-files --others --exclude-standard -- . ':(exclude,top)cert/')
if [ "$branch" != main ] || [ -n "$tracked_changes$untracked_changes" ]; then
  echo "Le checkout serveur doit être sur main, sans modifications suivies." >&2
  exit 1
fi

# Pas de migration WordPress sans les deux secrets définis côté serveur.
if [ ! -f .env ]; then
  echo "Créer le fichier .env avec les identifiants SQL avant fusion." >&2
  exit 1
fi
set -a
. ./.env
set +a
: "${CELESTIN_DB_PASSWORD:?Secret SQL absent}"
: "${CELESTIN_DB_ROOT_PASSWORD:?Secret root SQL absent}"

git fetch origin main
remote_sha=$(git rev-parse origin/main)
if [ "$tested_sha" != "$remote_sha" ]; then
  echo "Un commit plus récent existe ; son déploiement sera lancé séparément."
  exit 0
fi

git merge --ff-only "$tested_sha"
test "$(git rev-parse HEAD)" = "$tested_sha"

docker network inspect celestin_proxy >/dev/null 2>&1 || docker network create celestin_proxy >/dev/null

compose() { docker compose -p celestin "$@"; }
compose config --quiet
compose build --pull web wordpress
# L'option --no-build maintient le même commit testé.
if ! compose up -d --no-build --wait --wait-timeout 240; then
  compose ps >&2 || true
  compose logs --tail=80 web wordpress db >&2 || true
  exit 1
fi
compose ps
echo "WordPress Célestin déployé : $tested_sha"
