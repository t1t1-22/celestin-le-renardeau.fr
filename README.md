# Célestin le renardeau — site et atelier d'administration

Site illustré de Fleurville : **Accueil, Personnages, Les livres, Mini-histoires, Coloriages**.

## Fonctionnement

Le site dispose d'un serveur Node.js natif (sans dépendances npm), d'une API de lecture publique et d'une administration privée accessible sur **/admin**.

Dans l'atelier, tu peux :

- Ajouter, modifier ou supprimer des **livres**, **mini-histoires** et **coloriages** ;
- Transférer une **couverture PNG/JPEG/WebP**, un **PDF** ou une image à télécharger (jusqu'à 10 Mo par fichier) ;
- Saisir le titre, le résumé, la catégorie, le texte intégral d'une histoire et, pour les livres, un lien externe ;
- Choisir **brouillon** ou **publié**. Seuls les contenus publiés sont visibles sur le site ;
- Modifier l'illustration principale de l'accueil (choisir une image **sans texte**, le texte du site se superposant à celle-ci).

Le texte des deux mini-histoires de l'ancien site est importé lors de la **première initialisation** depuis `public/ancienne-version.html`. Les ressources d'origine et la page ancienne restent disponibles pour comparaison.

## Mot de passe administrateur : configuration indispensable

Aucun mot de passe n'est créé dans le code ou dans Git. Sur le **serveur Ubuntu**, créer à la racine du dépôt déployé un fichier nommé `.env` :

```dotenv
CELESTIN_ADMIN_PASSWORD=choisir-ici-un-mot-de-passe-unique-et-long
```

Choisir un secret de 16 à 32 caractères (ou davantage), unique. **Ne pas committer ce fichier.** La connexion admin reste désactivée tant qu'aucun mot de passe de 12 caractères minimum n'est configuré.

Le processus déployé avec Docker Compose lit automatiquement cette variable au prochain `docker compose up -d` (ou au redéploiement). Le mot de passe est transmis au conteneur sous la variable `ADMIN_PASSWORD`.

L'administration utilise un cookie HttpOnly/SameSite=Strict, une expiration des sessions après 12 h, une vérification de l'origine des écritures, un délai après tentatives de connexion répétées et une validation des types de fichiers. Le serveur doit rester derrière le reverse-proxy HTTPS existant.

## Stockage persistant et sauvegardes

Les contenus et images sont enregistrés **hors de l'image Docker** dans le volume Docker nommé `celestin_celestin_content` (projet Compose `celestin`) ; les mises à jour du code ne les effacent pas.

La sauvegarde régulière de ce volume est indispensable. **Ne pas utiliser `docker compose down -v`**, qui détruirait le volume. Ne jamais stocker tes mots de passe dans le dépôt.

## Exécution locale

```bash
ADMIN_PASSWORD='un-test-local-d-au-moins-12-caracteres' NODE_ENV=development node server.mjs
```

Puis ouvrir http://localhost:8080 et http://localhost:8080/admin. Les données locales sont stockées dans `./data/` (exclu de Git).

## CI et déploiement

GitHub Actions vérifie le JavaScript, Docker, l'API publique, le statut privé de l'administration et les routes 404. Une fusion sur `main` déclenche ensuite le déploiement SSH existant via `scripts/deploy.sh`.

L'infrastructure Nginx inverse indépendante `ubuntu-edge` continue de router vers `celestin-web:8080` sur le réseau Docker `celestin_proxy`. Le conteneur HTTP devient Node plutôt que Nginx, mais conserve l'alias et le port.

Le fichier `public/ancienne-version.html` est une sauvegarde de la page antérieure. Les contenus ont été laissés éditables et des images de démonstration du dépôt d'origine sont utilisées tant que la nouvelle image finale n'a pas été transférée via l'atelier.

## Sécurité

Ne partage jamais le mot de passe de l'atelier. Pour révoquer les connexions en cours, redémarrer le conteneur (les sessions sont uniquement en mémoire). Sauvegarder les volumes Docker avant toute modification majeure.
