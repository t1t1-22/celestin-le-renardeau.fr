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
        'principal' => ['Identité du livre'=>['resume'=>'Résumé','numero'=>'Numéro du livre (facultatif)','nombre_pages'=>'Nombre de pages','points_livre'=>'Dans ce livre… (un élément par ligne)']],
        'fichier' => ['Les pages'=>['pdf'=>'PDF complet du livre carré']]
      ],
      'cel_histoire' => [
        'principal'=>['Identité de la mini-histoire'=>['resume'=>'Résumé']],
        'audio'=>['Audio et texte'=>['audio'=>'Narration MP3','texte'=>'Texte intégral','synchronisation'=>'Repères de synchronisation (facultatif)']]
      ],
      'cel_coloriage' => [
        'principal'=>['Activité'=>['resume'=>'Présentation','type'=>'Type d’activité (coloriage, jeu, découpage…)','pdf'=>'Fichier PDF à imprimer']]
      ],
      'cel_lieu' => [
        'principal'=>['Lieu de Fleuriville'=>['resume'=>'Description du lieu','histoire'=>'Son histoire','adresse'=>'Emplacement ou repère sur la carte']]
      ]
    ];

    static function init(): void {
        add_action('init',[__CLASS__,'remove_editor'],30);
        add_filter('wp_insert_post_data',[__CLASS__,'automatic_character_title'],20,2);
        add_action('add_meta_boxes',[__CLASS__,'boxes'],30);
        add_action('save_post',[__CLASS__,'save'],20,2);
        add_action('admin_enqueue_scripts',[__CLASS__,'admin_assets']);
        add_filter('enter_title_here',[__CLASS__,'title_placeholder'],10,2);
        add_filter('use_block_editor_for_post_type',[__CLASS__,'disable_blocks'],10,2);
    }
    // Le nom affiché sur le site vient directement des champs Prénom + Nom.
    // Le filtre intervient avant l'insertion pour éviter un titre manquant au premier enregistrement.
    static function automatic_character_title(array $data,array $postarr): array {
        if(in_array($data['post_type']??'',['cel_livre','cel_histoire'],true)){
            if(isset($_POST['cel_fields_nonce'],$_POST['cel_book_title']) && is_string($_POST['cel_fields_nonce']) && is_string($_POST['cel_book_title']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cel_fields_nonce'])),'cel_fields_save')){
                $data['post_title']=wp_slash(sanitize_text_field(wp_unslash($_POST['cel_book_title'])));
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
            wp_enqueue_script('cel-pdf-preview',plugins_url('../assets/pdf-preview.js',__FILE__),['jquery','cel-admin-pdfjs'],'0.4.25',true);
        }
        wp_enqueue_style('cel-fields',plugins_url('../assets/fields.css',__FILE__),[],'0.4.25');
        wp_enqueue_script('cel-fields',plugins_url('../assets/fields.js',__FILE__),['jquery'],'0.4.25',true);
        if($screen->post_type==='cel_personnage')wp_add_inline_style('cel-fields','#post-body-content #titlediv{display:none!important}');
    }
    static function render(WP_Post $post): void {
        wp_nonce_field('cel_fields_save','cel_fields_nonce');
        $groups=self::fields_for($post);
        if(in_array($post->post_type,['cel_livre','cel_histoire'],true))echo '<div class="cel-editor-heading"><span>Bibliothèque Célestin</span><h2>'.($post->post_type==='cel_livre'?'Fiche du livre':'Fiche de la mini-histoire audio').'</h2></div>';
        echo '<div class="cel-form-head"><p>'.($post->post_type==='cel_personnage'?'Le titre du personnage est généré automatiquement avec le prénom et le nom. ':'').'Complète les champs ci-dessous : la page du site sera construite automatiquement. Les champs vides ne seront pas affichés.</p></div>';
        echo '<div class="cel-form-tabs" role="tablist" aria-label="Sections de la fiche">';
        $i=0;
        foreach($groups as $slug=>$group) {
          echo '<button type="button" role="tab" data-tab="'.esc_attr($slug).'" class="cel-form-tab'.(!$i?' active':'').'" aria-selected="'.(!$i?'true':'false').'">'.esc_html(array_key_first($group)).'</button>';
          if($slug==='principal' && in_array($post->post_type,['cel_livre','cel_histoire','cel_coloriage'],true))echo '<button type="button" role="tab" data-tab="categories" class="cel-form-tab" aria-selected="false">Catégories</button>';
          $i++;
        }
        if($post->post_type==='cel_personnage') echo '<button type="button" role="tab" data-tab="extras" class="cel-form-tab" aria-selected="false">Attributs personnalisés</button>';
        echo '<button type="button" role="tab" data-tab="relations" class="cel-form-tab" aria-selected="false">'.($post->post_type==='cel_livre'?'Contenus associés':'Associations').'</button>';
        echo '</div>';
        if(in_array($post->post_type,['cel_livre','cel_histoire'],true)){
            echo '<div class="cel-book-savebar"><span class="cel-book-save-label">'.esc_html($post->post_status==='publish'?'Contenu publié':'Contenu en préparation').'</span><button type="button" class="cel-book-preview-action">Prévisualiser</button><button type="button" class="cel-book-save-action">'.esc_html($post->post_status==='publish'?'Enregistrer les modifications':'Enregistrer le brouillon').'</button>';
            if($post->post_status!=='publish')echo '<button type="button" class="cel-book-publish-action">Publier</button>';
            echo '</div>';
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
            foreach($fields as $key=>$label) self::field($post,$key,$label);
            echo '</div>';
            if($post->post_type==='cel_livre' && $slug==='principal') Celestin_Library::identity($post);
            if($post->post_type==='cel_livre' && $slug==='fichier'){
                echo '<div id="cel-pdf-preview"><h3>Aperçu des pages du livre</h3><p class="cel-pdf-status" aria-live="polite">Choisis le PDF pour afficher ses pages.</p><div class="cel-admin-reader" hidden><button type="button" class="button cel-admin-prev">←</button><div class="cel-admin-spread"><canvas></canvas><canvas></canvas></div><button type="button" class="button cel-admin-next">→</button><p class="cel-admin-counter"></p></div><div class="cel-pdf-grid"></div></div>';
            } elseif(!in_array($post->post_type,['cel_livre','cel_histoire'],true)) self::section_image($post,$slug,$title);
          }
          echo '</section>';
          $i++;
        }
        if($post->post_type==='cel_personnage') self::extras($post);
        if(in_array($post->post_type,['cel_livre','cel_histoire','cel_coloriage'],true))Celestin_Library::categories($post);
        self::relations($post);
    }
    // Image dédiée à chaque onglet, sans avoir à manipuler les réglages WordPress.
    // La photo d'identité / image principale est aussi la vignette automatique.
    static function section_image(WP_Post $post,string $section,string $title): void {
        $primary=$post->post_type==='cel_personnage' ? 'identite' : 'principal';
        $id=absint(get_post_meta($post->ID,'_cel_section_image_'.$section,true));
        if(!$id && $section===$primary)$id=(int)get_post_thumbnail_id($post);
        $url=$id?wp_get_attachment_image_url($id,'medium'):'';
        echo '<div class="cel-section-image"><h4>'.esc_html($section===$primary?($post->post_type==='cel_livre'?'Première de couverture':'Illustration principale'):'Illustration de cette rubrique').'</h4>';
        echo '<p class="description">'.($section===$primary?'Cette image sera aussi utilisée comme vignette sur le site et dans les nouveautés.':'Illustration facultative propre à « '.esc_html($title).' ».').'</p>';
        echo '<input type="hidden" name="cel_section_images['.esc_attr($section).']" value="'.esc_attr((string)$id).'" class="cel-section-image-id">';
        echo '<img class="cel-section-image-preview" src="'.esc_url($url?:'').'" alt="Aperçu de l’illustration" style="display:'.($url?'block':'none').'">';
        echo '<div class="cel-section-image-actions"><button type="button" class="button cel-select-section-image">Choisir une image</button> <button type="button" class="button cel-remove-section-image">Retirer</button></div></div>';
    }
    static function field(WP_Post $post,string $key,string $label): void {
        $value=(string)get_post_meta($post->ID,'_cel_f_'.$key,true);
        // Continuité avec les fiches de la version précédente.
        if(!$value && $key==='pdf' && $post->post_type==='cel_livre') $value=(string)get_post_meta($post->ID,'_cel_book_pdf',true);
        if(!$value && $key==='pdf' && $post->post_type==='cel_coloriage') $value=(string)get_post_meta($post->ID,'_cel_pdf_url',true);
        if(!$value && $key==='audio' && $post->post_type==='cel_histoire') $value=(string)get_post_meta($post->ID,'_cel_story_mp3',true);
        $long=in_array($key,['points_livre','description','anecdote','resume','texte','histoire','synchronisation','qualites','defauts','aime','deteste','peurs','reves'],true);
        $media=in_array($key,['audio','pdf','couverture_arriere'],true);
        echo '<div class="cel-field'.(($long||$key==='audio')?' cel-field-wide':'').'"><label for="cel_f_'.esc_attr($key).'">'.esc_html($label).'</label>';
        if($long) {
            echo '<textarea id="cel_f_'.esc_attr($key).'" name="cel_f['.esc_attr($key).']" rows="'.($key==='texte'?12:($key==='synchronisation'?5:4)).'">'.esc_textarea($value).'</textarea>';
            if($key==='synchronisation') echo '<p class="description">Facultatif : une ligne par phrase, sous la forme <code>0.0|Première phrase</code> puis <code>4.5|Deuxième phrase</code>. À ajuster en écoutant le MP3.</p>';
        } else {
            echo '<div class="cel-input-row"><input id="cel_f_'.esc_attr($key).'" name="cel_f['.esc_attr($key).']" type="'.($media?'url':($key==='nombre_pages'?'number':'text')).'" value="'.esc_attr($value).'" '.($media?'class="cel-media-value" placeholder="Choisir un fichier dans la médiathèque"':'').'>';
            if($media) echo '<button type="button" class="button cel-pick-media" data-media-type="'.($key==='pdf'?'application/pdf':($key==='audio'?'audio':'image')).'">Choisir un fichier</button>';
            echo '</div>';
        }
        if($key==='audio' && $post->post_type==='cel_histoire'){
            echo '<div class="cel-admin-audio"><strong>Écouter et vérifier la narration</strong><audio controls preload="metadata" src="'.esc_url($value).'"'.($value?'':' hidden').'></audio><p class="cel-audio-preview-status" role="status">'.($value?'Chargement des informations audio…':'Choisis un MP3 pour afficher le lecteur.').'</p><button type="button" class="button cel-audio-remove"'.($value?'':' hidden').'>Retirer le fichier audio</button></div>';
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
    static function relations(WP_Post $post): void {
        $types=[
            'cel_personnage'=>['cel_personnage'=>'Famille, amis et connaissances','cel_lieu'=>'Lieux associés','cel_livre'=>'Livres associés','cel_histoire'=>'Mini-histoires associées'],
            'cel_livre'=>['cel_coloriage'=>'Activités associées','cel_histoire'=>'Mini-histoires associées','cel_personnage'=>'Personnages du livre','cel_lieu'=>'Lieux du livre'],
            'cel_histoire'=>['cel_livre'=>'Livres associés','cel_personnage'=>'Personnages associés','cel_lieu'=>'Lieux associés'],
            'cel_coloriage'=>['cel_livre'=>'Livres associés','cel_personnage'=>'Personnages associés'],
            'cel_lieu'=>['cel_personnage'=>'Personnages associés','cel_livre'=>'Livres associés','cel_histoire'=>'Mini-histoires associées']
        ];
        $relations=get_post_meta($post->ID,'_cel_relations',true);
        if(!is_array($relations))$relations=[];
        echo '<section class="cel-form-panel" data-panel="relations" hidden><h3>Relier cette fiche à d’autres contenus</h3><p>Sélectionne les fiches correspondantes. Les liens seront visibles sur le site, sans recopier les informations.</p><div class="cel-relations-grid">';
        foreach(($types[$post->post_type]??[]) as $type=>$label) {
           $available=get_posts(['post_type'=>$type,'post_status'=>['publish','draft','future','private'],'posts_per_page'=>-1,'orderby'=>'title','order'=>'ASC','exclude'=>[$post->ID]]);
           if(in_array($post->post_type,['cel_livre','cel_histoire'],true)){
              $groups=[];
              foreach($available as $item){
                $group='Autres';
                if($type==='cel_coloriage')$group=trim((string)get_post_meta($item->ID,'_cel_f_type',true))?:'Non classés';
                if($type==='cel_histoire'){$terms=wp_get_object_terms($item->ID,Celestin_Library::TAX['categorie'],['fields'=>'names']);$group=!is_wp_error($terms)&&$terms?implode(' / ',$terms):'Sans thème';}
                $groups[$group][]=$item;
              }
              ksort($groups,SORT_NATURAL|SORT_FLAG_CASE);
              echo '<div class="cel-relation-field cel-library-related"><h3>'.esc_html($label).'</h3>';
              if(!$groups)echo '<p>Aucun contenu disponible.</p>';
              foreach($groups as $group=>$items){echo '<h4>'.esc_html($group).'</h4><div class="cel-lib-related-grid">';
                foreach($items as $item){echo '<label><input type="checkbox" name="cel_relation['.esc_attr($type).'][]" value="'.(int)$item->ID.'"'.checked(in_array($item->ID,array_map('intval',$relations[$type]??[]),true),true,false).'>'.get_the_post_thumbnail($item->ID,'thumbnail').'<span>'.esc_html($item->post_title).'</span></label>';}
                echo '</div>';
              }
              echo '</div>';continue;
           }
           echo '<div class="cel-relation-field"><label>'.esc_html($label).'</label><select multiple size="6" name="cel_relation['.esc_attr($type).'][]" aria-label="'.esc_attr($label).'">';
           foreach($available as $item) echo '<option value="'.absint($item->ID).'"'.selected(in_array($item->ID,array_map('intval',$relations[$type]??[]),true),true,false).'>'.esc_html($item->post_title?:'Sans titre').'</option>';
           echo '</select><p class="description">Maintiens Cmd (Mac) ou Ctrl pour sélectionner plusieurs éléments.</p></div>';
        }
        echo '</div></section>';
    }
    static function save(int $id,WP_Post $post): void {
        if(!in_array($post->post_type,self::TYPES,true)) return;
        if(!isset($_POST['cel_fields_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cel_fields_nonce'])),'cel_fields_save')) return;
        if((defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE)||wp_is_post_revision($id)||!current_user_can('edit_post',$id))return;
        $defs=self::fields_for($post);$submitted=isset($_POST['cel_f'])&&is_array($_POST['cel_f'])?wp_unslash($_POST['cel_f']):[];
        foreach($defs as $group)foreach($group as $fields)foreach($fields as $key=>$label) {
          $v=isset($submitted[$key]) && is_string($submitted[$key]) ? $submitted[$key] : '';
          $v=in_array($key,['audio','pdf','couverture_arriere'],true)?esc_url_raw($v):sanitize_textarea_field($v);
          update_post_meta($id,'_cel_f_'.$key,$v);
          if($key==='pdf'&&$post->post_type==='cel_livre')update_post_meta($id,'_cel_book_pdf',$v);
          if($key==='pdf'&&$post->post_type==='cel_coloriage')update_post_meta($id,'_cel_pdf_url',$v);
          if($key==='audio'&&$post->post_type==='cel_histoire')update_post_meta($id,'_cel_story_mp3',$v);
        }
        // Les images de chaque onglet sont conservées séparément.
        // Seule l'image de l'onglet principal devient l'image mise en avant.
        $images=isset($_POST['cel_section_images'])&&is_array($_POST['cel_section_images'])?wp_unslash($_POST['cel_section_images']):[];
        $primary=$post->post_type==='cel_personnage'?'identite':'principal';
        foreach(array_keys($defs) as $section){
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
        $r=isset($_POST['cel_relation'])&&is_array($_POST['cel_relation'])?wp_unslash($_POST['cel_relation']):[];
        $out=[];
        foreach(['cel_personnage','cel_lieu','cel_livre','cel_histoire','cel_coloriage'] as $type){
            $list=isset($r[$type])&&is_array($r[$type])?$r[$type]:[];
            $ids=array_slice(array_unique(array_filter(array_map('absint',$list))),0,100);
            $out[$type]=array_values(array_filter($ids,fn($related)=>$related!==$id && get_post_type($related)===$type));
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
