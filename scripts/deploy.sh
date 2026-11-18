#!/bin/sh
set -eu

cd "${1:?Chemin du dépôt requis}"
tested_sha=${2:?SHA testé requis}

if [ "$(git branch --show-current)" != main ] || [ -n "$(git status --porcelain)" ]; then
  echo "Le checkout du serveur doit être sur main et sans modifications locales." >&2
  exit 1
fi

git fetch origin main
remote_sha="$(git rev-parse origin/main)"
if [ "$tested_sha" != "$remote_sha" ]; then
  echo "Un commit plus récent est arrivé sur main. Son déploiement sera traité par sa propre CI."
  exit 0
fi

git merge --ff-only "$tested_sha"
if [ "$(git rev-parse HEAD)" != "$tested_sha" ]; then
  echo "Le checkout du serveur ne correspond pas au commit testé." >&2
  exit 1
fi

docker network inspect celestin_proxy >/dev/null 2>&1 ||
  docker network create celestin_proxy >/dev/null

compose() {
  docker compose -p celestin "$@"
}

compose config --quiet
compose build --pull web
if ! compose up -d --no-build --wait --wait-timeout 60; then
  compose ps >&2 || true
  compose logs --tail=100 web >&2 || true
  exit 1
fi
compose ps
echo "Célestin répond en HTTP sur le serveur. Commit déployé $tested_sha."
