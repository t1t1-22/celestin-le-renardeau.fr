<?php get_header(); ?>
<section id="accueil" class="hero" aria-labelledby="hero-title" style="--hero-image:url('<?php echo esc_url(clst_hero_image()); ?>')">
  <div class="wrap hero-content">
    <h1 id="hero-title">Pars à l'aventure<br><em>avec Célestin !</em></h1>
    <p><?php echo esc_html(get_theme_mod('celestin_intro', 'Des histoires douces, des coloriages et de jolies découvertes pour les petits curieux !')); ?></p>
    <div class="hero-actions">
      <a class="button button-gold" href="#livres">Découvrir l'univers →</a>
      <a class="button button-outline" href="#coloriages">Voir les coloriages 🎨</a>
    </div>
  </div>
</section>
<section class="wrap shortcuts" aria-label="Découvrir les rubriques">
  <a class="shortcut cream" href="#personnages"><span class="shortcut-icon">🦊 🐶</span><strong>Personnages</strong></a>
  <a class="shortcut coral" href="#coloriages"><span class="shortcut-icon">🎨</span><strong>Coloriages</strong></a>
  <a class="shortcut mint" href="#livres"><span class="shortcut-icon">📚</span><strong>Les livres</strong></a>
  <a class="shortcut lavender" href="#mini-histoires"><span class="shortcut-icon">🎬</span><strong>Mini-histoires</strong></a>
</section>
<?php
clst_section('celestin_livre', 'livres', '📖 Les livres de Célestin ✨', 'Voir tous les livres');
clst_section('celestin_histoire', 'mini-histoires', '🎬 Mini-histoires ✨', 'Toutes les mini-histoires');
clst_section('celestin_coloriage', 'coloriages', '🎨 Coloriages à imprimer ✨', 'Tous les coloriages');
?>
<section class="village-section" id="fleurville"><div class="wrap village-inner">
  <div class="village-scene" aria-hidden="true"><span>🏡</span><span>🌳</span><span>🌼</span></div>
  <div class="village-description"><h2>Bienvenue à Fleurville !</h2>
    <p>Un petit coin de nature plein de charme où Célestin vit de grandes et de petites aventures avec Octet, son fidèle compagnon.</p>
    <a class="button button-gold" href="#personnages">Découvrir Fleurville →</a>
  </div>
</div></section>
<?php clst_section('celestin_personnage', 'personnages', '🦊 Les personnages de Fleurville', 'Tous les personnages'); ?>
<section class="values"><div class="wrap"><h2>❤️ Un univers bienveillant pour les enfants ✨</h2>
  <div class="value-grid">
    <div><span>🛡️</span><section><h3>Des contenus adaptés</h3><p>Des histoires tendres, positives et ludiques pour les enfants.</p></section></div>
    <div><span>📚</span><section><h3>Pour les parents</h3><p>Lecture, imagination et créativité à partager en famille.</p></section></div>
    <div><span>🌱</span><section><h3>Des aventures du quotidien</h3><p>Entraide, amitié, découverte et bienveillance.</p></section></div>
  </div>
</div></section>
<?php get_footer(); ?>
