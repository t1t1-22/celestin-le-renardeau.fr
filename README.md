# celestin-le-renardeau.fr

Le dépôt contient une page minimale pour valider la chaîne de déploiement. Le conteneur HTTP écoute sur `celestin-web:8080` sur le réseau Docker `celestin_proxy`. Le projet indépendant [ubuntu-edge](https://github.com/t1t1-22/ubuntu-edge) relaie le trafic public et termine le TLS. Le dossier local `cert/` est exclu de Git.

## CI et production

GitHub Actions contrôle la syntaxe des configurations, construit l'image, vérifie la réponse de la page d'accueil et teste une URL introuvable. Après les tests, chaque push sur `main` déclenche le déploiement SSH du commit testé. Le script met à jour le checkout du serveur en fast-forward, reconstruit le conteneur et attend que sa vérification HTTP passe.

Préparer une fois le serveur Ubuntu avec Git, Docker et Docker Compose v2. Cloner ce dépôt privé sur `main` dans un chemin absolu, par exemple `/srv/celestin-le-renardeau`. Le compte SSH de déploiement doit pouvoir lire et modifier ce checkout, exécuter Docker et effectuer un `git fetch origin main` sans invite. Configurer côté serveur un accès en lecture au dépôt privé, par exemple une clé de déploiement GitHub dédiée.

Dans **Settings → Secrets and variables → Actions**, ajouter les secrets du dépôt suivants.

| Secret | Valeur |
| --- | --- |
| `CELESTIN_SSH_HOST` | Nom d'hôte ou IP du serveur Ubuntu |
| `CELESTIN_SSH_USER` | Compte de déploiement Ubuntu |
| `CELESTIN_SSH_PASSWORD` | Mot de passe du compte SSH Ubuntu. |
| `CELESTIN_SSH_KNOWN_HOSTS` | Ligne `known_hosts` de l'hôte Ubuntu vérifiée via une session de confiance. Pour un port personnalisé, utiliser le format `[hôte]:port`. |

Ajouter la variable `CELESTIN_DEPLOY_PATH` avec le chemin absolu du checkout. La variable `CELESTIN_SSH_PORT` est facultative et vaut 22 par défaut. Lancer ensuite **CI et déploiement Célestin** depuis l'onglet **Actions**, sur `main`, ou pousser un changement sur cette branche.

Pour la mise en service du domaine et son certificat, suivre la procédure du dépôt [ubuntu-edge](https://github.com/t1t1-22/ubuntu-edge).
