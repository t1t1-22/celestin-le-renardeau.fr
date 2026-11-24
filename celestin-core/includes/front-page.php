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
<style>
body.cel-page .cel-book-editorial .cel-books-layout{grid-template-columns:minmax(0, .9fr) minmax(0,1.1fr);gap:clamp(32px,6vw,85px);max-width:1000px;padding:30px 12px;align-items:center}
body.cel-page .cel-book-editorial .cel-book-description{background:transparent;border:0;padding:0;box-shadow:none}
body.cel-page .cel-book-editorial .cel-book-editorial-title{font:700 clamp(27px,3vw,38px)/1.15 'Baloo 2',sans-serif;margin:0 0 15px;color:#633619}
body.cel-page .cel-book-editorial-title a{color:inherit;text-decoration:none}
body.cel-page .cel-book-editorial .cel-book-preview{padding:24px 12px 38px 32px;perspective:1300px;display:flex;justify-content:center;position:relative;isolation:isolate}
body.cel-page .cel-book-editorial .cel-book-preview:before{content:"";position:absolute;left:50%;bottom:28px;width:84%;max-width:365px;height:22px;transform:translateX(-50%);background:radial-gradient(ellipse at center,#51371965 0%,#51371930 38%,transparent 72%);filter:blur(6px);z-index:-1;pointer-events:none}
body.cel-page .cel-book-editorial .cel-book-preview .cel-book-mockup{--book-depth:28px;position:relative;display:block;width:100%;max-width:365px;aspect-ratio:1;padding:0;border:0;border-radius:4px 9px 9px 4px;background:#f4e7d4;transform-style:preserve-3d;transform:rotateY(9deg) rotateX(0deg) rotateZ(0deg);box-shadow:none;overflow:visible;transition:transform .55s cubic-bezier(.22,.61,.36,1)}
body.cel-page .cel-book-editorial .cel-book-mockup:before{content:"";position:absolute;inset:1px;border-radius:4px 9px 9px 4px;background:#d9791f;transform:translateZ(calc(-1 * var(--book-depth)));box-shadow:none;pointer-events:none}
body.cel-page .cel-book-editorial .cel-book-mockup:after{content:"";position:absolute;left:0;top:0;bottom:0;width:var(--book-depth);transform-origin:left center;transform:rotateY(90deg);background:linear-gradient(90deg,#b85e13 0%,#e98932 18%,#f3a24e 45%,#cf741f 100%);border-radius:3px 0 0 3px;pointer-events:none}
body.cel-page .cel-book-editorial .cel-book-mockup .cel-book-face{position:absolute;inset:0;display:block;overflow:hidden;border-radius:inherit;background:#f4e7d4;transform:translateZ(.5px);box-shadow:0 0 0 1px #88674630}
body.cel-page .cel-book-editorial .cel-book-mockup .cel-book-face:after{content:"";position:absolute;inset:0;background:linear-gradient(90deg,#33200d36 0%,#fff2 1.2%,#34220f16 2.2%,transparent 4%);pointer-events:none}
body.cel-page .cel-book-editorial .cel-book-mockup img{display:block;width:100%;height:100%;max-width:none;object-fit:contain;border-radius:inherit;box-shadow:none}
body.cel-page .cel-book-editorial .cel-book-mockup .cel-book-pages{display:none}
body.cel-page .cel-book-editorial .cel-book-preview .cel-book-mockup:hover{transform:rotateY(32deg) rotateX(0deg) rotateZ(0deg)}
body.cel-page .cel-book-editorial .cel-feature-tags{margin-bottom:17px} body.cel-page .cel-book-editorial .cel-book-library-link{display:block;margin-top:12px;font-size:14px;color:#633619;text-decoration:underline}
body.cel-page .cel-book-editorial .cel-book-more summary{list-style:none;cursor:pointer;color:#633619;font-size:15px;line-height:1.65}
body.cel-page .cel-book-editorial .cel-book-more summary::-webkit-details-marker{display:none}
body.cel-page .cel-book-editorial .cel-book-more-label{display:block;color:#a75721;font-weight:700;font-size:14px;margin-top:5px}
body.cel-page .cel-book-editorial .cel-book-close,body.cel-page .cel-book-editorial .cel-book-more[open] .cel-book-open,body.cel-page .cel-book-editorial .cel-book-more[open] .cel-book-excerpt{display:none}
body.cel-page .cel-book-editorial .cel-book-more[open] .cel-book-close{display:inline}
body.cel-page .cel-book-editorial .cel-book-listen{display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin:15px 0;color:#633619;font-size:14px;font-weight:700}
body.cel-page .cel-book-editorial .cel-book-listen audio{width:100%;max-width:350px;height:42px}
body.cel-page .cel-book-editorial .cel-home-book-all{margin-top:18px} body.cel-page .cel-book-editorial .cel-book-listen .cel-audio-toggle{position:relative;inset:auto;transform:none;width:48px;height:48px;flex:0 0 48px;padding:0;border:0;background:transparent;box-shadow:none} body.cel-page .cel-book-editorial .cel-book-listen .cel-audio-toggle svg{width:48px;height:48px;display:block} body.cel-page .cel-book-editorial .cel-book-listen .cel-audio-error[hidden]{display:none}
@media(max-width:760px){body.cel-page .cel-book-editorial .cel-books-layout{grid-template-columns:1fr;gap:12px;padding:15px 8px}body.cel-page .cel-book-editorial .cel-book-preview .cel-book-mockup{max-width:240px}body.cel-page .cel-book-editorial .cel-feature-tags{justify-content:flex-start}}
@media(prefers-reduced-motion:reduce){body.cel-page .cel-book-editorial .cel-book-preview .cel-book-mockup:hover{transform:rotateY(9deg) rotateX(0deg) rotateZ(0deg)}}
/* Présentation éditoriale de l’album. */
body.cel-page .cel-book-editorial .cel-book-description{display:flex;flex-direction:column;align-items:flex-start;gap:16px}
body.cel-page .cel-book-editorial .cel-book-editorial-title{margin:0;line-height:1.12;font-size:clamp(29px,3vw,40px)}
body.cel-page .cel-book-editorial .cel-feature-tags{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px 18px;width:100%;margin:0;padding:14px 0;border-top:1px solid #e5d8c7;border-bottom:1px solid #e5d8c7}
body.cel-page .cel-book-editorial .cel-feature-tags>span{display:flex;flex-direction:column;align-items:flex-start;gap:3px;background:none;border:0;border-radius:0;padding:3px 0;font-size:14px;line-height:1.35}
body.cel-page .cel-book-editorial .cel-feature-tags>span>strong{font-size:10px;text-transform:uppercase;letter-spacing:.09em;color:#a75b28;font-weight:700}
body.cel-page .cel-book-editorial .cel-feature-tags>span>span{font-size:14px;background:none;border:0;padding:0;border-radius:0;font-weight:600}
body.cel-page .cel-book-editorial .cel-home-book-summary{width:100%;margin:0;font-size:15px;line-height:1.65}
body.cel-page .cel-book-editorial .cel-book-more summary{font-size:15px;line-height:1.7}
body.cel-page .cel-book-editorial .cel-book-more-label{display:inline-flex;margin:4px 0 0 5px;text-decoration:underline;text-underline-offset:3px}
body.cel-page .cel-book-editorial .cel-book-actions{display:flex;flex-wrap:wrap;align-items:center;gap:10px 14px;width:100%;margin-top:4px}
body.cel-page .cel-book-editorial .cel-book-actions .cel-home-book-all{margin:0;background:#e98932;color:#fff!important;padding:12px 20px;border-radius:25px;box-shadow:0 3px 0 #c46c23}
body.cel-page .cel-book-editorial .cel-book-actions .cel-home-book-all:hover{background:#d77929}
body.cel-page .cel-book-editorial .cel-book-actions .cel-book-library-link{display:inline-flex;align-items:center;gap:8px;margin:0;padding:10px 14px;border:1px solid #d9c5ad;border-radius:25px;text-decoration:none;font-weight:600}
body.cel-page .cel-book-editorial .cel-book-actions .cel-book-library-link:hover{background:#f2e5d5}
body.cel-page .cel-book-editorial .cel-book-listen{width:auto;display:flex;align-items:center;gap:10px;margin:0;padding:5px 13px 5px 5px;border:1px solid #e5d8c7;border-radius:32px;background:#fffaf3;box-shadow:none}
@media(max-width:760px){body.cel-page .cel-book-editorial .cel-book-description{gap:13px}body.cel-page .cel-book-editorial .cel-book-actions{justify-content:flex-start}}
/* Album jeunesse : couverture, présentation et mémo épinglé. */
body.cel-page .cel-book-editorial .cel-books-layout{max-width:1250px;gap:clamp(20px,3vw,45px);grid-template-columns:minmax(0,.9fr) minmax(0,1.25fr)}
body.cel-page .cel-book-editorial .cel-books-layout.cel-has-book-note{grid-template-columns:minmax(0,.9fr) minmax(0,1.2fr) minmax(0,.85fr)}
body.cel-page .cel-book-editorial .cel-book-description{gap:12px}
body.cel-page .cel-book-editorial .cel-home-book-ribbon{display:inline-block;background:#e98932;color:#fff;padding:5px 18px 5px 12px;clip-path:polygon(0 0,100% 0,94% 50%,100% 100%,0 100%);font-size:13px;font-weight:700;border-radius:4px}
body.cel-page .cel-book-editorial .cel-book-facts{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px 8px;padding:12px 0;border:0}
body.cel-page .cel-book-editorial .cel-book-facts>span{align-items:center;text-align:center;gap:3px;padding:0}
body.cel-page .cel-book-editorial .cel-book-facts i{display:block;color:#e98932;height:24px}
body.cel-page .cel-book-editorial .cel-book-facts i svg{width:24px;height:24px;stroke:currentColor;stroke-width:1.7;fill:none;stroke-linecap:round;stroke-linejoin:round}
body.cel-page .cel-book-editorial .cel-book-facts>span>strong{font-size:10px;letter-spacing:.04em}
body.cel-page .cel-book-editorial .cel-book-facts>span>span{font-size:12px;line-height:1.35}
body.cel-page .cel-book-editorial .cel-home-book-note{position:relative;align-self:center;background:linear-gradient(135deg,#fff6db,#fff0c5);border:1px solid #e8c98e;border-radius:15px;padding:26px 20px 22px;box-shadow:0 9px 18px #8052231d;transform:rotate(-2deg);color:#633619}
body.cel-page .cel-book-editorial .cel-note-pin{position:absolute;width:30px;height:36px;top:-19px;left:50%;transform:translateX(-50%) rotate(12deg);filter:drop-shadow(1px 2px 2px #84522144)}
body.cel-page .cel-book-editorial .cel-note-pin svg{display:block;width:100%;height:100%}
body.cel-page .cel-book-editorial .cel-home-book-note h3{font:700 23px/1.2 'Baloo 2',sans-serif;color:#633619;margin:0 0 14px;display:flex;align-items:center;justify-content:space-between;gap:8px}
body.cel-page .cel-book-editorial .cel-home-book-note h3 svg{width:25px;height:25px;fill:none;stroke:#e98932;stroke-width:1.8;flex:none}
body.cel-page .cel-book-editorial .cel-home-book-note ul{list-style:none;padding:0;margin:0;display:grid;gap:10px}
body.cel-page .cel-book-editorial .cel-home-book-note li{position:relative;padding-left:23px;font-size:14px;line-height:1.45}
body.cel-page .cel-book-editorial .cel-home-book-note li:before{content:"✓";position:absolute;left:0;top:0;color:#d77929;font-weight:900;font-size:19px}
@media(max-width:1050px){body.cel-page .cel-book-editorial .cel-books-layout.cel-has-book-note{grid-template-columns:minmax(0,.85fr) minmax(0,1.15fr)}body.cel-page .cel-book-editorial .cel-home-book-note{grid-column:2;max-width:440px;width:100%;margin-top:12px}}
@media(max-width:760px){body.cel-page .cel-book-editorial .cel-books-layout,body.cel-page .cel-book-editorial .cel-books-layout.cel-has-book-note{grid-template-columns:1fr}body.cel-page .cel-book-editorial .cel-home-book-note{grid-column:auto;justify-self:center;margin:20px 0}body.cel-page .cel-book-editorial .cel-book-description{width:100%}}
body.cel-page .cel-book-editorial .cel-book-quick-facts{display:flex;flex-wrap:wrap;align-items:center;gap:6px 0;color:#94603c;font-size:13px;line-height:1.5;margin:0 0 5px}
body.cel-page .cel-book-editorial .cel-book-quick-facts>span{display:inline-flex;gap:4px;align-items:baseline}
body.cel-page .cel-book-editorial .cel-book-quick-facts>span+span:before{content:"·";margin:0 9px;color:#e98932;font-weight:700}
body.cel-page .cel-book-editorial .cel-book-classification{display:grid;gap:6px;width:100%;font-size:14px;line-height:1.5;color:#633619;padding:12px 0;border-top:1px solid #e5d8c7}
body.cel-page .cel-book-editorial .cel-book-classification strong{font-weight:700;color:#94603c}
body.cel-page .cel-book-editorial .cel-book-classification a{color:#a75721;text-decoration:underline;text-underline-offset:3px}
body.cel-page .cel-book-editorial .cel-book-cover-audio{position:absolute!important;top:18px;left:19px;z-index:5;display:block;width:auto;height:auto;padding:0;margin:0;border:0;background:transparent;box-shadow:none;transform:none}
body.cel-page .cel-book-editorial .cel-book-cover-audio .cel-audio-toggle{position:relative!important;inset:auto!important;display:block;width:58px;height:58px;padding:0;border:0;border-radius:50%;background:#fffaf3;box-shadow:0 3px 12px #63361945;transform:none!important;cursor:pointer}
body.cel-page .cel-book-editorial .cel-book-cover-audio .cel-audio-toggle svg{display:block;width:100%;height:100%}
body.cel-page .cel-book-editorial .cel-book-cover-audio .cel-audio-toggle:hover{box-shadow:0 0 0 3px #e9893255,0 4px 12px #63361945}
body.cel-page .cel-book-editorial .cel-book-cover-audio .cel-audio-error[hidden]{display:none}
body.cel-page .cel-book-editorial .cel-book-cover-audio .cel-audio-error:not([hidden]){position:absolute;top:100%;left:0;width:max-content;background:#fffaf3;padding:5px 8px;border-radius:5px;font-size:12px}
@media(max-width:760px){body.cel-page .cel-book-editorial .cel-book-cover-audio{left:calc(50% - 130px)}body.cel-page .cel-book-editorial .cel-book-cover-audio .cel-audio-toggle{width:48px;height:48px}}
body.cel-page .cel-book-editorial .cel-book-audio-unavailable button{display:block;width:58px;height:58px;border:0;padding:0;border-radius:50%;background:#fffaf3;box-shadow:0 3px 12px #63361945;cursor:default;opacity:.85}body.cel-page .cel-book-editorial .cel-book-audio-unavailable button svg{width:100%;height:100%;display:block}body.cel-page .cel-book-editorial .cel-book-audio-unavailable>span{display:block;font-size:10px;color:#633619;background:#fffaf3;padding:3px 7px;border-radius:12px;margin-top:4px;width:max-content}
body.cel-page .cel-book-editorial .cel-book-heading-row{display:flex;align-items:center;justify-content:space-between;gap:14px;width:100%}
body.cel-page .cel-book-editorial .cel-book-heading-copy{flex:1;min-width:0}
body.cel-page .cel-book-editorial .cel-book-heading-copy .cel-book-quick-facts{margin-top:10px}
body.cel-page .cel-book-editorial .cel-book-heading-row .cel-book-cover-audio{position:relative!important;top:auto!important;left:auto!important;right:auto!important;flex:0 0 auto;align-self:center;text-align:center}
body.cel-page .cel-book-editorial .cel-book-heading-row .cel-book-audio-unavailable>span{white-space:normal;max-width:85px;margin:4px auto 0}
body.cel-page .cel-book-editorial .cel-book-heading-row .cel-book-audio-unavailable button{margin:auto}
@media(max-width:760px){body.cel-page .cel-book-editorial .cel-book-heading-row{gap:8px}body.cel-page .cel-book-editorial .cel-book-heading-row .cel-book-audio-unavailable button{width:48px;height:48px}}
</style>

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
      $preview=$item->post_type==='cel_histoire'?(Celestin_Fields::value($item->ID,'resume_audio')?:Celestin_Fields::value($item->ID,'audio')):($item->post_type==='cel_personnage'?Celestin_Fields::value($item->ID,'audio'):'');
      $title=get_the_title($item);
      $new_label=['cel_coloriage'=>'Nouvelle activité','cel_personnage'=>'Nouveau personnage','cel_livre'=>'Nouveau livre','cel_histoire'=>'Nouvelle mini-histoire audio'][$item->post_type]??'Nouvelle découverte';
    ?>

<?php
$summary=Celestin_Fields::value($item->ID,'resume');
if(!$summary && $item->post_type==='cel_personnage')$summary=Celestin_Fields::value($item->ID,'description');
$summary=$summary?:$item->post_excerpt;
$kicker=$item->post_type==='cel_coloriage'?(Celestin_Fields::value($item->ID,'type')?:'Activité'):($item->post_type==='cel_personnage'?'':$new_label);
$story_tags=[];
if($item->post_type==='cel_histoire'){
 $themes=wp_get_object_terms($item->ID,Celestin_Library::TAX['categorie'],['fields'=>'names']);
 $kicker=(!is_wp_error($themes)&&$themes)?implode(' · ',$themes):'Mini-histoire audio';
 $labels=['collection'=>'Collection','saison'=>'Saison','mois'=>'Parution','age_lecture'=>'Âge','evenement'=>'Événement'];
 foreach($labels as $key=>$label){
  $terms=wp_get_object_terms($item->ID,Celestin_Library::TAX[$key],['fields'=>'names']);
  if(is_wp_error($terms)||!$terms)continue;
  if($key==='age_lecture'){
   $ranges=[];$other=[];
   foreach($terms as $term){if(preg_match('/^(\\d+)\\s*[-–]\\s*(\\d+)(?:\\s*ans)?$/u',trim($term),$m))$ranges[]=[(int)$m[1],(int)$m[2]];else $other[]=$term;}
   usort($ranges,fn($a,$b)=>$a[0]<=>$b[0]);$merged=[];
   foreach($ranges as $range){$last=count($merged)-1;if($last>=0&&$range[0]<=$merged[$last][1])$merged[$last][1]=max($merged[$last][1],$range[1]);else $merged[]=$range;}
   $terms=array_merge(array_map(fn($range)=>$range[0].'–'.$range[1].' ans',$merged),$other);
  }
  $story_tags[]=$label.' : '.implode(', ',$terms);
 }
 if(!$summary)$summary=Celestin_Fields::value($item->ID,'texte');
}
$cta=['cel_histoire'=>'Découvrir cette mini-histoire','cel_livre'=>'Découvrir ce livre','cel_coloriage'=>'Découvrir cette activité','cel_personnage'=>'Rencontrer ce personnage'][$item->post_type]??'Découvrir';
?>
<div class="cel-feature-slide" <?php if($idx!==0)echo 'hidden'; ?> data-slide="<?php echo esc_attr($idx); ?>">
 <?php if($img): ?><div class="cel-feature-backdrop" aria-hidden="true"><img src="<?php echo esc_url(get_the_post_thumbnail_url($item->ID,'large')?:$img); ?>" alt="" decoding="async"></div><?php endif; ?>
 <div class="cel-feature-layout">
       <article class="cel-card cel-news-card cel-news-<?php echo esc_attr($item->post_type); ?>">
        <span class="cel-card-type cel-news-badge"><?php echo esc_html($new_label); ?></span>
        <div class="cel-card-image">
         <?php if($item->post_type==='cel_coloriage' && $img): ?><div class="cel-paper-stack" aria-hidden="true"><img src="<?php echo esc_url($img); ?>" alt=""><img src="<?php echo esc_url($img); ?>" alt=""></div><?php endif; ?>
         <a class="cel-news-image-link" href="<?php echo esc_url(get_permalink($item)); ?>" aria-label="<?php echo esc_attr($title); ?>">
         <?php if($img): ?><img src="<?php echo esc_url($img); ?>" alt="" loading="lazy"><?php else: ?><div class="cel-card-placeholder" aria-hidden="true">✦</div><?php endif; ?>
         </a>
         
         <?php if($preview): ?>
         <audio preload="metadata" src="<?php echo esc_url($preview); ?>"></audio>
         <button type="button" class="cel-audio-toggle" aria-label="<?php echo esc_attr(($item->post_type==='cel_personnage'?'Écouter la présentation de ':'Écouter ').$title); ?>" data-title="<?php echo esc_attr($title); ?>" aria-pressed="false">
         <svg viewBox="0 0 64 64" aria-hidden="true"><circle cx="32" cy="32" r="30" fill="#fffaf3"/><circle cx="32" cy="32" r="26" fill="none" stroke="#e9d3bb" stroke-width="4"/><circle class="cel-audio-ring" cx="32" cy="32" r="26" fill="none" stroke="#e98932" stroke-width="4" pathLength="100" stroke-dasharray="0 100" stroke-linecap="round" transform="rotate(-90 32 32)"/><path class="cel-audio-play" d="M25 20L44 32L25 44Z" fill="#a45b2c"/><rect class="cel-audio-stop" x="23" y="23" width="18" height="18" rx="3" fill="#a45b2c"/></svg>
         </button><span class="cel-audio-error" role="status" hidden>Audio indisponible.</span>
         <?php endif; ?>
        </div>
      </article>
 <div class="cel-feature-copy"><?php if($kicker): ?><p class="cel-feature-kicker"><?php echo esc_html($kicker); ?></p><?php endif; ?><h2><?php echo esc_html($title); ?></h2>
 <?php if($story_tags): ?><div class="cel-feature-tags"><?php foreach($story_tags as $tag): ?><span><?php echo esc_html($tag); ?></span><?php endforeach; ?></div><?php endif; ?>
 <?php if($summary): ?><p class="cel-feature-summary"><?php echo esc_html(wp_trim_words(wp_strip_all_tags($summary),$item->post_type==='cel_personnage'?38:65,'…')); ?></p><?php endif; ?>
<?php if($item->post_type==='cel_personnage'): ?>
<div class="cel-feature-character-facts">
<?php foreach(['anniversaire'=>['Anniversaire','<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>'],'age'=>['Âge','<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'],'espece'=>['Espèce','<path d="M4 4l5 3h6l5-3v9l-8 7-8-7z"/><path d="M9 12h.01M15 12h.01M10 16h4"/>']] as $key=>$fact): $value=Celestin_Fields::value($item->ID,$key); if($value): ?>
<div><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?php echo $fact[1]; ?></svg><span><strong><?php echo esc_html($fact[0]); ?></strong> : <?php echo esc_html($value); ?></span></div>
<?php endif; endforeach; ?>
</div>
<?php endif; ?>
        <?php if($item->post_type==='cel_histoire'): ?><div class="cel-feature-audio-info"><?php if($preview): ?><div class="cel-feature-duration"><svg width="16" height="16" style="width:16px!important;height:16px!important;max-width:16px!important;max-height:16px!important;flex:0 0 16px!important;display:inline-block!important" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><span class="cel-time-total">--:--</span></div><?php elseif($item->post_type==='cel_histoire'): ?><span>Audio bientôt disponible</span><?php endif; ?></div><?php endif; ?>
 <a class="cel-feature-link" href="<?php echo esc_url(get_permalink($item)); ?>"><?php echo esc_html($cta); ?> →</a></div>
 </div>
</div>
<?php endforeach; ?>
<?php if(count($news)>1): ?><div class="cel-feature-pagination" aria-label="Choisir une nouveauté"><?php foreach($news as $idx=>$item): ?><button type="button" data-slide="<?php echo esc_attr($idx); ?>" aria-label="<?php echo esc_attr(get_the_title($item)); ?>" aria-current="<?php echo $idx===0?'true':'false'; ?>"></button><?php endforeach; ?></div><?php endif; ?>
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

<section class="cel-section cel-books cel-book-editorial" aria-label="Livre à découvrir">
 <?php if($book):
 $book_resume=Celestin_Fields::value($book->ID,'resume');
 $book_short=wp_trim_words(wp_strip_all_tags($book_resume),30,'…');
 $book_audio=Celestin_Fields::value($book->ID,'resume_audio');
 $book_points=preg_split('/\R/u',trim((string)Celestin_Fields::value($book->ID,'points_livre')),-1,PREG_SPLIT_NO_EMPTY);
 $book_number=Celestin_Fields::value($book->ID,'numero'); $book_month=Celestin_Fields::value($book->ID,'mois'); $book_pages=absint(Celestin_Fields::value($book->ID,'nombre_pages'));
 ?>
 <div class="cel-books-layout<?php echo $book_points?' cel-has-book-note':''; ?>">
 <div class="cel-book-preview">
 <a class="cel-home-cover-card cel-book-mockup" href="<?php echo esc_url(get_permalink($book)); ?>" aria-label="<?php echo esc_attr('Découvrir '.$book->post_title); ?>">
 <span class="cel-book-pages" aria-hidden="true"></span><span class="cel-book-face"><?php echo Celestin_Public::illustration($book->ID,'large'); ?></span>
 </a>

 </div>
 <div class="cel-book-description">
 <div class="cel-book-heading-row"><div class="cel-book-heading-copy">
 <h2 class="cel-book-editorial-title"><a href="<?php echo esc_url(get_permalink($book)); ?>"><?php echo esc_html($book->post_title); ?></a></h2>
 <div class="cel-book-quick-facts">
 <?php if($book_month): ?><span><strong>Date de sortie :</strong> <?php echo esc_html($book_month.(preg_match('/\b\d{4}\b/u',$book_month)?'':' 2027')); ?></span><?php endif; ?>
 <?php $book_age=Celestin_Fields::value($book->ID,'age_lecture'); if($book_age): ?><span><strong>Âge :</strong> <?php echo esc_html($book_age); ?></span><?php endif; ?>
 <?php if($book_pages): ?><span><?php echo esc_html($book_pages); ?> pages</span><?php endif; ?>
 </div>
 </div>
 <?php if($book_audio): ?><div class="cel-book-cover-audio cel-audio-card"><audio preload="metadata" src="<?php echo esc_url($book_audio); ?>"></audio><button type="button" class="cel-audio-toggle" data-title="<?php echo esc_attr($book->post_title); ?>" aria-label="Écouter le résumé ou un extrait" title="Écouter un extrait" aria-pressed="false"><svg viewBox="0 0 64 64" aria-hidden="true"><circle cx="32" cy="32" r="30" fill="#fffaf3"/><circle cx="32" cy="32" r="26" fill="none" stroke="#e9d3bb" stroke-width="4"/><circle class="cel-audio-ring" cx="32" cy="32" r="26" fill="none" stroke="#e98932" stroke-width="4" pathLength="100" stroke-dasharray="0 100" stroke-linecap="round" transform="rotate(-90 32 32)"/><path class="cel-audio-play" d="M25 20L44 32L25 44Z" fill="#a45b2c"/><rect class="cel-audio-stop" x="23" y="23" width="18" height="18" rx="3" fill="#a45b2c"/></svg></button><span class="cel-audio-error" role="status" hidden>Audio indisponible.</span></div><?php else: ?><div class="cel-book-cover-audio cel-book-audio-unavailable"><button type="button" disabled aria-label="Extrait audio bientôt disponible" title="Extrait audio bientôt disponible"><svg viewBox="0 0 64 64" aria-hidden="true"><circle cx="32" cy="32" r="30" fill="#fffaf3"/><circle cx="32" cy="32" r="26" fill="none" stroke="#e98932" stroke-width="3"/><path d="M25 20L44 32L25 44Z" fill="#a45b2c"/></svg></button><span>Audio bientôt disponible</span></div><?php endif; ?>
 </div>
 <?php if($book_resume): ?><div class="cel-home-book-summary"><?php if($book_short!==wp_strip_all_tags($book_resume) && mb_strlen($book_short)<mb_strlen(wp_strip_all_tags($book_resume))): ?><details class="cel-book-more"><summary><span class="cel-book-excerpt"><?php echo esc_html($book_short); ?></span><span class="cel-book-more-label"><span class="cel-book-open">Lire plus +</span><span class="cel-book-close">Lire moins −</span></span></summary><div class="cel-book-full"><?php echo wpautop(esc_html($book_resume)); ?></div></details><?php else: ?><p><?php echo esc_html($book_resume); ?></p><?php endif; ?></div><?php endif; ?>
 <div class="cel-book-classification">
 <?php foreach(['collection'=>'Collection','saison'=>'Saison','evenement'=>'Événement','categorie'=>'Thème'] as $key=>$label): $value=Celestin_Fields::value($book->ID,$key); if(!$value)continue; ?>
 <div><strong><?php echo esc_html($label); ?> :</strong> <?php if($key==='collection'): $terms=wp_get_object_terms($book->ID,Celestin_Library::TAX['collection']); if(!is_wp_error($terms)&&$terms): foreach($terms as $i=>$term): if($i)echo ', '; ?><a href="<?php echo esc_url(add_query_arg('filtre_collection',$term->slug,Celestin_Home::url('cel_livre'))); ?>"><?php echo esc_html($term->name); ?></a><?php endforeach; else: echo esc_html($value); endif; else: echo esc_html($value); endif; ?></div>
 <?php endforeach; ?>
 </div>

 <div class="cel-book-actions"><a class="cel-feature-link cel-home-book-all" href="<?php echo esc_url(get_permalink($book)); ?>">Découvrir ce livre →</a>
 <a class="cel-book-library-link" href="<?php echo esc_url(Celestin_Home::url('cel_livre')); ?>">Voir tous les livres <span aria-hidden="true">↗</span></a></div>
 </div>
 <?php if($book_points): ?><aside class="cel-home-book-note"><span class="cel-note-pin" aria-hidden="true"><svg viewBox="0 0 32 40"><path d="m16 24-5 13" stroke="#77533a" stroke-width="2" stroke-linecap="round"/><ellipse cx="16" cy="22" rx="10" ry="6" fill="#d77b30" stroke="#ad5b22" stroke-width="1.5"/><path d="m10 9-2 12q8 8 15 0L22 9" fill="#e98932" stroke="#ad5b22" stroke-width="1.5"/><ellipse cx="16" cy="9" rx="10" ry="7" fill="#f6aa56" stroke="#ad5b22" stroke-width="1.5"/></svg></span><h3>Dans ce livre… <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 21 3 12C-2 5 7 0 12 7c5-7 14-2 9 5Z"/></svg></h3><ul><?php foreach($book_points as $point): if(trim($point)==='')continue; ?><li><?php echo esc_html(trim($point)); ?></li><?php endforeach; ?></ul></aside><?php endif; ?>
 </div>
 <?php else: ?><p class="cel-home-empty">Sélectionne un livre depuis sa fiche WordPress pour l’afficher ici.</p><?php endif; ?>
</section>
<?php if($stories): ?>
<section class="cel-story-section cel-audio-feature" aria-labelledby="cel-story-title">

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
<footer class="cel-footer cel-footer-fox"><div aria-hidden="true" class="cel-fox-divider" style="position:absolute;bottom:calc(100% - 2px);left:50%;transform:translateX(-50%);width:min(52vw,800px);aspect-ratio:4/1;z-index:20;pointer-events:none;background:#e98932;"></div>
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
