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
</style><?php endif; ?></head>
<body <?php body_class('cel-content-page'); ?>><?php wp_body_open(); ?>
<?php require __DIR__.'/interior-header.php'; ?>
<main class="cel-content-container"><?php $view=sanitize_key($_GET['vue']??''); if($type==='cel_lieu'&&$view!=='lieux'): if($view==='galerie')Celestin_Town::gallery();else Celestin_Town::town(); else: ?><h1><?php echo esc_html($heading); ?></h1>
<?php if(in_array($type,['cel_histoire','cel_coloriage'],true))Celestin_Library::filter_form($type); ?>
<?php if(have_posts()): ?><div class="cel-archive-grid<?php echo $type==='cel_personnage'?' cel-character-archive':''; ?>"><?php while(have_posts()):the_post(); ?>
<a class="cel-archive-card" href="<?php the_permalink(); ?>"><div class="cel-archive-thumb"><?php echo Celestin_Public::illustration(get_the_ID(),'medium_large'); ?></div><div class="cel-archive-description"><h2><?php echo esc_html(get_the_title()); ?></h2><?php if($type==='cel_personnage'): ?><dl class="cel-character-summary"><?php foreach(['espece'=>'Espèce','age'=>'Âge','genre'=>'Genre','role'=>'Rôle'] as $key=>$label): $value=Celestin_Fields::value(get_the_ID(),$key);if($value==='')continue;if($key==='age'&&preg_match('/^\d+$/',trim($value)))$value.=' ans'; ?><div class="cel-character-fact<?php echo $key==='role'?' cel-character-role':''; ?>"><dt><?php echo esc_html($label); ?></dt><dd><?php echo esc_html($value); ?></dd></div><?php endforeach; ?></dl><?php endif; ?><span>Découvrir →</span></div></a>
<?php endwhile; ?></div><div class="cel-pagination"><?php the_posts_pagination(); ?></div>
<?php else: ?><p class="cel-empty-info">Aucun contenu ne correspond à cette sélection.</p><?php endif; ?><?php endif; ?></main>
<?php require __DIR__.'/shared-footer.php'; ?>
<?php wp_footer(); ?></body></html>