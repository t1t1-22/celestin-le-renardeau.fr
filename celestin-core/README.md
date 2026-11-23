# Célestin Core (version initiale)

Extension WordPress dédiée au site **Célestin le renardeau**, rangée dans le dépôt d'infrastructure existant, dossier `celestin-core/`.

## Fichiers

- `celestin-core.php` : extension WordPress autonome.

## État actuel

- Types de contenus dédiés : Mes petites activités, Livres, Mini-histoires audio, Fiches personnages, Lieux de Fleuriville.
- Catégories et étiquettes pour les livres, activités et mini-histoires.
- Illustration principale via l'image mise en avant de WordPress.
- URL du PDF d'activité, du PDF du livre et du MP3 de l'histoire.
- Case « Afficher dans Les nouveautés » et shortcode `[celestin_nouveautes]`.

**Pas encore implémentés** : visionneuse carrée à double page avec feuilletage, suivi et défilement du texte au rythme du MP3, relations entre contenus, fiches personnages enrichies, carte interactive, interface publique complète.

## Installation

Ce dossier est **uniquement versionné dans GitHub** : il n'est pas déployé automatiquement dans WordPress par cette modification.

Pour installer depuis l'administration WordPress, créer un ZIP contenant le dossier `celestin-core` et son fichier `celestin-core.php`, puis aller dans Extensions > Ajouter > Téléverser une extension.

Pour WP Pusher : le dépôt est `t1t1-22/celestin-le-renardeau.fr`, branche `main`, sous-dossier `celestin-core`. Le dépôt est privé et WP Pusher peut demander une licence pour l'utiliser.

## Sécurité du déploiement

Ne pas monter ce dossier en must-use plugin et ne pas modifier `compose.yml`, scripts de déploiement, autres sites ou volumes sans validation spécifique.
