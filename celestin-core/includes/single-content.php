<?php if(!defined('ABSPATH'))exit;
$p=get_queried_object();if(!$p instanceof WP_Post)exit;
$type=$p->post_type;
?><!DOCTYPE html><html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class('cel-content-page'); ?>><?php wp_body_open(); ?>
<?php require __DIR__.'/interior-header.php'; ?>
<main class="cel-content-container <?php echo $type==='cel_livre'?'cel-book-detail':($type==='cel_histoire'?'cel-story-detail':''); ?>">
  <?php if($type==='cel_livre'): ?><p class="cel-breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Accueil</a> › <a href="<?php echo esc_url(get_post_type_archive_link($type)); ?>">Les livres</a> › <?php echo esc_html(get_the_title($p)); ?></p><?php elseif($type!=='cel_histoire'): ?>
  <p class="cel-kicker"><?php echo esc_html(Celestin_Public::title($type)); ?></p>
  <h1><?php echo esc_html(get_the_title($p)); ?></h1><?php endif; ?>
  <?php if($type==='cel_personnage') Celestin_Public::character($p->ID);
        elseif($type==='cel_livre') Celestin_Public::book($p->ID);
        elseif($type==='cel_histoire') Celestin_Public::story($p->ID);
        else Celestin_Public::simple($p->ID,$type); ?>
</main>
<?php require __DIR__.'/shared-footer.php'; ?>
<?php wp_footer(); ?></body></html>