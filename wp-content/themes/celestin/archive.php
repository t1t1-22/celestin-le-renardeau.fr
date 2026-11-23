<?php get_header(); ?>
<div class="wrap content-page archive-page">
  <div class="page-heading"><p class="eyebrow">Bienvenue à Fleurville</p><h1><?php post_type_archive_title(); ?></h1>
    <p>Explore toutes les aventures publiées dans cette rubrique.</p></div>
  <?php if (have_posts()) : ?><div class="cards archive-cards">
  <?php while (have_posts()) : the_post(); clst_card(get_post()); endwhile; ?>
  </div>
  <div class="pager"><?php the_posts_pagination(['mid_size'=>1,'prev_text'=>'← Précédent','next_text'=>'Suivant →']); ?></div>
  <?php else : ?><p>De nouveaux contenus arrivent bientôt !</p><?php endif; ?>
</div>
<?php get_footer(); ?>
