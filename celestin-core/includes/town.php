<?php
if(!defined('ABSPATH'))exit;
final class Celestin_Town {
 static function init(){
  add_action('add_meta_boxes', [__CLASS__,'boxes']);
  add_action('save_post',[__CLASS__,'save'],30,2);
  add_action('admin_menu',[__CLASS__,'menu']);
  add_action('admin_post_cel_town_settings',[__CLASS__,'save_settings']);
 }
 static function menu(){add_submenu_page('celestin-core','Fleuriville','Fleuriville','manage_options','cel-town',[__CLASS__,'settings']);}
 static function settings(){
  if(!current_user_can('manage_options'))return;
  $o=get_option('cel_town',[]);
  ?><div class="wrap"><h1>Présentation de Fleuriville</h1><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="cel_town_settings"><?php wp_nonce_field('cel_town_settings'); ?>
  <p><label>Texte de présentation<br><textarea name="intro" rows="6" class="large-text"><?php echo esc_textarea($o['intro']??''); ?></textarea></label></p>
  <p><label>URL de l’illustration panoramique de la ville<br><input type="url" name="hero" class="large-text" value="<?php echo esc_attr($o['hero']??''); ?>"></label></p>
  <p><label>URL du plan illustré de Fleuriville<br><input type="url" name="map" class="large-text" value="<?php echo esc_attr($o['map']??''); ?>"></label></p>
  <p>Importe tes illustrations dans la médiathèque puis colle leurs URL. Dans chaque fiche lieu, indique sa position sur le plan en pourcentage.</p><?php submit_button(); ?></form></div><?php
 }
 static function save_settings(){
  if(!current_user_can('manage_options'))wp_die('Accès refusé');
  check_admin_referer('cel_town_settings');
  update_option('cel_town',['intro'=>sanitize_textarea_field(wp_unslash($_POST['intro']??'')),'hero'=>esc_url_raw(wp_unslash($_POST['hero']??'')),'map'=>esc_url_raw(wp_unslash($_POST['map']??''))]);
  wp_safe_redirect(admin_url('admin.php?page=cel-town'));exit;
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
  if(!empty($o['map']))echo '<img id="cel-map-picker" style="max-width:100%;cursor:crosshair" src="'.esc_url($o['map']).'" alt="Plan de Fleuriville">';
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
  if(!empty($o['map'])){
   echo '<div class="cel-town-map"><img src="'.esc_url($o['map']).'" alt="Plan illustré de Fleuriville">';
   foreach($places as $p){$x=get_post_meta($p->ID,'_cel_map_x',true);$y=get_post_meta($p->ID,'_cel_map_y',true);if($x===''||$y==='')continue;echo '<button class="cel-town-pin" type="button" style="left:'.esc_attr($x).'%;top:'.esc_attr($y).'%;" data-place="'.esc_attr($p->ID).'" aria-label="'.esc_attr(get_the_title($p)).'">✿</button>';}
   echo '</div>';
  }else echo '<p class="cel-town-map-note">Le plan illustré de Fleuriville sera bientôt disponible. En attendant, découvre les lieux ci-dessous.</p>';
  echo '<div class="cel-town-place-list">';
  foreach($places as $p){echo '<article class="cel-town-place" id="cel-place-'.absint($p->ID).'">'.Celestin_Public::illustration($p->ID,'medium').'<div><h3>'.esc_html(get_the_title($p)).'</h3><p>'.esc_html(wp_trim_words(Celestin_Fields::value($p->ID,'resume')?:Celestin_Fields::value($p->ID,'histoire'),40)).'</p><a href="'.esc_url(get_permalink($p)).'">Découvrir ce lieu →</a></div></article>';}
  echo '</div><script>document.querySelectorAll(".cel-town-pin").forEach(b=>b.addEventListener("click",()=>{const a=document.getElementById("cel-place-"+b.dataset.place);document.querySelectorAll(".cel-town-place").forEach(p=>p.classList.remove("is-selected"));a?.classList.add("is-selected");a?.scrollIntoView({behavior:"smooth",block:"center"})}))</script>';
 }
}
Celestin_Town::init();
