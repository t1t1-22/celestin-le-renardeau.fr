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
        'principal' => ['Informations du livre'=>['resume'=>'Résumé','collection'=>'Collection / édition','mois'=>'Mois de parution','saison'=>'Saison','couverture_arriere'=>'Quatrième de couverture (URL image)']],
        'fichier' => ['Lecture'=>['pdf'=>'PDF complet du livre carré']]
      ],
      'cel_histoire' => [
        'principal'=>['Mini-histoire (environ 5 minutes)'=>['resume'=>'Résumé','texte'=>'Texte intégral','audio'=>'Narration MP3','synchronisation'=>'Repères de synchronisation (facultatif)']]
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
        add_action('add_meta_boxes',[__CLASS__,'boxes'],30);
        add_action('save_post',[__CLASS__,'save'],20,2);
        add_action('admin_enqueue_scripts',[__CLASS__,'admin_assets']);
        add_filter('enter_title_here',[__CLASS__,'title_placeholder'],10,2);
        add_filter('use_block_editor_for_post_type',[__CLASS__,'disable_blocks'],10,2);
    }
    static function remove_editor(): void {
        foreach(self::TYPES as $type) remove_post_type_support($type,'editor');
    }
    static function disable_blocks($use,$type) {
        return in_array($type,self::TYPES,true)?false:$use;
    }
    static function title_placeholder($placeholder,$post): string {
        return in_array($post->post_type,self::TYPES,true)?'Titre de la fiche (affiché sur le site)':$placeholder;
    }
    static function boxes(): void {
        foreach(self::TYPES as $type) {
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
        wp_enqueue_style('cel-fields',plugins_url('../assets/fields.css',__FILE__),[],'0.3.0');
        wp_enqueue_script('cel-fields',plugins_url('../assets/fields.js',__FILE__),['jquery'],'0.3.0',true);
    }
    static function render(WP_Post $post): void {
        wp_nonce_field('cel_fields_save','cel_fields_nonce');
        $groups=self::fields_for($post);
        echo '<div class="cel-form-head"><p>Complète les champs ci-dessous : la page du site sera construite automatiquement. Les champs vides ne seront pas affichés.</p></div>';
        echo '<div class="cel-form-tabs" role="tablist" aria-label="Sections de la fiche">';
        $i=0;
        foreach($groups as $slug=>$group) {
          echo '<button type="button" role="tab" data-tab="'.esc_attr($slug).'" class="cel-form-tab'.(!$i?' active':'').'" aria-selected="'.(!$i?'true':'false').'">'.esc_html(array_key_first($group)).'</button>';
          $i++;
        }
        if($post->post_type==='cel_personnage') echo '<button type="button" role="tab" data-tab="extras" class="cel-form-tab" aria-selected="false">Attributs personnalisés</button>';
        echo '<button type="button" role="tab" data-tab="relations" class="cel-form-tab" aria-selected="false">Associations</button>';
        echo '</div>';

        $i=0;
        foreach($groups as $slug=>$group) {
          echo '<section class="cel-form-panel" data-panel="'.esc_attr($slug).'"'.($i?' hidden':'').'>';
          foreach($group as $title=>$fields) {
            echo '<h3>'.esc_html($title).'</h3><div class="cel-field-grid">';
            foreach($fields as $key=>$label) self::field($post,$key,$label);
            echo '</div>';
          }
          echo '</section>';
          $i++;
        }
        if($post->post_type==='cel_personnage') self::extras($post);
        self::relations($post);
    }
    static function field(WP_Post $post,string $key,string $label): void {
        $value=(string)get_post_meta($post->ID,'_cel_f_'.$key,true);
        // Continuité avec les fiches de la version précédente.
        if(!$value && $key==='pdf' && $post->post_type==='cel_livre') $value=(string)get_post_meta($post->ID,'_cel_book_pdf',true);
        if(!$value && $key==='pdf' && $post->post_type==='cel_coloriage') $value=(string)get_post_meta($post->ID,'_cel_pdf_url',true);
        if(!$value && $key==='audio' && $post->post_type==='cel_histoire') $value=(string)get_post_meta($post->ID,'_cel_story_mp3',true);
        $long=in_array($key,['description','anecdote','resume','texte','histoire','synchronisation','qualites','defauts','aime','deteste','peurs','reves'],true);
        $media=in_array($key,['audio','pdf','couverture_arriere'],true);
        echo '<div class="cel-field'.($long?' cel-field-wide':'').'"><label for="cel_f_'.esc_attr($key).'">'.esc_html($label).'</label>';
        if($long) {
            echo '<textarea id="cel_f_'.esc_attr($key).'" name="cel_f['.esc_attr($key).']" rows="'.($key==='texte'?12:($key==='synchronisation'?5:4)).'">'.esc_textarea($value).'</textarea>';
            if($key==='synchronisation') echo '<p class="description">Facultatif : une ligne par phrase, sous la forme <code>0.0|Première phrase</code> puis <code>4.5|Deuxième phrase</code>. À ajuster en écoutant le MP3.</p>';
        } else {
            echo '<div class="cel-input-row"><input id="cel_f_'.esc_attr($key).'" name="cel_f['.esc_attr($key).']" type="'.($media?'url':'text').'" value="'.esc_attr($value).'" '.($media?'class="cel-media-value" placeholder="Choisir un fichier dans la médiathèque"':'').'>';
            if($media) echo '<button type="button" class="button cel-pick-media" data-media-type="'.($key==='pdf'?'application/pdf':($key==='audio'?'audio':'image')).'">Choisir un fichier</button>';
            echo '</div>';
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
