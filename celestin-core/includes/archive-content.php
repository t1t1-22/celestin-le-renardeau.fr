<?php if(!defined('ABSPATH'))exit;
$type=is_tax(array_values(Celestin_Library::TAX))?'cel_livre':get_query_var('post_type');if(is_array($type))$type=reset($type);
$heading=Celestin_Public::title((string)$type);
?><!DOCTYPE html><html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class('cel-content-page'); ?>><?php wp_body_open(); ?>
<?php require __DIR__.'/interior-header.php'; ?>
<main class="cel-content-container"><?php $view=sanitize_key($_GET['vue']??''); if($type==='cel_lieu'&&$view!=='lieux'): if($view==='galerie')Celestin_Town::gallery();else Celestin_Town::town(); else: ?><h1><?php echo esc_html($heading); ?></h1>
<?php if(in_array($type,['cel_histoire','cel_coloriage'],true))Celestin_Library::filter_form($type); ?>
<?php if(have_posts()): ?><div class="cel-archive-grid"><?php while(have_posts()):the_post(); ?>
<a class="cel-archive-card" href="<?php the_permalink(); ?>"><div class="cel-archive-thumb"><?php echo Celestin_Public::illustration(get_the_ID(),'medium_large'); ?></div><div class="cel-archive-description"><h2><?php echo esc_html(get_the_title()); ?></h2><span>Découvrir →</span></div></a>
<?php endwhile; ?></div><div class="cel-pagination"><?php the_posts_pagination(); ?></div>
<?php else: ?><p class="cel-empty-info">Aucun contenu ne correspond à cette sélection.</p><?php endif; ?><?php endif; ?></main>
<?php require __DIR__.'/shared-footer.php'; ?>
<?php wp_footer(); ?></body></html>