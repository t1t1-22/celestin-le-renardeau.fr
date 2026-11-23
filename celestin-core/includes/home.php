<?php
/**
 * Présentation du site Célestin — indépendante du thème Astra.
 * Les illustrations sont choisies dans WordPress > Célestin Core > Apparence du site.
 */
if (!defined('ABSPATH')) exit;

final class Celestin_Home {
    const OPTION = 'celestin_home_design';
    static function init(): void {
        add_filter('template_include', [__CLASS__, 'template'], 99);
        add_action('admin_menu', [__CLASS__, 'admin'], 20);
        add_action('admin_enqueue_scripts', [__CLASS__, 'admin_assets']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'assets']);
        add_action('admin_post_celestin_save_design', [__CLASS__, 'save_design']);
    }
    static function defaults(): array {
        return [
            'logo'=>0, 'footer_logo'=>0, 'hero'=>0, 'footer'=>0, 'story'=>0, 'book'=>0,
            'character'=>0, 'city'=>0, 'activity'=>0,
            'headline'=>'Pars à l’aventure à Fleuriville',
            'welcome'=>'Un petit coin de nature plein de charme où Célestin vit de grandes et de petites aventures avec Octet, son fidèle compagnon.',
            'newsletter'=>''
        ];
    }
    static function options(): array {
        $saved = get_option(self::OPTION, []);
        return wp_parse_args(is_array($saved)?$saved:[], self::defaults());
    }
    static function image(string $key, string $size='full'): string {
        $o = self::options();
        $id = absint($o[$key] ?? 0);
        return $id ? (string) wp_get_attachment_image_url($id,$size) : '';
    }
    static function render_image(string $key, string $alt, string $classes=''): string {
        $url=self::image($key);
        if (!$url) return '<div class="cel-illustration-placeholder ' . esc_attr($classes) . '"><span aria-hidden="true">✦</span><span>' . esc_html($alt) . '</span></div>';
        return '<img class="' . esc_attr($classes) . '" src="' . esc_url($url) . '" alt="' . esc_attr($alt) . '" loading="lazy">';
    }
    static function admin(): void {
        add_submenu_page('celestin-core','Apparence du site','Apparence du site','manage_options','celestin-design',[__CLASS__,'settings']);
    }
    static function admin_assets($hook): void {
        if ($hook !== 'celestin-core_page_celestin-design') return;
        wp_enqueue_media();
        wp_enqueue_script('celestin-media', plugins_url('../assets/admin.js',__FILE__), ['jquery'], '0.2.1', true);
    }
    static function settings(): void {
        if (!current_user_can('manage_options')) return;
        $o=self::options();
        $images=['logo'=>'Logo du haut (utilisé aussi en bas par défaut)', 'footer_logo'=>'Logo du pied de page (facultatif : sans sélection, reprend le logo du haut)', 'hero'=>'Grande illustration d’accueil (sans texte)', 'activity'=>'Visuel des activités',
                 'story'=>'Illustration de mini-histoire', 'book'=>'Illustration du livre ouvert',
                 'character'=>'Portrait de Célestin', 'city'=>'Carte illustrée de Fleuriville',
                 'footer'=>'Bannière Bienvenue à Fleuriville'];
        echo '<div class="wrap"><h1>Célestin — Apparence du site</h1><p>Les textes, rubriques et mises en page sont codés. Ici, tu remplaces librement les illustrations par tes dessins originaux.</p>';
        if (isset($_GET['saved'])) echo '<div class="notice notice-success"><p>Apparence enregistrée.</p></div>';
        echo '<form action="' . esc_url(admin_url('admin-post.php')) . '" method="post">';
        echo '<input type="hidden" name="action" value="celestin_save_design">';
        wp_nonce_field('celestin_save_design');
        foreach($images as $key=>$label) {
            $id=absint($o[$key]??0);
            $url=$id?wp_get_attachment_image_url($id,'medium'):'';
            echo '<div class="cel-media-setting" style="background:white;border:1px solid #ddd;border-radius:12px;padding:15px;margin:12px 0;max-width:700px"><h3>' . esc_html($label) . '</h3>';
            echo '<input type="hidden" class="cel-media-id" name="design[' . esc_attr($key) . ']" value="' . $id . '">';
            echo '<img class="cel-media-preview" src="' . esc_url($url ?: '') . '" alt="" style="max-width:260px;max-height:130px;display:' . ($url?'block':'none') . ';margin-bottom:12px;object-fit:contain">';
            echo '<button type="button" class="button cel-media-choose">Choisir une image</button> <button type="button" class="button cel-media-remove">Retirer</button></div>';
        }
        foreach(['headline'=>'Grand titre de l’accueil','welcome'=>'Texte de bienvenue','newsletter'=>'Lien vers la newsletter (facultatif)'] as $key=>$label){
            echo '<p><label for="cel_' . esc_attr($key) . '"><strong>' . esc_html($label) . '</strong></label></p>';
            if ($key==='welcome') echo '<p><textarea rows="3" style="width:700px;max-width:95%" name="design[' . esc_attr($key) . ']" id="cel_' . esc_attr($key) . '">' . esc_textarea($o[$key]) . '</textarea></p>';
            else echo '<p><input style="width:700px;max-width:95%" type="text" name="design[' . esc_attr($key) . ']" id="cel_' . esc_attr($key) . '" value="' . esc_attr($o[$key]) . '"></p>';
        }
        submit_button('Enregistrer mon site');
        echo '</form></div>';
    }
    static function save_design(): void {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('celestin_save_design');
        $raw=isset($_POST['design'])&&is_array($_POST['design'])?wp_unslash($_POST['design']):[];
        $out=self::defaults();
        foreach(['logo','footer_logo','hero','footer','story','book','character','city','activity'] as $key) $out[$key]=absint($raw[$key]??0);
        foreach(['headline','welcome'] as $key) $out[$key]=sanitize_textarea_field($raw[$key]??$out[$key]);
        $out['newsletter']=esc_url_raw($raw['newsletter']??'');
        update_option(self::OPTION,$out);
        wp_safe_redirect(add_query_arg('saved','1',admin_url('admin.php?page=celestin-design')));
        exit;
    }
    static function assets(): void {
        if (!is_front_page()) return;
        wp_enqueue_style('celestin-home', plugins_url('../assets/home.css',__FILE__), [], '0.2.1');
        wp_enqueue_script('celestin-home', plugins_url('../assets/home.js',__FILE__), [], '0.2.1', true);
    }
    static function template(string $template): string {
        if (is_front_page() && !is_admin() && !is_feed() && !is_embed() && !is_preview()) return __DIR__.'/front-page.php';
        return $template;
    }
    static function latest(string $type, bool $featured=false): ?WP_Post {
        $args=['post_type'=>$type,'post_status'=>'publish','posts_per_page'=>1,'orderby'=>'date'];
        if($featured) $args['meta_query']=[['key'=>'_cel_en_nouveaute','value'=>'1']];
        $items=get_posts($args);
        return $items?$items[0]:null;
    }
    static function cards(): array {
        $q=new WP_Query(['post_type'=>['cel_coloriage','cel_livre','cel_histoire'],'post_status'=>'publish',
            'posts_per_page'=>4,'meta_query'=>[['key'=>'_cel_en_nouveaute','value'=>'1']],'orderby'=>'date','order'=>'DESC']);
        $posts=$q->posts; wp_reset_postdata();
        if(count($posts)<4){
            $ids=array_map(fn($p)=>$p->ID,$posts);
            $more=get_posts(['post_type'=>['cel_coloriage','cel_livre','cel_histoire'],'post_status'=>'publish',
                'posts_per_page'=>4-count($posts),'post__not_in'=>$ids,'orderby'=>'date','order'=>'DESC']);
            $posts=array_merge($posts,$more);
        }
        return $posts;
    }
    static function url(string $type): string {
        $url=get_post_type_archive_link($type);
        return $url?:home_url('/');
    }
    static function story_text(?WP_Post $story): string {
        if(!$story) return 'Retrouve bientôt les premières mini-histoires de Célestin, à écouter et à lire en famille.';
        return wp_strip_all_tags($story->post_content);
    }
}
Celestin_Home::init();
