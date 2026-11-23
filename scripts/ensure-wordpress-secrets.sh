#!/bin/sh
# Prépare les mots de passe SQL de Célestin une fois, sans saisie SSH.
# Jamais de secrets dans les logs GitHub, le dépôt Git ou une variable CI.
set -eu
umask 077

target_dir=${1:-.}
env_file="$target_dir/.env"
if [ -L "$env_file" ]; then
  echo "Le fichier .env ne doit pas être un lien symbolique." >&2
  exit 1
fi
if [ ! -f "$env_file" ]; then
  : > "$env_file"
fi
chmod 600 "$env_file"

if ! command -v openssl >/dev/null 2>&1; then
  echo "OpenSSL est nécessaire à la génération sécurisée des secrets SQL." >&2
  exit 1
fi

ensure_secret() {
  key=$1
  existing=$(grep "^$key=" "$env_file" | tail -n 1 || true)
  if [ -n "$existing" ]; then
    current=${existing#*=}
    case "$current" in
      ""|REMPLACER*|CHANGE_ME*|YOUR_*|ton_secret*|ton_autre_secret*)
        : ;;
      *)
        # Un secret existant est conservé même aux redéploiements.
        return 0 ;;
    esac
  fi
  secret=$(openssl rand -hex 32)
  temp_file=$(mktemp "$target_dir/.celestin-env.XXXXXX")
  if [ -n "$existing" ]; then
    # Supprime les anciens placeholders et évite les définitions multiples.
    grep -v "^$key=" "$env_file" > "$temp_file" || true
    printf '%s=%s\n' "$key" "$secret" >> "$temp_file"
    mv -f "$temp_file" "$env_file"
  else
    rm -f "$temp_file"
    printf '%s=%s\n' "$key" "$secret" >> "$env_file"
  fi
  chmod 600 "$env_file"
}

ensure_secret CELESTIN_DB_PASSWORD
ensure_secret CELESTIN_DB_ROOT_PASSWORD

for key in CELESTIN_DB_PASSWORD CELESTIN_DB_ROOT_PASSWORD; do
  if ! grep -q "^$key=." "$env_file"; then
    echo "Impossible de préparer la variable $key." >&2
    exit 1
  fi
done

echo "Configuration SQL Célestin prête (secrets conservés et non affichés)."
