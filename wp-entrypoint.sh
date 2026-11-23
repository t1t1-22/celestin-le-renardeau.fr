#!/bin/sh
set -eu
# Le volume Docker persistant peut contenir une ancienne copie du thème
# créée avec un autre propriétaire ou des permissions trop restrictives.
theme_source=/usr/src/wordpress/wp-content/themes/celestin
theme_target=/var/www/html/wp-content/themes/celestin
mu_source=/usr/src/wordpress/wp-content/mu-plugins
mu_target=/var/www/html/wp-content/mu-plugins

if [ ! -s "$theme_source/style.css" ]; then
  echo "Thème Célestin invalide : fichier source style.css manquant ou vide." >&2
  exit 1
fi

# Vérifier que rien n'est redirigé par des liens symboliques inattendus.
for directory in /var/www/html/wp-content /var/www/html/wp-content/themes "$theme_target" "$mu_target"; do
  if [ -L "$directory" ]; then
    echo "Lien symbolique inattendu sur le chemin WordPress : $directory" >&2
    exit 1
  fi
done

mkdir -p "$theme_target" "$mu_target"
cp -a "$theme_source/." "$theme_target/"
cp -a "$mu_source/." "$mu_target/"

# Le serveur Apache exécute PHP en tant que www-data ; le thème doit
# être lisible par ce compte, pas seulement par root lors de la CI.
# Ne modifier que les fichiers Célestin, jamais les autres sites/volumes.
chown -R www-data:www-data "$theme_target"
find "$theme_target" -type d -exec chmod 755 {} +
find "$theme_target" -type f -exec chmod 644 {} +

for file in celestin-content.php celestin-media.js; do
  if [ -f "$mu_target/$file" ]; then
    chown www-data:www-data "$mu_target/$file"
    chmod 644 "$mu_target/$file"
  fi
done

# Permettre à PHP de traverser les dossiers du thème et de créer les
# médias dans wp-content (sans écraser les uploads déjà présents).
chown www-data:www-data /var/www/html/wp-content
chmod 755 /var/www/html/wp-content
chmod 755 /var/www/html/wp-content/themes "$mu_target"

if ! su -s /bin/sh www-data -c 'test -s /var/www/html/wp-content/themes/celestin/style.css && test -r /var/www/html/wp-content/themes/celestin/style.css'; then
  echo "ERREUR : WordPress ne peut pas lire le style.css du thème Célestin." >&2
  exit 1
fi

echo "Thème Célestin : style.css vérifié et lisible par WordPress."
exec docker-entrypoint.sh "$@"
