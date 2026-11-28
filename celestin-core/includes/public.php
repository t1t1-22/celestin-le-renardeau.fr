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
        if(is_post_type_archive('cel_histoire')||is_singular('cel_livre'))wp_enqueue_script('celestin-home',plugins_url('../assets/home.js',__FILE__),[],(string)filemtime(__DIR__.'/../assets/home.js'),true);
        if(is_front_page()||is_singular('cel_livre')){
            wp_enqueue_script('cel-pdfjs','https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js',[],'3.11.174',true);
            wp_enqueue_script('cel-reader',plugins_url('../assets/reader.js',__FILE__),['cel-pdfjs'],(string)filemtime(__DIR__.'/../assets/reader.js'),true);
        }
        if(is_singular('cel_histoire')){
            wp_enqueue_script('cel-story',plugins_url('../assets/story.js',__FILE__),[],(string)filemtime(__DIR__.'/../assets/story.js'),true);
            wp_enqueue_script('cel-story-reader',plugins_url('../assets/story-reader.js',__FILE__),['cel-story'],(string)filemtime(__DIR__.'/../assets/story-reader.js'),true);
        }
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
        $out='<section class="cel-related"><h2>'.esc_html($title).'</h2><div class="cel-related-list'.($type==='cel_personnage'?' cel-related-characters':'').'">';
        foreach($items as $post){
            if($type==='cel_personnage'){
                $first=trim(Celestin_Fields::value($post->ID,'prenom'));
                $last=trim(Celestin_Fields::value($post->ID,'nom'));
                if($first===''){$first=$last?:$post->post_title;$last='';}
                $out.='<a class="cel-related-character" href="'.esc_url(get_permalink($post)).'"><div class="cel-related-character-art">'.self::illustration($post->ID,'medium').'</div><div class="cel-related-character-name"><strong>'.esc_html($first).'</strong>'.($last!==''?'<small>'.esc_html($last).'</small>':'').'</div></a>';
            }else{
                $out.='<a href="'.esc_url(get_permalink($post)).'">'.self::illustration($post->ID,'thumbnail').'<span>'.esc_html($post->post_title).'</span></a>';
            }
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
        echo '<section class="cel-reader-section'.($compact?' cel-reader-compact':'').'" aria-label="Visionneuse du livre"><div class="cel-book-reader" data-pdf="'.esc_url($pdf).'"><div class="cel-reader-rotate-hint"><svg viewBox="0 0 48 40" width="48" height="40" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="9" width="13" height="23" rx="3"/><path d="M7 28h5M19 5a15 15 0 0 1 17 7m-6-1 6 2 2-6"/><rect x="24" y="18" width="23" height="15" rx="3"/><path d="M35.5 21v9"/></svg><span><strong>Tourne ton téléphone</strong><small>Pour lire en double page, comme un vrai livre.</small></span></div><div class="cel-reader-layout">'.'<div class="cel-reader-center"><div class="cel-reader-viewport"><div class="cel-reader-spread"><canvas class="cel-reader-left" aria-label="Page de gauche"></canvas><canvas class="cel-reader-right" aria-label="Page de droite"></canvas><canvas class="cel-reader-flip" aria-hidden="true"></canvas></div></div></div></div>';
            echo '<div class="cel-reader-controls"><button type="button" class="cel-reader-prev" aria-label="Page précédente">❮</button><span class="cel-reader-counter">Chargement…</span><input class="cel-reader-progress" type="range" min="1" max="1" value="1" aria-label="Aller à une page"><button type="button" class="cel-reader-next" aria-label="Page suivante">❯</button><button type="button" class="cel-reader-zoom-out" aria-label="Dézoomer">−</button><button type="button" class="cel-reader-zoom-in" aria-label="Zoomer">+</button><button type="button" class="cel-reader-full" aria-label="Plein écran">⛶</button></div><p class="cel-reader-error" hidden></p><a class="cel-reader-fallback" href="'.esc_url($pdf).'" target="_blank" rel="noopener">Ouvrir le PDF</a></div></section>';
    }
    static function book(int $id): void {
        $pdf=Celestin_Fields::value($id,'pdf');
        $book=get_post($id);
        ?>
<style>
body.cel-content-page .cel-book-editorial .cel-books-layout{grid-template-columns:minmax(0, .9fr) minmax(0,1.1fr);gap:clamp(32px,6vw,85px);max-width:1000px;padding:30px 12px;align-items:center}
body.cel-content-page .cel-book-editorial .cel-book-description{background:transparent;border:0;padding:0;box-shadow:none}
body.cel-content-page .cel-book-editorial .cel-book-editorial-title{font:700 clamp(27px,3vw,38px)/1.15 'Baloo 2',sans-serif;margin:0 0 15px;color:#633619}
body.cel-content-page .cel-book-editorial-title a{color:inherit;text-decoration:none}
body.cel-content-page .cel-book-editorial .cel-book-preview{padding:24px 12px 38px 32px;perspective:1300px;display:flex;justify-content:center;position:relative;isolation:isolate}
body.cel-content-page .cel-book-editorial .cel-book-preview:before{content:"";position:absolute;left:50%;bottom:28px;width:84%;max-width:365px;height:22px;transform:translateX(-50%);background:radial-gradient(ellipse at center,#51371965 0%,#51371930 38%,transparent 72%);filter:blur(6px);z-index:-1;pointer-events:none}
body.cel-content-page .cel-book-editorial .cel-book-preview .cel-book-mockup{--book-depth:28px;position:relative;display:block;width:100%;max-width:365px;aspect-ratio:1;padding:0;border:0;border-radius:4px 9px 9px 4px;background:#f4e7d4;transform-style:preserve-3d;transform:rotateY(9deg) rotateX(0deg) rotateZ(0deg);box-shadow:none;overflow:visible;transition:none}
body.cel-content-page .cel-book-editorial .cel-book-mockup:before{content:"";position:absolute;inset:1px;border-radius:4px 9px 9px 4px;background:#d9791f;transform:translateZ(calc(-1 * var(--book-depth)));box-shadow:none;pointer-events:none}
body.cel-content-page .cel-book-editorial .cel-book-mockup:after{content:"";position:absolute;left:0;top:0;bottom:0;width:var(--book-depth);transform-origin:left center;transform:rotateY(90deg);background:linear-gradient(90deg,#b85e13 0%,#e98932 18%,#f3a24e 45%,#cf741f 100%);border-radius:3px 0 0 3px;pointer-events:none}
body.cel-content-page .cel-book-editorial .cel-book-mockup .cel-book-face{position:absolute;inset:0;display:block;overflow:hidden;border-radius:inherit;background:#f4e7d4;transform:translateZ(.5px);box-shadow:0 0 0 1px #88674630}
body.cel-content-page .cel-book-editorial .cel-book-mockup .cel-book-face:after{content:"";position:absolute;inset:0;background:linear-gradient(90deg,#33200d36 0%,#fff2 1.2%,#34220f16 2.2%,transparent 4%);pointer-events:none}
body.cel-content-page .cel-book-editorial .cel-book-mockup img{display:block;width:100%;height:100%;max-width:none;object-fit:contain;border-radius:inherit;box-shadow:none}
body.cel-content-page .cel-book-editorial .cel-book-mockup .cel-book-pages{display:none}
body.cel-content-page .cel-book-editorial .cel-book-preview .cel-book-mockup:hover{transform:rotateY(9deg) rotateX(0deg) rotateZ(0deg);box-shadow:none;border:0}
body.cel-content-page .cel-book-editorial .cel-feature-tags{margin-bottom:17px} body.cel-content-page .cel-book-editorial .cel-book-library-link{display:block;margin-top:12px;font-size:14px;color:#633619;text-decoration:underline}
body.cel-content-page .cel-book-editorial .cel-book-more summary{list-style:none;cursor:pointer;color:#633619;font-size:15px;line-height:1.65}
body.cel-content-page .cel-book-editorial .cel-book-more summary::-webkit-details-marker{display:none}
body.cel-content-page .cel-book-editorial .cel-book-more-label{display:block;color:#a75721;font-weight:700;font-size:14px;margin-top:5px}
body.cel-content-page .cel-book-editorial .cel-book-close,body.cel-content-page .cel-book-editorial .cel-book-more[open] .cel-book-open,body.cel-content-page .cel-book-editorial .cel-book-more[open] .cel-book-excerpt{display:none}
body.cel-content-page .cel-book-editorial .cel-book-more[open] .cel-book-close{display:inline}
body.cel-content-page .cel-book-editorial .cel-book-listen{display:flex;flex-wrap:wrap;align-items:center;gap:8px;margin:15px 0;color:#633619;font-size:14px;font-weight:700}
body.cel-content-page .cel-book-editorial .cel-book-listen audio{width:100%;max-width:350px;height:42px}
body.cel-content-page .cel-book-editorial .cel-home-book-all{margin-top:18px} body.cel-content-page .cel-book-editorial .cel-book-listen .cel-audio-toggle{position:relative;inset:auto;transform:none;width:48px;height:48px;flex:0 0 48px;padding:0;border:0;background:transparent;box-shadow:none} body.cel-content-page .cel-book-editorial .cel-book-listen .cel-audio-toggle svg{width:48px;height:48px;display:block} body.cel-content-page .cel-book-editorial .cel-book-listen .cel-audio-error[hidden]{display:none}
@media(max-width:760px){body.cel-content-page .cel-book-editorial .cel-books-layout{grid-template-columns:1fr;gap:12px;padding:15px 8px}body.cel-content-page .cel-book-editorial .cel-book-preview .cel-book-mockup{max-width:240px}body.cel-content-page .cel-book-editorial .cel-feature-tags{justify-content:flex-start}}
@media(prefers-reduced-motion:reduce){body.cel-content-page .cel-book-editorial .cel-book-preview .cel-book-mockup:hover{transform:rotateY(9deg) rotateX(0deg) rotateZ(0deg)}}
/* Présentation éditoriale de l’album. */
body.cel-content-page .cel-book-editorial .cel-book-description{display:flex;flex-direction:column;align-items:flex-start;gap:16px}
body.cel-content-page .cel-book-editorial .cel-book-editorial-title{margin:0;line-height:1.12;font-size:clamp(29px,3vw,40px)}
body.cel-content-page .cel-book-editorial .cel-feature-tags{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px 18px;width:100%;margin:0;padding:14px 0;border-top:1px solid #e5d8c7;border-bottom:1px solid #e5d8c7}
body.cel-content-page .cel-book-editorial .cel-feature-tags>span{display:flex;flex-direction:column;align-items:flex-start;gap:3px;background:none;border:0;border-radius:0;padding:3px 0;font-size:14px;line-height:1.35}
body.cel-content-page .cel-book-editorial .cel-feature-tags>span>strong{font-size:10px;text-transform:uppercase;letter-spacing:.09em;color:#a75b28;font-weight:700}
body.cel-content-page .cel-book-editorial .cel-feature-tags>span>span{font-size:14px;background:none;border:0;padding:0;border-radius:0;font-weight:600}
body.cel-content-page .cel-book-editorial .cel-home-book-summary{width:100%;margin:0;font-size:15px;line-height:1.65}
body.cel-content-page .cel-book-editorial .cel-book-more summary{font-size:15px;line-height:1.7}
body.cel-content-page .cel-book-editorial .cel-book-more-label{display:inline-flex;margin:4px 0 0 5px;text-decoration:underline;text-underline-offset:3px}
body.cel-content-page .cel-book-editorial .cel-book-actions{display:flex;flex-wrap:wrap;align-items:center;gap:10px 14px;width:100%;margin-top:4px}
body.cel-content-page .cel-book-editorial .cel-book-actions .cel-home-book-all{margin:0;background:#e98932;color:#fff!important;padding:12px 20px;border-radius:25px;box-shadow:0 3px 0 #c46c23}
body.cel-content-page .cel-book-editorial .cel-book-actions .cel-home-book-all:hover{background:#d77929}
body.cel-content-page .cel-book-editorial .cel-book-actions .cel-book-library-link{display:inline-flex;align-items:center;gap:8px;margin:0;padding:10px 14px;border:1px solid #d9c5ad;border-radius:25px;text-decoration:none;font-weight:600}
body.cel-content-page .cel-book-editorial .cel-book-actions .cel-book-library-link:hover{background:#f2e5d5}
body.cel-content-page .cel-book-editorial .cel-book-listen{width:auto;display:flex;align-items:center;gap:10px;margin:0;padding:5px 13px 5px 5px;border:1px solid #e5d8c7;border-radius:32px;background:#fffaf3;box-shadow:none}
@media(max-width:760px){body.cel-content-page .cel-book-editorial .cel-book-description{gap:13px}body.cel-content-page .cel-book-editorial .cel-book-actions{justify-content:flex-start}}
/* Album jeunesse : couverture, présentation et mémo épinglé. */
body.cel-content-page .cel-book-editorial .cel-books-layout{max-width:1250px;gap:clamp(20px,3vw,45px);grid-template-columns:minmax(0,.9fr) minmax(0,1.25fr)}
body.cel-content-page .cel-book-editorial .cel-books-layout.cel-has-book-note{grid-template-columns:minmax(0,.9fr) minmax(0,1.2fr) minmax(0,.85fr)}
body.cel-content-page .cel-book-editorial .cel-book-description{gap:12px}
body.cel-content-page .cel-book-editorial .cel-home-book-ribbon{display:inline-block;background:#e98932;color:#fff;padding:5px 18px 5px 12px;clip-path:polygon(0 0,100% 0,94% 50%,100% 100%,0 100%);font-size:13px;font-weight:700;border-radius:4px}
body.cel-content-page .cel-book-editorial .cel-book-facts{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px 8px;padding:12px 0;border:0}
body.cel-content-page .cel-book-editorial .cel-book-facts>span{align-items:center;text-align:center;gap:3px;padding:0}
body.cel-content-page .cel-book-editorial .cel-book-facts i{display:block;color:#e98932;height:24px}
body.cel-content-page .cel-book-editorial .cel-book-facts i svg{width:24px;height:24px;stroke:currentColor;stroke-width:1.7;fill:none;stroke-linecap:round;stroke-linejoin:round}
body.cel-content-page .cel-book-editorial .cel-book-facts>span>strong{font-size:10px;letter-spacing:.04em}
body.cel-content-page .cel-book-editorial .cel-book-facts>span>span{font-size:12px;line-height:1.35}
body.cel-content-page .cel-book-editorial .cel-home-book-note{position:relative;align-self:center;background:linear-gradient(135deg,#fff6db,#fff0c5);border:1px solid #e8c98e;border-radius:15px;padding:26px 20px 22px;box-shadow:0 9px 18px #8052231d;transform:rotate(-2deg);color:#633619}
body.cel-content-page .cel-book-editorial .cel-note-pin{position:absolute;width:30px;height:36px;top:-19px;left:50%;transform:translateX(-50%) rotate(12deg);filter:drop-shadow(1px 2px 2px #84522144)}
body.cel-content-page .cel-book-editorial .cel-note-pin svg{display:block;width:100%;height:100%}
body.cel-content-page .cel-book-editorial .cel-home-book-note h3{font:700 23px/1.2 'Baloo 2',sans-serif;color:#633619;margin:0 0 14px;display:flex;align-items:center;justify-content:space-between;gap:8px}
body.cel-content-page .cel-book-editorial .cel-home-book-note h3 svg{width:25px;height:25px;fill:none;stroke:#e98932;stroke-width:1.8;flex:none}
body.cel-content-page .cel-book-editorial .cel-home-book-note ul{list-style:none;padding:0;margin:0;display:grid;gap:10px}
body.cel-content-page .cel-book-editorial .cel-home-book-note li{position:relative;padding-left:23px;font-size:14px;line-height:1.45}
body.cel-content-page .cel-book-editorial .cel-home-book-note li:before{content:"✓";position:absolute;left:0;top:0;color:#d77929;font-weight:900;font-size:19px}
@media(max-width:1050px){body.cel-content-page .cel-book-editorial .cel-books-layout.cel-has-book-note{grid-template-columns:minmax(0,.85fr) minmax(0,1.15fr)}body.cel-content-page .cel-book-editorial .cel-home-book-note{grid-column:2;max-width:440px;width:100%;margin-top:12px}}
@media(max-width:760px){body.cel-content-page .cel-book-editorial .cel-books-layout,body.cel-content-page .cel-book-editorial .cel-books-layout.cel-has-book-note{grid-template-columns:1fr}body.cel-content-page .cel-book-editorial .cel-home-book-note{grid-column:auto;justify-self:center;margin:20px 0}body.cel-content-page .cel-book-editorial .cel-book-description{width:100%}}
body.cel-content-page .cel-book-editorial .cel-book-quick-facts{display:flex;flex-wrap:wrap;align-items:center;gap:6px 0;color:#94603c;font-size:13px;line-height:1.5;margin:0 0 5px}
body.cel-content-page .cel-book-editorial .cel-book-quick-facts>span{display:inline-flex;gap:4px;align-items:baseline}
body.cel-content-page .cel-book-editorial .cel-book-quick-facts>span+span:before{content:"·";margin:0 9px;color:#e98932;font-weight:700}
body.cel-content-page .cel-book-editorial .cel-book-classification{display:grid;gap:6px;width:100%;font-size:14px;line-height:1.5;color:#633619;padding:12px 0;border-top:1px solid #e5d8c7}
body.cel-content-page .cel-book-editorial .cel-book-classification strong{font-weight:700;color:#94603c}
body.cel-content-page .cel-book-editorial .cel-book-classification a{color:#a75721;text-decoration:underline;text-underline-offset:3px}
body.cel-content-page .cel-book-editorial .cel-book-cover-audio{position:absolute!important;top:18px;left:19px;z-index:5;display:block;width:auto;height:auto;padding:0;margin:0;border:0;background:transparent;box-shadow:none;transform:none}
body.cel-content-page .cel-book-editorial .cel-book-cover-audio .cel-audio-toggle{position:relative!important;inset:auto!important;display:block;width:58px;height:58px;padding:0;border:0;border-radius:50%;background:#fffaf3;box-shadow:0 3px 12px #63361945;transform:none!important;cursor:pointer}
body.cel-content-page .cel-book-editorial .cel-book-cover-audio .cel-audio-toggle svg{display:block;width:100%;height:100%}
body.cel-content-page .cel-book-editorial .cel-book-cover-audio .cel-audio-toggle:hover{box-shadow:0 0 0 3px #e9893255,0 4px 12px #63361945}
body.cel-content-page .cel-book-editorial .cel-book-cover-audio .cel-audio-error[hidden]{display:none}
body.cel-content-page .cel-book-editorial .cel-book-cover-audio .cel-audio-error:not([hidden]){position:absolute;top:100%;left:0;width:max-content;background:#fffaf3;padding:5px 8px;border-radius:5px;font-size:12px}
@media(max-width:760px){body.cel-content-page .cel-book-editorial .cel-book-cover-audio{left:calc(50% - 130px)}body.cel-content-page .cel-book-editorial .cel-book-cover-audio .cel-audio-toggle{width:48px;height:48px}}
body.cel-content-page .cel-book-editorial .cel-book-audio-unavailable button{display:block;width:58px;height:58px;border:0;padding:0;border-radius:50%;background:#fffaf3;box-shadow:0 3px 12px #63361945;cursor:default;opacity:.85}body.cel-content-page .cel-book-editorial .cel-book-audio-unavailable button svg{width:100%;height:100%;display:block}body.cel-content-page .cel-book-editorial .cel-book-audio-unavailable>span{display:block;font-size:10px;color:#633619;background:#fffaf3;padding:3px 7px;border-radius:12px;margin-top:4px;width:max-content}
body.cel-content-page .cel-book-editorial .cel-book-heading-row{display:flex;align-items:center;justify-content:space-between;gap:14px;width:100%}
body.cel-content-page .cel-book-editorial .cel-book-heading-copy{flex:1;min-width:0}
body.cel-content-page .cel-book-editorial .cel-book-heading-copy .cel-book-quick-facts{margin-top:10px}
body.cel-content-page .cel-book-editorial .cel-book-heading-row .cel-book-cover-audio{position:relative!important;top:auto!important;left:auto!important;right:auto!important;flex:0 0 auto;align-self:center;text-align:center}
body.cel-content-page .cel-book-editorial .cel-book-heading-row .cel-book-audio-unavailable>span{white-space:normal;max-width:85px;margin:4px auto 0}
body.cel-content-page .cel-book-editorial .cel-book-heading-row .cel-book-audio-unavailable button{margin:auto}
@media(max-width:760px){body.cel-content-page .cel-book-editorial .cel-book-heading-row{gap:8px}body.cel-content-page .cel-book-editorial .cel-book-heading-row .cel-book-audio-unavailable button{width:48px;height:48px}}

/* Animation douce du livre, indépendante du survol. */
@keyframes cel-detail-book-float {
  0%,100% {transform:translateY(0) rotateY(9deg) rotateX(0deg)}
  50% {transform:translateY(-10px) rotateY(19deg) rotateX(2deg)}
}
@keyframes cel-detail-book-shadow {
  0%,100% {opacity:1;transform:translateX(-50%) scaleX(1)}
  50% {opacity:.65;transform:translateX(-50%) scaleX(.88)}
}
@media(prefers-reduced-motion:no-preference){
 body.cel-content-page .cel-book-editorial .cel-book-preview .cel-book-mockup{animation:cel-detail-book-float 5s ease-in-out infinite}
 body.cel-content-page .cel-book-editorial .cel-book-preview:before{animation:cel-detail-book-shadow 5s ease-in-out infinite}
}
@media(prefers-reduced-motion:reduce){
 body.cel-content-page .cel-book-editorial .cel-book-preview .cel-book-mockup,body.cel-content-page .cel-book-editorial .cel-book-preview:before{animation:none}
}

/* Garder le livre et la note en haut lorsque le résumé se déplie. */
body.cel-content-page .cel-book-editorial .cel-books-layout{align-items:start}
body.cel-content-page .cel-book-editorial .cel-book-preview,body.cel-content-page .cel-book-editorial .cel-home-book-note{align-self:start}
body.cel-content-page .cel-book-editorial .cel-book-preview .cel-home-cover-card{margin-top:0;margin-bottom:0}
body.cel-content-page .cel-book-editorial .cel-book-more summary{display:block}
body.cel-content-page .cel-book-editorial .cel-book-more[open] summary{display:none}
body.cel-content-page .cel-book-editorial .cel-book-more[open] .cel-book-full{display:block}
body.cel-content-page .cel-book-editorial .cel-book-collapse{display:none;background:none;border:0;padding:0;color:#a75721;font:700 14px/1.5 Nunito,sans-serif;text-decoration:underline;text-underline-offset:3px;cursor:pointer}
body.cel-content-page .cel-book-editorial .cel-book-more[open] .cel-book-collapse{display:inline-block}
body.cel-content-page .cel-book-editorial{width:100%;margin:12px auto 28px;color:#633619}body.cel-content-page .cel-book-editorial .cel-books-layout{display:grid;margin:auto;box-sizing:border-box}body.cel-content-page .cel-book-editorial .cel-book-preview .cel-book-mockup{margin:auto;box-sizing:border-box}body.cel-content-page .cel-book-editorial .cel-book-actions .cel-home-book-all{display:inline-block;font:700 15px/1.4 Nunito,sans-serif;text-decoration:none!important}body.cel-content-page .cel-book-editorial .cel-book-description{min-width:0}body.cel-content-page .cel-book-editorial .cel-book-heading-row{min-width:0}body.cel-content-page .cel-book-editorial .cel-book-classification a:hover{text-decoration:none}#cel-lire-livre{scroll-margin-top:125px} @media(prefers-reduced-motion:no-preference){html:has(body.single-cel_livre){scroll-behavior:smooth}}
</style>
<section class="cel-section cel-books cel-book-editorial" aria-label="Présentation du livre">
 <?php if($book):
 $book_resume=Celestin_Fields::value($book->ID,'resume');
 $book_short=wp_trim_words(wp_strip_all_tags($book_resume),30,'…');
 $book_audio=Celestin_Fields::value($book->ID,'resume_audio');
 $book_points=preg_split('/\R/u',trim((string)Celestin_Fields::value($book->ID,'points_livre')),-1,PREG_SPLIT_NO_EMPTY);
 $book_number=Celestin_Fields::value($book->ID,'numero'); $book_month=Celestin_Fields::value($book->ID,'mois'); $book_pages=absint(Celestin_Fields::value($book->ID,'nombre_pages'));
 ?>
 <div class="cel-books-layout<?php echo $book_points?' cel-has-book-note':''; ?>">
 <div class="cel-book-preview">
 <a class="cel-home-cover-card cel-book-mockup" href="#cel-lire-livre" aria-label="<?php echo esc_attr('Lire '.$book->post_title); ?>">
 <span class="cel-book-pages" aria-hidden="true"></span><span class="cel-book-face"><?php echo Celestin_Public::illustration($book->ID,'large'); ?></span>
 </a>

 </div>
 <div class="cel-book-description">
 <div class="cel-book-heading-row"><div class="cel-book-heading-copy">
 <h1 class="cel-book-editorial-title"><?php echo esc_html($book->post_title); ?></h1>
 <div class="cel-book-quick-facts">
 <?php if($book_month): ?><span><strong>Date de sortie :</strong> <?php echo esc_html($book_month.(preg_match('/\b\d{4}\b/u',$book_month)?'':' 2027')); ?></span><?php endif; ?>
 <?php $book_age=Celestin_Fields::value($book->ID,'age_lecture'); if($book_age): ?><span><strong>Âge :</strong> <?php echo esc_html($book_age); ?></span><?php endif; ?>
 <?php if($book_pages): ?><span data-cel-page-total><?php echo esc_html($book_pages); ?> pages</span><?php endif; ?>
 </div>
 </div>
 <?php if($book_audio): ?><div class="cel-book-cover-audio cel-audio-card"><audio preload="metadata" src="<?php echo esc_url($book_audio); ?>"></audio><button type="button" class="cel-audio-toggle" data-title="<?php echo esc_attr($book->post_title); ?>" aria-label="Écouter le résumé ou un extrait" title="Écouter un extrait" aria-pressed="false"><svg viewBox="0 0 64 64" aria-hidden="true"><circle cx="32" cy="32" r="30" fill="#fffaf3"/><circle cx="32" cy="32" r="26" fill="none" stroke="#e9d3bb" stroke-width="4"/><circle class="cel-audio-ring" cx="32" cy="32" r="26" fill="none" stroke="#e98932" stroke-width="4" pathLength="100" stroke-dasharray="0 100" stroke-linecap="round" transform="rotate(-90 32 32)"/><path class="cel-audio-play" d="M25 20L44 32L25 44Z" fill="#a45b2c"/><rect class="cel-audio-stop" x="23" y="23" width="18" height="18" rx="3" fill="#a45b2c"/></svg></button><span class="cel-audio-error" role="status" hidden>Audio indisponible.</span></div><?php else: ?><div class="cel-book-cover-audio cel-book-audio-unavailable"><button type="button" disabled aria-label="Extrait audio bientôt disponible" title="Extrait audio bientôt disponible"><svg viewBox="0 0 64 64" aria-hidden="true"><circle cx="32" cy="32" r="30" fill="#fffaf3"/><circle cx="32" cy="32" r="26" fill="none" stroke="#e98932" stroke-width="3"/><path d="M25 20L44 32L25 44Z" fill="#a45b2c"/></svg></button><span>Audio bientôt disponible</span></div><?php endif; ?>
 </div>
 <?php if($book_resume): ?><div class="cel-home-book-summary"><?php if($book_short!==wp_strip_all_tags($book_resume) && mb_strlen($book_short)<mb_strlen(wp_strip_all_tags($book_resume))): ?><details class="cel-book-more"><summary><span class="cel-book-excerpt"><?php echo esc_html($book_short); ?></span><span class="cel-book-more-label"><span class="cel-book-open">Lire plus +</span><span class="cel-book-close">Lire moins −</span></span></summary><div class="cel-book-full"><?php echo wpautop(esc_html($book_resume)); ?><button type="button" class="cel-book-collapse" onclick="this.closest('details').open=false;this.closest('details').querySelector('summary').focus()">Lire moins −</button></div></details><?php else: ?><p><?php echo esc_html($book_resume); ?></p><?php endif; ?></div><?php endif; ?>
 <div class="cel-book-classification">
 <?php foreach(['collection'=>'Collection','saison'=>'Saison','evenement'=>'Événement','categorie'=>'Thème'] as $key=>$label): $value=Celestin_Fields::value($book->ID,$key); if(!$value)continue; ?>
 <div><strong><?php echo esc_html($label); ?> :</strong> <?php if($key==='collection'): $terms=wp_get_object_terms($book->ID,Celestin_Library::TAX['collection']); if(!is_wp_error($terms)&&$terms): foreach($terms as $i=>$term): if($i)echo ', '; ?><a href="<?php echo esc_url(add_query_arg('filtre_collection',$term->slug,Celestin_Home::url('cel_livre'))); ?>"><?php echo esc_html($term->name); ?></a><?php endforeach; else: echo esc_html($value); endif; else: echo esc_html($value); endif; ?></div>
 <?php endforeach; ?>
 </div>

 <div class="cel-book-actions"><a class="cel-feature-link cel-home-book-all" href="#cel-lire-livre">Lire le livre ↓</a>
 </div>
 </div>
 <?php if($book_points): ?><aside class="cel-home-book-note"><span class="cel-note-pin" aria-hidden="true"><svg viewBox="0 0 32 40"><path d="m16 24-5 13" stroke="#77533a" stroke-width="2" stroke-linecap="round"/><ellipse cx="16" cy="22" rx="10" ry="6" fill="#d77b30" stroke="#ad5b22" stroke-width="1.5"/><path d="m10 9-2 12q8 8 15 0L22 9" fill="#e98932" stroke="#ad5b22" stroke-width="1.5"/><ellipse cx="16" cy="9" rx="10" ry="7" fill="#f6aa56" stroke="#ad5b22" stroke-width="1.5"/></svg></span><h3>Dans ce livre… <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 21 3 12C-2 5 7 0 12 7c5-7 14-2 9 5Z"/></svg></h3><ul><?php foreach($book_points as $point): if(trim($point)==='')continue; ?><li><?php echo esc_html(trim($point)); ?></li><?php endforeach; ?></ul></aside><?php endif; ?>
 </div>
 <?php else: ?><p class="cel-home-empty">Sélectionne un livre depuis sa fiche WordPress pour l’afficher ici.</p><?php endif; ?>
</section>
<?php
        if(get_post_meta($id,'_cel_demo_may',true)==='1')echo '<p class="cel-demo-notice">Livre de démonstration — contenu provisoire.</p>';
        echo '<div id="cel-lire-livre">';
        if($pdf)self::reader($pdf);
        else echo '<p class="cel-empty-info">Ce livre sera bientôt disponible à la lecture.</p>';
        echo '</div>';
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
            'volume_off'=>'<path d="M3 9h4l5-4v14l-5-4H3zM16 9l6 6m0-6-6 6"/>',
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
            echo '<div class="cel-story-player"><audio controls controlslist="nodownload" preload="metadata" data-cel-story-audio src="'.esc_url($audio).'"></audio><div class="cel-story-player-ui" hidden><button type="button" class="cel-story-main-play" data-story-play aria-label="Écouter l’histoire"><span data-play-symbol>'.self::story_icon('play').'</span><span data-pause-symbol hidden>'.self::story_icon('pause').'</span></button><div class="cel-story-player-progress"><input type="range" data-story-seek min="0" max="1000" value="0" disabled aria-label="Progression de l’histoire"><div class="cel-story-times"><span data-story-current>0:00</span><span data-story-duration>–:––</span></div></div><button type="button" class="cel-story-volume" data-story-mute aria-label="Couper le son" title="Couper le son" aria-pressed="false"><span data-volume-on>'.self::story_icon('volume').'</span><span data-volume-off hidden>'.self::story_icon('volume_off').'</span></button></div><p class="cel-story-audio-error" data-story-error hidden role="status">Audio indisponible.</p></div>';
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
        if(trim($texte)!=='')echo '<button type="button" class="cel-story-action cel-story-read-link" data-story-read aria-haspopup="dialog" aria-controls="cel-story-reading" hidden>'.self::story_icon('book').'<span>Lire le texte de l’histoire</span></button>';
        echo '<button type="button" class="cel-story-action" data-story-share hidden>'.self::story_icon('share').'<span>Partager</span></button></div><p data-story-status role="status" class="cel-story-status" hidden></p></div></div>';
        if(trim($texte)!==''){
            echo '<dialog id="cel-story-reading" class="cel-ereader" data-ereader aria-labelledby="cel-ereader-title"><div class="cel-ereader-shell"><header class="cel-ereader-top"><span class="cel-ereader-brand">'.self::story_icon('book').' Mode lecture</span><button type="button" class="cel-ereader-close" data-reader-close aria-label="Fermer la lecture">×</button></header>';
            echo '<div class="cel-ereader-tools"><div class="cel-ereader-font"><label for="cel-reader-font">Police</label><select id="cel-reader-font" data-reader-font><option value="serif">Classique</option><option value="sans">Sans serif</option><option value="rounded">Arrondie</option></select></div><div class="cel-ereader-size" role="group" aria-label="Taille du texte"><button type="button" data-reader-smaller aria-label="Réduire le texte">A−</button><button type="button" data-reader-larger aria-label="Agrandir le texte">A+</button></div><div class="cel-ereader-themes" role="group" aria-label="Couleur du fond"><button type="button" data-reader-theme="white" aria-label="Fond blanc" title="Blanc"></button><button type="button" data-reader-theme="cream" aria-label="Fond crème" title="Crème"></button><button type="button" data-reader-theme="dark" aria-label="Mode nuit" title="Nuit"></button></div></div>';
            echo '<div class="cel-ereader-heading"><span>Les mini-histoires de Célestin</span><h2 id="cel-ereader-title">'.esc_html($title).'</h2></div><div class="cel-ereader-viewport" data-reader-viewport><div class="cel-ereader-paper" data-reader-paper role="document" aria-label="Page de l’histoire"></div></div>';
            echo '<footer class="cel-ereader-footer"><div class="cel-ereader-nav"><button type="button" data-reader-prev aria-label="Page précédente"><span aria-hidden="true">‹</span><span class="cel-ereader-nav-label">Précédente</span></button><span data-reader-count role="status" aria-live="polite">Page 1</span><button type="button" data-reader-next aria-label="Page suivante"><span class="cel-ereader-nav-label">Suivante</span><span aria-hidden="true">›</span></button></div><div class="cel-ereader-progress" role="progressbar" aria-label="Progression de lecture" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-reader-progress><span></span></div>';
            if($audio)echo '<button type="button" class="cel-ereader-audio" data-reader-audio>'.self::story_icon('play').'<span>Écouter l’histoire</span></button>';
            echo '</footer></div><template data-reader-source>';
            foreach(preg_split('/\R+/u',trim($texte),-1,PREG_SPLIT_NO_EMPTY) as $paragraph)echo '<p>'.esc_html(trim($paragraph)).'</p>';
            echo '</template></dialog>';
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
