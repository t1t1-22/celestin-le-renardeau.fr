<?php
/**
 * Plugin Name: Célestin — Site et contenus
 * Description: Accueil, univers, histoires, livres, activités, personnages et lieux pour Astra.
 * Version: 1.0.0
 */
defined('ABSPATH') || exit;

add_action('init', function () {
    $types = [
        'cel_livre'=>['Livres','Livre','dashicons-book-alt','livres'],
        'cel_histoire'=>['Mini-histoires','Mini-histoire','dashicons-controls-volumeon','histoires'],
        'cel_activite'=>['Mes petites activités','Activité','dashicons-art','activites'],
        'cel_personnage'=>['Personnages','Personnage','dashicons-groups','personnages'],
        'cel_lieu'=>['Lieux de Fleuriville','Lieu','dashicons-location','lieux'],
    ];
    foreach ($types as $key=>$t) {
        register_post_type($key, [
            'labels'=>['name'=>$t[0], 'singular_name'=>$t[1], 'add_new_item'=>'Ajouter : '.$t[1], 'edit_item'=>'Modifier : '.$t[1]],
            'public'=>true,'show_in_rest'=>true,'show_in_menu'=>true,
            'menu_icon'=>$t[2],'supports'=>['title','editor','thumbnail','excerpt','revisions'],
            'has_archive'=>true,'rewrite'=>['slug'=>$t[3]]
        ]);
    }
    register_taxonomy('cel_theme',['cel_livre','cel_histoire','cel_activite'],[
        'labels'=>['name'=>'Thèmes et événements'],'public'=>true,'show_in_rest'=>true,'hierarchical'=>true,
        'rewrite'=>['slug'=>'theme']
    ]);
    register_taxonomy('cel_mois',['cel_livre','cel_histoire','cel_activite'],[
        'labels'=>['name'=>'Mois de l’année'],'public'=>true,'show_in_rest'=>true,'hierarchical'=>true,
        'rewrite'=>['slug'=>'mois']
    ]);
    register_taxonomy('cel_generation',['cel_personnage','cel_livre','cel_histoire'],[
        'labels'=>['name'=>'Générations'],'public'=>true,'show_in_rest'=>true,'hierarchical'=>true,
        'rewrite'=>['slug'=>'generation']
    ]);
});

add_action('admin_init', function() {
    if (get_option('cel_site_initialized')) return;
    $pages = [
        'accueil'=>['Accueil','Bienvenue dans l’univers de Célestin le renardeau.'],
        'fleuriville'=>['Fleuriville','Découvrez les habitants et les lieux de Fleuriville.'],
        'notre-histoire'=>['Notre histoire','L’histoire du personnage et du projet.'],
        'rejoins-l-aventure'=>['Rejoins l’aventure','Rejoins l’aventure et découvre les nouveautés de Célestin.'],
        'contact'=>['Nous contacter','Vous pouvez nous contacter ici.']
    ];
    foreach ($pages as $slug=>$info) {
        $page=get_page_by_path($slug);
        if (!$page) {
            $id=wp_insert_post(['post_type'=>'page','post_title'=>$info[0],'post_name'=>$slug,
                'post_content'=>$info[1],'post_status'=>'publish']);
        } else $id=$page->ID;
        if ($slug==='accueil' && !get_option('page_on_front') && !is_wp_error($id)) {
            update_option('show_on_front','page');
            update_option('page_on_front',$id);
        }
    }
    if (!term_exists('Génération 1','cel_generation')) wp_insert_term('Génération 1','cel_generation');
    if (!term_exists('Génération 2','cel_generation')) wp_insert_term('Génération 2','cel_generation');
    foreach (range(1,12) as $i) {
        $date=DateTimeImmutable::createFromFormat('!m',sprintf('%02d',$i));
        if ($date) {
            $months=['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
            if (!term_exists($months[$i-1],'cel_mois')) wp_insert_term($months[$i-1],'cel_mois',['slug'=>sprintf('%02d',$i)]);
        }
    }
    update_option('cel_site_initialized',1);
    flush_rewrite_rules(false);
});
function cel_setting($key,$fallback='') {
    $settings=get_option('cel_site_settings',[]);
    return isset($settings[$key]) && $settings[$key]!=='' ? $settings[$key] : $fallback;
}
function cel_asset($key,$fallback='') {
    $id=absint(cel_setting($key));
    return $id ? wp_get_attachment_image_url($id,'full') : $fallback;
}
function cel_url($path) { return esc_url(home_url('/'.$path.'/')); }
add_action('admin_menu',function(){
    add_menu_page('Site Célestin','Site Célestin','manage_options','celestin-site','cel_admin_settings','dashicons-admin-appearance',3);
});
add_action('admin_enqueue_scripts',function($hook) {
    if ($hook!=='toplevel_page_celestin-site') return;
    wp_enqueue_media();
    wp_add_inline_script('jquery-core', "jQuery(function($){$(document).on('click','.cel-select-media',function(e){e.preventDefault();let el=$(this).closest('.cel-image-row');let picker=wp.media({title:'Choisir une illustration',button:{text:'Utiliser cette image'},multiple:false});picker.on('select',function(){let a=picker.state().get('selection').first().toJSON();el.find('input').val(a.id);el.find('img').attr('src',a.url).show();});picker.open();});$(document).on('click','.cel-clear-media',function(e){e.preventDefault();let el=$(this).closest('.cel-image-row');el.find('input').val('');el.find('img').hide();});});");
});
function cel_admin_settings() {
    if (!current_user_can('manage_options')) return;
    if (isset($_POST['cel_save']) && check_admin_referer('cel_save_site')) {
        $old=get_option('cel_site_settings',[]);
        foreach (['logo','hero','fleuriville','footer_left','footer_right'] as $key) $old[$key]=absint($_POST['cel_'.$key]??0);
        foreach (['headline','button','intro','footer_intro'] as $key) $old[$key]=sanitize_text_field(wp_unslash($_POST['cel_'.$key]??''));
        update_option('cel_site_settings',$old);
        echo '<div class="notice notice-success"><p>Modifications enregistrées.</p></div>';
    }
    $imgs=['logo'=>'Logo original (transparent)','hero'=>'Illustration panoramique du slide (sans textes)','fleuriville'=>'Illustration du village (optionnelle)','footer_left'=>'Bas de page — village à gauche','footer_right'=>'Bas de page — Célestin et Octet à droite'];
    echo '<div class="wrap"><h1>Site Célestin — personnalisation</h1><p>Les images restent modifiables dans la médiathèque. Ne publiez que vos dessins originaux lorsque le site sera ouvert au public.</p><form method="post">';
    wp_nonce_field('cel_save_site');
    foreach ($imgs as $key=>$label) {
        $id=absint(cel_setting($key)); $src=$id?wp_get_attachment_image_url($id,'thumbnail'):'';
        echo '<div class="cel-image-row" style="padding:12px 0;border-bottom:1px solid #ddd"><strong>'.esc_html($label).'</strong><br>';
        echo '<input type="hidden" name="cel_'.esc_attr($key).'" value="'.esc_attr($id).'">';
        echo '<img style="max-width:160px;max-height:100px;margin:10px 16px 0 0;'.(!$src?'display:none':'').'" src="'.esc_url($src).'">';
        echo '<button class="button cel-select-media">Choisir dans la médiathèque</button> <button class="button cel-clear-media">Retirer</button></div>';
    }
    $texts=['headline'=>['Titre du slide','Pars à l’aventure à Fleuriville !'],
        'button'=>['Texte du bouton','Découvrir l’univers'],
        'intro'=>['Texte du bandeau Fleuriville','Un petit coin de nature plein de charme où Célestin vit de grandes et de petites aventures avec ses amis.'],
        'footer_intro'=>['Texte au-dessus du footer','Un petit coin de nature plein de charme où Célestin vit de grandes et de petites aventures avec Octet, son fidèle compagnon.']];
    foreach ($texts as $key=>$d) {
        echo '<p><label><strong>'.esc_html($d[0]).'</strong><br><input class="large-text" style="max-width:650px" name="cel_'.esc_attr($key).'" value="'.esc_attr(cel_setting($key,$d[1])).'"></label></p>';
    }
    submit_button('Enregistrer','primary','cel_save');
    echo '</form><hr><h2>Contenus</h2><p>Ajoute les livres, histoires, activités, personnages et lieux avec les menus à gauche. Les trois dernières publications apparaissent automatiquement dans « Les nouveautés ».</p></div>';
}
function cel_image_from_post($post) {
    $url=get_the_post_thumbnail_url($post,'large');
    if ($url) return '<img src="'.esc_url($url).'" alt="'.esc_attr(get_the_title($post)).'">';
    return '<div class="cel-noimage"><span>Illustration à venir</span></div>';
}
function cel_front_styles() { ?>
<style>
:root{--cel-orange:#f4a552;--cel-ink:#12120d;--cel-brown:#58240f;--cel-cream:#ffebd6;--cel-earth:#ca773d}
*{box-sizing:border-box}html{scroll-behavior:smooth}body.cel-front{margin:0;background:#fffaf5;color:var(--cel-ink);font:500 16px/1.5 system-ui,-apple-system,"Segoe UI",sans-serif}a{color:inherit;text-decoration:none}
.cel-top{height:106px;background:#fff;display:flex;align-items:center;justify-content:center;position:relative;z-index:2;box-shadow:0 5px 20px #472b1a14}
.cel-logo{display:block;max-height:88px;max-width:250px;object-fit:contain}.cel-logo-text{font-family:Georgia,serif;font-style:italic;font-size:40px;color:var(--cel-brown);line-height:.85;text-align:center}.cel-logo-text small{display:block;font-size:23px}
.cel-join{position:absolute;right:5%;background:var(--cel-orange);border-radius:28px;padding:11px 24px;box-shadow:0 4px 0 #ca773d;color:var(--cel-ink);font-weight:800}
.cel-hero{min-height:470px;position:relative;display:grid;place-items:center;overflow:hidden;background:linear-gradient(180deg,#bbe8ff 0%,#e6f7d9 70%,#70ae65 100%);border-radius:0 0 45px 45px}
.cel-hero:before{content:"";position:absolute;inset:0;background-image:var(--cel-hero);background-position:center;background-size:cover}
.cel-hero-text{position:relative;max-width:520px;margin:0 0 25px;z-index:1;text-align:center;padding:25px;border-radius:22px;background:#fff9efdf;box-shadow:0 6px 30px #40220916}
.cel-hero h1{font-family:Georgia,serif;font-size:clamp(32px,4vw,58px);line-height:1.1;color:var(--cel-brown);margin:0 0 25px}.cel-btn{display:inline-block;border:0;border-radius:60px;background:linear-gradient(130deg,#ffc067,#ee8f25);color:#271307;font-weight:800;padding:13px 24px;box-shadow:0 4px 0 #c66e27}
.cel-menu{position:relative;z-index:2;max-width:1000px;margin:-27px auto 0;padding:14px 20px;display:grid;grid-template-columns:repeat(4,1fr);border:2px solid #f4d7b0;background:#fff8ef;border-radius:60px;box-shadow:0 8px 22px #7d431927}
.cel-menu a{padding:8px 10px;text-align:center;font-weight:800;color:var(--cel-brown);font-size:16px;border-right:1px solid #e9bc8a}.cel-menu a:last-child{border-right:0}.cel-menu a:hover{color:#b75117}
.cel-main{max-width:1100px;margin:64px auto 80px;padding:0 22px}.cel-main h2{text-align:center;color:var(--cel-brown);font-family:Georgia,serif;font-size:clamp(29px,3vw,40px);margin:0 0 30px}
.cel-cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:22px}.cel-card{background:#fff;border:1px solid #f3d8be;border-radius:22px;box-shadow:0 8px 18px #7c4e2011;overflow:hidden;display:block;position:relative}.cel-card img{width:100%;aspect-ratio:4/3;object-fit:cover;display:block}.cel-noimage{aspect-ratio:4/3;display:grid;place-items:center;background:#ffebd6;color:#a65c37;font-weight:700}.cel-card-body{padding:14px 18px 22px}.cel-card-body strong{display:block;color:var(--cel-brown);font-size:20px}.cel-card-body p{margin:3px 0 0}.cel-type{font-size:12px;font-weight:800;display:inline-block;background:#fff1df;padding:5px 12px;border-radius:18px;margin-bottom:8px}
.cel-town{background:#ffebd6;border-radius:45px;display:grid;grid-template-columns:38% auto;gap:25px;align-items:center;overflow:hidden;margin:65px 0 0;min-height:260px}.cel-town img{width:100%;height:100%;min-height:260px;object-fit:cover}.cel-town-blank{background:linear-gradient(135deg,#cbe8ce,#fff2c7);height:100%;min-height:260px}.cel-town-body{padding:28px 30px 28px 0}.cel-town h2{text-align:left;margin:0 0 10px}.cel-town p{max-width:520px}
.cel-bottom-scene{max-width:1300px;margin:0 auto;min-height:205px;display:grid;grid-template-columns:1fr 1.1fr 1fr;align-items:end;gap:15px;text-align:center}.cel-bottom-scene img{width:100%;height:210px;object-fit:contain;object-position:bottom}.cel-bottom-scene h2{font-family:Georgia,serif;color:var(--cel-brown);font-size:30px;line-height:1.1}.cel-bottom-scene p{font-size:14px}.cel-bottom-scene .cel-btn{padding:9px 20px}
.cel-footer{background:var(--cel-orange);margin-top:-3px;padding:48px max(25px,calc((100% - 1170px)/2)) 15px;clip-path:polygon(0 4%,13% 0,33% 4%,53% 0,70% 4%,87% 0,100% 3%,100% 100%,0 100%)}
.cel-footgrid{display:grid;grid-template-columns:1.3fr 1fr 1fr 1.7fr;gap:22px}.cel-footgrid>div{padding:0 12px;border-right:1px solid #ffffff80}.cel-footgrid>div:last-child{border:0}.cel-footgrid h3{margin:0 0 10px;font-size:17px}.cel-footgrid a{display:block;font-size:14px}.cel-footgrid p{font-size:14px}.cel-email{width:100%;padding:11px;border:0;border-radius:25px}.cel-legal{text-align:center;border-top:1px solid #ffffff80;padding:15px 5px;margin-top:30px;font-size:12px}
@media(max-width:800px){.cel-top{height:84px}.cel-logo{max-width:180px;max-height:70px}.cel-join{right:10px;font-size:11px;padding:9px}.cel-hero{min-height:410px}.cel-menu{margin:-17px 15px 0;border-radius:24px;grid-template-columns:repeat(2,1fr)}.cel-menu a:nth-child(2){border:0}.cel-menu a{font-size:14px}.cel-cards{grid-template-columns:1fr}.cel-town{grid-template-columns:1fr;gap:0}.cel-town img,.cel-town-blank{height:220px}.cel-town-body{padding:20px}.cel-bottom-scene{grid-template-columns:1fr}.cel-bottom-scene img{height:130px}.cel-footgrid{grid-template-columns:repeat(2,1fr)}}
</style>
<?php }
function cel_latest_cards() {
    $posts=get_posts(['post_type'=>['cel_histoire','cel_livre','cel_activite'],'post_status'=>'publish','numberposts'=>3,'orderby'=>'date','order'=>'DESC']);
    if (!$posts) {
        echo '<div class="cel-cards">';
        foreach (['Mini-histoire','Livre','Activité'] as $kind) {
            echo '<article class="cel-card"><div class="cel-noimage"><span>Votre illustration ici</span></div><div class="cel-card-body"><span class="cel-type">'.esc_html($kind).'</span><strong>À découvrir bientôt</strong><p>Ajoutez votre premier contenu dans WordPress.</p></div></article>';
        }
        echo '</div>'; return;
    }
    echo '<div class="cel-cards">';
    foreach($posts as $post) {
        $type=['cel_livre'=>'Livre','cel_histoire'=>'Mini-histoire','cel_activite'=>'Activité'][$post->post_type]??'Nouveauté';
        echo '<a class="cel-card" href="'.esc_url(get_permalink($post)).'">'.cel_image_from_post($post).'<div class="cel-card-body"><span class="cel-type">'.esc_html($type).'</span><strong>'.esc_html(get_the_title($post)).'</strong><p>'.esc_html(wp_trim_words(get_the_excerpt($post),15)).'</p></div></a>';
    }
    echo '</div>';
}
function cel_front_page() {
    $logo=cel_asset('logo');
    $hero=cel_asset('hero');
    $town=cel_asset('fleuriville');
    $left=cel_asset('footer_left');
    $right=cel_asset('footer_right');
    ?><!doctype html><html <?php language_attributes();?>><head><meta charset="<?php bloginfo('charset');?>"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?php bloginfo('name');?></title><?php wp_head();cel_front_styles();?></head>
    <body <?php body_class('cel-front');?>>
    <?php wp_body_open();?>
    <header class="cel-top"><a href="<?php echo esc_url(home_url('/')); ?>" aria-label="Célestin le renardeau"><?php if($logo):?><img class="cel-logo" src="<?php echo esc_url($logo);?>" alt="Célestin le renardeau"><?php else:?><span class="cel-logo-text">Célestin<small>le renardeau</small></span><?php endif;?></a><a class="cel-join" href="<?php echo cel_url('rejoins-l-aventure');?>">★ Rejoins l’aventure !</a></header>
    <section class="cel-hero" <?php if($hero):?>style="--cel-hero:url('<?php echo esc_url($hero);?>')"<?php endif;?>><div class="cel-hero-text"><h1><?php echo esc_html(cel_setting('headline','Pars à l’aventure à Fleuriville !'));?></h1><a class="cel-btn" href="<?php echo cel_url('fleuriville');?>"><?php echo esc_html(cel_setting('button','Découvrir l’univers'));?> →</a></div></section>
    <nav class="cel-menu" aria-label="Découvrir Célestin"><a href="<?php echo esc_url(get_post_type_archive_link('cel_personnage'));?>">Les personnages</a><a href="<?php echo esc_url(get_post_type_archive_link('cel_activite'));?>">Mes petites activités</a><a href="<?php echo esc_url(get_post_type_archive_link('cel_livre'));?>">Les livres</a><a href="<?php echo esc_url(get_post_type_archive_link('cel_histoire'));?>">Les histoires</a></nav>
    <main class="cel-main"><section><h2>Les nouveautés</h2><?php cel_latest_cards();?></section>
    <section class="cel-town"><?php if($town):?><img src="<?php echo esc_url($town);?>" alt="Fleuriville"><?php else:?><div class="cel-town-blank"></div><?php endif;?><div class="cel-town-body"><h2>Bienvenue à Fleuriville !</h2><p><?php echo esc_html(cel_setting('intro','Un petit coin de nature plein de charme où Célestin vit de grandes et de petites aventures avec ses amis.'));?></p><a class="cel-btn" href="<?php echo cel_url('fleuriville');?>">Découvrir Fleuriville →</a></div></section></main>
    <div class="cel-bottom-scene"><div><?php if($left):?><img src="<?php echo esc_url($left);?>" alt="Paysage de Fleuriville"><?php endif;?></div><div><h2>Bienvenue à Fleuriville !</h2><p><?php echo esc_html(cel_setting('footer_intro','Un petit coin de nature plein de charme où Célestin vit de grandes et de petites aventures avec Octet, son fidèle compagnon.'));?></p><a class="cel-btn" href="<?php echo cel_url('fleuriville');?>">Découvrir Fleuriville →</a></div><div><?php if($right):?><img src="<?php echo esc_url($right);?>" alt="Célestin et Octet"><?php endif;?></div></div>
    <footer class="cel-footer"><div class="cel-footgrid"><div><?php if($logo):?><img class="cel-logo" src="<?php echo esc_url($logo);?>" alt="Célestin le renardeau"><?php else:?><span class="cel-logo-text">Célestin<small>le renardeau</small></span><?php endif;?></div><div><h3>Explorer</h3><a href="<?php echo esc_url(home_url('/'));?>">Accueil</a><a href="<?php echo esc_url(get_post_type_archive_link('cel_activite'));?>">Activités</a><a href="<?php echo esc_url(get_post_type_archive_link('cel_livre'));?>">Livres</a><a href="<?php echo esc_url(get_post_type_archive_link('cel_histoire'));?>">Histoires</a></div><div><h3>À propos</h3><a href="<?php echo cel_url('fleuriville');?>">Fleuriville</a><a href="<?php echo cel_url('notre-histoire');?>">Notre histoire</a><a href="<?php echo cel_url('contact');?>">Nous contacter</a></div><div><h3>La newsletter de Fleuriville</h3><p>Recevez les nouveautés, les histoires et les coloriages exclusifs.</p><p>Inscription disponible prochainement.</p></div></div><div class="cel-legal">© <?php echo esc_html(date('Y'));?> Célestin le renardeau · Mentions légales · Politique de confidentialité</div></footer>
    <?php wp_footer();?></body></html><?php
}
add_action('template_redirect', function() {
    if (is_front_page() && !is_admin() && !is_feed() && !is_preview() && !is_customize_preview()) {
        status_header(200); cel_front_page(); exit;
    }
},1);
