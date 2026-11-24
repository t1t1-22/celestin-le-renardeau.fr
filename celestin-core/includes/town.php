<?php
if(!defined('ABSPATH'))exit;
require_once __DIR__.'/town-vector.php';
final class Celestin_Town {
 static function init(){
  add_action('add_meta_boxes', [__CLASS__,'boxes']);
  add_action('save_post',[__CLASS__,'save'],30,2);
  add_action('admin_menu',[__CLASS__,'menu'],20);
  add_action('admin_enqueue_scripts',static function($hook){if(isset($_GET['page'])&&$_GET['page']==='cel-town')wp_enqueue_media();});
  add_action('admin_post_cel_town_settings',[__CLASS__,'save_settings']);
 }
 static function menu(){add_submenu_page('celestin-core','Fleuriville','Fleuriville','manage_options','cel-town',[__CLASS__,'settings']);}
 static function settings(){
  if(!current_user_can('manage_options'))return;
  $o=get_option('cel_town',[]);
  ?><div class="wrap cel-town-admin"><style>
  .cel-town-admin{color:#633619;max-width:1250px}.cel-town-admin h1{font-size:30px;font-weight:800;color:#633619}.cel-town-admin .town-panel{background:#fffdfa;border:1px solid #ead5bb;border-radius:16px;padding:24px;margin:16px 0}.cel-town-admin .town-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.cel-town-admin label{font-weight:700;display:block;margin-bottom:10px}.cel-town-admin textarea,.cel-town-admin input{border:1px solid #dec3a7;border-radius:8px;padding:10px}.cel-town-admin .town-preview{width:100%;height:220px;object-fit:contain;background:#fff4e6;border-radius:10px;display:block;margin:10px 0}.cel-town-admin .town-preview[hidden]{display:none}.cel-town-admin .button-primary{background:#e98932;border-color:#d47b29;border-radius:20px;color:white;padding:4px 18px}.cel-town-admin .button{margin:5px 5px 5px 0}@media(max-width:780px){.cel-town-admin .town-grid{grid-template-columns:1fr}}
  </style><h1>Fleuriville</h1><p>Personnalise la présentation de la ville et son plan illustré.</p>
  <?php if(isset($_GET['updated'])): ?><div class="notice notice-success inline"><p>Modifications enregistrées.</p></div><?php endif; ?>
  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="cel_town_settings"><?php wp_nonce_field('cel_town_settings'); ?>
  <div class="town-panel"><h2>Bienvenue à Fleuriville</h2><label for="cel-town-intro">Texte de présentation</label><textarea id="cel-town-intro" name="intro" rows="5" class="large-text"><?php echo esc_textarea($o['intro']??''); ?></textarea></div>
  <div class="town-grid"><?php foreach(['hero'=>'Illustration panoramique de la ville','map'=>'Plan illustré de Fleuriville'] as $key=>$label): ?><div class="town-panel town-media"><h2><?php echo esc_html($label); ?></h2><img class="town-preview" alt="" src="<?php echo esc_url($o[$key]??''); ?>" <?php if(empty($o[$key]))echo 'hidden'; ?>><label for="cel-town-<?php echo esc_attr($key); ?>">Image</label><input id="cel-town-<?php echo esc_attr($key); ?>" type="url" name="<?php echo esc_attr($key); ?>" class="large-text" value="<?php echo esc_attr($o[$key]??''); ?>"><button type="button" class="button town-pick">Choisir une illustration</button><button type="button" class="button town-remove">Retirer</button></div><?php endforeach; ?></div>
  <div class="town-panel"><h2>Les lieux de Fleuriville</h2><p>Ajoute les lieux et positionne-les directement sur le plan depuis leurs fiches.</p><a class="button" href="<?php echo esc_url(admin_url('edit.php?post_type=cel_lieu')); ?>">Gérer les lieux</a><a class="button" href="<?php echo esc_url(admin_url('post-new.php?post_type=cel_lieu')); ?>">Ajouter un lieu</a></div><?php submit_button('Enregistrer les modifications'); ?></form></div>
  <script>document.addEventListener("DOMContentLoaded",()=>{document.querySelectorAll(".town-media").forEach(box=>{const input=box.querySelector("input"),img=box.querySelector("img");function sync(){img.src=input.value;img.hidden=!input.value}input.addEventListener("change",sync);box.querySelector(".town-remove").onclick=()=>{input.value="";sync()};box.querySelector(".town-pick").onclick=()=>{const picker=wp.media({title:"Choisir une illustration",library:{type:"image"},multiple:false});picker.on("select",()=>{input.value=picker.state().get("selection").first().toJSON().url;sync()});picker.open()}})});</script><?php
 }
 static function save_settings(){
  if(!current_user_can('manage_options'))wp_die('Accès refusé');
  check_admin_referer('cel_town_settings');
  update_option('cel_town',['intro'=>sanitize_textarea_field(wp_unslash($_POST['intro']??'')),'hero'=>esc_url_raw(wp_unslash($_POST['hero']??'')),'map'=>esc_url_raw(wp_unslash($_POST['map']??''))]);
  wp_safe_redirect(admin_url('admin.php?page=cel-town&updated=1'));exit;
 }
 static function boxes(){
  add_meta_box('cel_character_album','Album photo du personnage',[__CLASS__,'album_box'],'cel_personnage','normal','default');
  add_meta_box('cel_place_position','Position sur la carte de Fleuriville',[__CLASS__,'position_box'],'cel_lieu','normal','default');
 }
 static function album($id){$a=get_post_meta($id,'_cel_character_album',true);return is_array($a)?$a:[];}
 static function album_box($p){
  wp_enqueue_media();wp_nonce_field('cel_album_save','cel_album_nonce');
  echo '<p>Ajoute autant de photos que tu le souhaites, avec un titre et une description.</p><div id="cel-album-rows">';
  foreach(self::album($p->ID) as $i=>$a)self::album_row($i,$a);
  echo '</div><button type="button" class="button button-primary" id="cel-album-add">+ Ajouter une photo</button><template id="cel-album-template">';
  self::album_row('INDEX',[]);echo '</template>';
  ?><script>
  (()=>{const rows=document.getElementById('cel-album-rows');let i=Date.now();document.getElementById('cel-album-add').onclick=()=>{rows.insertAdjacentHTML('beforeend',document.getElementById('cel-album-template').innerHTML.replaceAll('INDEX',String(i++)))};rows.addEventListener('click',e=>{const row=e.target.closest('.cel-album-row');if(!row)return;if(e.target.matches('.cel-album-remove'))row.remove();if(e.target.matches('.cel-album-pick')){const media=wp.media({title:'Choisir une illustration',library:{type:'image'},multiple:false});media.on('select',()=>{const a=media.state().get('selection').first().toJSON();row.querySelector('input[type=hidden]').value=a.id;const im=row.querySelector('img');im.src=a.url;im.hidden=false});media.open()}})})();
  </script><?php
 }
 static function album_row($i,$a){
  $id=absint($a['image']??0);$n='cel_album['.$i.']';
  ?><div class="cel-album-row" style="display:grid;grid-template-columns:120px 1fr;gap:15px;padding:14px;border:1px solid #ead5bb;border-radius:10px;margin-bottom:12px;background:#fffaf4"><div><img style="width:100%;aspect-ratio:1;object-fit:contain" src="<?php echo esc_url($id?wp_get_attachment_image_url($id,'thumbnail'):''); ?>" <?php if(!$id)echo 'hidden'; ?>><input type="hidden" name="<?php echo esc_attr($n.'[image]'); ?>" value="<?php echo $id; ?>"><button type="button" class="button cel-album-pick">Choisir l’image</button></div><div><label>Nom de la photo<input class="widefat" name="<?php echo esc_attr($n.'[title]'); ?>" value="<?php echo esc_attr($a['title']??''); ?>"></label><p><label>Description<textarea class="widefat" rows="3" name="<?php echo esc_attr($n.'[description]'); ?>"><?php echo esc_textarea($a['description']??''); ?></textarea></label></p><button type="button" class="button cel-album-remove">Retirer</button></div></div><?php
 }
 static function position_box($p){
  wp_nonce_field('cel_position_save','cel_position_nonce');
  $o=get_option('cel_town',[]);
  echo '<p>Coordonnées en pourcentage depuis le bord gauche et le bord supérieur. Clique sur le plan pour placer le lieu.</p>';
  echo '<div style="max-width:950px;cursor:crosshair">'.Celestin_Town_Vector::render('cel-map-picker').'</div>';
  foreach(['x'=>'Horizontal (%)','y'=>'Vertical (%)'] as $k=>$label)echo '<p><label>'.esc_html($label).' <input id="cel-pos-'.$k.'" name="cel_pos_'.$k.'" type="number" min="0" max="100" step="0.1" value="'.esc_attr(get_post_meta($p->ID,'_cel_map_'.$k,true)).'"></label></p>';
  echo '<script>document.getElementById("cel-map-picker")?.addEventListener("click",e=>{const r=e.currentTarget.getBoundingClientRect();document.getElementById("cel-pos-x").value=((e.clientX-r.left)/r.width*100).toFixed(1);document.getElementById("cel-pos-y").value=((e.clientY-r.top)/r.height*100).toFixed(1)})</script>';
 }
 static function save($id,$p){
  if(wp_is_post_revision($id)||wp_is_post_autosave($id)||!current_user_can('edit_post',$id))return;
  if($p->post_type==='cel_personnage'&&isset($_POST['cel_album_nonce'])&&wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cel_album_nonce'])),'cel_album_save')){
   $out=[];foreach((array)wp_unslash($_POST['cel_album']??[]) as $a){$im=absint($a['image']??0);if($im&&wp_attachment_is_image($im))$out[]=['image'=>$im,'title'=>sanitize_text_field($a['title']??''),'description'=>sanitize_textarea_field($a['description']??'')];}update_post_meta($id,'_cel_character_album',wp_slash($out));
  }
  if($p->post_type==='cel_lieu'&&isset($_POST['cel_position_nonce'])&&wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cel_position_nonce'])),'cel_position_save'))foreach(['x','y'] as $k){$v=$_POST['cel_pos_'.$k]??'';if(is_numeric($v))update_post_meta($id,'_cel_map_'.$k,max(0,min(100,(float)$v)));else delete_post_meta($id,'_cel_map_'.$k);}
 }
 static function photos($id){
  foreach(self::album($id) as $a){
   $url=wp_get_attachment_image_url($a['image'],'full');if(!$url)continue;
   echo '<figure class="cel-town-photo"><a href="'.esc_url($url).'" target="_blank" rel="noopener">'.wp_get_attachment_image($a['image'],'medium_large',false,['loading'=>'lazy']).'</a><figcaption><strong>'.esc_html($a['title']).'</strong><p>'.nl2br(esc_html($a['description'])).'</p><a href="'.esc_url(get_permalink($id)).'">'.esc_html(get_the_title($id)).'</a></figcaption></figure>';
  }
 }
 static function character_album($id){if(!self::album($id))return;echo '<section class="cel-detail-section"><h2>Mon album photo</h2><div class="cel-town-gallery">';self::photos($id);echo '</div></section>';}
 static function gallery(){
  echo '<h1>La galerie photo de Fleuriville</h1><p>Les petits et grands souvenirs de nos habitants.</p><div class="cel-town-gallery">';
  foreach(get_posts(['post_type'=>'cel_personnage','post_status'=>'publish','numberposts'=>-1]) as $p)self::photos($p->ID);
  echo '</div>';
 }
 static function town(){
  $o=get_option('cel_town',[]);$places=get_posts(['post_type'=>'cel_lieu','post_status'=>'publish','numberposts'=>-1,'orderby'=>'title','order'=>'ASC']);
  echo '<div class="cel-town-hero"><span aria-hidden="true">✿ ❀ ✿</span><h1>Bienvenue à Fleuriville !</h1><p>'.nl2br(esc_html(($o['intro']??'')?:'Découvre le petit monde de Célestin, ses habitants et les lieux de ses aventures.')).'</p>';
  if(!empty($o['hero']))echo '<img src="'.esc_url($o['hero']).'" alt="Fleuriville">';echo '</div><h2>Explore Fleuriville</h2>';
  {
   echo '<div class="cel-vector-tools" style="display:flex;gap:8px;align-items:center;margin:12px 0"><button type="button" data-zoom="in" aria-label="Agrandir le plan">+</button><button type="button" data-zoom="out" aria-label="Réduire le plan">−</button><button type="button" data-zoom="reset">Vue entière</button><span>Explore la carte et clique sur les lieux.</span></div><div id="cel-vector-viewport" style="overflow:auto;border-radius:18px;max-height:75vh;overscroll-behavior:contain"><div class="cel-town-map" id="cel-vector-stage" style="width:100%;max-width:none;margin:0;border:0;box-shadow:none;border-radius:0">'.Celestin_Town_Vector::render();
   foreach($places as $p){$x=get_post_meta($p->ID,'_cel_map_x',true);$y=get_post_meta($p->ID,'_cel_map_y',true);if($x===''||$y==='')continue;echo '<button class="cel-town-pin" type="button" style="left:'.esc_attr($x).'%;top:'.esc_attr($y).'%;" data-place="'.esc_attr($p->ID).'" aria-label="'.esc_attr(get_the_title($p)).'">✿</button>';}
   echo '</div></div><style>.cel-vector-tools button{border:1px solid #e0b58b;border-radius:20px;background:#fff8ed;color:#633619;padding:7px 15px;cursor:pointer;font-weight:700}.cel-vector-tools button:hover{background:#e98932;color:white}.cel-vector-tools span{font-size:13px;color:#80532b}@media(prefers-reduced-motion:reduce){#cel-town-vector animate{display:none}}</style><script>(()=>{const stage=document.getElementById("cel-vector-stage"),viewport=document.getElementById("cel-vector-viewport");let z=1;document.querySelectorAll(".cel-vector-tools [data-zoom]").forEach(b=>b.addEventListener("click",()=>{const old=z;z=b.dataset.zoom==="reset"?1:Math.max(1,Math.min(3,z+(b.dataset.zoom==="in"?.35:-.35)));const x=(viewport.scrollLeft+viewport.clientWidth/2)/old,y=(viewport.scrollTop+viewport.clientHeight/2)/old;stage.style.width=(100*z)+"%";viewport.scrollLeft=z===1?0:x*z-viewport.clientWidth/2;viewport.scrollTop=z===1?0:y*z-viewport.clientHeight/2}))})();</script>';
  }
  echo '<div class="cel-town-place-list">';
  foreach($places as $p){echo '<article class="cel-town-place" id="cel-place-'.absint($p->ID).'">'.Celestin_Public::illustration($p->ID,'medium').'<div><h3>'.esc_html(get_the_title($p)).'</h3><p>'.esc_html(wp_trim_words(Celestin_Fields::value($p->ID,'resume')?:Celestin_Fields::value($p->ID,'histoire'),40)).'</p><a href="'.esc_url(get_permalink($p)).'">Découvrir ce lieu →</a></div></article>';}
  echo '</div><script>document.querySelectorAll(".cel-town-pin").forEach(b=>b.addEventListener("click",()=>{const a=document.getElementById("cel-place-"+b.dataset.place);document.querySelectorAll(".cel-town-place").forEach(p=>p.classList.remove("is-selected"));a?.classList.add("is-selected");a?.scrollIntoView({behavior:"smooth",block:"center"})}))</script>';
 }
}
Celestin_Town::init();
