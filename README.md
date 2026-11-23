# Célestin le renardeau — WordPress + Fleurville

Le site `celestin-le-renardeau.fr` utilise **WordPress** (PHP/Apache), **MariaDB**, un frontal **Nginx**, et le thème WordPress **Astra** choisi dans l'administration. Le plugin obligatoire **Célestin × Astra** réalise les sections illustrées sans remplacer Astra. Il reste indépendant de `factcast` et du dépôt `ubuntu-edge`.

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

1. Vérifier que les tests GitHub Actions passent avant fusion sur `main`.
2. GitHub Actions déploie les trois conteneurs `web`, `wordpress` et `db` automatiquement. Le frontal conserve l'alias `celestin-web:8080` sur `celestin_proxy` et le domaine actuel : aucun DNS à modifier.
3. Ouvrir **https://celestin-le-renardeau.fr/wp-admin/install.php** et créer le premier administrateur WordPress **dans le navigateur**, avec un identifiant personnel, un mot de passe fort et un e-mail valide.
4. Se connecter ensuite à **https://celestin-le-renardeau.fr/wp-admin/**. WordPress conserve le thème Astra et charge automatiquement les rubriques administrables.
5. Dans **Réglages → Permaliens**, sélectionner « Titre de la publication », puis **Enregistrer**.
6. Dans **Univers Célestin**, choisir l'illustration finale de la bannière **sans texte**, puis celle de Fleurville, dans la médiathèque WordPress.

## Le site avec le thème Astra

L'utilisateur a activé **Astra** depuis WordPress. **Le thème Astra reste actif** : le code GitHub ne doit plus utiliser `switch_theme()`.

Le plugin obligatoire `wp-content/mu-plugins/celestin-astra.php` :
- crée une page d'accueil éditable « Accueil – Célestin le renardeau » avec le shortcode `[celestin_accueil]` ;
- l'assigne comme page d'accueil à la première initialisation, sans supprimer l'ancienne page (la configuration précédente est sauvegardée dans une option) ;
- crée quatre pages de catalogues distinctes, modifiables dans **Pages** : livres, mini-histoires, coloriages et personnages ;
- conserve les publications existantes et affiche automatiquement les derniers contenus publiés depuis les rubriques natives WordPress ;
- crée le menu « Navigation Célestin » uniquement si le menu principal Astra n'est pas déjà défini ;
- utilise le logo historique uniquement si aucun logo Astra n'a été sélectionné ;
- ajoute un menu **Univers Célestin** dans l'administration, pour charger les images principales avec la médiathèque et modifier le texte d'introduction ;
- ajoute les boutons de lecture, téléchargement des PDF et liens de livres sur les pages Astra.

Si la page d'accueil avait déjà été personnalisée dans Astra, son contenu reste conservé dans WordPress : aucune page antérieure n'est supprimée.

**Modifier la maquette** : dans **Pages → Accueil – Célestin le renardeau**, on retrouve le shortcode ; les sections sont gérées dynamiquement via les rubriques. Les images de la bannière et du village se changent dans **Univers Célestin**. Pour personnaliser l'en-tête, le logo, le menu et le pied de page : **Apparence → Personnaliser → Astra**.

Les images initiales `/images/jardin.webp` et `/images/logo.webp` servent de références provisoires. Remplace-les par les illustrations définitives depuis la médiathèque pour respecter les personnages et la maison.

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

Le module WordPress obligatoire `Atelier de Fleurville` et l'intégration `Célestin × Astra` sont synchronisés via GitHub. Le thème **Astra**, les autres extensions, les personnalisations et les uploads restent dans le volume WordPress : ils ne sont pas écrasés par les déploiements.

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
