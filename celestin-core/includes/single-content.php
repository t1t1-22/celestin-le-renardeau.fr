<?php if(!defined('ABSPATH'))exit;
$p=get_queried_object();if(!$p instanceof WP_Post)exit;
$type=$p->post_type;
?><!DOCTYPE html><html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class('cel-content-page'); ?>><?php wp_body_open(); ?>
<header class="cel-content-header"><a href="<?php echo esc_url(home_url('/')); ?>" class="cel-back">← Retour à Célestin</a><a href="<?php echo esc_url(get_post_type_archive_link($type)); ?>"><?php echo esc_html(Celestin_Public::title($type)); ?></a></header>
<main class="cel-content-container">
  <p class="cel-kicker"><?php echo esc_html(Celestin_Public::title($type)); ?></p>
  <h1><?php echo esc_html(get_the_title($p)); ?></h1>
  <?php if($type==='cel_personnage') Celestin_Public::character($p->ID);
        elseif($type==='cel_livre') Celestin_Public::book($p->ID);
        elseif($type==='cel_histoire') Celestin_Public::story($p->ID);
        else Celestin_Public::simple($p->ID,$type); ?>
</main>
<footer class="cel-content-footer"><a href="<?php echo esc_url(home_url('/')); ?>">Célestin le renardeau · Revenir à l’accueil</a></footer>
<?php wp_footer(); ?></body></html>