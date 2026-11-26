<?php if(!defined('ABSPATH'))exit;
$type=is_tax(array_values(Celestin_Library::TAX))?'cel_livre':get_query_var('post_type');if(is_array($type))$type=reset($type);
$heading=Celestin_Public::title((string)$type);
?><!DOCTYPE html><html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><?php wp_head(); ?><?php if($type==='cel_personnage'): ?><style>
body.cel-content-page .cel-character-archive{grid-template-columns:repeat(auto-fill,minmax(min(100%,220px),270px));gap:22px;align-items:stretch}
body.cel-content-page .cel-character-archive .cel-archive-card{display:flex;flex-direction:column;text-decoration:none!important;border-radius:16px}
body.cel-content-page .cel-character-archive .cel-archive-thumb{height:auto;aspect-ratio:1/1;width:100%;overflow:hidden}
body.cel-content-page .cel-character-archive .cel-archive-thumb img{width:100%;height:100%;object-fit:contain}
body.cel-content-page .cel-character-archive .cel-archive-description{display:flex;flex:1;flex-direction:column;padding:14px}
body.cel-content-page .cel-character-archive .cel-archive-description h2{font:700 22px/1.15 'Baloo 2',sans-serif;color:#633619;margin:0 0 12px}
.cel-character-summary{display:grid;grid-template-columns:1fr 1fr;gap:8px 12px;margin:0 0 14px}
.cel-character-fact dt{font:700 10px/1.3 Nunito,sans-serif;text-transform:uppercase;color:#a76129;margin:0}
.cel-character-fact dd{font:500 14px/1.4 Nunito,sans-serif;color:#633619;margin:2px 0 0}
.cel-character-role{grid-column:1/-1}
body.cel-content-page .cel-character-archive .cel-archive-description>span{margin-top:auto;color:#a76129}
@media(max-width:520px){body.cel-content-page .cel-character-archive{grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}body.cel-content-page .cel-character-archive .cel-archive-description{padding:10px}body.cel-content-page .cel-character-archive .cel-archive-description h2{font-size:18px}.cel-character-summary{gap:6px}.cel-character-fact dd{font-size:12px}}
</style><?php endif; ?><?php if($type==='cel_livre'): ?><style>
body.cel-content-page .cel-bookshelf{display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,210px),1fr));gap:36px 26px;align-items:start;padding:22px 18px 12px;background:linear-gradient(180deg,#f9efe0,#fff9f0);border:1px solid #eddbc3;border-radius:20px}
body.cel-content-page .cel-bookshelf .cel-archive-card{display:flex;flex-direction:column;align-items:center;background:transparent;border:0;border-radius:0;box-shadow:none;overflow:visible;text-decoration:none!important;color:#633619;min-width:0}
body.cel-content-page .cel-bookshelf .cel-archive-thumb{position:relative;isolation:isolate;width:100%;height:auto;aspect-ratio:1.04;background:transparent;overflow:visible;display:flex;align-items:flex-end;justify-content:center;padding:12px 16px 18px;box-sizing:border-box}
body.cel-content-page .cel-bookshelf .cel-archive-thumb:before{content:"";position:absolute;z-index:-1;bottom:0;left:-9px;right:-9px;height:20px;border-radius:3px;background:linear-gradient(to bottom,#edc99b 0 42%,#c88a50 43% 85%,#b57640 86%);box-shadow:0 7px 10px #80522826}
body.cel-content-page .cel-bookshelf .cel-archive-thumb img{display:block;width:100%;height:auto;aspect-ratio:1;object-fit:contain;max-height:100%;border-radius:3px 7px 7px 3px;box-shadow:5px 1px 0 #f1e4ce,8px 2px 0 #b88d65,11px 9px 16px #63361938;transform:perspective(900px) rotateY(-7deg);transform-origin:left center;transition:transform .25s ease,box-shadow .25s ease}
body.cel-content-page .cel-bookshelf .cel-archive-card:is(:hover,:focus-visible) .cel-archive-thumb img{transform:perspective(900px) rotateY(0deg) translateY(-7px);box-shadow:4px 1px 0 #f1e4ce,7px 2px 0 #b88d65,12px 15px 20px #63361932}
body.cel-content-page .cel-bookshelf .cel-archive-description{padding:14px 4px 4px;text-align:center;width:100%}
body.cel-content-page .cel-bookshelf .cel-archive-description h2{font-size:20px;line-height:1.2;color:#633619;margin:0 0 5px;text-decoration:none!important}
body.cel-content-page .cel-bookshelf .cel-archive-description span{color:#a76129;text-decoration:none!important}
body.cel-content-page .cel-bookshelf .cel-archive-card:focus-visible{outline:2px solid #e98932;outline-offset:6px;border-radius:8px}
@media(min-width:700px){body.cel-content-page .cel-bookshelf{grid-template-columns:repeat(auto-fill,minmax(210px,250px));justify-content:start;padding:26px 26px 18px}}
@media(max-width:520px){body.cel-content-page .cel-bookshelf{grid-template-columns:repeat(2,minmax(0,1fr));padding:12px 12px 8px;gap:22px 14px}body.cel-content-page .cel-bookshelf .cel-archive-thumb{padding:8px 9px 13px}body.cel-content-page .cel-bookshelf .cel-archive-thumb:before{height:14px;left:-4px;right:-4px}body.cel-content-page .cel-bookshelf .cel-archive-description h2{font-size:16px}}
@media(prefers-reduced-motion:reduce){body.cel-content-page .cel-bookshelf .cel-archive-thumb img{transition:none}}
</style><?php endif; ?><?php if($type==='cel_histoire'): ?><style id="cel-story-catalogue-layout">
body.cel-content-page .cel-archive-grid.cel-story-list{display:grid!important;grid-template-columns:repeat(auto-fill,minmax(min(100%,258px),258px));gap:22px!important;align-items:stretch;justify-content:center;width:100%;max-width:100%;margin:0 auto}
body.cel-content-page .cel-story-list article.cel-story-list-card{position:relative;display:flex!important;flex-direction:column;min-width:0;width:100%;max-width:none!important;margin:0!important;overflow:hidden;border:1px solid #ecd6b9;border-radius:18px;background:#fffdfa;box-sizing:border-box}
body.cel-content-page .cel-story-list .cel-story-list-art{position:relative!important;isolation:isolate;display:block;width:100%;aspect-ratio:1;overflow:hidden;background:#fff3df}
body.cel-content-page .cel-story-list .cel-story-list-art>a{display:block;width:100%;height:100%}
body.cel-content-page .cel-story-list .cel-story-list-art>a>img{display:block;width:100%;height:100%;object-fit:contain}
body.cel-content-page .cel-story-list .cel-story-list-theme,body.cel-content-page .cel-story-list .cel-story-list-duration{position:absolute!important;z-index:2;display:block;width:max-content;max-width:calc(100% - 24px);padding:5px 10px;border-radius:18px;font:700 12px/1.3 Nunito,sans-serif;overflow-wrap:anywhere;box-sizing:border-box}
body.cel-content-page .cel-story-list .cel-story-list-theme{top:12px;left:12px;background:#e98932;color:white}
body.cel-content-page .cel-story-list .cel-story-list-duration{bottom:12px;left:12px;background:#fffaf3;color:#633619;max-width:calc(100% - 78px)}
body.cel-content-page .cel-story-list .cel-audio-toggle{position:absolute!important;inset:auto 12px 12px auto!important;z-index:3;display:block;width:48px!important;height:48px!important;padding:0;border:0;border-radius:50%;background:#fffaf3;box-shadow:0 3px 12px #63361944;cursor:pointer}
body.cel-content-page .cel-story-list .cel-audio-toggle svg{display:block;width:100%;height:100%}
body.cel-content-page .cel-story-list .cel-audio-error{position:absolute;bottom:65px;right:8px;max-width:90%;background:#fffaf3;padding:6px;border-radius:6px;font-size:12px}
body.cel-content-page .cel-story-list [hidden]{display:none!important}
body.cel-content-page .cel-story-list .cel-archive-description{display:flex!important;flex:1;flex-direction:column;gap:12px;padding:15px;min-width:0;box-sizing:border-box}
body.cel-content-page .cel-story-list .cel-archive-description h2{font:700 20px/1.2 'Baloo 2',sans-serif!important;color:#633619!important;margin:0!important;overflow-wrap:anywhere}
body.cel-content-page .cel-story-list .cel-archive-description h2 a{color:inherit!important;text-decoration:none!important}
body.cel-content-page .cel-story-list .cel-story-list-meta{display:grid;gap:7px;min-width:0;font:600 13px/1.4 Nunito,sans-serif;color:#633619}
body.cel-content-page .cel-story-list .cel-story-list-meta>div{display:grid;grid-template-columns:minmax(0,auto) minmax(0,1fr);gap:8px;align-items:baseline;overflow-wrap:anywhere}
body.cel-content-page .cel-story-list .cel-story-list-meta strong{font:700 12px/1.4 Nunito,sans-serif;color:#94603c}
body.cel-content-page .cel-story-list .cel-story-list-meta span{font:600 13px/1.4 Nunito,sans-serif;color:#633619}
body.cel-content-page .cel-story-list .cel-story-list-link{margin-top:auto;font-size:14px;font-weight:700;color:#a76129;text-decoration:none!important}
body.cel-content-page .cel-story-list a:hover{text-decoration:none!important}
@media(max-width:720px){body.cel-content-page .cel-archive-grid.cel-story-list{grid-template-columns:repeat(2,minmax(0,258px));gap:12px!important}body.cel-content-page .cel-story-list .cel-archive-description{padding:10px;gap:8px}body.cel-content-page .cel-story-list .cel-archive-description h2{font-size:17px!important}body.cel-content-page .cel-story-list .cel-audio-toggle{width:42px!important;height:42px!important}}
</style><?php endif; ?></head>
<body <?php body_class('cel-content-page'); ?>><?php wp_body_open(); ?>
<?php require __DIR__.'/interior-header.php'; ?>
<main class="cel-content-container"><?php $view=sanitize_key($_GET['vue']??''); if($type==='cel_lieu'&&$view!=='lieux'): if($view==='galerie')Celestin_Town::gallery();else Celestin_Town::town(); else: ?><h1><?php echo esc_html($heading); ?></h1>
<?php if(in_array($type,['cel_histoire','cel_coloriage'],true))Celestin_Library::filter_form($type); ?>
<?php if(have_posts()): ?><div class="cel-archive-grid<?php echo $type==='cel_personnage'?' cel-character-archive':($type==='cel_livre'?' cel-bookshelf':($type==='cel_histoire'?' cel-story-list':'')); ?>"><?php while(have_posts()):the_post(); ?>
<?php if($type==='cel_histoire'):
$id=get_the_ID();$title=get_the_title();$preview=Celestin_Fields::value($id,'resume_audio');$audio=Celestin_Fields::value($id,'audio');$themes=Celestin_Library::ordered_themes($id);$theme=$themes[0]??'';$collection=Celestin_Library::display($id,'collection');$age=Celestin_Library::display($id,'age_lecture');
?>
<article class="cel-archive-card cel-audio-card cel-story-list-card">
<div class="cel-story-list-art"><a href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr($title); ?>"><?php echo Celestin_Public::illustration($id,'medium_large'); ?></a>
<?php if($theme): ?><span class="cel-story-list-theme"><?php echo esc_html($theme); ?></span><?php endif; ?>
<?php if($preview): ?><audio preload="none" src="<?php echo esc_url($preview); ?>"></audio><button type="button" class="cel-audio-toggle" data-title="<?php echo esc_attr('le résumé de '.$title); ?>" aria-label="<?php echo esc_attr('Écouter le résumé de '.$title); ?>" title="Écouter le résumé" aria-pressed="false"><svg viewBox="0 0 64 64" aria-hidden="true"><circle cx="32" cy="32" r="30" fill="#fffaf3"/><circle cx="32" cy="32" r="26" fill="none" stroke="#e9d3bb" stroke-width="4"/><circle class="cel-audio-ring" cx="32" cy="32" r="26" fill="none" stroke="#e98932" stroke-width="4" pathLength="100" stroke-dasharray="0 100" stroke-linecap="round" transform="rotate(-90 32 32)"/><path class="cel-audio-play" d="M25 20L44 32L25 44Z" fill="#a45b2c"/><rect class="cel-audio-stop" x="23" y="23" width="18" height="18" rx="3" fill="#a45b2c"/></svg></button><span class="cel-audio-error" role="status" hidden>Audio indisponible.</span><?php endif; ?>
<?php if($audio): ?><span class="cel-story-list-duration" data-cel-duration-url="<?php echo esc_url($audio); ?>" hidden></span><?php endif; ?>
</div><div class="cel-archive-description"><h2><a href="<?php the_permalink(); ?>"><?php echo esc_html($title); ?></a></h2>
<div class="cel-story-list-meta"><?php if($collection): ?><div><strong>Collection :</strong><span><?php echo esc_html($collection); ?></span></div><?php endif; ?><?php if($age): ?><div><strong>Âge :</strong><span><?php echo esc_html($age); ?></span></div><?php endif; ?></div>
<a class="cel-story-list-link" href="<?php the_permalink(); ?>">Découvrir →</a></div></article>
<?php else: ?><a class="cel-archive-card" href="<?php the_permalink(); ?>"><div class="cel-archive-thumb"><?php echo Celestin_Public::illustration(get_the_ID(),'medium_large'); ?></div><div class="cel-archive-description"><h2><?php echo esc_html(get_the_title()); ?></h2><?php if($type==='cel_personnage'): ?><dl class="cel-character-summary"><?php foreach(['espece'=>'Espèce','age'=>'Âge','genre'=>'Genre','role'=>'Rôle'] as $key=>$label): $value=Celestin_Fields::value(get_the_ID(),$key);if($value==='')continue;if($key==='age'&&preg_match('/^\d+$/',trim($value)))$value.=' ans'; ?><div class="cel-character-fact<?php echo $key==='role'?' cel-character-role':''; ?>"><dt><?php echo esc_html($label); ?></dt><dd><?php echo esc_html($value); ?></dd></div><?php endforeach; ?></dl><?php endif; ?><span>Découvrir →</span></div></a><?php endif; ?>
<?php endwhile; ?></div><div class="cel-pagination"><?php the_posts_pagination(); ?></div>
<?php else: ?><p class="cel-empty-info">Aucun contenu ne correspond à cette sélection.</p><?php endif; ?><?php endif; ?></main>
<?php require __DIR__.'/shared-footer.php'; ?>
<?php wp_footer(); ?></body></html>