<?php
/**
 * Fiches structurées : identité, histoire, livres, relations et champs personnalisés.
 * On conserve les post-types et les contenus déjà présents dans WordPress.
 */
if (!defined('ABSPATH')) exit;

final class Celestin_Fields {
    const TYPES = ['cel_personnage','cel_livre','cel_histoire','cel_coloriage','cel_lieu'];
    const DEFINITIONS = [
      'cel_personnage' => [
        'identite' => ['Identité'=>['prenom'=>'Prénom','nom'=>'Nom de famille','genre'=>'Genre','age'=>'Âge','espece'=>'Espèce','role'=>'Rôle dans les histoires','surnom'=>'Surnom','anniversaire'=>'Anniversaire']],
        'physique' => ['Portrait physique'=>['taille'=>'Taille','pelage'=>'Couleur du pelage','yeux'=>'Couleur des yeux','vetements'=>'Tenue habituelle','particularite'=>'Signes distinctifs']],
        'caractere' => ['Personnalité'=>['qualites'=>'Qualités','defauts'=>'Petits défauts','aime'=>'Ce que j’adore','deteste'=>'Ce que je n’aime pas','peurs'=>'Mes petites peurs','reves'=>'Mes rêves','phrase'=>'Ma phrase préférée']],
        'univers' => ['Dans l’univers de Célestin'=>['description'=>'Description humoristique','anecdote'=>'Une petite anecdote','audio'=>'Présentation audio (MP3)']]
      ],
      'cel_livre' => [
        'principal' => ['Identité du livre'=>['resume'=>'Résumé','resume_audio'=>'Résumé audio (MP3)','numero'=>'Numéro du livre (facultatif)','nombre_pages'=>'Nombre de pages','points_livre'=>'Dans ce livre… (un élément par ligne)']],
        'fichier' => ['Les pages'=>['pdf'=>'PDF complet du livre carré']]
      ],
      'cel_histoire' => [
        'principal'=>['Identité de la mini-histoire'=>['resume'=>'Résumé écrit','resume_audio'=>'Résumé audio (MP3)']],
        'audio'=>['Audio et texte'=>['audio'=>'Narration MP3','texte'=>'Texte intégral','synchronisation'=>'Repères de synchronisation (facultatif)']]
      ],
      'cel_coloriage' => [
        'principal'=>['Activité'=>['resume'=>'Présentation','type'=>'Type d’activité','pdf'=>'Fichier PDF à imprimer']]
      ],
      'cel_lieu' => [
        'principal'=>['Lieu de Fleuriville'=>['resume'=>'Description du lieu','histoire'=>'Son histoire','adresse'=>'Emplacement ou repère sur la carte']]
      ]
    ];

    static function init(): void {
        add_action('init',[__CLASS__,'remove_editor'],30);
        add_filter('post_row_actions',[__CLASS__,'character_duplicate_action'],20,2);
        add_action('admin_post_cel_duplicate_character',[__CLASS__,'duplicate_character']);
        add_action('wp_ajax_cel_activity_type_add',[__CLASS__,'add_activity_type']);
        add_filter('wp_insert_post_data',[__CLASS__,'automatic_character_title'],20,2);
        add_action('add_meta_boxes',[__CLASS__,'boxes'],30);
        add_action('add_meta_boxes_cel_coloriage',static function(){
            foreach(['cel_illustration_help','postimagediv','categorydiv','cel_theme_cel_coloriagediv'] as $box)foreach(['normal','advanced','side'] as $context)remove_meta_box($box,'cel_coloriage',$context);
        },100);
        add_action('save_post',[__CLASS__,'save'],20,2);
        add_action('admin_enqueue_scripts',[__CLASS__,'admin_assets']);
        add_filter('enter_title_here',[__CLASS__,'title_placeholder'],10,2);
        add_filter('use_block_editor_for_post_type',[__CLASS__,'disable_blocks'],10,2);
    }
    static function duplicate_character_url(int $id): string {
        return wp_nonce_url(admin_url('admin-post.php?action=cel_duplicate_character&post='.$id),'cel_duplicate_character_'.$id);
    }
    static function character_duplicate_action(array $actions,WP_Post $post): array {
        $type=get_post_type_object('cel_personnage');
        if($post->post_type==='cel_personnage'&&$post->post_status!=='trash'&&$type&&current_user_can($type->cap->create_posts)&&current_user_can('edit_post',$post->ID)){
            $actions['cel_duplicate']='<a href="'.esc_url(self::duplicate_character_url($post->ID)).'">Dupliquer</a>';
        }
        return $actions;
    }
    static function duplicate_character(): void {
        $id=absint($_GET['post']??0);
        $source=get_post($id);$type=get_post_type_object('cel_personnage');
        if(!$source||$source->post_type!=='cel_personnage'||$source->post_status==='trash'||!$type||!current_user_can($type->cap->create_posts)||!current_user_can('edit_post',$id))wp_die('Accès refusé.');
        check_admin_referer('cel_duplicate_character_'.$id);
        $new=wp_insert_post(wp_slash([
            'post_type'=>'cel_personnage','post_status'=>'draft','post_author'=>get_current_user_id(),
            'post_title'=>$source->post_title.' — Copie','post_content'=>$source->post_content,
            'post_excerpt'=>$source->post_excerpt,'menu_order'=>$source->menu_order
        ]),true);
        if(is_wp_error($new)||!$new)wp_die('Impossible de dupliquer la fiche.');
        foreach(get_post_meta($id) as $key=>$values){
            if(strpos($key,'_cel_f_')!==0&&strpos($key,'_cel_section_image_')!==0&&!in_array($key,['_cel_relations','_thumbnail_id'],true))continue;
            foreach($values as $value)add_post_meta($new,$key,wp_slash(maybe_unserialize($value)));
        }
        foreach(get_object_taxonomies('cel_personnage') as $taxonomy){
            $terms=wp_get_object_terms($id,$taxonomy,['fields'=>'ids']);
            if(!is_wp_error($terms))wp_set_object_terms($new,array_map('intval',$terms),$taxonomy);
        }
        update_post_meta($new,'_cel_en_nouveaute','0');
        update_post_meta($new,'_cel_feature_home','0');
        wp_safe_redirect(admin_url('post.php?post='.$new.'&action=edit'));exit;
    }
    // Le nom affiché sur le site vient directement des champs Prénom + Nom.
    // Le filtre intervient avant l'insertion pour éviter un titre manquant au premier enregistrement.
    static function automatic_character_title(array $data,array $postarr): array {
        if(in_array($data['post_type']??'',['cel_livre','cel_histoire','cel_coloriage'],true)){
            if(isset($_POST['cel_fields_nonce'],$_POST['cel_book_title']) && is_string($_POST['cel_fields_nonce']) && is_string($_POST['cel_book_title']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cel_fields_nonce'])),'cel_fields_save')){
                $data['post_title']=wp_slash(sanitize_text_field(wp_unslash($_POST['cel_book_title'])));
                if($data['post_type']==='cel_coloriage' && !in_array($data['post_status']??'',['auto-draft','trash','inherit'],true)){
                    $title_slug=sanitize_title(wp_unslash($data['post_title']));
                    $type=isset($_POST['cel_f']['type'])&&is_string($_POST['cel_f']['type'])?sanitize_title(wp_unslash($_POST['cel_f']['type'])):'';
                    if($title_slug!==''){
                        $slug=$title_slug;
                        if($type!=='' && $slug!==$type && !str_ends_with($slug,'-'.$type))$slug.='-'.$type;
                        $data['post_name']=wp_unique_post_slug($slug,absint($postarr['ID']??0),$data['post_status'],'cel_coloriage',absint($data['post_parent']??0));
                    }
                }
            }
            return $data;
        }
        if(($data['post_type']??'')!=='cel_personnage')return $data;
        if(!isset($_POST['cel_fields_nonce']) || !is_string($_POST['cel_fields_nonce']))return $data;
        $nonce=sanitize_text_field(wp_unslash($_POST['cel_fields_nonce']));
        if(!wp_verify_nonce($nonce,'cel_fields_save'))return $data;
        if(!isset($_POST['cel_f']) || !is_array($_POST['cel_f']))return $data;
        $fields=wp_unslash($_POST['cel_f']);
        $first=sanitize_text_field(is_string($fields['prenom']??null)?$fields['prenom']:'');
        $last=sanitize_text_field(is_string($fields['nom']??null)?$fields['nom']:'');
        $title=trim($first.' '.$last);
        if($title!=='')$data['post_title']=$title;
        return $data;
    }
    static function remove_editor(): void {
        foreach(self::TYPES as $type) {
            remove_post_type_support($type,'editor');
            remove_post_type_support($type,'custom-fields');
        }
    }
    static function disable_blocks($use,$type) {
        return in_array($type,self::TYPES,true)?false:$use;
    }
    static function title_placeholder($placeholder,$post): string {
        return in_array($post->post_type,self::TYPES,true)?'Titre de la fiche (affiché sur le site)':$placeholder;
    }
    static function boxes(): void {
        foreach(self::TYPES as $type) {
            foreach(['normal','advanced','side'] as $context) remove_meta_box('postcustom',$type,$context);
            add_meta_box('cel_structured','Remplir ma fiche',[__CLASS__,'render'],$type,'normal','high');
            add_meta_box('cel_illustration_help','Illustration principale',[__CLASS__,'illustration_help'],$type,'side','high');
        }
    }
    static function illustration_help(): void {
        echo '<p>L’image mise en avant de WordPress est la photo, la couverture ou l’illustration de cette fiche.</p><p>Elle apparaît automatiquement dans les vignettes du site et dans les nouveautés.</p>';
    }
    static function fields_for(WP_Post $post): array {
        return self::DEFINITIONS[$post->post_type]??[];
    }
    static function admin_assets($hook): void {
        if(!in_array($hook,['post.php','post-new.php'],true)) return;
        $screen=get_current_screen();
        if(!$screen || !in_array($screen->post_type,self::TYPES,true)) return;
        wp_enqueue_media();
        if($screen->post_type==='cel_livre'){
            wp_enqueue_script('cel-admin-pdfjs','https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js',[],'3.11.174',true);
            wp_enqueue_script('cel-pdf-preview',plugins_url('../assets/pdf-preview.js',__FILE__),['jquery','cel-admin-pdfjs'],'0.4.30',true);
        }
        wp_enqueue_style('cel-fields',plugins_url('../assets/fields.css',__FILE__),[],(string)filemtime(__DIR__.'/../assets/fields.css'));
        wp_enqueue_script('cel-fields',plugins_url('../assets/fields.js',__FILE__),['jquery'],(string)filemtime(__DIR__.'/../assets/fields.js'),true);
        if($screen->post_type==='cel_coloriage'){
            wp_add_inline_style('cel-fields','
.post-type-cel_coloriage #wpcontent{background:#fcf7ef}
.post-type-cel_coloriage .wrap>h1{color:#633619;font-weight:800;font-size:27px}
.post-type-cel_coloriage .wrap>.page-title-action{background:#e98932;border-color:#d47b29;color:white;border-radius:24px;padding:7px 16px;font-weight:700}
.post-type-cel_coloriage #cel_structured{border:1px solid #ead9c5;border-radius:18px;box-shadow:0 6px 22px #79523112}
.post-type-cel_coloriage #cel_structured>.postbox-header{display:none}
.post-type-cel_coloriage #cel_structured>.inside{padding:24px;margin:0}
.post-type-cel_coloriage .cel-form-head{display:none}
.post-type-cel_coloriage .cel-activity-title{margin:0 0 22px}
.post-type-cel_coloriage.cel-book-custom-editor #post-body.columns-2{margin-right:0}
.post-type-cel_coloriage.cel-book-custom-editor #postbox-container-1{display:none}
.post-type-cel_coloriage.cel-book-custom-editor #postbox-container-2{width:100%;float:none}
.post-type-cel_coloriage.cel-book-custom-editor #cel_structured{max-width:1500px;margin:0 auto 25px}
.post-type-cel_coloriage .cel-book-savebar{margin-bottom:10px}
.post-type-cel_coloriage .cel-editor-heading{padding-bottom:8px}
.post-type-cel_coloriage .cel-form-panel[data-panel=principal]>h3{display:none}
.post-type-cel_coloriage .cel-form-panel[data-panel=principal]>.cel-field-grid:empty{display:none}
.post-type-cel_coloriage .cel-activity-layout{margin-top:10px}

.post-type-cel_coloriage .cel-activity-layout{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(0,1fr);gap:24px;align-items:start}
.post-type-cel_coloriage .cel-activity-layout .cel-section-image{margin:0;background:#fff8ed;padding:16px}
.post-type-cel_coloriage .cel-activity-layout .cel-section-image-preview{width:100%;max-width:100%;height:auto;max-height:300px;box-sizing:border-box}
.post-type-cel_coloriage .cel-activity-images{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;align-items:stretch}.post-type-cel_coloriage .cel-activity-images .cel-section-image{min-width:0;display:flex;flex-direction:column;gap:6px}.post-type-cel_coloriage .cel-activity-images .cel-section-image-actions{margin-top:auto}.post-type-cel_coloriage .cel-activity-images .cel-section-image-preview[src=""]{display:none!important}.post-type-cel_coloriage .cel-activity-images .cel-section-image:has(.cel-section-image-preview[src=""])::before{content:"Aucune image sélectionnée";display:grid;place-items:center;min-height:140px;border:1px dashed #d9ba94;border-radius:10px;background:#fffdfa;color:#94603c;font-size:12px;order:0}.post-type-cel_coloriage .cel-activity-images .cel-section-image h4{order:-2}.post-type-cel_coloriage .cel-activity-images .cel-section-image .description{order:-1}@media(max-width:600px){.post-type-cel_coloriage .cel-activity-images{grid-template-columns:1fr}}
.post-type-cel_coloriage .cel-activity-content{display:grid;gap:18px}
.post-type-cel_coloriage .cel-activity-content .cel-field-grid{grid-template-columns:1fr}
.post-type-cel_coloriage .cel-activity-content .cel-field{padding:14px 16px;border:1px solid #ead9c5;border-radius:12px;background:#fffdfa}
.post-type-cel_coloriage .cel-activity-content textarea{min-height:135px}
.post-type-cel_coloriage .cel-form-tab{padding:12px 20px}
.post-type-cel_coloriage .cel-form-panel .button{border-color:#e4c4a1;color:#77431f;background:#fff7eb;border-radius:8px}
.post-type-cel_coloriage #submitdiv{border:1px solid #ead9c5;border-radius:14px;overflow:hidden}
.post-type-cel_coloriage #publish{background:#e98932;border-color:#d47b29;border-radius:20px;font-weight:700;padding:0 14px}
.post-type-cel_coloriage #post-preview{border-color:#d7b28b;color:#75421f;border-radius:20px}
.post-type-cel_coloriage #titlediv #title{border:1px solid #e3c9aa;border-radius:12px;padding:10px 15px;background:#fffdfa;color:#633619;font-size:23px}
@media(max-width:1000px){.post-type-cel_coloriage .cel-activity-layout{grid-template-columns:1fr}.post-type-cel_coloriage .cel-activity-layout .cel-section-image-preview{max-width:260px}}
');
        }
        if($screen->post_type==='cel_personnage')wp_add_inline_style('cel-fields','#post-body-content #titlediv{display:none!important}');
    }
    static function render(WP_Post $post): void {
        wp_nonce_field('cel_fields_save','cel_fields_nonce');
        $groups=self::fields_for($post);
        if(in_array($post->post_type,['cel_livre','cel_histoire'],true))echo '<div class="cel-editor-heading"><span>Bibliothèque Célestin</span><h2>'.($post->post_type==='cel_livre'?'Fiche du livre':'Fiche de la mini-histoire audio').'</h2></div>';
        if($post->post_type==='cel_personnage')echo '<div class="cel-editor-heading"><span>Les habitants de Fleuriville</span><h2>Fiche personnage</h2></div>';
        if($post->post_type==='cel_coloriage')echo '<div class="cel-editor-heading"><span>Les petites activités de Célestin</span><h2>Fiche de l’activité</h2></div>';
        if($post->post_type==='cel_coloriage')echo '<div class="cel-book-title-slot cel-activity-title"><label for="cel-book-title">Titre de l’activité</label><input id="cel-book-title" name="cel_book_title" type="text" value="'.esc_attr($post->post_title).'" placeholder="Nom de l’activité"></div>';
        echo '<div class="cel-form-head"><p>'.($post->post_type==='cel_personnage'?'Le titre du personnage est généré automatiquement avec le prénom et le nom. ':'').'Complète les champs ci-dessous : la page du site sera construite automatiquement. Les champs vides ne seront pas affichés.</p></div>';
        echo '<div class="cel-form-tabs" role="tablist" aria-label="Sections de la fiche">';
        $i=0;
        foreach($groups as $slug=>$group) {
          echo '<button type="button" role="tab" data-tab="'.esc_attr($slug).'" class="cel-form-tab'.(!$i?' active':'').'" aria-selected="'.(!$i?'true':'false').'">'.esc_html(array_key_first($group)).'</button>';
          if($slug==='principal' && in_array($post->post_type,['cel_livre','cel_histoire','cel_coloriage'],true))echo '<button type="button" role="tab" data-tab="categories" class="cel-form-tab" aria-selected="false">Catégories</button>';
          $i++;
        }
        if($post->post_type==='cel_personnage') echo '<button type="button" role="tab" data-tab="extras" class="cel-form-tab" aria-selected="false">Attributs personnalisés</button>';
        if($post->post_type==='cel_personnage')echo '<button type="button" role="tab" data-tab="album" class="cel-form-tab" aria-selected="false">Album photo</button>';
        if($post->post_type==='cel_personnage')echo '<button type="button" role="tab" data-tab="family" class="cel-form-tab" aria-selected="false">Famille et relations</button>';
        echo '<button type="button" role="tab" data-tab="relations" class="cel-form-tab" aria-selected="false">'.($post->post_type==='cel_livre'?'Contenus associés':'Associations').'</button>';
        echo '</div>';
        if(in_array($post->post_type,['cel_livre','cel_histoire','cel_coloriage','cel_personnage'],true)){
            echo '<div class="cel-book-savebar"><span class="cel-book-save-label">'.esc_html($post->post_status==='publish'?'Contenu publié':'Contenu en préparation').'</span><button type="button" class="cel-book-preview-action">Prévisualiser</button><button type="button" class="cel-book-save-action">'.esc_html($post->post_status==='publish'?'Enregistrer les modifications':'Enregistrer le brouillon').'</button>';
            if($post->post_status!=='publish')echo '<button type="button" class="cel-book-publish-action">Publier</button>';
            if($post->post_type==='cel_personnage'&&!in_array($post->post_status,['auto-draft','trash'],true)){
                $type=get_post_type_object('cel_personnage');
                if($type&&current_user_can($type->cap->create_posts)&&current_user_can('edit_post',$post->ID))echo '<a class="button" style="border:1px solid #e4c4a1;border-radius:9px;color:#77431f;background:#fff7eb;padding:6px 12px" href="'.esc_url(self::duplicate_character_url($post->ID)).'">Dupliquer la fiche enregistrée</a>';
            }
            echo '</div>';
        }

        if($post->post_type==='cel_personnage'){
            echo '<div class="cel-person-editor"><aside class="cel-person-portrait">';
            self::section_image($post,'identite','Identité');
            echo '<h3 class="cel-person-live-name">'.esc_html(trim(self::value($post->ID,'prenom').' '.self::value($post->ID,'nom'))?:'Nouveau personnage').'</h3><p>Portrait de référence pour tous les onglets.</p></aside><div class="cel-person-panels">';
        }
        $i=0;
        foreach($groups as $slug=>$group) {
          echo '<section class="cel-form-panel" data-panel="'.esc_attr($slug).'"'.($i?' hidden':'').'>';
          foreach($group as $title=>$fields) {
            echo '<h3>'.esc_html($title).'</h3><div class="cel-field-grid">';
            if(in_array($post->post_type,['cel_livre','cel_histoire'],true) && $slug==='principal'){
                echo '</div><div class="cel-book-identity-header">';
                self::section_image($post,$slug,$title);
                $title_label=$post->post_type==='cel_livre'?'Titre du livre':'Titre de la mini-histoire';
                echo '<div class="cel-book-title-slot"><label for="cel-book-title">'.esc_html($title_label).'</label><input id="cel-book-title" name="cel_book_title" type="text" value="'.esc_attr($post->post_title).'" placeholder="'.esc_attr($title_label).'"><p class="description">Ce titre sera affiché sur le site et dans la bibliothèque.</p></div></div><div class="cel-field-grid">';
            }
            if($post->post_type==='cel_coloriage' && $slug==='principal'){
                echo '</div><div class="cel-activity-layout"><div class="cel-activity-images">';
                self::section_image($post,$slug,$title);
                self::section_image($post,'modele_colore','Modèle colorié');
                echo '</div><div class="cel-activity-content"><div class="cel-field-grid">';
                foreach(['type','resume','pdf'] as $key)self::field($post,$key,$fields[$key]);
                echo '</div></div></div><div>';
            }else foreach($fields as $key=>$label) self::field($post,$key,$label);
            echo '</div>';
            if($post->post_type==='cel_livre' && $slug==='principal') Celestin_Library::identity($post);
            if($post->post_type==='cel_livre' && $slug==='fichier'){
                echo '<div id="cel-pdf-preview"><h3>Aperçu des pages du livre</h3><p class="cel-pdf-status" aria-live="polite">Choisis le PDF pour afficher ses pages.</p><div class="cel-admin-reader" hidden><button type="button" class="button cel-admin-prev">←</button><div class="cel-admin-spread"><canvas></canvas><canvas></canvas></div><button type="button" class="button cel-admin-next">→</button><p class="cel-admin-counter"></p></div><div class="cel-pdf-grid"></div></div>';
            } elseif(!in_array($post->post_type,['cel_livre','cel_histoire','cel_coloriage'],true)&&!($post->post_type==='cel_personnage'&&$slug==='identite')) self::section_image($post,$slug,$title);
          }
          echo '</section>';
          $i++;
        }
        if($post->post_type==='cel_personnage') self::extras($post);
        if(in_array($post->post_type,['cel_livre','cel_histoire','cel_coloriage'],true))Celestin_Library::categories($post);
        if($post->post_type==='cel_personnage'){echo '<section class="cel-form-panel" data-panel="album" hidden><h3>Album photo du personnage</h3>';Celestin_Town::album_box($post);echo '</section>';}
        if($post->post_type==='cel_personnage')self::family_panel($post);
        self::relations($post);
        if($post->post_type==='cel_personnage')echo '</div></div>';
    }
    // Image dédiée à chaque onglet, sans avoir à manipuler les réglages WordPress.
    // La photo d'identité / image principale est aussi la vignette automatique.
    static function section_image(WP_Post $post,string $section,string $title): void {
        $primary=$post->post_type==='cel_personnage' ? 'identite' : 'principal';
        $id=absint(get_post_meta($post->ID,'_cel_section_image_'.$section,true));
        if(!$id && $section===$primary)$id=(int)get_post_thumbnail_id($post);
        $url=$id?wp_get_attachment_image_url($id,'medium'):'';
        echo '<div class="cel-section-image"><h4>'.esc_html($section===$primary?($post->post_type==='cel_livre'?'Première de couverture':'Illustration principale'):($section==='modele_colore'?'Modèle déjà colorié':'Illustration de cette rubrique')).'</h4>';
        echo '<p class="description">'.($section===$primary?'Cette image sera aussi utilisée comme vignette sur le site et dans les nouveautés.':($section==='modele_colore'?'Ajoute le dessin en couleurs qui servira de modèle, indépendamment du coloriage à imprimer.':'Illustration facultative propre à « '.esc_html($title).' ».')).'</p>';
        echo '<input type="hidden" name="cel_section_images['.esc_attr($section).']" value="'.esc_attr((string)$id).'" class="cel-section-image-id">';
        echo '<img class="cel-section-image-preview" src="'.esc_url($url?:'').'" alt="Aperçu de l’illustration" style="display:'.($url?'block':'none').'">';
        echo '<div class="cel-section-image-actions"><button type="button" class="button cel-select-section-image">Choisir une image</button> <button type="button" class="button cel-remove-section-image">Retirer</button></div></div>';
    }
    static function activity_types(): array {
        $values=array_merge(['Coloriage','Jeu','Découpage','Labyrinthe','Jeu des différences'],(array)get_option('cel_activity_types',[]));
        foreach(get_posts(['post_type'=>'cel_coloriage','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids']) as $id){
            $v=trim((string)get_post_meta($id,'_cel_f_type',true));if($v!=='')$values[]=$v;
        }
        $unique=[];foreach($values as $v){if(!is_string($v)||!trim($v))continue;$unique[mb_strtolower(trim($v))]=trim($v);}
        $values=array_values($unique);natcasesort($values);return array_values($values);
    }
    static function add_activity_type(): void {
        check_ajax_referer('cel_activity_type','nonce');
        if(!current_user_can('manage_options'))wp_send_json_error('Accès refusé.',403);
        $name=trim(sanitize_text_field(wp_unslash($_POST['name']??'')));
        if(!$name||mb_strlen($name)>150)wp_send_json_error('Indique un nom valide.',400);
        foreach(self::activity_types() as $value)if(mb_strtolower($value)===mb_strtolower($name))wp_send_json_success(['name'=>$value]);
        $values=(array)get_option('cel_activity_types',[]);$values[]=$name;update_option('cel_activity_types',$values,false);
        wp_send_json_success(['name'=>$name]);
    }
    static function field(WP_Post $post,string $key,string $label): void {
        $value=(string)get_post_meta($post->ID,'_cel_f_'.$key,true);
        // Continuité avec les fiches de la version précédente.
        if(!$value && $key==='pdf' && $post->post_type==='cel_livre') $value=(string)get_post_meta($post->ID,'_cel_book_pdf',true);
        if(!$value && $key==='pdf' && $post->post_type==='cel_coloriage') $value=(string)get_post_meta($post->ID,'_cel_pdf_url',true);
        if(!$value && $key==='audio' && $post->post_type==='cel_histoire') $value=(string)get_post_meta($post->ID,'_cel_story_mp3',true);
        $long=in_array($key,['points_livre','description','anecdote','resume','texte','histoire','synchronisation','qualites','defauts','aime','deteste','peurs','reves'],true);
        $media=in_array($key,['audio','resume_audio','pdf','couverture_arriere'],true);
        echo '<div class="cel-field'.(($long||in_array($key,['audio','resume_audio'],true))?' cel-field-wide':'').'"><label for="cel_f_'.esc_attr($key).'">'.esc_html($label).'</label>';
        if($key==='genre' && $post->post_type==='cel_personnage'){
            $genres=['Masculin','Féminin','Autre','Non précisé'];
            if($value!==''&&!in_array($value,$genres,true))$genres[]=$value;
            echo '<select id="cel_f_genre" name="cel_f[genre]"><option value="">Sélectionner un genre</option>';
            foreach($genres as $genre)echo '<option value="'.esc_attr($genre).'"'.selected($value,$genre,false).'>'.esc_html($genre).'</option>';
            echo '</select>';
        } elseif($key==='type' && $post->post_type==='cel_coloriage'){
            $types=self::activity_types();if($value!==''&&!in_array($value,$types,true))$types[]=$value;
            echo '<div class="cel-activity-type-picker" data-nonce="'.esc_attr(wp_create_nonce('cel_activity_type')).'"><div class="cel-input-row"><select id="cel_f_type" name="cel_f[type]"><option value="">Sélectionner un type</option>';
            foreach($types as $type)echo '<option value="'.esc_attr($type).'"'.selected($value,$type,false).'>'.esc_html($type).'</option>';
            echo '</select><button type="button" class="button cel-activity-type-open" aria-label="Ajouter un type d’activité" aria-expanded="false">+</button></div><div class="cel-activity-type-create" hidden><div class="cel-input-row"><input type="text" class="cel-activity-type-name" maxlength="150" placeholder="Nouveau type d’activité" aria-label="Nouveau type d’activité"><button type="button" class="button cel-activity-type-add">Ajouter</button></div></div><p class="cel-activity-type-status" role="status"></p></div>';
        } elseif($long) {
            echo '<textarea id="cel_f_'.esc_attr($key).'" name="cel_f['.esc_attr($key).']" rows="'.($key==='texte'?12:($key==='synchronisation'?5:4)).'">'.esc_textarea($value).'</textarea>';
            if($key==='synchronisation') echo '<p class="description">Facultatif : une ligne par phrase, sous la forme <code>0.0|Première phrase</code> puis <code>4.5|Deuxième phrase</code>. À ajuster en écoutant le MP3.</p>';
        } else {
            echo '<div class="cel-input-row"><input id="cel_f_'.esc_attr($key).'" name="cel_f['.esc_attr($key).']" type="'.($media?'url':($key==='nombre_pages'?'number':'text')).'" value="'.esc_attr($value).'" '.($media?'class="cel-media-value" placeholder="Choisir un fichier dans la médiathèque"':'').'>';
            if($media) echo '<button type="button" class="button cel-pick-media" data-media-type="'.($key==='pdf'?'application/pdf':(in_array($key,['audio','resume_audio'],true)?'audio':'image')).'">Choisir un fichier</button>';
            echo '</div>';
        }
        if(in_array($key,['audio','resume_audio'],true) && $post->post_type==='cel_histoire'){
            echo '<div class="cel-admin-audio"><strong>'.($key==='resume_audio'?'Écouter le résumé — utilisé sur l’accueil et la fiche publique':'Écouter et vérifier la narration intégrale').'</strong><audio controls preload="metadata" src="'.esc_url($value).'"'.($value?'':' hidden').'></audio><p class="cel-audio-preview-status" role="status">'.($value?'Chargement des informations audio…':'Choisis un MP3 pour afficher le lecteur.').'</p><button type="button" class="button cel-audio-remove"'.($value?'':' hidden').'>Retirer le fichier audio</button></div>';
        }
        echo '</div>';
    }
    static function extras(WP_Post $post): void {
        $items=get_post_meta($post->ID,'_cel_f_extras',true);
        if(!is_array($items)) $items=[];
        $categories=['identite'=>'Identité','physique'=>'Portrait physique','caractere'=>'Personnalité','univers'=>'Autres détails'];
        echo '<section class="cel-form-panel" data-panel="extras" hidden><h3>Particularités personnalisées</h3><p>Tu peux ajouter autant de caractéristiques que tu veux à chaque personnage. Exemple : <em>Tenue</em> → <em>Chemise à carreaux</em>.</p><div id="cel-extra-rows">';
        foreach($items as $item) self::extra_row($item,$categories);
        echo '</div><button type="button" class="button button-secondary" id="cel-add-extra">+ Ajouter une caractéristique</button>';
        echo '<template id="cel-extra-template">';
        self::extra_row(['category'=>'physique','label'=>'','value'=>''],$categories);
        echo '</template></section>';
    }
    static function extra_row(array $item,array $categories): void {
        echo '<div class="cel-extra-row"><select name="cel_extra_category[]">';
        foreach($categories as $key=>$label) echo '<option value="'.esc_attr($key).'"'.selected($item['category']??'',$key,false).'>'.esc_html($label).'</option>';
        echo '</select><input type="text" placeholder="Nom de la caractéristique" aria-label="Caractéristique" name="cel_extra_label[]" value="'.esc_attr($item['label']??'').'">';
        echo '<input type="text" placeholder="Valeur (ex. Chemise à carreaux)" aria-label="Valeur" name="cel_extra_value[]" value="'.esc_attr($item['value']??'').'">';
        echo '<button class="button cel-remove-extra" type="button" aria-label="Supprimer la caractéristique">×</button></div>';
    }

    const KINDS=['parent'=>'Est le parent de','child'=>'Est l’enfant de','spouse'=>'Est marié(e) avec','sibling'=>'Est frère ou sœur de','half'=>'Est demi-frère ou demi-sœur de','friend'=>'Est ami(e) avec','bestfriend'=>'Est meilleur(e) ami(e) avec','neighbor'=>'Est voisin(e) de','classmate'=>'Est camarade de classe de','colleague'=>'Est collègue de','acquaintance'=>'Est une connaissance de'];
    static function family_graph(bool $public=false): array {
        $ids=get_posts(['post_type'=>'cel_personnage','post_status'=>$public?['publish']:['publish','draft','private','pending','future'],'posts_per_page'=>-1,'fields'=>'ids']);
        $valid=array_fill_keys($ids,true);$g=[];$direct=[];
        $inverse=['parent'=>'child','child'=>'parent','spouse'=>'spouse','sibling'=>'sibling','half'=>'half','friend'=>'friend','bestfriend'=>'bestfriend','neighbor'=>'neighbor','classmate'=>'classmate','colleague'=>'colleague','acquaintance'=>'acquaintance','grandparent'=>'grandchild','grandchild'=>'grandparent'];
        $add=function($a,$b,$kind,$explicit=false)use(&$g,&$direct,$valid,$inverse){
            if($a===$b||!isset($valid[$a],$valid[$b],$inverse[$kind]))return false;
            if($explicit){$direct[$a][$kind][$b]=true;$direct[$b][$inverse[$kind]][$a]=true;}
            if(isset($g[$a][$kind][$b]))return false;
            $g[$a][$kind][$b]=true;$g[$b][$inverse[$kind]][$a]=true;return true;
        };
        foreach($ids as $id)foreach((array)get_post_meta($id,'_cel_family_links',true) as $row){
            if(!is_array($row))continue;$kind=$row['kind']??'';if(isset(self::KINDS[$kind]))$add((int)$id,absint($row['id']??0),$kind,true);
        }
        // Convention de Fleuriville : les frères/sœurs (hors demi-fratrie) partagent leurs parents;
        // le conjoint d’un parent est considéré comme coparent, sauf lien contradictoire.
        $reachable=function($from,$to)use(&$g){$seen=[];$todo=[$from];while($todo){$n=array_pop($todo);if($n===$to)return true;if(isset($seen[$n]))continue;$seen[$n]=true;foreach(array_keys($g[$n]['parent']??[]) as $child)$todo[]=$child;}return false;};
        $parent=function($a,$b)use(&$g,$add,$reachable){
            if(isset($g[$a]['spouse'][$b])||isset($g[$a]['sibling'][$b])||isset($g[$a]['half'][$b])||$reachable($b,$a))return false;
            return $add($a,$b,'parent');
        };
        for($pass=0;$pass<count($ids)+1;$pass++){
            $changed=false;
            foreach($ids as $a){
                $parents=array_keys($g[$a]['child']??[]);
                foreach($parents as $p){
                    foreach(array_keys($g[$a]['sibling']??[]) as $sib)$changed=$parent($p,$sib)||$changed;
                    foreach(array_keys($g[$p]['spouse']??[]) as $partner)$changed=$parent($partner,$a)||$changed;
                }
            }
            if(!$changed)break;
        }
        foreach($ids as $p){
            $children=array_keys($g[$p]['parent']??[]);
            foreach($children as $a){
                foreach($children as $b)if($a!==$b&&!isset($g[$a]['half'][$b])&&!isset($g[$a]['spouse'][$b])&&!$reachable($a,$b)&&!$reachable($b,$a)){
                    $pa=array_keys($g[$a]['child']??[]);$pb=array_keys($g[$b]['child']??[]);
                    if(count(array_intersect($pa,$pb))>=2)$add($a,$b,'sibling');
                }
                foreach(array_keys($g[$a]['parent']??[]) as $b)if($p!==$b&&!$reachable($b,$p))$add($p,$b,'grandparent');
            }
        }
        return [$g,$direct];
    }
    static function family_label(string $kind,int $other): string {
        $gender=mb_strtolower(trim(self::value($other,'genre')));
        $f=in_array($gender,['féminin','feminin','femme','fille'],true);$m=in_array($gender,['masculin','homme','garçon','garcon'],true);
        $labels=['parent'=>[$f?'Fille':($m?'Fils':'Enfant')],'child'=>[$f?'Mère':($m?'Père':'Parent')],'spouse'=>[$f?'Épouse':($m?'Époux':'Conjoint(e)')],'sibling'=>[$f?'Sœur':($m?'Frère':'Frère ou sœur')],'half'=>[$f?'Demi-sœur':($m?'Demi-frère':'Demi-frère ou demi-sœur')],'friend'=>[$f?'Amie':($m?'Ami':'Ami(e)')],'bestfriend'=>[$f?'Meilleure amie':($m?'Meilleur ami':'Meilleur(e) ami(e)')],'neighbor'=>[$f?'Voisine':($m?'Voisin':'Voisin(e)')],'classmate'=>['Camarade de classe'],'colleague'=>['Collègue'],'acquaintance'=>['Connaissance'],'grandparent'=>[$f?'Petite-fille':($m?'Petit-fils':'Petit-enfant')],'grandchild'=>[$f?'Grand-mère':($m?'Grand-père':'Grand-parent')]];
        return $labels[$kind][0]??'Proche';
    }
    static function family_panel(WP_Post $post): void {
        $items=get_posts(['post_type'=>'cel_personnage','post_status'=>['publish','draft','private','pending','future'],'posts_per_page'=>-1,'exclude'=>[$post->ID],'orderby'=>'title','order'=>'ASC']);
        $items=array_values(array_filter($items,fn($p)=>current_user_can('edit_post',$p->ID)));
        $rows=get_post_meta($post->ID,'_cel_family_links',true);if(!is_array($rows))$rows=[];
        $manual=[];foreach($rows as $row)if(is_array($row)&&isset(self::KINDS[$row['kind']??'']))$manual[(int)$row['id']][]=$row['kind'];
        [$graph,$direct]=self::family_graph();$known=[];
        foreach($graph[$post->ID]??[] as $kind=>$others)foreach(array_keys($others) as $other)$known[$other][]=$kind;
        $groups=['manual'=>[],'auto'=>[],'none'=>[]];
        foreach($items as $item){
            $id=$item->ID;
            $groups[isset($manual[$id])?'manual':(isset($known[$id])?'auto':'none')][]=$item;
        }
        $labels=['manual'=>['Relations manuelles','Liens que tu as renseignés sur cette fiche.'],'auto'=>['Relations automatiques','Liens réciproques ou déduits à partir des autres fiches. Tu peux aussi préciser une relation sociale.'],'none'=>['Personnages sans relation','Choisis un lien sous le portrait ou laisse « Aucun lien ».']];
        echo '<section class="cel-form-panel" data-panel="family" hidden><h3>Famille et relations</h3><p>Choisis le lien de chaque personnage par rapport à '.esc_html($post->post_title?:'cette fiche').'. Les amitiés ne sont jamais déduites des liens familiaux.</p><input type="hidden" name="cel_family_present" value="1">';
        echo '<style>.cel-family-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,155px),1fr));gap:12px;margin:12px 0 25px}.cel-family-card{min-width:0;background:#fffdfa;border:1px solid #ead5bb;border-radius:12px;padding:9px;display:flex;flex-direction:column;gap:7px}.cel-family-card[data-group=manual]{border-color:#e98932}.cel-family-card[data-group=auto]{background:#fff7eb}.cel-family-photo{width:100%;aspect-ratio:1/1;background:#f4e3cc;border-radius:8px;overflow:hidden;display:flex;align-items:center;justify-content:center}.cel-family-photo img{width:100%;height:100%;object-fit:contain}.cel-family-card a{color:#633619;font-weight:700;line-height:1.3;text-decoration:none}.cel-family-card select{width:100%;max-width:100%;min-width:0;border:1px solid #e4c4a1;border-radius:7px;font-size:12px}.cel-family-tag{color:#89512a;background:#f3e5d2;padding:4px 6px;border-radius:6px;font-size:12px}.cel-family-card .cel-family-add-social{border:0;background:none;color:#a76129;text-align:left;cursor:pointer;padding:3px}.cel-family-heading{margin-top:22px!important}</style>';
        foreach($groups as $group=>$people){
            echo '<h3 class="cel-family-heading">'.esc_html($labels[$group][0]).' ('.count($people).')</h3><p>'.esc_html($labels[$group][1]).'</p>';
            if(!$people){echo '<p class="description">Aucun personnage dans cette catégorie.</p>';continue;}
            echo '<div class="cel-family-grid">';
            foreach($people as $item){
                $id=$item->ID;$local=array_values(array_unique($manual[$id]??[]));$all=array_values(array_unique($known[$id]??[]));
                echo '<article class="cel-family-card" data-group="'.esc_attr($group).'"><div class="cel-family-photo">'.(has_post_thumbnail($item)?get_the_post_thumbnail($item,'medium'):'<span>Portrait à venir</span>').'</div><a href="'.esc_url(get_edit_post_link($id)).'">'.esc_html($item->post_title).'</a>';
                foreach($all as $kind)if(!in_array($kind,$local,true)){
                    echo '<span class="cel-family-tag">'.esc_html(self::family_label($kind,$id)).' · '.(isset($direct[$post->ID][$kind][$id])?'Réciproque':'Déduit').'</span>';
                }
                $select=function($current)use($id,$all,$local){
                    echo '<select name="cel_family_cards['.(int)$id.'][]" aria-label="'.esc_attr('Lien avec '.get_the_title($id)).'"><option value="">Aucun lien manuel</option>';
                    foreach(self::KINDS as $kind=>$label){
                        if(in_array($kind,$all,true)&&!in_array($kind,$local,true)&&$kind!==$current)continue;
                        echo '<option value="'.esc_attr($kind).'"'.selected($current,$kind,false).'>'.esc_html(self::family_label($kind,$id)).'</option>';
                    }
                    echo '</select>';
                };
                echo '<div class="cel-family-selects" style="display:grid;gap:6px">';
                foreach($local?:[''] as $kind)$select($kind);
                echo '</div><template>';$select('');echo '</template><button type="button" class="cel-family-add-social">+ Autre lien</button></article>';
            }
            echo '</div>';
        }
        echo '</section><script>document.addEventListener("click",function(e){const button=e.target.closest(".cel-family-add-social");if(!button)return;const card=button.closest(".cel-family-card");card.querySelector(".cel-family-selects").append(card.querySelector("template").content.cloneNode(true))})</script>';
    }

    static function relations(WP_Post $post): void {
        $types=[
            'cel_personnage'=>['cel_lieu'=>'Lieux associés','cel_livre'=>'Livres associés','cel_histoire'=>'Mini-histoires associées'],
            'cel_livre'=>['cel_coloriage'=>'Activités associées','cel_histoire'=>'Mini-histoires associées','cel_personnage'=>'Personnages du livre','cel_lieu'=>'Lieux du livre'],
            'cel_histoire'=>['cel_livre'=>'Livres associés','cel_coloriage'=>'Activités associées','cel_personnage'=>'Personnages associés','cel_lieu'=>'Lieux associés'],
            'cel_coloriage'=>['cel_livre'=>'Livres associés','cel_histoire'=>'Mini-histoires associées','cel_personnage'=>'Personnages associés'],
            'cel_lieu'=>['cel_personnage'=>'Personnages associés','cel_livre'=>'Livres associés','cel_histoire'=>'Mini-histoires associées']
        ];
        $relations=get_post_meta($post->ID,'_cel_relations',true);
        if(!is_array($relations))$relations=[];
        $allowed=$types[$post->post_type]??[];
        $labels=['cel_personnage'=>'Personnages','cel_lieu'=>'Lieux','cel_livre'=>'Livres','cel_histoire'=>'Mini-histoires','cel_coloriage'=>'Activités'];
        echo '<section class="cel-form-panel" data-panel="relations" hidden><h3>Contenus associés</h3><p>Choisis les contenus liés à cette fiche. Aucun ordre à définir.</p>';
        if($post->post_type==='cel_personnage')echo '<p>Les liens familiaux et amicaux précis se gèrent dans l’onglet « Famille et relations ».</p>';
        ?>
        <style>
        .cel-assoc-layout{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:18px;align-items:start;margin-top:18px}
        .cel-assoc-box{background:#fff8ef;border:1px solid #ead5bb;border-radius:15px;padding:14px;min-width:0}
        .cel-assoc-box h3{margin:0 0 10px;color:#633619}
        .cel-assoc-list{max-height:600px;overflow:auto}
        .cel-assoc-choice,.cel-assoc-selected{display:flex;align-items:center;gap:10px;background:#fff;border:1px solid #ead5bb;border-radius:10px;padding:8px;margin:7px 0;min-width:0}
        .cel-assoc-choice{cursor:pointer}.cel-assoc-choice:has(input:checked){border-color:#e98932;background:#fff3e3}
        .cel-assoc-choice img,.cel-assoc-selected img,.cel-assoc-thumb{width:52px!important;height:52px!important;flex:none;object-fit:contain;border-radius:6px;background:#f4e3cc}
        .cel-assoc-copy{flex:1;min-width:0;overflow-wrap:anywhere;line-height:1.35}.cel-assoc-copy small{display:block;color:#94603c}
        .cel-assoc-filters{display:flex;flex-wrap:wrap;gap:5px;margin:9px 0}.cel-assoc-filter,.cel-assoc-remove{border:1px solid #e4c4a1;color:#77431f;background:#fff7eb;border-radius:7px;padding:5px 9px;cursor:pointer}
        .cel-assoc-filter.active{background:#e98932;color:#fff;border-color:#e98932}.cel-assoc-search{width:100%;border:1px solid #e4c4a1;border-radius:8px}
        .cel-assoc-layout [hidden]{display:none!important}.cel-assoc-choice input{accent-color:#e98932;flex:none}
        @media(max-width:1050px){.cel-assoc-layout{grid-template-columns:1fr}}
        </style>
        <div class="cel-assoc-layout">
          <div class="cel-assoc-box"><h3>Contenus associés <span class="cel-assoc-count"></span></h3><p class="cel-assoc-empty">Aucun contenu sélectionné.</p><div class="cel-assoc-list cel-assoc-selection"></div></div>
          <div class="cel-assoc-box"><h3>Tous les contenus</h3><input type="search" class="cel-assoc-search" placeholder="Rechercher un contenu…" aria-label="Rechercher un contenu">
          <div class="cel-assoc-filters"><button type="button" class="cel-assoc-filter active" data-type="">Tous</button>
          <?php foreach($allowed as $type=>$label): ?><button type="button" class="cel-assoc-filter" data-type="<?php echo esc_attr($type); ?>"><?php echo esc_html($labels[$type]); ?></button><?php endforeach; ?>
          </div><div class="cel-assoc-list cel-assoc-library">
          <?php foreach($allowed as $type=>$label):
            $available=get_posts(['post_type'=>$type,'post_status'=>['publish','draft','future','private'],'posts_per_page'=>-1,'orderby'=>'title','order'=>'ASC','exclude'=>[$post->ID]]);
            foreach($available as $item):
                if(!current_user_can('read_post',$item->ID))continue;
                $selected=in_array($item->ID,array_map('intval',$relations[$type]??[]),true);
          ?>
          <label class="cel-assoc-choice" data-type="<?php echo esc_attr($type); ?>">
          <?php echo has_post_thumbnail($item)?get_the_post_thumbnail($item,'thumbnail'):'<span class="cel-assoc-thumb"></span>'; ?>
          <span class="cel-assoc-copy"><strong><?php echo esc_html($item->post_title?:'Sans titre'); ?></strong><small><?php echo esc_html($labels[$type]); ?></small></span>
          <input type="checkbox" name="cel_relation[<?php echo esc_attr($type); ?>][]" value="<?php echo (int)$item->ID; ?>" <?php checked($selected); ?> aria-label="<?php echo esc_attr('Associer '.$item->post_title); ?>">
          </label>
          <?php endforeach; endforeach; ?>
          <p class="cel-assoc-noresult" hidden>Aucun contenu correspondant.</p></div></div>
        </div>
        <script>
        (function(){
          const root=document.currentScript.previousElementSibling;if(!root||!root.classList.contains('cel-assoc-layout'))return;
          const choices=Array.from(root.querySelectorAll('.cel-assoc-choice')),selection=root.querySelector('.cel-assoc-selection'),search=root.querySelector('.cel-assoc-search');let type='';
          function refresh(){
            selection.replaceChildren();let count=0;
            choices.forEach(choice=>{const input=choice.querySelector('input');if(!input.checked)return;count++;
              const row=document.createElement('div');row.className='cel-assoc-selected';
              const thumb=choice.querySelector('img,.cel-assoc-thumb');if(thumb)row.append(thumb.cloneNode(true));row.append(choice.querySelector('.cel-assoc-copy').cloneNode(true));
              const remove=document.createElement('button');remove.type='button';remove.className='cel-assoc-remove';remove.textContent='×';remove.setAttribute('aria-label','Retirer '+choice.querySelector('strong').textContent);remove.onclick=()=>{input.checked=false;refresh()};row.append(remove);selection.append(row);
            });root.querySelector('.cel-assoc-count').textContent='('+count+')';root.querySelector('.cel-assoc-empty').hidden=count!==0;
          }
          const norm=s=>s.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();
          function filter(){const q=norm(search.value.trim());let count=0;choices.forEach(choice=>{choice.hidden=!!((type&&choice.dataset.type!==type)||!norm(choice.textContent).includes(q));if(!choice.hidden)count++});root.querySelector('.cel-assoc-noresult').hidden=count!==0}
          choices.forEach(choice=>choice.querySelector('input').addEventListener('change',refresh));search.addEventListener('input',filter);
          root.querySelectorAll('.cel-assoc-filter').forEach(btn=>btn.addEventListener('click',()=>{type=btn.dataset.type;root.querySelectorAll('.cel-assoc-filter').forEach(b=>b.classList.toggle('active',b===btn));filter()}));
          search.addEventListener('keydown',e=>{if(e.key==='Enter')e.preventDefault()});refresh();filter();
        })();
        </script>
        </section>
        <?php
    }
        static function save(int $id,WP_Post $post): void {
        if(!in_array($post->post_type,self::TYPES,true)) return;
        if(!isset($_POST['cel_fields_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cel_fields_nonce'])),'cel_fields_save')) return;
        if((defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE)||wp_is_post_revision($id)||!current_user_can('edit_post',$id))return;
        $defs=self::fields_for($post);$submitted=isset($_POST['cel_f'])&&is_array($_POST['cel_f'])?wp_unslash($_POST['cel_f']):[];
        foreach($defs as $group)foreach($group as $fields)foreach($fields as $key=>$label) {
          $v=isset($submitted[$key]) && is_string($submitted[$key]) ? $submitted[$key] : '';
          $v=in_array($key,['audio','resume_audio','pdf','couverture_arriere'],true)?esc_url_raw($v):sanitize_textarea_field($v);
          update_post_meta($id,'_cel_f_'.$key,$v);
          if($key==='pdf'&&$post->post_type==='cel_livre')update_post_meta($id,'_cel_book_pdf',$v);
          if($key==='pdf'&&$post->post_type==='cel_coloriage')update_post_meta($id,'_cel_pdf_url',$v);
          if($key==='audio'&&$post->post_type==='cel_histoire')update_post_meta($id,'_cel_story_mp3',$v);
        }
        // Les images de chaque onglet sont conservées séparément.
        // Seule l'image de l'onglet principal devient l'image mise en avant.
        $images=isset($_POST['cel_section_images'])&&is_array($_POST['cel_section_images'])?wp_unslash($_POST['cel_section_images']):[];
        $primary=$post->post_type==='cel_personnage'?'identite':'principal';
        $image_sections=array_keys($defs);
        if($post->post_type==='cel_coloriage')$image_sections[]='modele_colore';
        foreach($image_sections as $section){
            if(!array_key_exists($section,$images))continue;
            $candidate=absint($images[$section]);
            $valid=$candidate && wp_attachment_is_image($candidate)?$candidate:0;
            $key='_cel_section_image_'.$section;
            $previous=(int)get_post_meta($id,$key,true);
            if($valid)update_post_meta($id,$key,$valid);
            else delete_post_meta($id,$key);
            if($section===$primary){
                if($valid)set_post_thumbnail($id,$valid);
                elseif($previous && (int)get_post_thumbnail_id($id)===$previous)delete_post_thumbnail($id);
            }
        }
        if($post->post_type==='cel_personnage') {
            $cats=isset($_POST['cel_extra_category'])&&is_array($_POST['cel_extra_category'])?wp_unslash($_POST['cel_extra_category']):[];
            $labels=isset($_POST['cel_extra_label'])&&is_array($_POST['cel_extra_label'])?wp_unslash($_POST['cel_extra_label']):[];
            $vals=isset($_POST['cel_extra_value'])&&is_array($_POST['cel_extra_value'])?wp_unslash($_POST['cel_extra_value']):[];
            $extras=[];
            foreach($labels as $i=>$raw_label){
                $label=sanitize_text_field($raw_label);
                if(!$label)continue;
                $extras[]=['category'=>in_array(($cats[$i]??''),['identite','physique','caractere','univers'],true)?$cats[$i]:'univers','label'=>$label,'value'=>sanitize_textarea_field($vals[$i]??'')];
                if(count($extras)>=150)break;
            }
            update_post_meta($id,'_cel_f_extras',$extras);
        }
        if($post->post_type==='cel_personnage'&&isset($_POST['cel_family_present'])){
            $k=(array)wp_unslash($_POST['cel_family_kind']??[]);$others=(array)wp_unslash($_POST['cel_family_id']??[]);$links=[];
            foreach($k as $i=>$kind){if(!is_string($kind)||!isset(self::KINDS[$kind]))continue;$other=absint($others[$i]??0);if(!$other||$other===$id||get_post_type($other)!=='cel_personnage'||!current_user_can('edit_post',$other))continue;$links[$kind.':'.$other]=['kind'=>$kind,'id'=>$other];}
            if(isset($_POST['cel_family_cards'])&&is_array($_POST['cel_family_cards'])){
                foreach(wp_unslash($_POST['cel_family_cards']) as $other=>$kinds){
                    $other=absint($other);if(!$other||$other===$id||get_post_type($other)!=='cel_personnage'||!current_user_can('edit_post',$other)||!is_array($kinds))continue;
                    foreach($kinds as $kind)if(is_string($kind)&&isset(self::KINDS[$kind]))$links[$kind.':'.$other]=['kind'=>$kind,'id'=>$other];
                }
                // Conserver les relations vers les fiches que cet utilisateur ne peut pas modifier.
                foreach((array)get_post_meta($id,'_cel_family_links',true) as $old){
                    if(!is_array($old))continue;$other=absint($old['id']??0);$kind=$old['kind']??'';
                    if($other&&get_post_type($other)==='cel_personnage'&&!current_user_can('edit_post',$other)&&isset(self::KINDS[$kind]))$links[$kind.':'.$other]=$old;
                }
            }
            update_post_meta($id,'_cel_family_links',array_values($links));
        }
        $r=isset($_POST['cel_relation'])&&is_array($_POST['cel_relation'])?wp_unslash($_POST['cel_relation']):[];
        $out=[];
        foreach(['cel_personnage','cel_lieu','cel_livre','cel_histoire','cel_coloriage'] as $type){
            $list=isset($r[$type])&&is_array($r[$type])?$r[$type]:[];
            $ids=array_slice(array_unique(array_filter(array_map('absint',$list))),0,100);
            $out[$type]=array_values(array_filter($ids,fn($related)=>$related!==$id && get_post_type($related)===$type));
        }
        if($post->post_type==='cel_personnage'){
            $previous=get_post_meta($id,'_cel_relations',true);
            $out['cel_personnage']=is_array($previous)?($previous['cel_personnage']??[]):[];
        }
        update_post_meta($id,'_cel_relations',$out);
    }
    static function value(int $id,string $key): string {
        if(get_post_type($id)==='cel_livre' && isset(Celestin_Library::TAX[$key]))return Celestin_Library::display($id,$key);
        $value=(string)get_post_meta($id,'_cel_f_'.$key,true);
        if(!$value && $key==='audio' && get_post_type($id)==='cel_histoire')$value=(string)get_post_meta($id,'_cel_story_mp3',true);
        if(!$value && $key==='pdf' && get_post_type($id)==='cel_livre')$value=(string)get_post_meta($id,'_cel_book_pdf',true);
        return $value;
    }
    static function linked(int $id,string $type): array {
        $result=[];$rel=get_post_meta($id,'_cel_relations',true);
        foreach((array)($rel[$type]??[]) as $rid) {
            $p=get_post((int)$rid);
            if($p && $p->post_status==='publish' && $p->post_type===$type)$result[$p->ID]=$p;
        }
        // Faire apparaître également les liens enregistrés depuis l'autre fiche.
        $candidates=get_posts(['post_type'=>$type,'post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids']);
        $origin=get_post_type($id);
        foreach($candidates as $candidate){
            $back=get_post_meta($candidate,'_cel_relations',true);
            if(is_array($back)&&in_array($id,array_map('intval',(array)($back[$origin]??[])),true))$result[$candidate]=get_post($candidate);
        }
        return array_values($result);
    }
}
Celestin_Fields::init();
