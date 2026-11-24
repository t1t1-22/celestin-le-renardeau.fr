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
            'hero_title_size'=>64, 'hero_text_size'=>18, 'hero_x'=>50, 'hero_y'=>50, 'hero_width'=>680, 'hero_text_location'=>'overlay',
            'hero_intro'=>'Bienvenue dans l’univers de Célestin', 'hero_description'=>'Des histoires à lire, des aventures à écouter et plein de petites activités à découvrir.', 'hero_button'=>'Découvrir l’univers', 'hero_link'=>'#cel-decouvrir',
            'welcome'=>'Un petit coin de nature plein de charme où Célestin vit de grandes et de petites aventures avec Octet, son fidèle compagnon.',
            'newsletter'=>'','facebook'=>'','instagram'=>'','pinterest'=>''
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
        wp_enqueue_script('celestin-media', plugins_url('../assets/admin.js',__FILE__), ['jquery'], '0.4.29', true);
        wp_enqueue_style('celestin-admin', plugins_url('../assets/admin.css',__FILE__), [], '0.4.29');
    }
    static function image_setting(string $key, string $label, array $options): void {
        $id = absint($options[$key] ?? 0);
        $url = $id ? wp_get_attachment_image_url($id, 'medium') : '';
        echo '<div class="cel-media-setting"><h3>' . esc_html($label) . '</h3>';
        echo '<input type="hidden" class="cel-media-id" name="design[' . esc_attr($key) . ']" value="' . $id . '">';
        echo '<img class="cel-media-preview" src="' . esc_url($url ?: '') . '" alt="" style="display:' . ($url ? 'block' : 'none') . '">';
        echo '<div class="cel-media-actions"><button type="button" class="button cel-media-choose">Choisir une image</button> <button type="button" class="button cel-media-remove">Retirer</button></div></div>';
    }
    static function text_setting(string $key, string $label, array $options, bool $multiline=false): void {
        echo '<div class="cel-text-setting"><label for="cel_' . esc_attr($key) . '"><strong>' . esc_html($label) . '</strong></label>';
        if ($multiline) echo '<textarea rows="4" name="design[' . esc_attr($key) . ']" id="cel_' . esc_attr($key) . '">' . esc_textarea($options[$key] ?? '') . '</textarea>';
        else echo '<input type="text" name="design[' . esc_attr($key) . ']" id="cel_' . esc_attr($key) . '" value="' . esc_attr($options[$key] ?? '') . '">';
        echo '</div>';
    }
    static function featured_settings(): void {
        echo '<h2 style="margin-top:32px">Contenus mis en avant</h2><p>Choisis un contenu publié par rubrique, et jusqu’à trois mini-histoires dans leur ordre d’affichage. Les nouveautés sont gérées séparément.</p><div class="cel-feature-settings">';
        foreach(Celestin_Core::TYPES as $type=>$config){
            $current=self::featured($type,$type==='cel_histoire'?3:1);
            $id=$current?(int)$current[0]->ID:0;
            $posts=get_posts(['post_type'=>$type,'post_status'=>'publish','posts_per_page'=>-1,'orderby'=>'title','order'=>'ASC']);
            echo '<div class="cel-feature-setting"><label for="featured_'.esc_attr($type).'"><strong>'.esc_html($config[0]).'</strong></label>';
            if($type==='cel_histoire'){
                for($i=0;$i<3;$i++){
                    $chosen=isset($current[$i])?(int)$current[$i]->ID:0;
                    echo '<label for="featured_story_'.$i.'">Histoire '.($i+1).'</label><select id="featured_story_'.$i.'" name="design[featured][cel_histoire][]"><option value="0">Aucune histoire</option>';
                    foreach($posts as $p)echo '<option value="'.(int)$p->ID.'"'.selected($chosen,$p->ID,false).'>'.esc_html($p->post_title).'</option>';
                    echo '</select>';
                }
                echo '</div>';continue;
            }
            if($id&&has_post_thumbnail($id))echo get_the_post_thumbnail($id,'thumbnail',['class'=>'cel-feature-thumb']);
            echo '<select id="featured_'.esc_attr($type).'" name="design[featured]['.esc_attr($type).']"><option value="0">Aucun contenu</option>';
            foreach($posts as $p)echo '<option value="'.(int)$p->ID.'"'.selected($id,$p->ID,false).'>'.esc_html($p->post_title).'</option>';
            echo '</select></div>';
        }
        echo '</div>';
    }
    static function settings(): void {
        if (!current_user_can('manage_options')) return;
        $o = self::options();
        $tabs = [
            'identite' => ['Identité visuelle', 'dashicons-admin-appearance'],
            'accueil' => ['Page d’accueil', 'dashicons-admin-home'],
            'rubriques' => ['Rubriques', 'dashicons-screenoptions'],
            'fleuriville' => ['Fleuriville & pied de page', 'dashicons-location-alt'],
            'newsletter' => ['Newsletter', 'dashicons-email-alt']
        ];
        $active = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'identite';
        if (!isset($tabs[$active])) $active = 'identite';
        echo '<div class="wrap cel-design-admin"><h1>Apparence du site</h1>';
        echo '<p class="cel-admin-intro">Personnalise les images et les textes de ton site. Tes illustrations restent dans la médiathèque WordPress.</p>';
        if (isset($_GET['saved'])) echo '<div class="notice notice-success is-dismissible"><p>Les paramètres ont été enregistrés.</p></div>';
        echo '<form id="cel-design-form" action="' . esc_url(admin_url('admin-post.php')) . '" method="post">';
        echo '<input type="hidden" name="action" value="celestin_save_design">';
        echo '<input type="hidden" name="cel_active_tab" id="cel-active-tab" value="' . esc_attr($active) . '">';
        wp_nonce_field('celestin_save_design');
        echo '<nav class="cel-design-tabs" role="tablist" aria-label="Catégories des paramètres">';
        foreach ($tabs as $key => [$title, $icon]) {
            echo '<button type="button" class="cel-design-tab' . ($active === $key ? ' is-active' : '') .
                '" id="cel-tab-' . esc_attr($key) . '" role="tab" aria-controls="cel-panel-' . esc_attr($key) . '" aria-selected="' .
                ($active === $key ? 'true' : 'false') . '" tabindex="' . ($active === $key ? '0' : '-1') .
                '" data-cel-tab="' . esc_attr($key) . '"><span class="dashicons ' . esc_attr($icon) . '" aria-hidden="true"></span>' .
                esc_html($title) . '</button>';
        }
        echo '</nav>';

        echo '<section id="cel-panel-identite" class="cel-design-panel" role="tabpanel" aria-labelledby="cel-tab-identite"' . ($active !== 'identite' ? ' hidden' : '') . '>';
        echo '<h2>Identité visuelle</h2><p>Le logo en haut du site est aussi utilisé dans le pied de page si aucun autre n’est sélectionné.</p>';
        self::image_setting('logo', 'Logo principal (haut du site)', $o);
        self::image_setting('footer_logo', 'Logo alternatif pour le pied de page (facultatif)', $o);
        echo '<h2>Réseaux sociaux</h2><p>Les icônes apparaissent dans le header des pages intérieures lorsque leur lien est renseigné.</p>';
        foreach(['facebook'=>'Facebook','instagram'=>'Instagram','pinterest'=>'Pinterest'] as $key=>$label)self::text_setting($key,'Lien '.$label,$o);
        echo '</section>';

        echo '<section id="cel-panel-accueil" class="cel-design-panel" role="tabpanel" aria-labelledby="cel-tab-accueil"' . ($active !== 'accueil' ? ' hidden' : '') . '>';
        echo '<h2>Page d’accueil</h2><p>Personnalise la grande illustration d’ouverture et son titre.</p>';
        self::image_setting('hero', 'Illustration principale de l’accueil (sans texte)', $o);
        self::text_setting('hero_intro', 'Accroche', $o);
        self::text_setting('headline', 'Grand titre de l’accueil', $o);
        self::text_setting('hero_description', 'Description', $o, true);
        self::text_setting('hero_button', 'Texte du bouton', $o);
        self::text_setting('hero_link', 'Lien du bouton', $o);
        echo '<h2 style="margin-top:28px">Taille et position du texte</h2><p>Déplace le bloc de texte sur ton illustration ou affiche-le sous l’image.</p>';
        foreach(['hero_title_size'=>['Taille maximale du titre (px)',24,100],'hero_text_size'=>['Taille de la description (px)',12,32],'hero_x'=>['Position horizontale (%) : gauche → droite',0,100],'hero_y'=>['Position verticale (%) : haut → bas',0,100],'hero_width'=>['Largeur maximale du bloc de texte (px)',240,1100]] as $key=>[$label,$min,$max]){
            echo '<div class="cel-text-setting"><label for="cel_'.esc_attr($key).'"><strong>'.esc_html($label).'</strong></label><input type="number" id="cel_'.esc_attr($key).'" name="design['.esc_attr($key).']" min="'.$min.'" max="'.$max.'" value="'.esc_attr((string)$o[$key]).'" style="max-width:180px"></div>';
        }
        echo '<div class="cel-text-setting"><label for="cel_hero_text_location"><strong>Emplacement du texte</strong></label><select id="cel_hero_text_location" name="design[hero_text_location]"><option value="overlay"'.selected($o['hero_text_location'],'overlay',false).'>Sur l’image</option><option value="below"'.selected($o['hero_text_location'],'below',false).'>Sous l’image</option></select></div>';

        self::featured_settings();
        echo '</section>';

        echo '<section id="cel-panel-rubriques" class="cel-design-panel" role="tabpanel" aria-labelledby="cel-tab-rubriques"' . ($active !== 'rubriques' ? ' hidden' : '') . '>';
        echo '<h2>Visuels des rubriques</h2><p>Images de présentation. Les contenus publiés utilisent en priorité leur propre image à la une.</p>';
        self::image_setting('activity', 'Mes petites activités', $o);
        self::image_setting('story', 'Mini-histoires audio', $o);
        self::image_setting('book', 'Les livres', $o);
        self::image_setting('character', 'Fiches personnages', $o);
        echo '</section>';

        echo '<section id="cel-panel-fleuriville" class="cel-design-panel" role="tabpanel" aria-labelledby="cel-tab-fleuriville"' . ($active !== 'fleuriville' ? ' hidden' : '') . '>';
        echo '<h2>Fleuriville & bienvenue</h2><p>Configure les visuels de Fleuriville et la bannière de bienvenue, juste avant le pied de page.</p>';
        self::image_setting('city', 'Carte illustrée de Fleuriville', $o);
        self::image_setting('footer', 'Bannière « Bienvenue à Fleuriville »', $o);
        self::text_setting('welcome', 'Texte de bienvenue', $o, true);
        echo '</section>';

        echo '<section id="cel-panel-newsletter" class="cel-design-panel" role="tabpanel" aria-labelledby="cel-tab-newsletter"' . ($active !== 'newsletter' ? ' hidden' : '') . '>';
        echo '<h2>Newsletter</h2><p>Si le lien est vide, le site affiche « Bientôt disponible ».</p>';
        self::text_setting('newsletter', 'Lien vers le formulaire de newsletter (facultatif)', $o);
        echo '</section>';

        echo '<div class="cel-design-actions">';
        submit_button('Enregistrer les paramètres', 'primary', 'submit', false);
        echo '<span>Les modifications de tous les onglets sont enregistrées ensemble.</span></div>';
        echo '</form></div>';
    }
    static function save_design(): void {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('celestin_save_design');
        $raw=isset($_POST['design'])&&is_array($_POST['design'])?wp_unslash($_POST['design']):[];
        $out=self::defaults();
        foreach(['logo','footer_logo','hero','footer','story','book','character','city','activity'] as $key) $out[$key]=absint($raw[$key]??0);
        foreach(['headline','welcome','hero_intro','hero_description','hero_button'] as $key) $out[$key]=sanitize_textarea_field($raw[$key]??$out[$key]);
        $out['hero_link']=esc_url_raw($raw['hero_link']??'#cel-decouvrir');
        foreach(['hero_title_size'=>[24,100],'hero_text_size'=>[12,32],'hero_x'=>[0,100],'hero_y'=>[0,100],'hero_width'=>[240,1100]] as $key=>[$min,$max]){
            $out[$key]=max($min,min($max,(int)($raw[$key]??$out[$key])));
        }
        $out['hero_text_location']=($raw['hero_text_location']??'overlay')==='below'?'below':'overlay';

        $out['newsletter']=esc_url_raw($raw['newsletter']??'');
        foreach(['facebook','instagram','pinterest'] as $key)$out[$key]=esc_url_raw($raw[$key]??'',['http','https']);
        $previous=get_option(self::OPTION,[]);
        $out['featured']=is_array($previous)?($previous['featured']??[]):[];
        if(isset($raw['featured'])&&is_array($raw['featured'])){
            foreach(Celestin_Core::TYPES as $type=>$config){
                if($type==='cel_histoire'){
                    $ids=array_unique(array_map('absint',(array)($raw['featured'][$type]??[])));
                    $out['featured'][$type]=array_slice(array_values(array_filter($ids,fn($id)=>$id&&get_post_type($id)===$type&&get_post_status($id)==='publish')),0,3);
                    continue;
                }
                $id=absint($raw['featured'][$type]??0);
                $out['featured'][$type]=$id&&get_post_type($id)===$type&&get_post_status($id)==='publish'?$id:0;
            }
        }
        update_option(self::OPTION,$out);
        $allowed_tabs = ['identite', 'accueil', 'rubriques', 'fleuriville', 'newsletter'];
        $active = isset($_POST['cel_active_tab']) ? sanitize_key(wp_unslash($_POST['cel_active_tab'])) : 'identite';
        if (!in_array($active, $allowed_tabs, true)) $active = 'identite';
        wp_safe_redirect(add_query_arg(['saved' => '1', 'tab' => $active], admin_url('admin.php?page=celestin-design')));
        exit;
    }
    static function assets(): void {
        if (!is_front_page()) return;
        wp_enqueue_style('celestin-home', plugins_url('../assets/home.css',__FILE__), [], '0.4.29');
        wp_enqueue_script('celestin-home', plugins_url('../assets/home.js',__FILE__), [], '0.4.29', true);
    }
    static function template(string $template): string {
        if (is_front_page() && !is_admin() && !is_feed() && !is_embed() && !is_preview()) return __DIR__.'/front-page.php';
        return $template;
    }
    // Les rubriques de l’accueil utilisent exclusivement les fiches cochées
    // « Afficher cette fiche sur la page d’accueil ».
    static function featured(string $type, int $limit=8): array {
        if (!in_array($type, array_keys(Celestin_Core::TYPES), true)) return [];
        $saved=self::options()['featured']??[];
        if(is_array($saved)&&array_key_exists($type,$saved)){
            $ids=array_unique(array_map('absint',(array)$saved[$type]));$result=[];
            foreach($ids as $id){$p=$id?get_post($id):null;if($p&&$p->post_type===$type&&$p->post_status==='publish')$result[]=$p;}
            return array_slice($result,0,$type==='cel_histoire'?max(1,min(3,$limit)):1);
        }
        return get_posts([
            'post_type' => $type,
            'post_status' => 'publish',
            'posts_per_page' => $type==='cel_histoire'?max(1,min(3,$limit)):1,
            'meta_key' => '_cel_feature_home',
            'meta_value' => '1',
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
    }
    static function latest(string $type, bool $featured=true): ?WP_Post {
        if($featured) {
            $items=self::featured($type,1);
        } else {
            $items=get_posts(['post_type'=>$type,'post_status'=>'publish','posts_per_page'=>1,'orderby'=>'date','order'=>'DESC']);
        }
        return $items?$items[0]:null;
    }
    // Accueil : uniquement les fiches explicitement cochées dans leur administration.
    // Les quatre types de contenus sont mélangés et triés par date de publication.
    static function cards(): array {
        return get_posts([
            'post_type' => ['cel_coloriage', 'cel_personnage', 'cel_livre', 'cel_histoire'],
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_key' => '_cel_en_nouveaute',
            'meta_value' => '1',
            'orderby' => 'date',
            'order' => 'DESC',
            'suppress_filters' => false,
        ]);
    }
    static function url(string $type): string {
        $url=get_post_type_archive_link($type);
        return $url?:home_url('/');
    }
    static function story_text(?WP_Post $story): string {
        if(!$story) return 'Retrouve bientôt les premières mini-histoires de Célestin, à écouter et à lire en famille.';
        $structured = class_exists('Celestin_Fields') ? Celestin_Fields::value($story->ID, 'texte') : '';
        return $structured ?: wp_strip_all_tags($story->post_content);
    }
}
Celestin_Home::init();
