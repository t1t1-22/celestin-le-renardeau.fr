# Site Célestin le renardeau — WordPress / Astra

Le dépôt déploie WordPress et MariaDB avec les volumes persistants existants. Le module personnalisé `site/celestin-site.php` est monté en lecture seule dans les mu-plugins WordPress. Aucun conteneur Factify n'est modifié.

## Dans l'administration
Ouvrir **Site Célestin** dans le menu de gauche pour choisir les illustrations depuis la médiathèque et modifier les textes du slide et de Fleuriville.

Les rubriques WordPress dédiées sont : **Livres, Mini-histoires, Mes petites activités, Personnages et Lieux de Fleuriville**. L'accueil présente les trois dernières publications. Les pages **Accueil, Fleuriville, Notre histoire, Rejoins l'aventure et Nous contacter** sont créées à la première visite de l'administration si elles n'existent pas déjà.

## Images
Le dépôt contient la mise en page et les emplacements d'images, pas les illustrations finales : importez des images depuis **Médias > Ajouter**, puis sélectionnez-les dans **Site Célestin**. Les dessins de maquette restent provisoires : remplacer par les créations originales avant la publication.

## Déploiement
Le workflow existant déploie le projet Docker. Les volumes WordPress et MariaDB sont conservés. **Ne pas exécuter docker compose down -v** sur le serveur.
