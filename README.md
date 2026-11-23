# Célestin le renardeau — WordPress + Fleurville

Le site `celestin-le-renardeau.fr` utilise **WordPress** (PHP/Apache), **MariaDB**, un frontal **Nginx**, et un thème sur mesure **Célestin**. Il reste indépendant de `factcast` et du dépôt `ubuntu-edge`.

## Avant la fusion GitHub : 2 secrets SQL côté serveur

La mise en production **remplacera le conteneur Nginx statique actuel** par un frontal Nginx envoyant les requêtes vers WordPress. **Ne pas fusionner la Pull Request tant que les secrets ci-dessous ne sont pas en place et que les vérifications ne sont pas vertes.**

1. Se connecter au serveur Ubuntu en SSH.
2. Aller dans **le dossier du checkout Célestin** : le chemin utilisé dans le paramètre GitHub Actions `CELESTIN_DEPLOY_PATH` du dépôt.
3. Créer un fichier `.env` **dans ce dossier**, par exemple en partant de `.env.example` après déploiement, ou en le saisissant directement avant :
   ```dotenv
   CELESTIN_DB_PASSWORD=SECRET_SQL_ALEATOIRE_UNIQUE_32_CARACTERES
   CELESTIN_DB_ROOT_PASSWORD=AUTRE_SECRET_ROOT_ALEATOIRE_32_CARACTERES
   ```
   Générer **deux secrets différents et aléatoires**. Pour obtenir deux valeurs sans les afficher dans cette documentation : exécuter deux fois `openssl rand -hex 24` sur le serveur, puis les saisir dans `nano .env`.
4. Exécuter `chmod 600 .env`. Ce fichier est exclu de Git. Ne pas publier les secrets dans une issue, une Pull Request ou dans le chat.

Le workflow de déploiement vérifie la présence de ces secrets **avant de mettre à jour le code** du checkout serveur.

## Déploiement

1. Vérifier les tests GitHub Actions sur la Pull Request WordPress.
2. Après la sauvegarde du site initial et la configuration de `.env`, fusionner la Pull Request dans `main`.
3. Le workflow CI et déploiement déploiera automatiquement les conteneurs `web`, `wordpress` et `db` sur le réseau Docker existant `celestin_proxy`. Le frontal garde l'alias `celestin-web` et le port **8080**. Aucune modification DNS n'est prévue.
4. Ouvrir **https://celestin-le-renardeau.fr/wp-admin/install.php** : sélectionner le français et créer le premier compte administrateur WordPress. Utiliser un identifiant personnel (éviter `admin`), un mot de passe fort et une adresse email valide. Le mot de passe WordPress **n'est pas** le secret SQL.
5. Une fois connecté, ouvrir **https://celestin-le-renardeau.fr/wp-admin**. Le thème **Célestin** et les rubriques personnalisées seront activés automatiquement au premier chargement après installation.
6. Dans **Réglages → Permaliens**, choisir « Titre de la publication » puis **Enregistrer** pour activer les adresses conviviales.
7. Dans **Apparence → Personnaliser → 🦊 Accueil Célestin**, téléverser l'illustration finale de la bannière **sans texte**. Le texte et les boutons sont générés en HTML par WordPress.

## Utilisation de l'administration

Dans le menu gauche, quatre rubriques dédiées :

- **Livres** : titre, résumé, contenu, image mise en avant et PDF ou lien externe. Affichage automatique dans la section « Livres ».
- **Mini-histoires** : titre, histoire en texte enrichi, extrait et illustration. Chaque mini-histoire possède sa propre page de lecture.
- **Coloriages** : image mise en avant + fichier PDF (ou image) choisi dans le bloc « Fichier téléchargeable et liens ». Bouton de téléchargement sur le site.
- **Personnages** : Célestin, Octet, Maman renard, Papa renard et habitants de Fleurville, avec portrait et biographie.

Tu peux utiliser les états natifs WordPress **Brouillon**, **En attente**, **Publié**, **Corbeille**. Les pages publiques montrent uniquement les publications publiées. Les photos, couvertures et PDF sont gérés dans **Médias**.

Le thème récupère automatiquement les **4 dernières publications** de chaque type sur la page d'accueil, puis l'archive de chaque type affiche les autres.

## Reprise du site actuel

- Une copie du site précédent reste disponible sous **/ancien-site/** dans le nouveau frontal Nginx, pour vérification. Elle est exclue de l'indexation.
- Les images historiques sont préservées sous **/images/**.
- Les mini-histoires « Une soirée pas comme les autres » et « Une luge pour Célestin » sont **importées une fois** depuis la copie HTML lors du premier démarrage de WordPress installé.
- Deux présentations de livres (« Le stand de muguet », « Le trou dans le jardin ») et un coloriage historique sont également créés à titre de reprise. Ils restent entièrement modifiables depuis l'administration.
- Les images finales des personnages, les couvertures précises et la grande illustration de la maquette sont **à télécharger via Médias après installation** : aucun visuel n'est inventé.
- L'ancien site n'est pas un WordPress : il n'y a donc pas de base WordPress préexistante à migrer.

## Données persistantes et sauvegardes

Le compose crée :
- Volume `celestin_celestin_wordpress` : fichiers WordPress, thèmes, plugins et médias ;
- Volume `celestin_celestin_db` : MariaDB.

Le code du thème et du plugin obligatoire `Atelier de Fleurville` est recopié dans les volumes au démarrage du conteneur, ce qui permet de les mettre à jour via GitHub. Les autres plugins et les uploads restent dans le volume.

**Sauvegarder impérativement les deux volumes** régulièrement. Ne jamais faire `docker compose down -v` sur le serveur de production.

En cas de problème au premier déploiement, conserver le backup, restaurer le checkout Git **et** les volumes dans un environnement contrôlé ; ne pas écraser une base WordPress déjà utilisée.

## Développement et vérification

Pour tester avec Docker localement :
```bash
cp .env.example .env
# Modifier les deux mots de passe dans .env
docker network create celestin_proxy
docker compose -p celestin up -d --build --wait
```
Le projet n'ouvre aucun port public directement : le reverse-proxy `ubuntu-edge` route vers `celestin-web:8080`. Pour tester localement, utiliser un conteneur de diagnostic connecté au réseau `celestin_proxy` ou un port temporaire de développement.

## Sécurité

Seul Nginx est relié au réseau externe de reverse proxy ; WordPress et MariaDB ne publient **aucun port**. L'endpoint XML-RPC est bloqué. L'éditeur de fichiers de thèmes/plugins WordPress est désactivé. WordPress est derrière TLS via `ubuntu-edge` et reconnaît le header `X-Forwarded-Proto`.

Mettre régulièrement à jour WordPress, les extensions et MariaDB avec sauvegarde préalable. Privilégier des mots de passe uniques et activer une authentification à deux facteurs pour les comptes administrateurs.
