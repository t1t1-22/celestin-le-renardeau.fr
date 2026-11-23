# WordPress vierge

Ce dépôt déploie **WordPress officiel et MariaDB**, derrière un Nginx technique relié au reverse-proxy HTTPS existant. Aucun thème, extension, page ou contenu spécifique n'est fourni.

## Installation

Une fois le job GitHub Actions `deploy` réussi, ouvrir :

https://celestin-le-renardeau.fr/wp-admin/install.php

Créer un **nouveau** compte administrateur WordPress dans le navigateur. Aucune ancienne connexion WordPress n'est réutilisée.

## Données

Cette réinitialisation utilise deux **nouveaux volumes isolés** :
- `celestin_wordpress_blank_v1` (fichiers et médias WordPress neufs)
- `celestin_db_blank_v1` (base MariaDB neuve)

Les volumes précédents ne sont pas montés et **ne sont pas supprimés** (retour arrière possible). Ni Factify ni le reverse-proxy `ubuntu-edge` ne sont modifiés. Le domaine et l'alias Docker `celestin-web:8080` restent identiques.

Les secrets SQL sont créés automatiquement et conservés sur le serveur dans `.env`, hors Git, avec le script `scripts/ensure-wordpress-secrets.sh`. Aucun travail SSH manuel n'est nécessaire.

**Ne pas exécuter `docker compose down -v` sur la production** sans sauvegarde préalable.

Ce dépôt ne contient plus le site Célestin ; seul le WordPress standard est proposé.
