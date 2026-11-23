<?php get_header(); ?>
<div class="wrap content-page single-page">
<?php while (have_posts()) : the_post(); ?>
  <a class="back-link" href="<?php echo esc_url(get_post_type_archive_link(get_post_type())); ?>">← Retour à la rubrique</a>
  <article <?php post_class('reading-article'); ?>>
    <header class="reading-heading"><p class="eyebrow">Les aventures de Célestin</p>
      <h1><?php the_title(); ?></h1>
      <?php if (has_excerpt()) : ?><p class="reading-intro"><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?>
    </header>
    <?php if (has_post_thumbnail()) : ?>
      <figure class="reading-picture"><?php the_post_thumbnail('large', ['loading'=>'eager']); ?></figure>
    <?php endif; ?>
    <div class="reading-content"><?php the_content(); ?></div>
    <?php $file = clst_download_url(get_the_ID()); $link = esc_url_raw(get_post_meta(get_the_ID(), '_clst_external_url', true)); ?>
    <?php if ($file || $link) : ?>
      <p class="reading-action"><a class="button button-gold" href="<?php echo esc_url($file ?: $link); ?>" <?php if ($link && !$file) { echo 'target="_blank" rel="noopener noreferrer"'; } ?>>
        <?php echo $file ? 'Télécharger le fichier ↓' : 'Découvrir le livre →'; ?></a></p>
    <?php endif; ?>
  </article>
<?php endwhile; ?>
</div>
<?php get_footer(); ?>
