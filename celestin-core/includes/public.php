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
        if(is_tax(array_values(Celestin_Library::TAX))||is_post_type_archive(self::TYPES))return __DIR__.'/archive-content.php';
        return $default;
    }
    static function assets(): void {
        if(!is_front_page() && !is_singular(self::TYPES) && !is_post_type_archive(self::TYPES) && !is_tax(array_values(Celestin_Library::TAX)))return;
        wp_enqueue_style('celestin-home',plugins_url('../assets/home.css',__FILE__),[],'0.4.65');
        wp_enqueue_style('cel-content',plugins_url('../assets/content.css',__FILE__),['celestin-home'],'0.4.65');
        if(is_front_page()||is_singular('cel_livre')){
            wp_enqueue_script('cel-pdfjs','https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js',[],'3.11.174',true);
            wp_enqueue_script('cel-reader',plugins_url('../assets/reader.js',__FILE__),['cel-pdfjs'],'0.4.65',true);
        }
        if(is_singular('cel_histoire'))wp_enqueue_script('cel-story',plugins_url('../assets/story.js',__FILE__),[],'0.4.65',true);
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
        Celestin_Town::character_album($id);
        echo self::related($id,'cel_personnage','Ma famille et mes amis');
        echo self::related($id,'cel_lieu','Mes lieux préférés');
        echo self::related($id,'cel_livre','Les livres où je suis présent');
        echo self::related($id,'cel_histoire','Mes mini-histoires');
    }
    static function reader(string $pdf,bool $compact=false): void {
        echo '<section class="cel-reader-section'.($compact?' cel-reader-compact':'').'" aria-label="Visionneuse du livre"><div class="cel-book-reader" data-pdf="'.esc_url($pdf).'"><div class="cel-reader-layout">'.($compact?'':'<div class="cel-reader-thumbs" aria-label="Miniatures des pages"></div>').'<div class="cel-reader-center"><div class="cel-reader-viewport"><div class="cel-reader-spread"><canvas class="cel-reader-left" aria-label="Page de gauche"></canvas><canvas class="cel-reader-right" aria-label="Page de droite"></canvas><canvas class="cel-reader-flip" aria-hidden="true"></canvas></div></div></div></div>';
            echo '<div class="cel-reader-controls"><button type="button" class="cel-reader-prev" aria-label="Page précédente">❮</button><span class="cel-reader-counter">Chargement…</span><input class="cel-reader-progress" type="range" min="1" max="1" value="1" aria-label="Aller à une page"><button type="button" class="cel-reader-next" aria-label="Page suivante">❯</button><button type="button" class="cel-reader-zoom-out" aria-label="Dézoomer">−</button><button type="button" class="cel-reader-zoom-in" aria-label="Zoomer">+</button><button type="button" class="cel-reader-full" aria-label="Plein écran">⛶</button></div><p class="cel-reader-error" hidden></p><a class="cel-reader-fallback" href="'.esc_url($pdf).'" target="_blank" rel="noopener">Ouvrir le PDF</a></div></section>';
    }
    static function book(int $id): void {
        $pdf=Celestin_Fields::value($id,'pdf');
        $resume=Celestin_Fields::value($id,'resume');
        $month=Celestin_Fields::value($id,'mois');
        $collection=Celestin_Fields::value($id,'collection');
        $season=Celestin_Fields::value($id,'saison');
        $age=Celestin_Fields::value($id,'age_lecture');
        $highlights=Celestin_Fields::value($id,'points_livre');
        $demo=get_post_meta($id,'_cel_demo_may',true)==='1';
        echo '<div class="cel-book-intro"><div class="cel-book-cover">'.self::illustration($id).'</div><div class="cel-book-intro-copy">';
        if($month)echo '<span class="cel-book-ribbon">'.esc_html($month).'</span>';
        echo '<h1>'.esc_html(get_the_title($id)).'</h1>';
        if($resume)echo '<div class="cel-book-resume-wrap"><div id="cel-book-resume" class="cel-book-resume">'.wpautop(esc_html($resume)).'</div><button type="button" class="cel-resume-toggle" aria-controls="cel-book-resume" aria-expanded="false" hidden>Lire plus ↓</button></div>';
        if($demo)echo '<p class="cel-demo-notice">Livre de démonstration — contenu provisoire.</p>';
        echo '<div class="cel-book-meta"><span data-cel-page-total>'.(absint(Celestin_Fields::value($id,'nombre_pages'))?absint(Celestin_Fields::value($id,'nombre_pages')).' pages':'Pages du livre').'</span>';
        foreach(['Âge'=>$age,'Thème'=>$season,'Collection'=>$collection] as $label=>$value)if($value)echo '<span><strong>'.esc_html($label).'</strong> '.esc_html($value).'</span>';
        echo '</div></div>';
        if($highlights){echo '<aside class="cel-book-note"><h2>Dans ce livre…</h2><ul>';
        foreach(preg_split('/\R/u',$highlights,-1,PREG_SPLIT_NO_EMPTY) as $line)echo '<li>'.esc_html($line).'</li>';
        echo '</ul></aside>';}
        echo '</div>';
        if($pdf){
            self::reader($pdf);
        }else echo '<p class="cel-empty-info">Ce livre sera bientôt disponible à la lecture.</p>';

        echo self::related($id,'cel_coloriage','Les activités de ce livre');
        echo self::related($id,'cel_personnage','Les personnages de ce livre');
        echo self::related($id,'cel_histoire','Mini-histoires associées');
    }
    static function story(int $id): void {
        $audio=Celestin_Fields::value($id,'audio');
        $texte=Celestin_Fields::value($id,'texte');
        $sync=Celestin_Fields::value($id,'synchronisation');
        echo '<div class="cel-story-layout"><div class="cel-story-visual">'.self::illustration($id).'</div><div class="cel-story-main">';
        $resume=Celestin_Fields::value($id,'resume');
        if($resume)echo '<p class="cel-story-resume">'.nl2br(esc_html($resume)).'</p>';
        $preview=Celestin_Fields::value($id,'resume_audio');
        if($preview)echo '<div class="cel-listen cel-listen-preview"><h2>Écouter le résumé</h2><p>Un petit aperçu avant de découvrir toute l’aventure.</p><audio controls preload="metadata" data-cel-story-preview src="'.esc_url($preview).'"></audio></div>';
        if($audio){
            echo '<div class="cel-listen"><h2>Écouter l’histoire complète</h2><audio controls preload="metadata" data-cel-story-audio src="'.esc_url($audio).'"></audio><p>Tu peux mettre en pause ou avancer dans l’histoire.</p></div>';
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
