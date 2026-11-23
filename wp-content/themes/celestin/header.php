<!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#fff9ef">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a href="#main" class="skip-link">Passer au contenu</a>
<header class="site-header">
  <div class="wrap header-inner">
    <a href="<?php echo esc_url(home_url('/')); ?>" class="brand" aria-label="Célestin le renardeau, accueil">
      <?php
      if (has_custom_logo()) { the_custom_logo(); }
      else { echo '<img src="' . esc_url(home_url('/images/logo.webp')) . '" alt="Célestin le renardeau">'; }
      ?>
    </a>
    <button type="button" class="mobile-menu-toggle" aria-controls="site-nav" aria-expanded="false">☰ Menu</button>
    <nav id="site-nav" class="main-nav" aria-label="Menu principal">
      <a href="<?php echo clst_home_anchor('accueil'); ?>">Accueil</a>
      <a href="<?php echo clst_home_anchor('personnages'); ?>">Personnages</a>
      <a href="<?php echo clst_home_anchor('livres'); ?>">Les livres</a>
      <a href="<?php echo clst_home_anchor('mini-histoires'); ?>">Mini-histoires</a>
      <a href="<?php echo clst_home_anchor('coloriages'); ?>">Coloriages</a>
    </nav>
    <a href="<?php echo clst_home_anchor('livres'); ?>" class="join-button">⭐ Rejoins l'aventure !</a>
  </div>
</header>
<main id="main">
