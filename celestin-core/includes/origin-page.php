<?php
if(!defined('ABSPATH'))exit;
$page=get_queried_object();
$raw=$page instanceof WP_Post?$page->post_content:'';
// Réutiliser les illustrations et légendes de la page WordPress, sans générer d’images.
$images=[];
$add_image=function($id,$url,$caption='',$alt='')use(&$images){
    $id=absint($id);$url=$id?(wp_get_attachment_image_url($id,'large')?:$url):$url;
    if(!$url)return;
    $key=$id?'id-'.$id:preg_replace('/-\d+x\d+(?=\.[^.]+$)/','',$url);
    if(isset($images[$key])){if($caption&&!$images[$key]['caption'])$images[$key]['caption']=$caption;return;}
    $images[$key]=['id'=>$id,'url'=>$url,'caption'=>trim(wp_strip_all_tags($caption)),'alt'=>$alt];
};
$scan=function($blocks)use(&$scan,$add_image){
    foreach($blocks as $block){
        $name=$block['blockName']??'';$attrs=$block['attrs']??[];$html=$block['innerHTML']??'';
        if($name==='core/image'){
            $url='';$alt='';$caption='';
            if(preg_match('/<img\b[^>]*\bsrc=["\']([^"\']+)["\']/i',$html,$m))$url=html_entity_decode($m[1]);
            if(preg_match('/<img\b[^>]*\balt=["\']([^"\']*)["\']/i',$html,$m))$alt=html_entity_decode($m[1]);
            if(preg_match('/<figcaption[^>]*>(.*?)<\/figcaption>/is',$html,$m))$caption=$m[1];
            $add_image($attrs['id']??0,$url,$caption,$alt);
        }elseif($name==='core/gallery'&&!empty($attrs['ids'])&&empty($block['innerBlocks'])){
            foreach($attrs['ids'] as $id)$add_image($id,'',wp_get_attachment_caption($id)?:'');
        }elseif(empty($block['innerBlocks'])){
            if(preg_match_all('/<img\b[^>]*\bsrc=["\']([^"\']+)["\'][^>]*>/i',$html,$matches,PREG_SET_ORDER))
                foreach($matches as $m){$id=0;if(preg_match('/wp-image-(\d+)/',$m[0],$n))$id=(int)$n[1];$add_image($id,html_entity_decode($m[1]),$id?(wp_get_attachment_caption($id)?:''):'');}
        }
        if(!empty($block['innerBlocks']))$scan($block['innerBlocks']);
    }
};
$scan(parse_blocks($raw));
if(!$images&&has_post_thumbnail($page))$add_image(get_post_thumbnail_id($page),'');
$images=array_values($images);$cover=array_shift($images);
if($cover&&!$images)$images=[$cover];
$render_image=function($img,$class=''){
    echo '<img class="'.esc_attr($class).'" src="'.esc_url($img['url']).'" alt="'.esc_attr($img['alt']?:$img['caption']?:'Illustration originale de Célestin').'" loading="lazy">';
};
?>
<!DOCTYPE html><html <?php language_attributes(); ?>><head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><?php wp_head(); ?>
<style>
body.cel-content-page .cel-origin-editorial{width:min(1100px,90%);margin:55px auto 70px;color:#633619}
.cel-origin-editorial h1,.cel-origin-editorial h2{font-family:'Baloo 2',sans-serif;font-weight:700;line-height:1.1;color:#633619}
.cel-origin-editorial h1{font-size:clamp(38px,5vw,66px);margin:9px 0 22px}
.cel-origin-editorial h2{font-size:clamp(26px,3vw,38px);margin:0 0 17px}
.cel-origin-editorial p{font:400 16px/1.85 Nunito,sans-serif;color:#80532b;margin:0 0 17px}
.cel-origin-kicker{font:800 12px/1.5 Nunito,sans-serif;letter-spacing:.12em;text-transform:uppercase;color:#a76129}
.cel-origin-hero{display:grid;grid-template-columns:minmax(0,.8fr) minmax(0,1.2fr);gap:clamp(35px,6vw,85px);align-items:center}
.cel-origin-hero.no-cover{grid-template-columns:1fr}
.cel-origin-cover{margin:0;position:relative;background:#fffdf7;padding:12px 12px 22px;border:1px solid #e7d2b7;box-shadow:0 12px 30px #63361922;border-radius:4px;transform:rotate(-2deg);max-width:380px;justify-self:center}
.cel-origin-cover img{display:block;width:100%;height:auto;border-radius:2px}
.cel-origin-cover figcaption{font-size:12px;text-align:center;margin-top:12px;color:#a76129}
.cel-origin-quote{margin:35px 0 0;padding:16px 23px;border-left:3px solid #e98932;font:700 23px/1.4 'Baloo 2',sans-serif;color:#633619;background:#f4e6d530}
.cel-origin-timeline{margin:70px 0 55px}
.cel-origin-timeline>h2,.cel-origin-timeline>p{text-align:center}
.cel-origin-gallery{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,155px),1fr));gap:22px;position:relative;margin-top:35px}
.cel-origin-gallery figure{margin:0;min-width:0;position:relative;text-align:center}
.cel-origin-gallery figure:before{content:'';width:10px;height:10px;border-radius:50%;background:#e98932;display:block;margin:0 auto 16px;box-shadow:0 0 0 4px #fcf7ef}
.cel-origin-gallery:before{content:'';height:1px;background:#dfb584;position:absolute;top:5px;left:5%;right:5%}
.cel-origin-snapshot{background:#fffdfa;border:1px solid #ead5bb;padding:8px;box-shadow:0 5px 15px #63361918;transform:rotate(-2deg);transition:transform .25s;aspect-ratio:4/5;display:flex;align-items:center;justify-content:center}
.cel-origin-gallery figure:nth-child(even) .cel-origin-snapshot{transform:rotate(2deg)}
.cel-origin-snapshot img{width:100%;height:100%;object-fit:contain}
.cel-origin-gallery figcaption{font-weight:700;font-size:14px;color:#a76129;margin-top:14px}
.cel-origin-learning{display:grid;grid-template-columns:1fr 1fr;gap:35px;padding:35px 0;border-top:1px solid #ead5bb}
.cel-origin-end{background:#f3e4d0;border-radius:20px;padding:35px;text-align:center;margin-top:25px}
.cel-origin-end p{max-width:650px;margin:0 auto 20px}
.cel-origin-cta{display:inline-block;background:#e98932;color:white!important;border-radius:25px;padding:12px 23px;text-decoration:none;font-weight:700}
@media(hover:hover){.cel-origin-gallery .cel-origin-snapshot:hover{transform:rotate(0) translateY(-4px)}}
@media(max-width:750px){.cel-origin-hero,.cel-origin-learning{grid-template-columns:1fr}.cel-origin-cover{max-width:260px}.cel-origin-gallery:before{display:none}.cel-origin-timeline{margin:45px 0}.cel-origin-editorial{margin-top:30px!important}.cel-origin-end{padding:24px}}
</style></head>
<body <?php body_class('cel-content-page cel-origin-page'); ?>><?php wp_body_open();require __DIR__.'/interior-header.php'; ?>
<main class="cel-origin-editorial">
<section class="cel-origin-hero<?php echo $cover?'':' no-cover'; ?>">
<?php if($cover): ?><figure class="cel-origin-cover"><?php $render_image($cover); ?><figcaption><?php echo esc_html($cover['caption']?:'Les premières illustrations à l’aquarelle'); ?></figcaption></figure><?php endif; ?>
<div><span class="cel-origin-kicker">Les origines du renardeau</span><h1>Il était une fois… Célestin</h1>
<p><strong>Tout a commencé avec ma passion pour l’aquarelle.</strong></p>
<p>Un jour, en panne d’inspiration, je cherchais quelque chose à dessiner. Un soir, alors que mon petit bout de chou n’avait que quelques mois, j’ai eu envie de créer un personnage qui le représenterait. Mais pas un petit garçon humain : un petit renard. Le renard avait toujours été le thème de sa chambre, alors le choix s’est imposé naturellement.</p>
<p>Je voulais imaginer des histoires pour enfants autour d’un personnage inspiré de mon fils, alors âgé de seulement quatre mois.</p>
<p>Sur un carnet, j’ai commencé par le dessiner, puis à l’imaginer, à lui donner une personnalité. J’ai écrit quelques lignes, puis je l’ai peint à l’aquarelle.</p>
<blockquote class="cel-origin-quote">Et une première histoire est née dans ma tête… puis sur le papier.</blockquote></div>
</section>
<?php if($images): ?><section class="cel-origin-timeline"><span class="cel-origin-kicker" style="display:block;text-align:center">Au fil des dessins</span><h2>Du premier croquis au personnage d’aujourd’hui</h2><p>Les différentes étapes de la création de Célestin, à travers mes illustrations.</p><div class="cel-origin-gallery">
<?php foreach($images as $img): ?><figure><div class="cel-origin-snapshot"><?php $render_image($img); ?></div><?php if($img['caption']): ?><figcaption><?php echo esc_html($img['caption']); ?></figcaption><?php endif; ?></figure><?php endforeach; ?>
</div></section><?php endif; ?>
<section class="cel-origin-learning">
<div><span class="cel-origin-kicker">Les premiers défis</span><h2>De l’aquarelle au numérique</h2><p>J’en ai ensuite imaginé d’autres, avec de nouveaux personnages et de nouvelles aventures. Mais un problème est rapidement apparu : la régularité.</p>
<p>Redessiner encore et encore le même personnage sans le déformer, sans modifier la forme d’un œil, la taille d’une oreille ou les proportions du visage… c’était loin d’être évident.</p>
<p>C’est à ce moment-là que le dessin numérique m’a énormément aidée. Il m’a permis de construire le personnage plus précisément, de travailler ses proportions et de comprendre ce qui n’allait pas : une tête trop petite, des bras trop longs, des jambes mal proportionnées…</p></div>
<div><span class="cel-origin-kicker">Un apprentissage permanent</span><h2>Apprendre, dessiner, recommencer</h2><p>Mais créer un personnage que l’on va devoir redessiner des milliers et des milliers de fois au fil des histoires demande d’apprendre.</p>
<p>Alors j’ai cherché, j’ai lu des livres de dessin, observé, testé, recommencé. J’ai appris à construire un personnage, à comprendre ses proportions et surtout à créer une base suffisamment solide pour que Célestin puisse rester Célestin, quelle que soit l’histoire dans laquelle il apparaît.</p></div>
</section>
<section class="cel-origin-end"><h2>Bien plus qu’un petit renard</h2><p>Petit à petit, le simple dessin d’un petit renard est devenu un véritable personnage, avec son univers, ses aventures et son identité.</p><a class="cel-origin-cta" href="<?php echo esc_url(Celestin_Home::url('cel_livre')); ?>">Découvrir ses aventures →</a></section>
</main><?php require __DIR__.'/shared-footer.php';wp_footer(); ?></body></html>