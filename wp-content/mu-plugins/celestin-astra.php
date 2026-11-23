<?php
/**
 * Plugin Name: Célestin × Astra — Univers de Fleurville
 * Description: Accueil illustré, navigation et bibliothèques éditables sans remplacer le thème Astra.
 * Version: 1.0.0
 */
if (!defined('ABSPATH')) { exit; }

function clst_astra_active() {
    return get_template() === 'astra';
}
function clst_astra_asset($file) {
    return plugin_dir_url(__FILE__) . 'celestin-astra/' . ltrim($file, '/');
}
function clst_astra_image_url($setting, $fallback) {
    $id = absint(get_option($setting, 0));
    $image = $id ? wp_get_attachment_image_url($id, 'full') : false;
    return $image ?: home_url('/images/' . $fallback);
}
function clst_astra_text($setting, $fallback) {
    $value = get_option($setting, $fallback);
    return is_string($value) && trim($value) ? $value : $fallback;
}
function clst_astra_catalogue_url($type) {
    $pages = ['livre' => 'bibliotheque', 'histoire' => 'toutes-les-histoires', 'coloriage' => 'coloriages-a-imprimer', 'personnage' => 'habitants-de-fleurville'];
    if (!isset($pages[$type])) { return home_url('/'); }
    $page = get_page_by_path($pages[$type], OBJECT, 'page');
    return $page ? get_permalink($page) : home_url('/#' . $type);
}
function clst_astra_post_file($id) {
    $aid = absint(get_post_meta($id, '_clst_file_id', true));
    if ($aid && in_array(get_post_mime_type($aid), ['application/pdf', 'image/png', 'image/jpeg', 'image/webp'], true)) {
        return wp_get_attachment_url($aid);
    }
    return esc_url_raw(get_post_meta($id, '_clst_legacy_file', true));
}
function clst_astra_card($post) {
    $type = get_post_type($post);
    $title = get_the_title($post);
    $permalink = get_permalink($post);
    $image = get_the_post_thumbnail_url($post, 'medium_large');
    $excerpt = get_the_excerpt($post);
    $link = $permalink;
    $action = 'Découvrir';
    $is_external = false;
    if ($type === 'celestin_coloriage') {
        $file = clst_astra_post_file($post->ID);
        if ($file) { $link = $file; $action = 'Télécharger'; }
    } elseif ($type === 'celestin_livre') {
        $external = esc_url_raw(get_post_meta($post->ID, '_clst_external_url', true));
        if ($external) { $link = $external; $is_external = true; }
        $action = 'Découvrir le livre';
    } elseif ($type === 'celestin_histoire') {
        $action = 'Lire l’histoire';
    } elseif ($type === 'celestin_personnage') {
        $action = 'Rencontrer';
    }

    $symbol = ['celestin_livre'=>'📚','celestin_histoire'=>'🌟','celestin_coloriage'=>'🎨','celestin_personnage'=>'🦊'];
    ob_start(); ?>
    <article class="clst-card">
      <a class="clst-card-picture" href="<?php echo esc_url($permalink); ?>" aria-label="<?php echo esc_attr($title); ?>">
        <?php if ($image) : ?>
          <img loading="lazy" src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>">
        <?php else : ?>
          <span class="clst-card-placeholder" aria-hidden="true"><?php echo esc_html($symbol[$type] ?? '🌼'); ?></span>
        <?php endif; ?>
      </a>
      <div class="clst-card-copy">
        <h3><a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a></h3>
        <?php if ($excerpt) : ?><p><?php echo esc_html(wp_trim_words($excerpt, 18)); ?></p><?php endif; ?>
        <a class="clst-card-action" href="<?php echo esc_url($link); ?>" <?php if ($is_external) { echo 'target="_blank" rel="noopener noreferrer"'; } ?>><?php echo esc_html($action); ?> →</a>
      </div>
    </article>
    <?php return ob_get_clean();
}
function clst_astra_list($short, $limit = 4) {
    $types = ['livre'=>'celestin_livre','histoire'=>'celestin_histoire','coloriage'=>'celestin_coloriage','personnage'=>'celestin_personnage'];
    if (!isset($types[$short])) { return ''; }
    $query = new WP_Query([
        'post_type'=>$types[$short], 'post_status'=>'publish',
        'posts_per_page'=>max(1, min(48, intval($limit))),
        'orderby'=>'date','order'=>'DESC', 'no_found_rows'=>true,
    ]);
    if (!$query->have_posts()) {
        return '<p class="clst-empty">De nouvelles aventures arrivent bientôt à Fleurville !</p>';
    }
    $out = '<div class="clst-grid">';
    foreach ($query->posts as $post) { $out .= clst_astra_card($post); }
    wp_reset_postdata();
    return $out . '</div>';
}
function clst_astra_section($type, $id, $title, $link_text) {
    $html = '<section class="clst-section clst-section-' . esc_attr($type) . '" id="' . esc_attr($id) . '"><div class="clst-shell">';
    $html .= '<div class="clst-section-heading"><h2>' . esc_html($title) . '</h2><a href="' . esc_url(clst_astra_catalogue_url($type)) . '">' . esc_html($link_text) . ' →</a></div>';
    $html .= clst_astra_list($type, 4);
    return $html . '</div></section>';
}

add_shortcode('celestin_catalogue', function ($atts) {
    $atts = shortcode_atts(['type'=>'livre','nombre'=>24], $atts, 'celestin_catalogue');
    $names = ['livre'=>'Les livres de Célestin','histoire'=>'Toutes les mini-histoires','coloriage'=>'Les coloriages à imprimer','personnage'=>'Les personnages de Fleurville'];
    if (!isset($names[$atts['type']])) { return ''; }
    return '<div class="clst-site clst-library"><div class="clst-shell"><p class="clst-overline">L’univers de Fleurville</p><h1>' .
       esc_html($names[$atts['type']]) . '</h1><p>Des histoires, des rencontres et de jolies découvertes à partager.</p>' .
       clst_astra_list($atts['type'], intval($atts['nombre'])) . '</div></div>';
});
add_shortcode('celestin_accueil', function () {
    $hero_image = clst_astra_image_url('clst_astra_hero_image', 'banniere-celestin-fleurville.webp');
    $village_image = clst_astra_image_url('clst_astra_village_image', 'jardin.webp');
    $intro = clst_astra_text('clst_astra_intro', 'Des histoires douces, des coloriages et de jolies découvertes pour les petits curieux !');
    ob_start(); ?>
    <div class="clst-site clst-home">
      <section class="clst-hero" id="accueil" aria-labelledby="clst-title">
        <div class="clst-hero-background" style="background-image:url('<?php echo esc_url($hero_image); ?>')" aria-hidden="true"></div>
        <div class="clst-hero-inner clst-shell">
          <h1 id="clst-title">Pars à l’aventure<br><em>avec Célestin !</em></h1>
          <p><?php echo esc_html($intro); ?></p>
          <div class="clst-actions">
            <a class="clst-button clst-primary" href="#livres">Découvrir l’univers →</a>
            <a class="clst-button clst-secondary" href="#coloriages">Voir les coloriages 🎨</a>
          </div>
        </div>
      </section>
      <nav class="clst-shortcuts clst-shell" aria-label="Découvrir l’univers de Célestin">
        <a class="clst-tile clst-tile-sun" href="#personnages"><span aria-hidden="true">🦊 🐶</span><strong>Personnages</strong></a>
        <a class="clst-tile clst-tile-pink" href="#coloriages"><span aria-hidden="true">🎨</span><strong>Coloriages</strong></a>
        <a class="clst-tile clst-tile-mint" href="#livres"><span aria-hidden="true">📚</span><strong>Les livres</strong></a>
        <a class="clst-tile clst-tile-lilac" href="#mini-histoires"><span aria-hidden="true">🎬</span><strong>Mini-histoires</strong></a>
      </nav>
      <?php
      echo clst_astra_section('livre', 'livres', '📖 Les livres de Célestin ✨', 'Voir tous les livres');
      echo clst_astra_section('histoire', 'mini-histoires', '🎬 Mini-histoires ✨', 'Toutes les mini-histoires');
      echo clst_astra_section('coloriage', 'coloriages', '🎨 Coloriages à imprimer ✨', 'Tous les coloriages');
      ?>
      <section class="clst-village" id="fleurville" aria-labelledby="clst-village-title">
        <div class="clst-shell clst-village-inner">
          <div class="clst-village-image"><img loading="lazy" src="<?php echo esc_url($village_image); ?>" alt="Découvrir le village de Fleurville"></div>
          <div class="clst-village-copy">
            <h2 id="clst-village-title">Bienvenue à Fleurville !</h2>
            <p>Un petit coin de nature plein de charme où Célestin vit de grandes et de petites aventures avec Octet, son fidèle compagnon.</p>
            <a class="clst-button clst-primary" href="#personnages">Découvrir Fleurville →</a>
          </div>
        </div>
      </section>
      <?php echo clst_astra_section('personnage', 'personnages', '🦊 Les personnages de Fleurville ✨', 'Tous les personnages'); ?>
      <section class="clst-values" aria-labelledby="clst-values-title"><div class="clst-shell">
        <h2 id="clst-values-title">❤️ Un univers bienveillant pour les enfants ✨</h2>
        <div class="clst-values-grid">
          <article><span aria-hidden="true">🛡️</span><div><h3>Des contenus adaptés</h3><p>Des histoires tendres et positives pour les enfants.</p></div></article>
          <article><span aria-hidden="true">👨‍👩‍👧</span><div><h3>Pour les parents</h3><p>Partager la lecture, l’imagination et la créativité.</p></div></article>
          <article><span aria-hidden="true">💛</span><div><h3>Des aventures du quotidien</h3><p>Entraide, amitié et découvertes à Fleurville.</p></div></article>
        </div>
      </div></section>
    </div>
    <?php return ob_get_clean();
});

// Page d'accueil et bibliothèques éditables. Le contenu d'une page existante
// n'est jamais écrasé ; l'ancienne page d'accueil reste intacte dans WordPress.
add_action('init', function () {
    if (!is_blog_installed() || !clst_astra_active()) { return; }
    if (get_option('clst_astra_pages_ready') === '1') { return; }
    $pages = [
      'accueil-celestin' => ['Accueil – Célestin le renardeau', '[celestin_accueil]'],
      'bibliotheque' => ['Les livres de Célestin', '[celestin_catalogue type="livre"]'],
      'toutes-les-histoires' => ['Les mini-histoires', '[celestin_catalogue type="histoire"]'],
      'coloriages-a-imprimer' => ['Les coloriages à imprimer', '[celestin_catalogue type="coloriage"]'],
      'habitants-de-fleurville' => ['Les personnages de Fleurville', '[celestin_catalogue type="personnage"]'],
    ];
    $homepage = 0;
    foreach ($pages as $slug => $data) {
        $existing = get_page_by_path($slug, OBJECT, 'page');
        if ($existing) { $id = $existing->ID; }
        else {
            $id = wp_insert_post([
               'post_type'=>'page', 'post_status'=>'publish', 'post_name'=>$slug,
               'post_title'=>$data[0], 'post_content'=>$data[1],
            ], true);
        }
        if (is_wp_error($id) || !$id) { return; }
        update_post_meta($id, 'site-sidebar-layout', 'no-sidebar');
        update_post_meta($id, 'site-content-layout', 'page-builder');
        update_post_meta($id, '_astra_content_layout_flag', 'disabled');
        update_post_meta($id, 'site-post-title', 'disabled');
        if ($slug === 'accueil-celestin') { $homepage = $id; }
    }
    if ($homepage) {
       if (!get_option('clst_astra_previous_homepage_recorded')) {
          update_option('clst_astra_previous_homepage', [
             'show_on_front'=>get_option('show_on_front'),
             'page_on_front'=>absint(get_option('page_on_front')),
          ], false);
          update_option('clst_astra_previous_homepage_recorded', '1', false);
       }
       update_option('show_on_front', 'page');
       update_option('page_on_front', $homepage);
    }
    update_option('clst_astra_pages_ready', '1', false);
}, 100);

// Ne configure le menu Astra que si aucun menu principal n'existe déjà.
add_action('admin_init', function () {
    if (!clst_astra_active() || get_option('clst_astra_nav_ready')) { return; }
    $locations = get_theme_mod('nav_menu_locations', []);
    if (!empty($locations['primary'])) {
        update_option('clst_astra_nav_ready', '1', false);
        return;
    }
    $menu_name = 'Navigation Célestin';
    $menu = wp_get_nav_menu_object($menu_name);
    $menu_id = $menu ? (int) $menu->term_id : wp_create_nav_menu($menu_name);
    if (is_wp_error($menu_id)) { return; }
    if (!$menu || !wp_get_nav_menu_items($menu_id)) {
        foreach ([
          ['Accueil', '/'], ['Personnages', '/#personnages'],
          ['Coloriages', '/#coloriages'], ['Les livres', '/#livres'],
          ['Mini-histoires', '/#mini-histoires'],
        ] as $item) {
          wp_update_nav_menu_item($menu_id, 0, [
            'menu-item-title'=>$item[0], 'menu-item-url'=>home_url($item[1]),
            'menu-item-status'=>'publish', 'menu-item-type'=>'custom',
          ]);
        }
    }
    $locations['primary'] = $menu_id;
    set_theme_mod('nav_menu_locations', $locations);
    update_option('clst_astra_nav_ready', '1', false);
}, 30);

// Les paramètres de la maquette sont éditables directement dans WordPress.
add_action('admin_menu', function () {
    add_menu_page('L’univers Célestin','Univers Célestin','manage_options','clst-astra-settings','clst_astra_settings_page','dashicons-palmtree',22);
});
add_action('admin_init', function () {
    register_setting('clst_astra_options','clst_astra_hero_image',['type'=>'integer','sanitize_callback'=>'absint']);
    register_setting('clst_astra_options','clst_astra_village_image',['type'=>'integer','sanitize_callback'=>'absint']);
    register_setting('clst_astra_options','clst_astra_intro',['type'=>'string','sanitize_callback'=>'sanitize_textarea_field']);
});
function clst_astra_settings_page() {
    if (!current_user_can('manage_options')) { return; }
    ?>
    <div class="wrap"><h1>🦊 L’univers de Célestin — Astra</h1>
      <p>Gère les illustrations de la page d’accueil sans changer de thème. Les images doivent rester fidèles à tes dessins.</p>
      <form method="post" action="options.php"><?php settings_fields('clst_astra_options'); ?>
      <table class="form-table">
        <?php foreach ([
           'clst_astra_hero_image' => ['Grande illustration du bandeau', 'banniere-celestin-fleurville.webp'],
           'clst_astra_village_image' => ['Illustration de Fleurville', 'jardin.webp'],
        ] as $key => $image_info) :
          $id = absint(get_option($key, 0));
          $src = $id ? wp_get_attachment_image_url($id, 'medium_large') : home_url('/images/' . $image_info[1]);
        ?>
        <tr><th scope="row"><?php echo esc_html($image_info[0]); ?></th>
        <td><img id="<?php echo esc_attr($key); ?>-preview" src="<?php echo esc_url($src); ?>" alt="" style="max-width:350px;max-height:170px;display:block;margin-bottom:10px">
        <input type="hidden" id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($id); ?>">
        <button class="button clst-astra-image-pick" type="button" data-field="<?php echo esc_attr($key); ?>">Choisir dans Médias</button>
        <button class="button clst-astra-image-clear" type="button" data-field="<?php echo esc_attr($key); ?>">Image par défaut</button></td></tr>
        <?php endforeach; ?>
        <tr><th><label for="clst_astra_intro">Texte d’accueil</label></th>
        <td><textarea id="clst_astra_intro" name="clst_astra_intro" class="large-text" rows="3"><?php echo esc_textarea(clst_astra_text('clst_astra_intro', 'Des histoires douces, des coloriages et de jolies découvertes pour les petits curieux !')); ?></textarea></td></tr>
      </table>
      <?php submit_button('Enregistrer les illustrations'); ?></form>
      <p><a href="<?php echo esc_url(home_url('/')); ?>" target="_blank" rel="noopener noreferrer">Voir le site →</a> |
      <a href="<?php echo esc_url(get_edit_post_link((int)get_option('page_on_front'))); ?>">Modifier la page d’accueil dans WordPress →</a></p>
    </div>
    <?php
}
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'toplevel_page_clst-astra-settings') { return; }
    wp_enqueue_media();
    wp_enqueue_script('clst-astra-admin',clst_astra_asset('admin.js'), ['jquery'], '1.0.0', true);
});
add_action('wp_enqueue_scripts', function () {
    if (!clst_astra_active()) { return; }
    if (is_front_page() || is_page(['accueil-celestin','bibliotheque','toutes-les-histoires','coloriages-a-imprimer','habitants-de-fleurville'])) {
        wp_enqueue_style('clst-astra-site',clst_astra_asset('site.css'), [], '1.0.0');
    }
    if (is_singular(['celestin_livre','celestin_coloriage','celestin_histoire','celestin_personnage'])) {
        wp_enqueue_style('clst-astra-site',clst_astra_asset('site.css'), [], '1.0.0');
    }
});

// Laisse Astra gérer le header / footer. On propose le logo historique
// uniquement lorsque aucun autre logo Astra n'a été choisi.
add_filter('astra_logo', function ($html) {
    if (!clst_astra_active() || get_theme_mod('custom_logo')) { return $html; }
    $image = esc_url(home_url('/images/logo.webp'));
    return '<a class="clst-astra-brand" href="' . esc_url(home_url('/')) . '" aria-label="Célestin le renardeau, accueil"><img src="' . $image . '" alt="Célestin le renardeau"></a>';
}, 10, 1);

// Application unique de la nouvelle bannière demandée pour l'accueil.
// L'ancien choix reste conservé dans la médiathèque, et l'utilisateur peut
// toujours changer l'image depuis « Univers Célestin » après ce déploiement.
add_action('init', function () {
    if (!clst_astra_active() || get_option('clst_hero_fleurville_20261123_applied') === '1') { return; }
    update_option('clst_astra_hero_image', 0, false);
    update_option('clst_hero_fleurville_20261123_applied', '1', false);
}, 110);

// Les fichiers PDF et liens de vente restent visibles sur les fiches Astra.
add_filter('the_content', function ($content) {
    if (!is_singular(['celestin_livre','celestin_coloriage']) || !in_the_loop() || !is_main_query()) { return $content; }
    $id = get_the_ID();
    $file = clst_astra_post_file($id);
    $external = esc_url_raw(get_post_meta($id,'_clst_external_url',true));
    $url = $file ?: $external;
    if (!$url) { return $content; }
    $label = $file ? 'Télécharger le PDF ou l’illustration ↓' : 'Découvrir le livre →';
    $target = $external && !$file ? ' target="_blank" rel="noopener noreferrer"' : '';
    return $content . '<p class="clst-single-cta"><a class="clst-button clst-primary" href="' . esc_url($url) . '"' . $target . '>' . esc_html($label) . '</a></p>';
}, 20);
