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
        if(is_page(['origine','origines','les-origines-de-celestin','origine-de-celestin'])&&!is_preview())return __DIR__.'/origin-page.php';
        if(is_singular(self::TYPES))return __DIR__.'/single-content.php';
        if(is_tax(array_values(Celestin_Library::TAX))||is_post_type_archive(self::TYPES))return __DIR__.'/archive-content.php';
        if(!is_feed()&&!is_embed()&&!is_preview()&&(is_page()||is_singular('post')||is_search()||is_404()||is_archive()||is_home()))return __DIR__.'/general-page.php';
        return $default;
    }
    static function assets(): void {
        if(is_feed()||is_embed())return;
        wp_enqueue_style('celestin-home',plugins_url('../assets/home.css',__FILE__),[],(string)filemtime(__DIR__.'/../assets/home.css'));
        wp_enqueue_style('cel-content',plugins_url('../assets/content.css',__FILE__),['celestin-home'],(string)filemtime(__DIR__.'/../assets/content.css'));
        if(is_front_page()||is_singular('cel_livre')){
            wp_enqueue_script('cel-pdfjs','https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js',[],'3.11.174',true);
            wp_enqueue_script('cel-reader',plugins_url('../assets/reader.js',__FILE__),['cel-pdfjs'],'0.4.83',true);
        }
        if(is_singular('cel_histoire'))wp_enqueue_script('cel-story',plugins_url('../assets/story.js',__FILE__),[],(string)filemtime(__DIR__.'/../assets/story.js'),true);
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
        [$family]=Celestin_Fields::family_graph(true);
        if(!empty($family[$id])){
            echo '<section class="cel-detail-section"><h2>Ma famille et mes relations</h2><div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(135px,190px));gap:14px">';
            foreach($family[$id] as $kind=>$others)foreach(array_keys($others) as $other){
                echo '<a style="display:block;text-decoration:none;background:#fff8ef;border:1px solid #ead5bb;border-radius:14px;padding:10px;color:#633619;text-align:center" href="'.esc_url(get_permalink($other)).'"><div style="aspect-ratio:1;overflow:hidden;border-radius:9px">'.get_the_post_thumbnail($other,'medium',['style'=>'width:100%;height:100%;object-fit:contain']).'</div><strong style="display:block">'.esc_html(get_the_title($other)).'</strong><span>'.esc_html(Celestin_Fields::family_label($kind,$other)).'</span></a>';
            }
            echo '</div></section>';
        }
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
    static function story_icon(string $name): string {
        $paths=[
            'play'=>'<path d="M8 5v14l12-7z" fill="currentColor" stroke="none"/>',
            'share'=>'<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.7 10.5 6.6-4M8.7 13.5l6.6 4"/>',
            'heart'=>'<path d="M20.8 8.6c0 4.1-8.8 9.5-8.8 9.5S3.2 12.7 3.2 8.6c0-4.8 6-5.9 8.8-1.7 2.8-4.2 8.8-3.1 8.8 1.7Z"/>',
            'book'=>'<path d="M12 6c-3-2-6-2-9-1v14c3-1 6-1 9 1 3-2 6-2 9-1V5c-3-1-6-1-9 1v14"/>',
            'volume'=>'<path d="M3 9h4l5-4v14l-5-4H3zM15 9a4 4 0 0 1 0 6m3-9a8 8 0 0 1 0 12"/>',
            'pause'=>'<path d="M7 5h3v14H7zm7 0h3v14h-3z" fill="currentColor" stroke="none"/>',
            'clock'=>'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'
        ];
        return '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($paths[$name]??'').'</svg>';
    }
    static function story_recommendations(int $id,string $type,string $title,array $items): void {
        if(!$items)return;
        echo '<section class="cel-story-recs" data-story-recs><div class="cel-story-recs-heading"><h2>'.esc_html($title).'</h2><div class="cel-story-recs-nav" hidden><button type="button" data-rec-prev aria-label="Précédent">‹</button><button type="button" data-rec-next aria-label="Suivant">›</button></div></div><div class="cel-story-recs-track" tabindex="0" aria-label="'.esc_attr($title).'">';
        foreach($items as $post){
            if($post->ID===$id)continue;
            $label=$type==='cel_coloriage'?Celestin_Fields::value($post->ID,'type'):'';
            echo '<a class="cel-story-rec-card" href="'.esc_url(get_permalink($post)).'"><div class="cel-story-rec-art">'.self::illustration($post->ID,'medium').($type==='cel_histoire'?'<span class="cel-story-rec-play">'.self::story_icon('play').'</span>':'').'</div><div class="cel-story-rec-copy">'.($label?'<small>'.esc_html($label).'</small>':'').'<strong>'.esc_html(get_the_title($post)).'</strong></div></a>';
        }
        echo '</div></section>';
    }
    static function story(int $id): void {
        $audio=Celestin_Fields::value($id,'audio');
        $texte=Celestin_Fields::value($id,'texte');
        $sync=Celestin_Fields::value($id,'synchronisation');
        $resume=Celestin_Fields::value($id,'resume');
        $preview=Celestin_Fields::value($id,'resume_audio');
        $title=get_the_title($id);
        $permalink=get_permalink($id);
        $art=get_the_post_thumbnail_url($id,'large')?:'';
        echo '<p class="cel-breadcrumb"><a href="'.esc_url(home_url('/')).'">Accueil</a> › <a href="'.esc_url(get_post_type_archive_link('cel_histoire')).'">Mini-histoires audio</a> › '.esc_html($title).'</p>';
        echo '<div class="cel-story-layout cel-story-new" data-story-page data-story-title="'.esc_attr($title).'" data-story-url="'.esc_url($permalink).'" data-story-art="'.esc_url($art).'" data-story-id="'.esc_attr((string)$id).'"><div class="cel-story-visual"><div class="cel-story-cover">'.self::illustration($id).'</div>';
        if($audio){
            echo '<div class="cel-story-player"><audio controls controlslist="nodownload" preload="metadata" data-cel-story-audio src="'.esc_url($audio).'"></audio><div class="cel-story-player-ui" hidden><button type="button" class="cel-story-main-play" data-story-play aria-label="Écouter l’histoire"><span data-play-symbol>'.self::story_icon('play').'</span><span data-pause-symbol hidden>'.self::story_icon('pause').'</span></button><div class="cel-story-player-progress"><input type="range" data-story-seek min="0" max="1000" value="0" disabled aria-label="Progression de l’histoire"><div class="cel-story-times"><span data-story-current>0:00</span><span data-story-duration>–:––</span></div></div><button type="button" class="cel-story-volume" data-story-mute aria-label="Couper le son" aria-pressed="false">'.self::story_icon('volume').'</button></div><p class="cel-story-audio-error" data-story-error hidden role="status">Audio indisponible.</p></div>';
        }else echo '<p class="cel-empty-info">La version audio sera bientôt disponible.</p>';
        echo '</div><div class="cel-story-main"><p class="cel-kicker">Mini-histoire audio</p><div class="cel-story-heading"><h1>'.esc_html($title).'</h1>';
        if($preview)echo '<div class="cel-story-teaser"><audio controls controlslist="nodownload" preload="none" data-cel-story-preview src="'.esc_url($preview).'"></audio><button type="button" data-story-teaser hidden aria-pressed="false"><span data-play-symbol>'.self::story_icon('play').'</span><span data-pause-symbol hidden>'.self::story_icon('pause').'</span><span data-teaser-label>Écouter le teaser</span></button></div>';
        echo '</div><dl class="cel-story-meta">';
        foreach(['collection'=>'Collection','mois'=>'Date de sortie','saison'=>'Saison','evenement'=>'Événement','age_lecture'=>'Âge','categorie'=>'Thèmes'] as $key=>$label){
            $value=Celestin_Library::display($id,$key);
            if($value!=='')echo '<div><dt>'.esc_html($label).'</dt><dd>'.esc_html($value).'</dd></div>';
        }
        if($audio)echo '<div data-story-duration-meta hidden><dt>Durée</dt><dd data-story-duration-label></dd></div>';
        echo '</dl>';
        if($resume)echo '<div class="cel-story-resume">'.wpautop(esc_html($resume)).'</div>';
        echo '<div class="cel-story-actions">';
        if($audio)echo '<button type="button" data-story-start class="cel-story-primary" hidden>'.self::story_icon('play').'<span>Écouter l’histoire</span></button>';
        if($texte)echo '<a class="cel-story-action" data-story-read href="#cel-story-reading">'.self::story_icon('book').'<span>Lire l’histoire</span></a>';
        echo '<button type="button" class="cel-story-action" data-story-share hidden>'.self::story_icon('share').'<span>Partager</span></button></div><p data-story-status role="status" class="cel-story-status" hidden></p></div></div>';
        if($texte){
            $timings=[];
            foreach(preg_split('/\R/u',$sync) as $line)if(preg_match('/^\s*(\d+(?:[.,]\d+)?)\s*\|(.*)$/u',trim($line),$m))$timings[]=floatval(str_replace(',','.',$m[1]));
            echo '<details id="cel-story-reading" class="cel-story-reading cel-story-reading-new"><summary>Lire l’histoire</summary><div class="cel-story-transcript" data-cel-story-transcript aria-label="Texte de la mini-histoire">';
            $idx=0;
            foreach(preg_split('/\R+/u',trim($texte),-1,PREG_SPLIT_NO_EMPTY) as $paragraph){
                echo '<p>';
                foreach(preg_split('/(?<=[.!?…])\s+/u',trim($paragraph),-1,PREG_SPLIT_NO_EMPTY) as $line){
                    $time=isset($timings[$idx])?' data-start="'.esc_attr((string)$timings[$idx]).'"':'';
                    echo '<span class="cel-story-sentence"'.$time.'>'.esc_html(trim($line)).' </span>';$idx++;
                }
                echo '</p>';
            }
            echo '</div></details>';
        }
        $collection=wp_get_object_terms($id,Celestin_Library::TAX['collection'],['fields'=>'ids']);
        $themes=wp_get_object_terms($id,Celestin_Library::TAX['categorie'],['fields'=>'ids']);
        $stories=[];
        if(!is_wp_error($collection)&&$collection)$stories=get_posts(['post_type'=>'cel_histoire','post_status'=>'publish','posts_per_page'=>12,'post__not_in'=>[$id],'orderby'=>'date','order'=>'DESC','tax_query'=>[['taxonomy'=>Celestin_Library::TAX['collection'],'field'=>'term_id','terms'=>$collection]]]);
        $activities=[];
        foreach(Celestin_Fields::linked($id,'cel_coloriage') as $item)$activities[$item->ID]=$item;
        if(!is_wp_error($themes)&&$themes){
            $matches=get_posts(['post_type'=>'cel_coloriage','post_status'=>'publish','posts_per_page'=>12,'orderby'=>'date','order'=>'DESC','tax_query'=>[['taxonomy'=>Celestin_Library::TAX['categorie'],'field'=>'term_id','terms'=>$themes]]]);
            foreach($matches as $item)if(!isset($activities[$item->ID]))$activities[$item->ID]=$item;
        }
        if($stories||$activities){
            echo '<div class="cel-story-recs-grid'.($stories&&$activities?' has-two':'').'">';
            self::story_recommendations($id,'cel_histoire','Dans la même collection',$stories);
            self::story_recommendations($id,'cel_coloriage','Les activités liées',array_slice(array_values($activities),0,12));
            echo '</div>';
        }
        echo self::related($id,'cel_personnage','Les personnages de cette histoire');
        echo self::related($id,'cel_livre','Livres associés');
        echo self::related($id,'cel_lieu','Les lieux de cette histoire');
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
