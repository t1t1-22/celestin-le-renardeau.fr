<?php
if (!defined('ABSPATH')) { exit; }
add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails', ['post','page','celestin_livre','celestin_histoire','celestin_coloriage','celestin_personnage']);
    add_theme_support('html5', ['search-form','gallery','caption','style','script']);
    add_theme_support('custom-logo', ['height' => 110, 'width' => 250, 'flex-height' => true, 'flex-width' => true]);
    add_theme_support('responsive-embeds');
    add_theme_support('editor-styles');
    register_nav_menus(['main' => 'Navigation principale']);
});
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('celestin-theme', get_stylesheet_uri(), [], '1.0.0');
    wp_enqueue_script('celestin-site', get_template_directory_uri() . '/site.js', [], '1.0.0', true);
});
add_action('customize_register', function ($customizer) {
    $customizer->add_section('celestin_home', ['title' => '🦊 Accueil Célestin', 'priority' => 30]);
    $customizer->add_setting('celestin_hero_image', [
        'default' => '', 'sanitize_callback' => 'esc_url_raw', 'transport' => 'refresh'
    ]);
    $customizer->add_control(new WP_Customize_Image_Control($customizer, 'celestin_hero_image', [
        'label' => 'Grande illustration de l’accueil (sans texte)',
        'section' => 'celestin_home', 'settings' => 'celestin_hero_image'
    ]));
    $customizer->add_setting('celestin_intro', [
        'default' => 'Des histoires douces, des coloriages et de jolies découvertes pour les petits curieux !',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $customizer->add_control('celestin_intro', [
        'label' => 'Texte d’introduction', 'section' => 'celestin_home', 'type' => 'textarea'
    ]);
});
function clst_home_anchor($anchor) {
    return esc_url(home_url('/#' . $anchor));
}
function clst_hero_image() {
    return get_theme_mod('celestin_hero_image') ?: home_url('/images/jardin.webp');
}
function clst_featured_image($id, $size = 'medium_large') {
    $img = get_the_post_thumbnail_url($id, $size);
    if ($img) { return $img; }
    if (get_post_type($id) === 'celestin_coloriage') { return home_url('/images/coloriage.webp'); }
    if (get_post_type($id) === 'celestin_livre') { return home_url('/images/jardin.webp'); }
    return '';
}
function clst_download_url($id) {
    $attachment = absint(get_post_meta($id, '_clst_file_id', true));
    if ($attachment && wp_attachment_is_image($attachment)) {
        return wp_get_attachment_url($attachment);
    }
    if ($attachment && get_post_mime_type($attachment) === 'application/pdf') {
        return wp_get_attachment_url($attachment);
    }
    return esc_url_raw(get_post_meta($id, '_clst_legacy_file', true));
}
function clst_card($post) {
    $post_id = $post->ID;
    $type = get_post_type($post_id);
    $image = clst_featured_image($post_id);
    $excerpt = get_the_excerpt($post_id);
    $link = get_permalink($post_id);
    $button = 'Découvrir →';
    $external = esc_url_raw(get_post_meta($post_id, '_clst_external_url', true));
    if ($type === 'celestin_coloriage' && clst_download_url($post_id)) {
        $link = clst_download_url($post_id);
        $button = 'Télécharger ↓';
    } elseif ($type === 'celestin_livre' && $external) {
        $link = $external;
        $button = 'Voir le livre →';
    } elseif ($type === 'celestin_histoire') {
        $button = 'Lire l’histoire →';
    } elseif ($type === 'celestin_personnage') {
        $button = 'Rencontrer →';
    }
    echo '<article class="story-card">';
    if ($image) {
        echo '<a class="picture-link" href="' . esc_url(get_permalink($post_id)) . '"><img loading="lazy" alt="' . esc_attr(get_the_title($post_id)) . '" src="' . esc_url($image) . '"></a>';
    } else {
        $icon = $type === 'celestin_histoire' ? '🎬' : ($type === 'celestin_personnage' ? '🦊' : '📚');
        echo '<a href="' . esc_url(get_permalink($post_id)) . '" class="card-fallback" aria-label="' . esc_attr(get_the_title($post_id)) . '">' . $icon . '</a>';
    }
    echo '<div class="card-copy"><h3><a href="' . esc_url(get_permalink($post_id)) . '">' . esc_html(get_the_title($post_id)) . '</a></h3>';
    if ($excerpt) { echo '<p>' . esc_html(wp_trim_words($excerpt, 18)) . '</p>'; }
    echo '<a class="card-action" href="' . esc_url($link) . '"' . ($external ? ' target="_blank" rel="noopener noreferrer"' : '') . '>' . esc_html($button) . '</a></div></article>';
}
function clst_section($type, $id, $heading, $all) {
    $query = new WP_Query(['post_type'=>$type,'post_status'=>'publish','posts_per_page'=>4,'orderby'=>'date','order'=>'DESC','no_found_rows'=>true]);
    echo '<section class="section-block" id="' . esc_attr($id) . '"><div class="wrap">';
    echo '<div class="section-head"><h2>' . esc_html($heading) . '</h2><a class="see-all" href="' . esc_url(get_post_type_archive_link($type)) . '">' . esc_html($all) . ' →</a></div>';
    if ($query->have_posts()) {
        echo '<div class="cards">';
        while ($query->have_posts()) { $query->the_post(); clst_card(get_post()); }
        echo '</div>';
    } else {
        echo '<div class="no-content"><p>De nouvelles aventures arrivent bientôt !</p></div>';
    }
    wp_reset_postdata();
    echo '</div></section>';
}
