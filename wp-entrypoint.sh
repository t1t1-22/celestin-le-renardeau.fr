#!/bin/sh
set -eu
# Compatible avec Astra : le thème actif est toujours celui choisi dans WordPress.
# Les fichiers source de Célestin sont synchronisés, sans toucher aux autres
# thèmes, aux plugins tiers ni au répertoire uploads du volume persistant.
theme_source=/usr/src/wordpress/wp-content/themes/celestin
theme_target=/var/www/html/wp-content/themes/celestin
mu_source=/usr/src/wordpress/wp-content/mu-plugins
mu_target=/var/www/html/wp-content/mu-plugins
wp_content=/var/www/html/wp-content

for directory in "$wp_content" "$wp_content/themes" "$theme_target" "$mu_target"; do
  if [ -L "$directory" ]; then
    echo "Lien symbolique inattendu dans WordPress : $directory" >&2
    exit 1
  fi
done

mkdir -p "$theme_target" "$mu_target"
# Ancien thème conservé pour référence, mais jamais activé automatiquement.
if [ -d "$theme_source" ]; then
  cp -a "$theme_source/." "$theme_target/"
fi
cp -a "$mu_source/." "$mu_target/"

# Donner à Apache/PHP la lecture des éléments gérés par GitHub.
chown -R www-data:www-data "$theme_target"
find "$theme_target" -type d -exec chmod 755 {} +
find "$theme_target" -type f -exec chmod 644 {} +

for item in celestin-content.php celestin-media.js celestin-astra.php; do
  if [ -f "$mu_target/$item" ]; then
    chown www-data:www-data "$mu_target/$item"
    chmod 644 "$mu_target/$item"
  fi
done
if [ -d "$mu_target/celestin-astra" ]; then
  chown -R www-data:www-data "$mu_target/celestin-astra"
  find "$mu_target/celestin-astra" -type d -exec chmod 755 {} +
  find "$mu_target/celestin-astra" -type f -exec chmod 644 {} +
fi
chown www-data:www-data "$wp_content"
chmod 755 "$wp_content" "$wp_content/themes" "$mu_target"

# Permettre au compte Apache www-data de créer les dossiers mensuels et
# les miniatures, sans toucher aux volumes de Factify ou aux autres sites.
mkdir -p "$wp_content/uploads"
chown www-data:www-data "$wp_content/uploads"
chmod 775 "$wp_content/uploads"

for file in "$mu_target/celestin-astra.php" "$mu_target/celestin-astra/site.css"; do
  if ! su -s /bin/sh www-data -c "test -r '$file' && test -s '$file'"; then
    echo "Erreur : module Célestin Astra illisible : $file" >&2
    exit 1
  fi
done

echo "Astra conservé : module Célestin disponible dans WordPress."
exec docker-entrypoint.sh "$@"
