<?php
if (!defined('ABSPATH')) exit;
$options=Celestin_Home::options();
$story=Celestin_Home::latest('cel_histoire');
$book=Celestin_Home::latest('cel_livre');
$character=Celestin_Home::latest('cel_personnage');
$city=Celestin_Home::latest('cel_lieu');
$places=Celestin_Home::featured('cel_lieu',5);
$activity=Celestin_Home::latest('cel_coloriage');
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
<?php
$hero_below=($options['hero_text_location']??'overlay')==='below';
$hero_style='--cel-hero-title-size:'.max(24,min(100,(int)$options['hero_title_size'])).'px;--cel-hero-text-size:'.max(12,min(32,(int)$options['hero_text_size'])).'px;--cel-hero-x:'.max(0,min(100,(int)$options['hero_x'])).'%;--cel-hero-y:'.max(0,min(100,(int)$options['hero_y'])).'%;--cel-hero-width:'.max(240,min(1100,(int)$options['hero_width'])).'px;';
?>
<section class="cel-hero <?php echo $hero_below?'cel-hero-below':'cel-hero-overlay'; ?>" aria-labelledby="cel-hero-title" style="<?php echo esc_attr($hero_style); ?>">
 <div class="cel-hero-picture">
 <?php if(Celestin_Home::image('hero')) echo '<img class="cel-hero-art" src="'.esc_url(Celestin_Home::image('hero')).'" alt="L’univers illustré de Célestin et de Fleuriville" fetchpriority="high">'; ?>
 </div>
 <div class="cel-hero-copy">
 <?php if($options['hero_intro']!==''): ?><p class="cel-eyebrow"><?php echo esc_html($options['hero_intro']); ?></p><?php endif; ?>
 <h1 id="cel-hero-title"><?php echo nl2br(esc_html($options['headline'])); ?></h1>
 <?php if($options['hero_description']!==''): ?><p class="cel-hero-description"><?php echo nl2br(esc_html($options['hero_description'])); ?></p><?php endif; ?>
 <?php if($options['hero_button']): ?><a class="cel-button cel-primary" href="<?php echo esc_url($options['hero_link']?:'#cel-decouvrir'); ?>"><?php echo esc_html($options['hero_button']); ?> <span aria-hidden="true">→</span></a><?php endif; ?>
 </div>
</section>
<?php require __DIR__.'/navigation.php'; ?>
<section id="cel-decouvrir" class="cel-section cel-news" aria-labelledby="cel-news-h">
  <h2 id="cel-news-h" class="cel-section-title"><span>✦</span> Les nouveautés <span>✦</span></h2>
  <?php if($news): ?>
  <div class="cel-cards">
    <?php foreach($news as $item):
      $kind = [
        'cel_coloriage' => 'Activité',
        'cel_personnage' => 'Personnage',
        'cel_livre' => 'Livre',
        'cel_histoire' => 'Mini-histoire audio',
      ][$item->post_type] ?? 'Découverte';
      // Illustration de la fiche uniquement : ne pas présenter une autre illustration à sa place.
      $img = get_the_post_thumbnail_url($item->ID, 'medium_large');
      $summary = Celestin_Fields::value($item->ID, 'resume') ?: ($item->post_excerpt ?: wp_strip_all_tags(strip_shortcodes($item->post_content)));
      if (!$summary && $item->post_type === 'cel_personnage') $summary = Celestin_Fields::value($item->ID, 'description');
    ?>
      <a class="cel-card" href="<?php echo esc_url(get_permalink($item)); ?>">
        <div class="cel-card-image">
         <?php if($img): ?><img src="<?php echo esc_url($img); ?>" alt="" loading="lazy"><?php else: ?><div class="cel-card-placeholder" aria-hidden="true">✦</div><?php endif; ?>
         <span class="cel-card-type"><?php echo esc_html($kind); ?></span>
        </div>
        <div class="cel-card-body"><h3><?php echo esc_html(get_the_title($item)); ?></h3>
          <?php if($summary): ?><p><?php echo esc_html(wp_trim_words($summary, 16)); ?></p><?php endif; ?>
          <span class="cel-arrow" aria-hidden="true">→</span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <p class="cel-news-empty">Les prochaines nouveautés de Célestin arrivent bientôt !</p>
  <?php endif; ?>
</section>
<section class="cel-section cel-story-section" aria-labelledby="cel-story-title">
 <h2 id="cel-story-title" class="cel-section-title"><span>✦</span> Mini-histoires audio <span>✦</span></h2>
 <?php if($story):
   $story_image=get_the_post_thumbnail_url($story->ID,'large');
   $mp3=Celestin_Fields::value($story->ID,'audio');
   $story_text=Celestin_Home::story_text($story);
   $story_summary=Celestin_Fields::value($story->ID,'resume');
 ?>
 <div class="cel-story-panel">
  <a class="cel-story-image" href="<?php echo esc_url(get_permalink($story)); ?>">
   <?php if($story_image): ?><img src="<?php echo esc_url($story_image); ?>" alt="<?php echo esc_attr($story->post_title); ?>" loading="lazy"><?php else: ?><div class="cel-illustration-placeholder"><span aria-hidden="true">✦</span><span>Illustration de l’histoire à venir</span></div><?php endif; ?>
  </a>
  <div class="cel-story-player">
    <h3><?php echo esc_html($story->post_title); ?></h3>
    <?php if($mp3): ?><audio controls preload="metadata" src="<?php echo esc_url($mp3); ?>" aria-label="Écouter <?php echo esc_attr($story->post_title); ?>"></audio><?php else: ?><p>Le fichier audio sera bientôt disponible.</p><?php endif; ?>
    <?php if($story_summary): ?><p><?php echo esc_html($story_summary); ?></p><?php endif; ?>
    <a class="cel-text-link" href="<?php echo esc_url(get_permalink($story)); ?>">Écouter ou lire cette histoire →</a>
  </div>
  <div class="cel-story-text"><div class="cel-story-scroll"><?php echo wp_kses_post(wpautop(esc_html(wp_trim_words($story_text,110,'…')))); ?></div></div>
 </div>
 <?php else: ?><p class="cel-home-empty">Choisis une mini-histoire dans son administration pour la présenter ici.</p><?php endif; ?>
</section>
<section class="cel-section cel-books" aria-labelledby="cel-book-title">
 <h2 id="cel-book-title" class="cel-section-title"><span>✦</span> Les livres <span>✦</span></h2>
 <?php if($book):
   $book_image=get_the_post_thumbnail_url($book->ID,'large');
   $book_resume=Celestin_Fields::value($book->ID,'resume');
   $book_month=Celestin_Fields::value($book->ID,'mois');
   $book_collection=Celestin_Fields::value($book->ID,'collection');
   $book_activities=Celestin_Fields::linked($book->ID,'cel_coloriage');
 ?>
 <div class="cel-books-layout">
   <div class="cel-book-preview">
     <div class="cel-book-spread">
       <div class="cel-book-page">
         <?php if($book_image): ?><img src="<?php echo esc_url($book_image); ?>" alt="<?php echo esc_attr('Couverture de '.$book->post_title); ?>" loading="lazy"><?php else: ?><span aria-hidden="true">✦</span><?php endif; ?>
       </div>
       <div class="cel-book-page"><div class="cel-book-text"><?php echo esc_html(wp_trim_words($book_resume ?: $book->post_title,22,'…')); ?></div><span aria-hidden="true" class="cel-book-flower">✿</span></div>
     </div>
     <a class="cel-button cel-book-read" href="<?php echo esc_url(get_permalink($book)); ?>">Découvrir ce livre →</a>
   </div>
   <div class="cel-book-description">
    <h3><?php echo esc_html($book->post_title); ?></h3>
    <?php if($book_resume): ?><p><?php echo nl2br(esc_html($book_resume)); ?></p><?php endif; ?>
    <?php if($book_month || $book_collection): ?><div class="cel-chips"><?php if($book_month): ?><span>📅 <?php echo esc_html($book_month); ?></span><?php endif; ?><?php if($book_collection): ?><span>📖 <?php echo esc_html($book_collection); ?></span><?php endif; ?></div><?php endif; ?>
    <?php if($book_activities): ?>
    <div class="cel-home-associated"><strong>Les activités de ce livre</strong>
      <?php foreach(array_slice($book_activities,0,3) as $related): ?>
        <a href="<?php echo esc_url(get_permalink($related)); ?>"><?php echo esc_html($related->post_title); ?> →</a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <a class="cel-text-link" href="<?php echo esc_url(Celestin_Home::url('cel_livre')); ?>">Voir tous les livres →</a>
   </div>
 </div>
 <?php else: ?><p class="cel-home-empty">Sélectionne un livre depuis sa fiche WordPress pour l’afficher ici.</p><?php endif; ?>
</section>
<?php if($activity): ?>
<section class="cel-section cel-home-activity" aria-labelledby="cel-activity-title">
 <h2 id="cel-activity-title" class="cel-section-title"><span>✦</span> Mes petites activités <span>✦</span></h2>
 <div class="cel-featured-activity">
  <a href="<?php echo esc_url(get_permalink($activity)); ?>" class="cel-featured-activity-image">
   <?php if(has_post_thumbnail($activity)): echo get_the_post_thumbnail($activity->ID,'medium_large'); else: ?><div class="cel-illustration-placeholder"><span>✎</span><span>Illustration à ajouter</span></div><?php endif; ?>
  </a>
  <div><h3><?php echo esc_html($activity->post_title); ?></h3>
   <p><?php echo esc_html(Celestin_Fields::value($activity->ID,'resume')); ?></p>
   <a class="cel-button cel-primary" href="<?php echo esc_url(get_permalink($activity)); ?>">Découvrir cette activité →</a>
  </div>
 </div>
</section>
<?php endif; ?>
<div class="cel-two-columns">
 <section class="cel-info-panel" aria-labelledby="cel-character-title">
   <h2 id="cel-character-title" class="cel-section-title small">Fiches personnages</h2>
   <?php if($character):
      $character_desc=Celestin_Fields::value($character->ID,'description');
      $character_prenom=Celestin_Fields::value($character->ID,'prenom');
      $character_age=Celestin_Fields::value($character->ID,'age');
   ?>
   <div class="cel-character-wrap">
    <div class="cel-character-image"><?php if(has_post_thumbnail($character))echo get_the_post_thumbnail($character->ID,'medium');else echo '<div class="cel-illustration-placeholder"><span>✦</span><span>Portrait à ajouter</span></div>'; ?></div>
    <div><h3><?php echo esc_html($character->post_title); ?></h3>
    <?php if($character_prenom): ?><p><strong>Prénom :</strong> <?php echo esc_html($character_prenom); ?></p><?php endif; ?>
    <?php if($character_age): ?><p><strong>Âge :</strong> <?php echo esc_html($character_age); ?></p><?php endif; ?>
    <?php if($character_desc): ?><p class="cel-character-desc"><?php echo esc_html(wp_trim_words($character_desc,30,'…')); ?></p><?php endif; ?>
    <a class="cel-text-link" href="<?php echo esc_url(get_permalink($character)); ?>">Découvrir sa fiche →</a>
    </div>
   </div>
   <?php else: ?><p class="cel-home-empty">Choisis un personnage à présenter sur l’accueil.</p><?php endif; ?>
 </section>
 <section class="cel-info-panel" aria-labelledby="cel-city-title">
   <h2 id="cel-city-title" class="cel-section-title small">Fleuriville</h2>
   <?php if($places): ?>
   <div class="cel-city-wrap">
     <div class="cel-city-image">
       <?php if($city && has_post_thumbnail($city)): ?><a href="<?php echo esc_url(get_permalink($city)); ?>"><?php echo get_the_post_thumbnail($city->ID,'medium_large'); ?></a>
       <?php else: ?><div class="cel-illustration-placeholder"><span aria-hidden="true">⌂</span><span>Illustration du lieu à ajouter</span></div><?php endif; ?>
     </div>
     <div><h3>Découvre les lieux de Fleuriville</h3>
       <?php if($city): ?><p><?php echo esc_html(wp_trim_words(Celestin_Fields::value($city->ID,'resume'),23,'…')); ?></p><?php endif; ?>
       <?php foreach($places as $place): ?>
       <a class="cel-city-link" href="<?php echo esc_url(get_permalink($place)); ?>">⌂ <?php echo esc_html($place->post_title); ?> →</a>
       <?php endforeach; ?>
       <a class="cel-text-link" href="<?php echo esc_url(Celestin_Home::url('cel_lieu')); ?>">Tous les lieux →</a>
     </div>
   </div>
   <?php else: ?><p class="cel-home-empty">Sélectionne les lieux de Fleuriville à afficher ici.</p><?php endif; ?>
 </section>
</div>
<section class="cel-welcome" aria-labelledby="cel-welcome-title">
 <?php if(Celestin_Home::image('footer')) echo '<img src="'.esc_url(Celestin_Home::image('footer')).'" alt="" loading="lazy">'; ?>
 <div class="cel-welcome-copy"><h2 id="cel-welcome-title">Bienvenue à Fleuriville !</h2><p><?php echo esc_html($options['welcome']); ?></p><a class="cel-button cel-primary" href="<?php echo esc_url(Celestin_Home::url('cel_lieu')); ?>">Découvrir Fleuriville →</a></div>
</section>
</main>
<footer class="cel-footer">
 <div class="cel-footer-columns">
 <div class="cel-footer-brand"><a href="<?php echo esc_url(home_url('/')); ?>" aria-label="Accueil — Célestin le renardeau"><?php
   // Le logo spécifique du pied de page est facultatif : à défaut, reprendre celui de l'en-tête.
   $footer_logo=Celestin_Home::image('footer_logo') ?: Celestin_Home::image('logo');
   if($footer_logo) echo '<img class="cel-footer-logo" src="'.esc_url($footer_logo).'" alt="Célestin le renardeau" loading="lazy">';
   else echo '<span class="cel-script">Célestin<small>le renardeau</small></span>';
 ?></a></div>
 <div><h3>Explorer</h3><a href="<?php echo esc_url(home_url('/')); ?>">Accueil</a><?php foreach($links as $label=>$v): ?><a href="<?php echo esc_url($v['url']); ?>"><?php echo esc_html($label); ?></a><?php endforeach; ?></div>
 <div><h3>À propos</h3><p>L’univers de Célestin et toutes ses petites aventures.</p></div>
 <div><h3>La newsletter de Fleuriville</h3><p>Retrouve les nouvelles histoires, les livres et les activités !</p><?php if($options['newsletter']): ?><a class="cel-button" href="<?php echo esc_url($options['newsletter']); ?>">S’inscrire →</a><?php else: ?><p class="cel-footer-note">Bientôt disponible</p><?php endif; ?></div>
 </div>
 <div class="cel-legal">© <?php echo esc_html(date_i18n('Y')); ?> Célestin le renardeau · Tous droits réservés</div>
</footer>
</div>
<?php wp_footer(); ?>
</body></html>
