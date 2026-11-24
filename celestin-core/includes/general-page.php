<?php if(!defined('ABSPATH'))exit; ?>
<!DOCTYPE html><html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class('cel-content-page'); ?>><?php wp_body_open(); ?>
<?php require __DIR__.'/interior-header.php'; ?>
<main class="cel-content-container">
<?php if(is_404()): ?><h1>Cette page est introuvable</h1><p><a href="<?php echo esc_url(home_url('/')); ?>">Revenir à l’accueil</a></p>
<?php elseif(is_singular()): while(have_posts()):the_post(); ?><article <?php post_class(); ?>><h1><?php the_title(); ?></h1><?php the_content(); wp_link_pages(); ?></article><?php endwhile; ?>
<?php else: ?><h1><?php if(is_search())echo esc_html('Recherche : '.get_search_query(false));elseif(is_archive())echo wp_kses_post(get_the_archive_title());else echo 'Les découvertes'; ?></h1>
<?php if(have_posts()): ?><div class="cel-archive-grid"><?php while(have_posts()):the_post(); ?><a class="cel-archive-card" href="<?php the_permalink(); ?>"><div class="cel-archive-thumb"><?php echo Celestin_Public::illustration(get_the_ID(),'medium_large'); ?></div><div class="cel-archive-description"><h2><?php the_title(); ?></h2><span>Découvrir →</span></div></a><?php endwhile; ?></div><?php the_posts_pagination(); else: ?><p>Aucun contenu trouvé.</p><?php endif; endif; ?>
</main>
<?php require __DIR__.'/shared-footer.php'; wp_footer(); ?></body></html>