<?php
/**
 * Templates publics automatiques des contenus Célestin.
 * Page de détail et liste : aucune page Gutenberg à créer manuellement.
 */
if(!defined('ABSPATH'))exit;

final class Celestin_Public {
    const TYPES=['cel_personnage','cel_livre','cel_histoire','cel_coloriage','cel_lieu'];
    static function init(): void {
        add_filter('template_include',[__CLASS__,'template'],90);
        add_action('wp_enqueue_scripts',[__CLASS__,'assets']);
    }
    static function template(string $default): string {
        if(is_singular(self::TYPES))return __DIR__.'/single-content.php';
        if(is_post_type_archive(self::TYPES))return __DIR__.'/archive-content.php';
        return $default;
    }
    static function assets(): void {
        if(!is_singular(self::TYPES) && !is_post_type_archive(self::TYPES))return;
        wp_enqueue_style('cel-content',plugins_url('../assets/content.css',__FILE__),[],'0.3.0');
        if(is_singular('cel_livre')){
            wp_enqueue_script('cel-pdfjs','https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js',[],'3.11.174',true);
            wp_enqueue_script('cel-reader',plugins_url('../assets/reader.js',__FILE__),['cel-pdfjs'],'0.3.0',true);
        }
        if(is_singular('cel_histoire'))wp_enqueue_script('cel-story',plugins_url('../assets/story.js',__FILE__),[],'0.3.0',true);
    }
    static function title(string $type): string {
        return ['cel_personnage'=>'Les personnages','cel_livre'=>'Les livres','cel_histoire'=>'Mini-histoires audio','cel_coloriage'=>'Mes petites activités','cel_lieu'=>'Les lieux de Fleuriville'][$type]??'Découvertes';
    }
    static function illustration(int $id,string $size='large'): string {
        if(has_post_thumbnail($id))return get_the_post_thumbnail($id,$size,['loading'=>'lazy']);
        return '<div class="cel-content-placeholder" aria-label="Illustration bientôt disponible">✦<small>Illustration à découvrir bientôt</small></div>';
    }
    static function section_illustration(int $id,string $section): string {
        $image_id=absint(get_post_meta($id,'_cel_section_image_'.$section,true));
        if(!$image_id || !wp_attachment_is_image($image_id))return '';
        return '<figure class="cel-section-illustration">'.wp_get_attachment_image($image_id,'large',false,['loading'=>'lazy']).'</figure>';
    }
    static function fact(int $id,string $key,string $label): string {
        $val=Celestin_Fields::value($id,$key);
        return $val!==''?'<div class="cel-fact"><dt>'.esc_html($label).'</dt><dd>'.nl2br(esc_html($val)).'</dd></div>':'';
    }
    static function facts(int $id,array $keys): string {
        $out='';
        foreach($keys as $key=>$label)$out.=self::fact($id,$key,$label);
        return $out?'<dl class="cel-facts">'.$out.'</dl>':'';
    }
    static function related(int $id,string $type,string $title): string {
        $items=Celestin_Fields::linked($id,$type);
        if(!$items)return '';
        $out='<section class="cel-related"><h2>'.esc_html($title).'</h2><div class="cel-related-list">';
        foreach($items as $post){
            $out.='<a href="'.esc_url(get_permalink($post)).'">'.self::illustration($post->ID,'thumbnail').'<span>'.esc_html($post->post_title).'</span></a>';
        }
        return $out.'</div></section>';
    }
    static function character(int $id): void {
        echo '<div class="cel-id-card"><div class="cel-id-photo">'.self::illustration($id).'</div><div class="cel-id-main"><p class="cel-kicker">Carte d’identité · Fleuriville</p><h2>'.esc_html(get_the_title($id)).'</h2>';
        echo self::facts($id,['prenom'=>'Prénom','nom'=>'Nom','surnom'=>'Surnom','genre'=>'Genre','age'=>'Âge','espece'=>'Espèce','role'=>'Rôle','anniversaire'=>'Anniversaire']);
        echo '</div></div>';
        $groups=[
            'Portrait physique'=>['taille'=>'Taille','pelage'=>'Pelage','yeux'=>'Yeux','vetements'=>'Tenue habituelle','particularite'=>'Particularités'],
            'Personnalité'=>['qualites'=>'Qualités','defauts'=>'Petits défauts','aime'=>'Ce que j’adore','deteste'=>'Ce que je n’aime pas','peurs'=>'Mes peurs','reves'=>'Mes rêves','phrase'=>'Phrase préférée'],
            'Pour mieux me connaître'=>['description'=>'Ma présentation','anecdote'=>'Une anecdote']
        ];
        $extras=get_post_meta($id,'_cel_f_extras',true);
        $extra_sections=['identite'=>'Identité','physique'=>'Portrait physique','caractere'=>'Personnalité','univers'=>'Pour mieux me connaître'];
        if(!is_array($extras))$extras=[];
        foreach($groups as $section=>$fields) {
            $rows=self::facts($id,$fields);
            $tag=array_search($section,$extra_sections,true);
            $additional='';
            foreach($extras as $extra)if(($extra['category']??'')===$tag && !empty($extra['label'])){
                $additional.='<div class="cel-fact"><dt>'.esc_html($extra['label']).'</dt><dd>'.nl2br(esc_html($extra['value']??'')).'</dd></div>';
            }
            $section_images=['Portrait physique'=>'physique','Personnalité'=>'caractere','Pour mieux me connaître'=>'univers'];
            $illustration=self::section_illustration($id,$section_images[$section]??'');
            if($rows||$additional||$illustration)echo '<section class="cel-detail-section"><h2>'.esc_html($section).'</h2>'.$illustration.($rows?:'').($additional?'<dl class="cel-facts">'.$additional.'</dl>':'').'</section>';
        }
        $extra_identite='';
        foreach($extras as $extra)if(($extra['category']??'')==='identite'&&!empty($extra['label'])){
            $extra_identite.='<div class="cel-fact"><dt>'.esc_html($extra['label']).'</dt><dd>'.nl2br(esc_html($extra['value']??'')).'</dd></div>';
        }
        if($extra_identite)echo '<section class="cel-detail-section"><h2>Autres informations</h2><dl class="cel-facts">'.$extra_identite.'</dl></section>';
        $audio=Celestin_Fields::value($id,'audio');
        if($audio)echo '<section class="cel-detail-section"><h2>Écoute-moi me présenter !</h2><audio controls preload="metadata" src="'.esc_url($audio).'"></audio></section>';
        echo self::related($id,'cel_personnage','Ma famille et mes amis');
        echo self::related($id,'cel_lieu','Mes lieux préférés');
        echo self::related($id,'cel_livre','Les livres où je suis présent');
        echo self::related($id,'cel_histoire','Mes mini-histoires');
    }
    static function book(int $id): void {
        $pdf=Celestin_Fields::value($id,'pdf');
        echo '<div class="cel-detail-two"><div class="cel-cover">'.self::illustration($id).'</div><div class="cel-summary">';
        echo '<h2>À propos de ce livre</h2><p>'.nl2br(esc_html(Celestin_Fields::value($id,'resume'))).'</p>';
        echo self::facts($id,['collection'=>'Collection','mois'=>'Mois','saison'=>'Saison']);
        $back=Celestin_Fields::value($id,'couverture_arriere');
        if($back) echo '<p><a class="cel-action-link" href="'.esc_url($back).'" target="_blank" rel="noopener">Voir la quatrième de couverture ↗</a></p>';
        echo '</div></div>';
        if($pdf){
            echo '<section class="cel-detail-section"><h2>Feuilleter le livre</h2><p>Tourne les pages comme dans un vrai livre. Les pages conservent leur format carré.</p>';
            echo '<div class="cel-book-reader" data-pdf="'.esc_url($pdf).'"><div class="cel-book-stage" aria-live="polite"><div class="cel-book-sheet cel-left-sheet"><canvas aria-label="Page de gauche"></canvas></div><div class="cel-book-sheet cel-right-sheet"><canvas aria-label="Page de droite"></canvas></div></div>';
            echo '<div class="cel-reader-controls"><button type="button" class="cel-reader-prev">← Page précédente</button><span class="cel-reader-counter">Chargement du livre…</span><button type="button" class="cel-reader-next">Page suivante →</button></div>';
            echo '<p class="cel-reader-error" hidden></p><p><a class="cel-reader-fallback" href="'.esc_url($pdf).'" target="_blank" rel="noopener">Ouvrir le PDF si la visionneuse ne fonctionne pas ↗</a></p></div></section>';
        } else echo '<p class="cel-empty-info">Le livre sera bientôt disponible à la lecture.</p>';
        echo self::related($id,'cel_coloriage','Les activités de ce livre');
        echo self::related($id,'cel_histoire','Les mini-histoires associées');
        echo self::related($id,'cel_personnage','Les personnages de ce livre');
    }
    static function story(int $id): void {
        $audio=Celestin_Fields::value($id,'audio');
        $texte=Celestin_Fields::value($id,'texte');
        $sync=Celestin_Fields::value($id,'synchronisation');
        echo '<div class="cel-story-layout"><div class="cel-story-visual">'.self::illustration($id).'</div><div class="cel-story-main">';
        $resume=Celestin_Fields::value($id,'resume');
        if($resume)echo '<p class="cel-story-resume">'.nl2br(esc_html($resume)).'</p>';
        if($audio){
            echo '<div class="cel-listen"><h2>Écouter l’histoire</h2><audio controls preload="metadata" data-cel-story-audio src="'.esc_url($audio).'"></audio><p>Tu peux mettre en pause ou avancer dans l’histoire.</p></div>';
        }else echo '<p class="cel-empty-info">La version audio sera bientôt disponible.</p>';
        echo '<div class="cel-story-reading"><h2>Lire l’histoire</h2><p>Tu peux lire tranquillement, même sans lancer l’audio.</p>';
        if($texte){
            $lines=preg_split('/(?<=[.!?…])\s+|\R+/u',trim($texte),-1,PREG_SPLIT_NO_EMPTY);
            $timings=[];
            foreach(preg_split('/\R/u',$sync) as $line)if(preg_match('/^\s*(\d+(?:[.,]\d+)?)\s*\|(.*)$/u',trim($line),$m))$timings[]=floatval(str_replace(',','.',$m[1]));
            echo '<div class="cel-story-transcript" data-cel-story-transcript aria-label="Texte de la mini-histoire">';
            foreach($lines as $idx=>$line){
               $time=isset($timings[$idx])?' data-start="'.esc_attr((string)$timings[$idx]).'"':'';
               echo '<span class="cel-story-sentence"'.$time.'>'.esc_html(trim($line)).' </span>';
            }
            echo '</div>';
        }else echo '<p class="cel-empty-info">Le texte de cette histoire sera bientôt disponible.</p>';
        echo '</div></div></div>';
        echo self::related($id,'cel_personnage','Les personnages de cette histoire');
        echo self::related($id,'cel_livre','Livres associés');
    }
    static function simple(int $id,string $type): void {
        echo '<div class="cel-detail-two"><div class="cel-cover">'.self::illustration($id).'</div><div class="cel-summary">';
        $description=Celestin_Fields::value($id,'resume');
        if($description)echo '<h2>Présentation</h2><p>'.nl2br(esc_html($description)).'</p>';
        if($type==='cel_coloriage'){
            echo self::facts($id,['type'=>'Type d’activité']);
            $pdf=Celestin_Fields::value($id,'pdf');
            if($pdf)echo '<p><a class="cel-action-link" href="'.esc_url($pdf).'" download>Télécharger l’activité (PDF) ↓</a></p>';
        }else echo self::facts($id,['histoire'=>'Histoire du lieu','adresse'=>'Repère sur la carte']);
        echo '</div></div>';
        echo self::related($id,'cel_livre','Livres associés');
        echo self::related($id,'cel_personnage','Personnages associés');
        echo self::related($id,'cel_histoire','Mini-histoires associées');
    }
}
Celestin_Public::init();
