<?php
if (!defined('ABSPATH')) exit;
$options=Celestin_Home::options();
$story=Celestin_Home::latest('cel_histoire');
$book=Celestin_Home::latest('cel_livre');
$character=Celestin_Home::latest('cel_personnage');
$city=Celestin_Home::latest('cel_lieu');
$news=Celestin_Home::cards();
$links=[
 'Personnages'=>['url'=>Celestin_Home::url('cel_personnage'),'symbol'=>'✿'],
 'Mes petites activités'=>['url'=>Celestin_Home::url('cel_coloriage'),'symbol'=>'✎'],
 'Les livres'=>['url'=>Celestin_Home::url('cel_livre'),'symbol'=>'▤'],
 'Mini-histoires'=>['url'=>Celestin_Home::url('cel_histoire'),'symbol'=>'♫']
];
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class('cel-page'); ?>>
<?php wp_body_open(); ?>
<a class="cel-skip" href="#cel-main">Aller au contenu</a>
<div class="cel-site">
<header class="cel-header">
  <a class="cel-logo" href="<?php echo esc_url(home_url('/')); ?>" aria-label="Accueil — Célestin le renardeau">
  <?php if(Celestin_Home::image('logo')) echo '<img src="'.esc_url(Celestin_Home::image('logo')).'" alt="Célestin le renardeau">'; else echo '<span class="cel-script">Célestin<small>le renardeau</small></span>'; ?>
  </a>
  <a class="cel-button cel-header-cta" href="#cel-decouvrir">Rejoins l’aventure ! <span aria-hidden="true">→</span></a>
</header>
<main id="cel-main">
<section class="cel-hero" aria-labelledby="cel-hero-title">
 <?php if(Celestin_Home::image('hero')) echo '<img class="cel-hero-art" src="'.esc_url(Celestin_Home::image('hero')).'" alt="L’univers illustré de Célestin et de Fleuriville" fetchpriority="high">'; ?>
 <div class="cel-hero-copy">
 <p class="cel-eyebrow">Bienvenue dans l’univers de Célestin</p>
 <h1 id="cel-hero-title"><?php echo esc_html($options['headline']); ?><i>✦</i></h1>
 <p>Des histoires à lire, des aventures à écouter et plein de petites activités à découvrir.</p>
 <a class="cel-button cel-primary" href="#cel-decouvrir">Découvrir l’univers <span aria-hidden="true">→</span></a>
 </div>
</section>
<nav class="cel-nav" aria-label="Explorer les rubriques">
 <?php foreach($links as $label=>$v): ?>
 <a href="<?php echo esc_url($v['url']); ?>"><span aria-hidden="true"><?php echo esc_html($v['symbol']); ?></span><?php echo esc_html($label); ?></a>
 <?php endforeach; ?>
</nav>
<section id="cel-decouvrir" class="cel-section cel-news" aria-labelledby="cel-news-h">
  <h2 id="cel-news-h" class="cel-section-title"><span>✦</span> Les nouveautés <span>✦</span></h2>
  <div class="cel-cards">
    <?php foreach($news as $item):
      $kind=$item->post_type==='cel_livre'?'Livre':($item->post_type==='cel_histoire'?'Mini-histoire audio':'Activité');
      $fallback=$item->post_type==='cel_livre'?'book':($item->post_type==='cel_histoire'?'story':'activity');
      $img=get_the_post_thumbnail_url($item->ID,'medium_large')?:Celestin_Home::image($fallback);
    ?>
      <a class="cel-card" href="<?php echo esc_url(get_permalink($item)); ?>">
        <div class="cel-card-image">
         <?php if($img): ?><img src="<?php echo esc_url($img); ?>" alt="" loading="lazy"><?php else: ?><div class="cel-card-placeholder" aria-hidden="true">✦</div><?php endif; ?>
         <span class="cel-card-type"><?php echo esc_html($kind); ?></span>
        </div>
        <div class="cel-card-body"><h3><?php echo esc_html($item->post_title); ?></h3><p><?php echo esc_html(wp_trim_words($item->post_excerpt?:wp_strip_all_tags($item->post_content),12)); ?></p><span class="cel-arrow" aria-hidden="true">→</span></div>
      </a>
    <?php endforeach; ?>
    <?php if(!$news): ?>
      <?php foreach([['Mini-histoire audio','La prochaine aventure à écouter','story'],['Activité','Un coloriage à découvrir','activity'],['Livre','Une histoire à feuilleter','book']] as $demo): ?>
      <article class="cel-card cel-card-empty"><div class="cel-card-image"><?php echo Celestin_Home::render_image($demo[2],$demo[0]); ?><span class="cel-card-type"><?php echo esc_html($demo[0]); ?></span></div>
      <div class="cel-card-body"><h3><?php echo esc_html($demo[1]); ?></h3><p>À découvrir prochainement !</p></div></article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>
<section class="cel-section cel-story-section" aria-labelledby="cel-story-title">
 <h2 id="cel-story-title" class="cel-section-title"><span>✦</span> Mini-histoires audio <span>✦</span></h2>
 <div class="cel-story-panel">
  <div class="cel-story-image"><?php
   if($story && has_post_thumbnail($story)) echo get_the_post_thumbnail($story,'large');
   else echo Celestin_Home::render_image('story','Illustration de mini-histoire');
  ?></div>
  <div class="cel-story-player">
    <h3><?php echo esc_html($story?$story->post_title:'Une petite histoire de Célestin'); ?></h3>
    <?php $mp3=$story?(string)get_post_meta($story->ID,'_cel_story_mp3',true):''; ?>
    <?php if($mp3): ?><audio controls preload="metadata" src="<?php echo esc_url($mp3); ?>" aria-label="Écouter la mini-histoire"></audio><?php else: ?><div class="cel-audio-fallback"><span aria-hidden="true">▶</span> Une histoire arrive bientôt</div><?php endif; ?>
    <p>Écoute la mini-histoire et suis le texte pas à pas !</p>
    <a class="cel-text-link" href="<?php echo esc_url($story?get_permalink($story):Celestin_Home::url('cel_histoire')); ?>">Découvrir les mini-histoires →</a>
  </div>
  <div class="cel-story-text"><div class="cel-story-scroll"><?php echo wp_kses_post(wpautop(esc_html(wp_trim_words(Celestin_Home::story_text($story),90,'…')))); ?></div></div>
 </div>
</section>
<section class="cel-section cel-books" aria-labelledby="cel-book-title">
 <h2 id="cel-book-title" class="cel-section-title"><span>✦</span> Les livres <span>✦</span></h2>
 <div class="cel-books-layout">
   <div class="cel-book-preview">
     <div class="cel-book-spread">
      <div class="cel-book-page">
       <?php if(Celestin_Home::image('book')) echo Celestin_Home::render_image('book','Une histoire de Célestin'); elseif($book && has_post_thumbnail($book)) echo get_the_post_thumbnail($book,'large'); else echo '<span aria-hidden="true">✦</span>'; ?>
      </div>
      <div class="cel-book-page"><div class="cel-book-text">Un monde rempli<br>de découvertes,<br>de douceur<br>et d’aventures !</div><span aria-hidden="true" class="cel-book-flower">✿</span></div>
     </div>
     <?php if($book): ?><a class="cel-button cel-book-read" href="<?php echo esc_url(get_permalink($book)); ?>">Feuilleter ce livre →</a><?php endif; ?>
   </div>
   <div class="cel-book-description">
    <h3>Des livres carrés à lire en double-page</h3>
    <p>De jolies histoires illustrées adaptées aux enfants, à lire comme un vrai livre : page après page.</p>
    <div class="cel-chips"><span>📖 Aventure</span><span>🌿 Nature</span><span>♡ Amitié</span><span>✦ Découverte</span></div>
    <a class="cel-activity-banner" href="<?php echo esc_url(Celestin_Home::url('cel_coloriage')); ?>"><span>✎</span><span><strong>Prolonge la lecture avec des activités !</strong><small>Coloriages, jeux, bricolages et découvertes…</small></span><span aria-hidden="true">→</span></a>
    <a class="cel-text-link" href="<?php echo esc_url(Celestin_Home::url('cel_livre')); ?>">Voir tous les livres →</a>
   </div>
 </div>
</section>
<div class="cel-two-columns">
 <section class="cel-info-panel" aria-labelledby="cel-character-title">
   <h2 id="cel-character-title" class="cel-section-title small">Fiches personnages</h2>
   <div class="cel-character-wrap">
    <div class="cel-character-image"><?php if($character && has_post_thumbnail($character)) echo get_the_post_thumbnail($character,'medium'); else echo Celestin_Home::render_image('character','Portrait de Célestin'); ?></div>
    <div><h3><?php echo esc_html($character?$character->post_title:'Célestin'); ?></h3>
    <p><strong>Prénom :</strong> Célestin</p><p><strong>Âge :</strong> 6 ans</p>
    <p class="cel-character-desc"><?php echo esc_html($character?wp_trim_words(wp_strip_all_tags($character->post_content),30):'Un petit renardeau curieux, joyeux et toujours prêt pour de nouvelles aventures !'); ?></p>
    <a class="cel-text-link" href="<?php echo esc_url($character?get_permalink($character):Celestin_Home::url('cel_personnage')); ?>">Rencontrer les personnages →</a>
    </div>
   </div>
 </section>
 <section class="cel-info-panel" aria-labelledby="cel-city-title">
   <h2 id="cel-city-title" class="cel-section-title small">Fleuriville</h2>
   <div class="cel-city-wrap">
     <div class="cel-city-image"><?php echo Celestin_Home::render_image('city','Carte illustrée de Fleuriville'); ?></div>
     <div><h3>Découvre Fleuriville !</h3><p>Un petit coin de nature plein de charme, où Célestin vit de grandes et de petites aventures.</p>
     <a class="cel-city-link" href="<?php echo esc_url(Celestin_Home::url('cel_lieu')); ?>">⌂ Le village →</a>
     <a class="cel-city-link" href="<?php echo esc_url(Celestin_Home::url('cel_lieu')); ?>">♣ La forêt →</a>
     <a class="cel-city-link" href="<?php echo esc_url(Celestin_Home::url('cel_lieu')); ?>">≈ La rivière →</a>
     </div>
   </div>
 </section>
</div>
<section class="cel-welcome" aria-labelledby="cel-welcome-title">
 <?php if(Celestin_Home::image('footer')) echo '<img src="'.esc_url(Celestin_Home::image('footer')).'" alt="" loading="lazy">'; ?>
 <div class="cel-welcome-copy"><h2 id="cel-welcome-title">Bienvenue à Fleuriville !</h2><p><?php echo esc_html($options['welcome']); ?></p><a class="cel-button cel-primary" href="<?php echo esc_url(Celestin_Home::url('cel_lieu')); ?>">Découvrir Fleuriville →</a></div>
</section>
</main>
<footer class="cel-footer">
 <div class="cel-footer-columns">
 <div class="cel-footer-brand"><span class="cel-script">Célestin<small>le renardeau</small></span></div>
 <div><h3>Explorer</h3><a href="<?php echo esc_url(home_url('/')); ?>">Accueil</a><?php foreach($links as $label=>$v): ?><a href="<?php echo esc_url($v['url']); ?>"><?php echo esc_html($label); ?></a><?php endforeach; ?></div>
 <div><h3>À propos</h3><p>L’univers de Célestin et toutes ses petites aventures.</p></div>
 <div><h3>La newsletter de Fleuriville</h3><p>Retrouve les nouvelles histoires, les livres et les activités !</p><?php if($options['newsletter']): ?><a class="cel-button" href="<?php echo esc_url($options['newsletter']); ?>">S’inscrire →</a><?php else: ?><p class="cel-footer-note">Bientôt disponible</p><?php endif; ?></div>
 </div>
 <div class="cel-legal">© <?php echo esc_html(date_i18n('Y')); ?> Célestin le renardeau · Tous droits réservés</div>
</footer>
</div>
<?php wp_footer(); ?>
</body></html>
