<?php
/**
 * Plugin Name: Atelier de Fleurville
 * Description: Rubriques administrables de l'univers Célestin : livres, mini-histoires, coloriages et personnages.
 * Version: 1.0.0
 */
if (!defined('ABSPATH')) { exit; }

add_action('init', function () {
    $types = [
        'celestin_livre' => ['Livres', 'Livre', 'dashicons-book-alt', 'livres'],
        'celestin_histoire' => ['Mini-histoires', 'Mini-histoire', 'dashicons-format-status', 'mini-histoires'],
        'celestin_coloriage' => ['Coloriages', 'Coloriage', 'dashicons-art', 'coloriages'],
        'celestin_personnage' => ['Personnages', 'Personnage', 'dashicons-groups', 'personnages'],
    ];
    foreach ($types as $slug => $parts) {
        [$plural, $singular, $icon, $rewrite] = $parts;
        register_post_type($slug, [
            'labels' => [
                'name' => $plural, 'singular_name' => $singular,
                'menu_name' => $plural,
                'add_new_item' => 'Ajouter : ' . $singular,
                'edit_item' => 'Modifier : ' . $singular,
                'view_item' => 'Voir : ' . $singular,
                'all_items' => 'Tous les ' . strtolower($plural),
                'not_found' => 'Aucun contenu dans cette rubrique',
            ],
            'public' => true,
            'show_in_rest' => true,
            'has_archive' => true,
            'rewrite' => ['slug' => $rewrite, 'with_front' => false],
            'menu_icon' => $icon,
            'menu_position' => 23,
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions'],
            'show_in_nav_menus' => true,
        ]);
    }
}, 5);

add_action('add_meta_boxes', function () {
    foreach (['celestin_livre', 'celestin_coloriage'] as $type) {
        add_meta_box('clst_resources', 'Fichier téléchargeable et liens', 'clst_resources_box', $type, 'normal', 'high');
    }
});
function clst_resources_box($post) {
    wp_nonce_field('clst_save_resources', 'clst_resources_nonce');
    $id = (int) get_post_meta($post->ID, '_clst_file_id', true);
    $file = $id ? wp_get_attachment_url($id) : '';
    $external = get_post_meta($post->ID, '_clst_external_url', true);
    echo '<p>Ajoute le PDF du livre ou le fichier du coloriage depuis la médiathèque WordPress.</p>';
    echo '<p><input type="hidden" id="clst-file-id" name="clst_file_id" value="' . esc_attr($id) . '">';
    echo '<button type="button" class="button clst-media-pick">Choisir un fichier</button> ';
    echo '<button type="button" class="button clst-media-remove">Retirer</button> ';
    echo '<span id="clst-media-label">' . ($file ? esc_html(wp_basename($file)) : 'Aucun fichier sélectionné') . '</span></p>';
    if ($post->post_type === 'celestin_livre') {
        echo '<p><label for="clst-external-url"><strong>Lien externe vers la vente ou la lecture</strong></label></p>';
        echo '<p><input class="widefat" type="url" id="clst-external-url" name="clst_external_url" value="' . esc_attr($external) . '" placeholder="https://…"></p>';
    }
}
add_action('save_post', function ($id, $post) {
    if (!in_array($post->post_type, ['celestin_livre', 'celestin_coloriage'], true)) { return; }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }
    if (!isset($_POST['clst_resources_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['clst_resources_nonce'])), 'clst_save_resources') ||
        !current_user_can('edit_post', $id)) { return; }
    $file_id = isset($_POST['clst_file_id']) ? absint($_POST['clst_file_id']) : 0;
    $allowed = ['application/pdf', 'image/png', 'image/jpeg', 'image/webp'];
    if ($file_id && in_array(get_post_mime_type($file_id), $allowed, true)) {
        update_post_meta($id, '_clst_file_id', $file_id);
    } else {
        delete_post_meta($id, '_clst_file_id');
    }
    $url = isset($_POST['clst_external_url']) ? esc_url_raw(wp_unslash($_POST['clst_external_url'])) : '';
    if ($url) { update_post_meta($id, '_clst_external_url', $url); }
    else { delete_post_meta($id, '_clst_external_url'); }
}, 10, 2);

add_action('admin_enqueue_scripts', function ($hook) {
    if (!in_array($hook, ['post-new.php', 'post.php'], true)) { return; }
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->post_type, ['celestin_livre', 'celestin_coloriage'], true)) { return; }
    wp_enqueue_media();
    wp_enqueue_script('clst-media-picker', plugin_dir_url(__FILE__) . 'celestin-media.js', ['jquery'], '1.0.0', true);
});

add_action('wp_dashboard_setup', function () {
    wp_add_dashboard_widget('clst_welcome', '🦊 Bienvenue dans ton atelier Célestin !', function () {
        echo '<p>Tout se gère ici : clique sur une rubrique dans le menu de gauche pour créer un contenu.</p>';
        echo '<ul>';
        foreach (['celestin_livre' => 'Ajouter un livre', 'celestin_histoire' => 'Écrire une mini-histoire', 'celestin_coloriage' => 'Ajouter un coloriage', 'celestin_personnage' => 'Présenter un personnage'] as $post_type => $label) {
            echo '<li><a href="' . esc_url(admin_url('post-new.php?post_type=' . $post_type)) . '">' . esc_html($label) . '</a></li>';
        }
        echo '</ul><p><strong>Conseil :</strong> définis une « Image mise en avant » pour chaque fiche. Pour les coloriages, ajoute aussi le fichier PDF à télécharger.</p>';
    });
});

// Ne jamais changer le thème actif. Astra est choisi par le propriétaire du site.

// Recopie les deux mini-histoires du site d'origine lors de la première installation.
add_action('init', function () {
    if (!is_blog_installed() || get_option('clst_initial_content_imported')) { return; }
    $existing = get_posts([
        'post_type' => ['celestin_livre','celestin_histoire','celestin_coloriage','celestin_personnage'],
        'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids',
    ]);
    if ($existing) {
        update_option('clst_initial_content_imported', '1', false);
        return;
    }
    $legacy = get_theme_root('celestin') . '/celestin/legacy/ancienne-version.html';
    if (is_file($legacy)) {
        $html = file_get_contents($legacy);
        if (preg_match_all('~<details class="story">([\s\S]*?)</details>~', $html, $matches)) {
            foreach ($matches[1] as $block) {
                if (!preg_match('~<strong>(.*?)</strong>~s', $block, $title_match)) { continue; }
                $title = html_entity_decode(wp_strip_all_tags($title_match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                preg_match('~<div class="story-text">([\s\S]*?)</div>~', $block, $text_match);
                preg_match_all('~<p>([\s\S]*?)</p>~', $text_match[1] ?? '', $paragraphs);
                $content = implode("\n\n", array_map(static function ($p) {
                    return html_entity_decode(wp_strip_all_tags($p), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }, $paragraphs[1]));
                if (!$title || !$content) { continue; }
                wp_insert_post([
                    'post_type' => 'celestin_histoire', 'post_status' => 'publish',
                    'post_title' => $title, 'post_content' => wpautop(esc_html($content)),
                    'post_excerpt' => wp_trim_words($content, 24),
                ]);
            }
        }
    }
    wp_insert_post([
        'post_type' => 'celestin_livre', 'post_status' => 'publish',
        'post_title' => 'Le stand de muguet',
        'post_excerpt' => 'Célestin décide de prendre le relais lorsque Papa ne peut plus monter son stand de muguet.',
        'post_content' => '<p>À Fleurville, Célestin décide de préparer des bouquets de muguet pour le 1er mai. Heureusement, ses amis sont là pour l’aider !</p>',
    ]);
    wp_insert_post([
        'post_type' => 'celestin_livre', 'post_status' => 'publish',
        'post_title' => 'Le trou dans le jardin',
        'post_excerpt' => 'Un mystérieux trou apparaît au pied de la clôture du jardin.',
        'post_content' => '<p>Avec Octet, Célestin cherche à comprendre qui est passé par son jardin.</p>',
    ]);
    $coloring_id = wp_insert_post([
        'post_type' => 'celestin_coloriage', 'post_status' => 'publish',
        'post_title' => 'Le stand de muguet à colorier',
        'post_excerpt' => 'Coloriage A4 à imprimer.',
        'post_content' => '<p>Donne des couleurs au joli stand de muguet !</p>',
    ]);
    if (!is_wp_error($coloring_id)) {
        update_post_meta($coloring_id, '_clst_legacy_file', home_url('/images/coloriage-celestin.pdf'));
    }
    update_option('clst_initial_content_imported', '1', false);
}, 100);
