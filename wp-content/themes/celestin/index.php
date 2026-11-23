<?php get_header(); ?>
<div class="wrap content-page archive-page"><h1><?php echo esc_html(get_bloginfo('name')); ?></h1>
<?php if (have_posts()) : ?><div class="cards archive-cards">
  <?php while (have_posts()) : the_post(); clst_card(get_post()); endwhile; ?>
</div><?php the_posts_pagination(); ?>
<?php else : ?><p>Aucune publication pour le moment.</p><?php endif; ?></div>
<?php get_footer(); ?>
