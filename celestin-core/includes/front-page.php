<?php
if (!defined('ABSPATH')) exit;
$options=Celestin_Home::options();
$stories=Celestin_Home::featured('cel_histoire',3);
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
<?php require __DIR__.'/interior-header.php'; ?>
<main id="cel-main">
<?php
$hero_below=($options['hero_text_location']??'overlay')==='below';
$hero_style='--cel-hero-title-size:'.max(24,min(100,(int)$options['hero_title_size'])).'px;--cel-hero-text-size:'.max(12,min(32,(int)$options['hero_text_size'])).'px;--cel-hero-x:'.max(0,min(100,(int)$options['hero_x'])).'%;--cel-hero-y:'.max(0,min(100,(int)$options['hero_y'])).'%;--cel-hero-width:'.max(240,min(1100,(int)$options['hero_width'])).'px;';
?>
<?php if($news): ?>
<section id="cel-decouvrir" class="cel-feature-slider" aria-label="Les nouveautés" aria-roledescription="carrousel">
    <?php foreach($news as $idx=>$item):
      $kind = [
        'cel_coloriage' => 'Activité',
        'cel_personnage' => 'Personnage',
        'cel_livre' => 'Livre',
        'cel_histoire' => 'Mini-histoire audio',
      ][$item->post_type] ?? 'Découverte';
      // Illustration de la fiche uniquement : ne pas présenter une autre illustration à sa place.
      $img = get_the_post_thumbnail_url($item->ID, 'medium_large');
      $preview=$item->post_type==='cel_histoire'?(Celestin_Fields::value($item->ID,'resume_audio')?:Celestin_Fields::value($item->ID,'audio')):'';
      $title=get_the_title($item);
    ?>

<?php
$summary=Celestin_Fields::value($item->ID,'resume');
if(!$summary && $item->post_type==='cel_personnage')$summary=Celestin_Fields::value($item->ID,'description');
$summary=$summary?:$item->post_excerpt;
$cta=['cel_histoire'=>'Découvrir cette mini-histoire','cel_livre'=>'Découvrir ce livre','cel_coloriage'=>'Découvrir cette activité','cel_personnage'=>'Rencontrer ce personnage'][$item->post_type]??'Découvrir';
?>
<div class="cel-feature-slide" <?php if($idx!==0)echo 'hidden'; ?> data-slide="<?php echo esc_attr($idx); ?>">
 <div class="cel-feature-layout">
       <article class="cel-card cel-news-card cel-news-<?php echo esc_attr($item->post_type); ?>">
        <div class="cel-card-image">
         <a class="cel-news-image-link" href="<?php echo esc_url(get_permalink($item)); ?>" aria-label="<?php echo esc_attr($title); ?>">
         <?php if($img): ?><img src="<?php echo esc_url($img); ?>" alt="" loading="lazy"><?php else: ?><div class="cel-card-placeholder" aria-hidden="true">✦</div><?php endif; ?>
         </a>
         <span class="cel-card-type cel-news-badge">Nouveauté</span>
         <?php if($preview): ?>
         <audio preload="none" src="<?php echo esc_url($preview); ?>"></audio>
         <button type="button" class="cel-audio-toggle" aria-label="<?php echo esc_attr('Écouter '.$title); ?>" data-title="<?php echo esc_attr($title); ?>" aria-pressed="false">
         <svg viewBox="0 0 64 64" aria-hidden="true"><circle cx="32" cy="32" r="30" fill="#fffaf3"/><circle cx="32" cy="32" r="26" fill="none" stroke="#e9d3bb" stroke-width="4"/><circle class="cel-audio-ring" cx="32" cy="32" r="26" fill="none" stroke="#e98932" stroke-width="4" pathLength="100" stroke-dasharray="0 100" stroke-linecap="round" transform="rotate(-90 32 32)"/><path class="cel-audio-play" d="M25 20L44 32L25 44Z" fill="#a45b2c"/><rect class="cel-audio-stop" x="23" y="23" width="18" height="18" rx="3" fill="#a45b2c"/></svg>
         </button><span class="cel-audio-error" role="status" hidden>Audio indisponible.</span>
         <?php endif; ?>
        </div>
        <div class="cel-card-body"><span class="cel-news-kind"><?php echo esc_html($kind); ?></span><h3><a href="<?php echo esc_url(get_permalink($item)); ?>"><?php echo esc_html($title); ?></a></h3></div>
      </article>
 <div class="cel-feature-copy"><p class="cel-feature-kicker"><?php echo esc_html($kind); ?> · Nouveauté</p><h2><?php echo esc_html($title); ?></h2>
 <?php if($summary): ?><p class="cel-feature-summary"><?php echo esc_html(wp_trim_words(wp_strip_all_tags($summary),65,'…')); ?></p><?php endif; ?>
 <a class="cel-feature-link" href="<?php echo esc_url(get_permalink($item)); ?>"><?php echo esc_html($cta); ?> →</a></div>
 </div>
</div>
<?php endforeach; ?>
<?php if(count($news)>1): ?><div class="cel-feature-pagination" aria-label="Choisir une nouveauté"><?php foreach($news as $idx=>$item): ?><button type="button" data-slide="<?php echo esc_attr($idx); ?>" aria-label="<?php echo esc_attr(get_the_title($item)); ?>" aria-current="<?php echo $idx===0?'true':'false'; ?>"></button><?php endforeach; ?><button type="button" class="cel-feature-pause" aria-label="Mettre le défilement en pause">Ⅱ</button></div><?php endif; ?>
</section>
<?php else: ?>
<section class="cel-hero <?php echo $hero_below?'cel-hero-below':'cel-hero-overlay'; ?>" aria-labelledby="cel-hero-title" style="<?php echo esc_attr($hero_style); ?>">
 <div class="cel-hero-picture">
 <?php if(!empty($options['hero'])) echo wp_get_attachment_image((int)$options['hero'],'full',false,['class'=>'cel-hero-art','alt'=>'L’univers illustré de Célestin et de Fleuriville','fetchpriority'=>'high','loading'=>'eager']); ?>
 </div>
 <div class="cel-hero-copy">
 <?php if($options['hero_intro']!==''): ?><p class="cel-eyebrow"><?php echo esc_html($options['hero_intro']); ?></p><?php endif; ?>
 <h1 id="cel-hero-title"><?php echo nl2br(esc_html($options['headline'])); ?></h1>
 <?php if($options['hero_description']!==''): ?><p class="cel-hero-description"><?php echo nl2br(esc_html($options['hero_description'])); ?></p><?php endif; ?>
 <?php if($options['hero_button']): ?><a class="cel-button cel-primary" href="<?php echo esc_url($options['hero_link']?:'#cel-decouvrir'); ?>"><?php echo esc_html($options['hero_button']); ?> <span aria-hidden="true">→</span></a><?php endif; ?>
 </div>
</section>

<?php endif; ?>

<?php if($stories): ?>
<section class="cel-story-section cel-audio-feature" aria-labelledby="cel-story-title"><div aria-hidden="true" class="cel-fox-divider" style="position:absolute;bottom:calc(100% - 2px);left:50%;transform:translateX(-50%);width:min(52vw,800px);aspect-ratio:4/1;z-index:20;pointer-events:none;background:#e98932;"></div>

 <svg class="cel-audio-wave" viewBox="0 0 1440 70" preserveAspectRatio="none" aria-hidden="true"><path d="M0 0H1440V25C1100 85 850 0 540 36S120 66 0 24Z" fill="#fcf7ef"/></svg>
 <div class="cel-audio-inner">
 <h2 id="cel-story-title" class="cel-section-title">Les mini-histoires du moment</h2>
 <p class="cel-audio-intro">Installe-toi confortablement et laisse-toi raconter une histoire.</p>
 <div class="cel-audio-grid">
 <?php foreach($stories as $story):
 $title=get_the_title($story);$mp3=Celestin_Fields::value($story->ID,'resume_audio')?:Celestin_Fields::value($story->ID,'audio');
 $terms=get_the_terms($story->ID,Celestin_Library::TAX['categorie']);$theme=($terms&&!is_wp_error($terms))?$terms[0]->name:'';
 ?>
 <article class="cel-audio-card">
 <div class="cel-audio-art">
 <a href="<?php echo esc_url(get_permalink($story)); ?>" aria-label="<?php echo esc_attr($title); ?>">
 <?php if(has_post_thumbnail($story))echo get_the_post_thumbnail($story->ID,'large',['loading'=>'lazy','alt'=>'']);else echo '<div class="cel-illustration-placeholder" aria-hidden="true"><span>✦</span></div>'; ?>
 </a>
 <?php if($theme): ?><span class="cel-audio-theme"><?php echo esc_html($theme); ?></span><?php endif; ?>
 <?php if($mp3): ?>
 <audio preload="none" src="<?php echo esc_url($mp3); ?>"></audio>
 <button type="button" class="cel-audio-toggle" aria-label="<?php echo esc_attr('Écouter '.$title); ?>" data-title="<?php echo esc_attr($title); ?>" aria-pressed="false">
 <svg viewBox="0 0 64 64" aria-hidden="true"><circle cx="32" cy="32" r="30" fill="#fffaf3"/><circle cx="32" cy="32" r="26" fill="none" stroke="#e9d3bb" stroke-width="4"/><circle class="cel-audio-ring" cx="32" cy="32" r="26" fill="none" stroke="#e98932" stroke-width="4" pathLength="100" stroke-dasharray="0 100" stroke-linecap="round" transform="rotate(-90 32 32)"/><path class="cel-audio-play" d="M25 20L44 32L25 44Z" fill="#a45b2c"/><rect class="cel-audio-stop" x="23" y="23" width="18" height="18" rx="3" fill="#a45b2c"/></svg>
 </button><span class="cel-audio-error" role="status" hidden>Audio indisponible.</span>
 <?php endif; ?>
 </div><h3><a href="<?php echo esc_url(get_permalink($story)); ?>"><?php echo esc_html($title); ?></a></h3>
 </article>
 <?php endforeach; ?>
 <div class="cel-audio-cta"><a class="cel-audio-all" href="<?php echo esc_url(Celestin_Home::url('cel_histoire')); ?>">Découvrir toutes les mini-histoires →</a></div>
 </div>
 </div>
 <svg class="cel-audio-wave" viewBox="0 0 1440 60" preserveAspectRatio="none" aria-hidden="true"><path d="M0 32Q360 -12 720 32T1440 15V60H0Z" fill="#fcf7ef"/></svg>
</section>
<?php endif; ?>
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
     <?php $pdf=Celestin_Fields::value($book->ID,'pdf'); if($pdf): Celestin_Public::reader($pdf,true); else: ?>
     <a href="<?php echo esc_url(get_permalink($book)); ?>"><?php echo Celestin_Public::illustration($book->ID); ?></a>
     <?php endif; ?>
   </div>
   <div class="cel-book-description">
    <h3><a href="<?php echo esc_url(get_permalink($book)); ?>"><?php echo esc_html($book->post_title); ?></a></h3>
    <?php if($book_resume): ?><div class="cel-home-book-summary"><?php echo wpautop(esc_html($book_resume)); ?></div><?php endif; ?>
    <div class="cel-chips"><?php foreach(['categorie','collection','mois','saison','age_lecture','evenement'] as $key): $value=Celestin_Fields::value($book->ID,$key); if($value): ?><span><?php echo esc_html($value); ?></span><?php endif; endforeach; ?></div>
    <a class="cel-button cel-primary cel-home-book-all" href="<?php echo esc_url(Celestin_Home::url('cel_livre')); ?>">Découvrir tous les livres →</a>
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
