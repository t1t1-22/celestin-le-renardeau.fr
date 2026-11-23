#!/bin/sh
# Applique une limite HTTP de 80 Mo au seul vhost Célestin dans ubuntu-edge.
# Aucune interruption/recréation du proxy ni modification des vhosts Factify.
set -eu

containers=$(docker ps --filter "label=com.docker.compose.project=ubuntu-edge" \
  --filter "label=com.docker.compose.service=nginx" --format '{{.ID}}')
set -- $containers
if [ "$#" -ne 1 ]; then
  echo "Proxy ubuntu-edge introuvable ou ambigu : aucun changement n'a été appliqué." >&2
  exit 1
fi
container=$1
tempdir=$(mktemp -d)
trap 'rm -rf "$tempdir"' EXIT HUP INT TERM

path=/etc/nginx/conf.d/celestin.conf
docker cp "$container:$path" "$tempdir/original.conf"
test -s "$tempdir/original.conf" || { echo "Configuration Célestin absente." >&2; exit 1; }
grep -q 'server_name celestin-le-renardeau.fr;' "$tempdir/original.conf" || {
  echo "Cette configuration n'est pas celle du domaine Célestin." >&2; exit 1;
}
if grep -Eq '^[[:space:]]*client_max_body_size[[:space:]]+80m;' "$tempdir/original.conf"; then
  echo "Proxy Célestin déjà configuré pour 80 Mo."
  exit 0
fi
test "$(grep -c '^[[:space:]]*http2 on;' "$tempdir/original.conf")" = 1 || {
  echo "Structure HTTPS Célestin inattendue, aucun changement." >&2; exit 1;
}
awk '{
  print
  if ($0 ~ /^[[:space:]]*http2 on;$/) {
    print "    client_max_body_size 80m;"
  }
}' "$tempdir/original.conf" > "$tempdir/updated.conf"

# Une syntaxe invalide annule l'opération avant tout rechargement nginx.
docker cp "$tempdir/updated.conf" "$container:$path"
if ! docker exec "$container" nginx -t; then
  docker cp "$tempdir/original.conf" "$container:$path"
  echo "Configuration refusée ; fichier d'origine restauré, proxy non rechargé." >&2
  exit 1
fi
if ! docker exec "$container" nginx -s reload; then
  docker cp "$tempdir/original.conf" "$container:$path"
  docker exec "$container" nginx -t >/dev/null 2>&1 || true
  docker exec "$container" nginx -s reload || true
  echo "Rechargement échoué ; configuration d'origine restaurée." >&2
  exit 1
fi
echo "Proxy Célestin : limite 80 Mo appliquée via un reload Nginx (sans arrêter Factify)."
