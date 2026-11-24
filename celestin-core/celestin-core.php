<?php
/**
 * Plugin Name: Célestin Core
 * Description: Gestion des contenus de l'univers Célestin le renardeau.
 * Version: 0.4.56
 * Requires PHP: 8.0
 * Text Domain: celestin-core
 */
if (!defined('ABSPATH')) exit;

final class Celestin_Core {
    const TYPES = [
        'cel_coloriage' => ['Mes petites activités', 'Activité', 'dashicons-art', 'activites'],
        'cel_livre' => ['Les livres', 'Livre', 'dashicons-book', 'livres'],
        'cel_histoire' => ['Mini-histoires audio', 'Mini-histoire', 'dashicons-controls-volumeon', 'mini-histoires'],
        'cel_personnage' => ['Fiches personnages', 'Personnage', 'dashicons-groups', 'personnages'],
        'cel_lieu' => ['Lieux de Fleuriville', 'Lieu', 'dashicons-location', 'lieux'],
    ];

    static function init(): void {
        add_action('init', [__CLASS__, 'register']);
        add_action('admin_head-edit.php', [__CLASS__, 'story_cards_style']);
        add_filter('manage_cel_histoire_posts_columns', [__CLASS__, 'story_columns']);
        add_action('manage_cel_histoire_posts_custom_column', [__CLASS__, 'story_column'], 10, 2);
        add_filter('post_row_actions', [__CLASS__, 'story_actions'], 10, 2);
        add_action('admin_post_cel_duplicate_story', [__CLASS__, 'duplicate_story']);
        add_action('add_meta_boxes', [__CLASS__, 'meta_boxes']);
        add_action('save_post', [__CLASS__, 'save'], 10, 2);
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_shortcode('celestin_nouveautes', [__CLASS__, 'news']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'styles']);
    }
    static function story_columns($columns): array {
        return ['cb'=>$columns['cb']??'<input type="checkbox">','cel_story_cover'=>'Illustration','title'=>'Mini-histoire','cel_story_info'=>'Informations','date'=>'Date'];
    }
    static function story_column($column,$id): void {
        if($column==='cel_story_cover'){echo '<div class="cel-list-cover">'.(has_post_thumbnail($id)?get_the_post_thumbnail($id,'medium_large',['loading'=>'lazy']):'<span>Illustration à venir</span>').'</div>';return;}
        if($column!=='cel_story_info')return;
        $terms=wp_get_post_terms($id,'cel_collection',['fields'=>'names']);
        if(!is_wp_error($terms))foreach($terms as $term)echo '<span class="cel-list-tag">'.esc_html($term).'</span> ';
        echo '<p>'.(Celestin_Fields::value($id,'audio')?'✓ Narration audio':'Narration à ajouter').'</p>';
        echo '<p>'.(Celestin_Fields::value($id,'resume_audio')?'✓ Résumé audio':'Résumé audio à ajouter').'</p>';
    }
    static function story_actions($actions,$post): array {
        if($post->post_type!=='cel_histoire')return $actions;
        if(current_user_can('edit_post',$post->ID)){
            $url=wp_nonce_url(admin_url('admin-post.php?action=cel_duplicate_story&post='.$post->ID),'cel_duplicate_story_'.$post->ID);
            $actions['cel_duplicate']='<a href="'.esc_url($url).'">Dupliquer</a>';
        }
        return $actions;
    }
    static function duplicate_story(): void {
        $id=absint($_GET['post']??0);$p=get_post($id);
        if(!$p||$p->post_type!=='cel_histoire'||!current_user_can('edit_post',$id)||!current_user_can(get_post_type_object('cel_histoire')->cap->create_posts))wp_die('Accès refusé.');
        check_admin_referer('cel_duplicate_story_'.$id);
        $copy=wp_insert_post(wp_slash(['post_type'=>'cel_histoire','post_status'=>'draft','post_title'=>$p->post_title.' (copie)','post_content'=>$p->post_content,'post_excerpt'=>$p->post_excerpt,'post_author'=>get_current_user_id()]),true);
        if(is_wp_error($copy)||!$copy)wp_die('Impossible de dupliquer la fiche.');
        foreach(get_post_meta($id) as $key=>$values){
            if(!str_starts_with($key,'_cel_f_')&&!str_starts_with($key,'_cel_section_image_')&&!in_array($key,['_thumbnail_id','_cel_relations','_cel_story_mp3'],true))continue;
            foreach($values as $value)add_post_meta($copy,$key,wp_slash(maybe_unserialize($value)));
        }
        foreach(get_object_taxonomies('cel_histoire') as $tax){
            $ids=wp_get_object_terms($id,$tax,['fields'=>'ids']);
            if(!is_wp_error($ids))wp_set_object_terms($copy,array_map('intval',$ids),$tax);
        }
        wp_safe_redirect(admin_url('post.php?post='.$copy.'&action=edit'));exit;
    }
    static function story_cards_style(): void {
        $screen=get_current_screen();if(!$screen||$screen->post_type!=='cel_histoire')return;
        echo '<style>
        body.post-type-cel_histoire #wpbody-content .wrap{color:#633619}
        body.post-type-cel_histoire .wp-list-table{display:block;background:transparent;border:0;box-shadow:none}
        body.post-type-cel_histoire .wp-list-table thead,body.post-type-cel_histoire .wp-list-table tfoot{display:none}
        body.post-type-cel_histoire .wp-list-table tbody{display:grid;grid-template-columns:repeat(auto-fill,minmax(235px,1fr));gap:20px;padding:16px 0}
        body.post-type-cel_histoire .wp-list-table tbody tr{display:flex;flex-direction:column;position:relative;background:#fffdfa;border:1px solid #ead5bb;border-radius:15px;padding:14px;box-shadow:0 3px 12px #6336190c}
        body.post-type-cel_histoire .wp-list-table tbody td,body.post-type-cel_histoire .wp-list-table tbody th{display:block;width:auto!important;padding:5px 0;border:0}
        body.post-type-cel_histoire .wp-list-table .check-column{position:absolute;top:20px;left:20px;z-index:2;padding:0}
        body.post-type-cel_histoire .wp-list-table .check-column input{margin:0}
        body.post-type-cel_histoire .wp-list-table .row-title{font-size:17px;color:#633619}
        body.post-type-cel_histoire .wp-list-table .row-actions{position:static;display:flex;flex-wrap:wrap;gap:5px;padding:8px 0}
        body.post-type-cel_histoire .wp-list-table .row-actions a{display:inline-block;border:1px solid #ead5bb;border-radius:6px;padding:4px 8px;color:#633619;text-decoration:none;background:#fff7eb}
        body.post-type-cel_histoire .wp-list-table .row-actions .cel_duplicate a{background:#e98932;color:#fff;border-color:#e98932}
        body.post-type-cel_histoire .cel-list-cover{width:100%;aspect-ratio:1;object-fit:contain;background:#f6e0d3;border-radius:10px;margin-bottom:10px}
        body.post-type-cel_histoire .cel-list-tag{display:inline-block;background:#f6e0d3;padding:3px 8px;border-radius:20px;margin:2px}
        body.post-type-cel_histoire .column-cel_story_info p{margin:4px 0;font-size:12px}
        body.post-type-cel_histoire .column-date{font-size:12px;color:#80532b}
        body.post-type-cel_histoire .toggle-row{display:none}
        body.post-type-cel_histoire #wpbody-content .wrap h1.wp-heading-inline{font-size:26px;font-weight:700;color:#633619}
        body.post-type-cel_histoire #wpbody-content .wrap .page-title-action{background:#e98932;color:#fff;border:1px solid #e98932;border-radius:8px;padding:7px 13px;font-weight:600}
        body.post-type-cel_histoire .tablenav .button,body.post-type-cel_histoire .search-box .button{border-color:#d6b18c;color:#87481e;background:#fff8ef;border-radius:7px}
        body.post-type-cel_histoire .tablenav select{border-color:#d6c2ac;border-radius:7px;color:#633619;background-color:#fffdfa}
        body.post-type-cel_histoire .subsubsub a{color:#a95b26}
        body.post-type-cel_histoire .wp-list-table tbody{grid-template-columns:repeat(auto-fill,minmax(225px,270px));align-items:start}
        body.post-type-cel_histoire .wp-list-table tbody tr{min-height:0;padding:12px;box-sizing:border-box}
        body.post-type-cel_histoire .wp-list-table td.column-cel_story_cover{width:100%!important;max-width:100%;padding:0!important;flex:none;min-height:0}
        body.post-type-cel_histoire .wp-list-table .cel-list-cover{display:block;position:relative;width:100%!important;height:auto!important;aspect-ratio:1/1;max-height:246px;overflow:hidden;padding:0;margin:0 0 8px;box-sizing:border-box}
        body.post-type-cel_histoire .wp-list-table .cel-list-cover img{display:block;position:absolute;inset:0;width:100%!important;height:100%!important;max-height:100%!important;object-fit:contain;margin:0!important}
        body.post-type-cel_histoire .wp-list-table .row-title{font-size:17px;line-height:1.35;font-weight:700;color:#633619!important}
        body.post-type-cel_histoire .wp-list-table .row-actions{left:auto!important;position:static!important;visibility:visible!important;font-size:0;gap:5px}
        body.post-type-cel_histoire .wp-list-table .row-actions span{font-size:0}
        body.post-type-cel_histoire .wp-list-table .row-actions a,body.post-type-cel_histoire .wp-list-table .row-actions button{display:inline-block;font-size:12px;line-height:1.4;padding:5px 8px;border:1px solid #ead5bb;border-radius:6px;background:#fff7eb;color:#633619;text-decoration:none}
        body.post-type-cel_histoire .wp-list-table .row-actions .cel_duplicate a{background:#e98932;color:#fff;border-color:#e98932}
        body.post-type-cel_histoire .wp-list-table .row-actions .trash a{color:#a63b32;background:#fff4ef}
        body.post-type-cel_histoire .wp-list-table .row-actions .inline{display:none}
        body.post-type-cel_histoire .wp-list-table .row-actions a:hover{border-color:#e98932}
        </style>';
    }
    static function register(): void {
        foreach (self::TYPES as $type => $config) {
            [$plural, $singular, $icon, $slug] = $config;
            register_post_type($type, [
                'labels' => [
                    'name' => $plural, 'singular_name' => $singular,
                    'add_new_item' => 'Ajouter : ' . $singular,
                    'edit_item' => 'Modifier : ' . $singular,
                    'all_items' => 'Tous les éléments',
                ],
                'public' => true, 'show_in_rest' => true,
                'menu_icon' => $icon, 'has_archive' => true,
                'rewrite' => ['slug' => $slug],
                'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions'],
            ]);
        }
        foreach (['cel_coloriage', 'cel_histoire'] as $type) {
            register_taxonomy('cel_theme_' . $type, $type, [
                'labels' => ['name' => 'Catégories', 'singular_name' => 'Catégorie'],
                'hierarchical' => true, 'show_ui' => true, 'show_admin_column' => true,
                'show_in_rest' => true, 'rewrite' => false,
            ]);
            register_taxonomy('cel_tags_' . $type, $type, [
                'labels' => ['name' => 'Étiquettes', 'singular_name' => 'Étiquette'],
                'hierarchical' => false, 'show_ui' => true, 'show_admin_column' => true,
                'show_in_rest' => true, 'rewrite' => false,
            ]);
        }
        register_taxonomy('cel_collection', 'cel_livre', [
            'labels' => ['name' => 'Catégories', 'singular_name' => 'Catégorie'],
            'hierarchical' => true, 'show_ui' => true, 'show_in_rest' => true,
        ]);
        register_taxonomy('cel_book_tags', 'cel_livre', [
            'labels' => ['name' => 'Étiquettes', 'singular_name' => 'Étiquette'],
            'hierarchical' => false, 'show_ui' => true, 'show_in_rest' => true,
        ]);
    }
    static function meta_boxes(): void {
        foreach (array_keys(self::TYPES) as $type) {
        }
        // Champs détaillés regroupés dans le formulaire de fiche (Celestin_Fields).
    }
    static function news_box(WP_Post $post): void {
        wp_nonce_field('cel_core_save', 'cel_core_nonce');
        echo '<label><input type="checkbox" name="cel_en_nouveaute" value="1" ' .
          checked(get_post_meta($post->ID, '_cel_en_nouveaute', true), '1', false) .
          '> <strong>Afficher sur l’accueil dans « Les nouveautés »</strong></label>';
        echo '<p class="description">La fiche apparaîtra automatiquement avec son titre, son image mise en avant et son lien, dès sa publication. Décochez pour la retirer.</p>';
    }
    static function home_box(WP_Post $post): void {
        wp_nonce_field('cel_core_save', 'cel_core_nonce');
        echo '<label><input type="checkbox" name="cel_feature_home" value="1" ' .
            checked(get_post_meta($post->ID, '_cel_feature_home', true), '1', false) .
            '> <strong>Afficher cette fiche sur la page d’accueil</strong></label>';
        echo '<p class="description">Cette option alimente la rubrique correspondante : Livres, Mini-histoires, Personnages, Activités ou Fleuriville. Elle est indépendante des « Nouveautés ».</p>';
        echo '<p class="description">Tu peux sélectionner plusieurs fiches. La plus récemment publiée apparaît en premier.</p>';
    }
    static function file_field(WP_Post $post, string $meta_key, string $name, string $description): void {
        wp_nonce_field('cel_core_save', 'cel_core_nonce');
        echo '<p><label for="' . esc_attr($name) . '">' . esc_html($description) . '</label></p>';
        echo '<input class="widefat" type="url" name="' . esc_attr($name) . '" id="' . esc_attr($name) .
          '" value="' . esc_attr(get_post_meta($post->ID, $meta_key, true)) . '" placeholder="https://...">';
        echo '<p class="description">Importer le fichier dans Médias, puis coller son URL ici.</p>';
    }
    static function activity_box(WP_Post $post): void {
        self::file_field($post, '_cel_pdf_url', 'cel_pdf_url', 'PDF imprimable');
    }
    static function book_box(WP_Post $post): void {
        self::file_field($post, '_cel_book_pdf', 'cel_book_pdf', 'PDF complet du livre carré');
    }
    static function audio_box(WP_Post $post): void {
        self::file_field($post, '_cel_story_mp3', 'cel_story_mp3', 'Narration MP3 (une illustration principale)');
    }
    static function save(int $id, WP_Post $post): void {
        if (!isset(self::TYPES[$post->post_type])) return;
        if (!isset($_POST['cel_core_nonce']) ||
          !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cel_core_nonce'])), 'cel_core_save')) return;
        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($id)) return;
        if (!current_user_can('edit_post', $id)) return;
        $fields = ['cel_pdf_url' => '_cel_pdf_url', 'cel_book_pdf' => '_cel_book_pdf', 'cel_story_mp3' => '_cel_story_mp3'];
        foreach ($fields as $field => $key) {
            if (isset($_POST[$field])) update_post_meta($id, $key, esc_url_raw(wp_unslash($_POST[$field])));
        }
    }
    static function menu(): void {
        add_menu_page('Célestin Core', 'Célestin Core', 'manage_options',
          'celestin-core', [__CLASS__, 'dashboard'], 'dashicons-admin-home', 2);
    }
    static function dashboard(): void {
        if (!current_user_can('manage_options')) return;
        echo '<div class="wrap"><h1>Célestin Core</h1><p>Gère les activités, livres, mini-histoires audio, personnages et lieux dans les menus WordPress.</p>';
        echo '<p>Pour sélectionner les contenus à afficher sur l’accueil, ouvre <a href="'.esc_url(admin_url('admin.php?page=celestin-news')).'">Nouveautés</a>.</p>';
        echo '<p>Version initiale : les visionneuses, le feuilletage, les relations entre contenus et la synchronisation audio/texte seront développés ultérieurement.</p></div>';
    }
    static function news($atts=[]): string {
        $atts = shortcode_atts(['nombre'=>3], (array) $atts, 'celestin_nouveautes');
        $n = max(1, min(12, (int)$atts['nombre']));
        $q = new WP_Query([
            'post_type'=>array_keys(self::TYPES), 'post_status'=>'publish',
            'posts_per_page'=>$n, 'meta_query'=>[['key'=>'_cel_en_nouveaute', 'value'=>'1']],
            'orderby'=>'date', 'order'=>'DESC'
        ]);
        ob_start();
        echo '<section class="celestin-news"><h2>Les nouveautés</h2><div class="celestin-news-grid">';
        while ($q->have_posts()) {
            $q->the_post();
            echo '<article class="celestin-news-card"><a href="' . esc_url(get_permalink()) . '">';
            if (has_post_thumbnail()) echo get_the_post_thumbnail(get_the_ID(), 'medium_large');
            echo '<h3>' . esc_html(get_the_title()) . '</h3><p>' .
                 esc_html(wp_trim_words(get_the_excerpt(), 17)) . '</p></a></article>';
        }
        if (!$q->post_count) echo '<p>Les prochaines nouveautés arrivent bientôt !</p>';
        echo '</div></section>';
        wp_reset_postdata();
        return (string)ob_get_clean();
    }
    static function styles(): void {
        wp_register_style('celestin-core', false, [], '0.4.3');
        wp_enqueue_style('celestin-core');
        wp_add_inline_style('celestin-core', '.celestin-news{max-width:1140px;margin:3rem auto;padding:0 1rem}.celestin-news-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1.5rem}.celestin-news-card{overflow:hidden;border-radius:18px;background:white;box-shadow:0 8px 24px #54371816}.celestin-news-card img{width:100%;aspect-ratio:4/3;object-fit:contain}.celestin-news-card h3,.celestin-news-card p{padding:0 1rem}.celestin-news-card a{color:inherit;text-decoration:none}@media(max-width:700px){.celestin-news-grid{grid-template-columns:1fr}}');
    }
    static function activate(): void { self::register(); flush_rewrite_rules(); }
    static function deactivate(): void { flush_rewrite_rules(); }
}
require_once __DIR__ . '/includes/home.php';
require_once __DIR__ . '/includes/news.php';
require_once __DIR__ . '/includes/library.php';
require_once __DIR__ . '/includes/fields.php';
require_once __DIR__ . '/includes/public.php';
require_once __DIR__ . '/includes/town.php';
Celestin_Core::init();
register_activation_hook(__FILE__, ['Celestin_Core','activate']);
register_deactivation_hook(__FILE__, ['Celestin_Core','deactivate']);
