# Célestin le renardeau — WordPress + Fleurville

Le site `celestin-le-renardeau.fr` utilise **WordPress** (PHP/Apache), **MariaDB**, un frontal **Nginx**, et un thème sur mesure **Célestin**. Il reste indépendant de `factcast` et du dépôt `ubuntu-edge`.

## Déploiement automatique : aucune intervention manuelle sur Ubuntu

Les modifications de Célestin sont testées par GitHub Actions, puis déployées en SSH **automatiquement** après fusion sur `main`, avec les identifiants SSH déjà configurés dans les paramètres GitHub du dépôt.

Le script `scripts/deploy.sh` réalise toutes les opérations suivantes sans assistance :

1. Vérifie le checkout Git et récupère le commit GitHub testé.
2. Crée si nécessaire `.env` sur Ubuntu avec **deux mots de passe SQL aléatoires distincts de 64 caractères hexadécimaux** générés par `openssl`. Un mot de passe déjà défini est conservé à tous les déploiements.
3. Construit les images Nginx et WordPress **avant** de supprimer l'ancien conteneur.
4. À la première migration uniquement, détecte les conteneurs ayant **exactement** le label Docker Compose `com.docker.compose.project=celestin`, vérifie leur appartenance et les supprime. Les **volumes Docker, réseaux externes, conteneurs Factify et conteneur ubuntu-edge** ne sont jamais supprimés.
5. Démarre la stack Compose `celestin` : Nginx `celestin-web:8080`, WordPress PHP et MariaDB.
6. Vérifie la disponibilité de l'installation WordPress par HTTP puis inscrit un marqueur idempotent `.celestin-wordpress-migrated`.

**Aucune commande SSH ni modification de fichier n'est nécessaire pour créer les secrets SQL.** Il faut toutefois que les secrets SSH GitHub Actions existants restent valides et que le serveur dispose de Docker, Compose, Git et OpenSSL (déjà requis par le système de déploiement).

**Important :** l'ancien site est statique. La base MariaDB et les médias WordPress sont sauvegardés dans des volumes Docker persistants. Cette migration peut causer une courte indisponibilité du **site Célestin uniquement**.


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
