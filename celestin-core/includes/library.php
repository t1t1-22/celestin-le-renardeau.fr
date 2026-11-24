<?php
if(!defined('ABSPATH'))exit;
final class Celestin_Library {
 const TAX=['categorie'=>'cel_collection','collection'=>'cel_book_collection','mois'=>'cel_book_month','saison'=>'cel_book_season','age_lecture'=>'cel_book_age','evenement'=>'cel_book_event'];
 const LABELS=['categorie'=>'Catégories','collection'=>'Collections','mois'=>'Mois','saison'=>'Saisons','age_lecture'=>'Tranches d’âge','evenement'=>'Événements'];
 const SEEDS=['mois'=>['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'],'saison'=>['Printemps','Été','Automne','Hiver'],'age_lecture'=>['0-3','3-6','6-8','8-12'],'evenement'=>['Nouvel An','Épiphanie','Chandeleur','Carnaval','Pâques','1er mai','Fête des mères','Fête des pères','Fête de la musique','Vacances d’été','Rentrée scolaire','Halloween','Saint-Nicolas','Noël']];
 static function init(){
  add_action('init',[__CLASS__,'register'],25);
  add_action('admin_init',[__CLASS__,'migrate']);
  add_action('save_post_cel_livre',[__CLASS__,'save'],30,2);
  add_action('add_meta_boxes_cel_livre',[__CLASS__,'clean'],100);
  add_action('pre_get_posts',[__CLASS__,'filter']);
 }
 static function register(){
  foreach(self::TAX as $key=>$tax)register_taxonomy($tax,['cel_livre'],['labels'=>['name'=>self::LABELS[$key],'singular_name'=>self::LABELS[$key]],'public'=>true,'show_ui'=>true,'show_admin_column'=>false,'show_in_rest'=>true,'show_in_nav_menus'=>true,'hierarchical'=>true,'rewrite'=>['slug'=>'livres-'.$key],'meta_box_cb'=>false]);
 }
 static function term($tax,$name){
  $name=trim($name);if(!$name)return 0;
  $t=term_exists($name,$tax);if(!$t)$t=wp_insert_term($name,$tax);
  return is_wp_error($t)?0:(int)(is_array($t)?$t['term_id']:$t);
 }
 static function migrate(){
  if(!current_user_can('manage_options')||get_option('cel_library_schema')==='2')return;
  foreach(self::SEEDS as $key=>$names)foreach($names as $name)self::term(self::TAX[$key],$name);
  foreach(get_posts(['post_type'=>'cel_livre','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids']) as $id){
   foreach(self::TAX as $key=>$tax){
    $existing=wp_get_object_terms($id,$tax,['fields'=>'ids']);if(is_wp_error($existing)||$existing)continue;
    $old=trim((string)get_post_meta($id,'_cel_f_'.$key,true));if(!$old)continue;
    $names=[$old];
    if($key==='age_lecture'&&preg_match('/^(\d+)\s*[-–]\s*(\d+)(?:\s*ans)?$/u',$old,$m)){
      $names=[];foreach(self::SEEDS['age_lecture'] as $band){[$a,$b]=array_map('intval',explode('-',$band));if($a>=(int)$m[1]&&$b<=(int)$m[2])$names[]=$band;}if(!$names)$names=[$old];
    }
    $ids=array_filter(array_map(fn($n)=>self::term($tax,$n),$names));if($ids)wp_set_object_terms($id,array_values($ids),$tax);
   }
  }
  update_option('cel_library_schema','2');flush_rewrite_rules(false);
 }
 static function clean(){
  foreach(['cel_news_box','cel_home_box','cel_illustration_help','postimagediv','postexcerpt','slugdiv','commentstatusdiv','commentsdiv','trackbacksdiv','authordiv','cel_collectiondiv','tagsdiv-cel_book_tags'] as $box)foreach(['side','normal','advanced'] as $where)remove_meta_box($box,'cel_livre',$where);
 }
 static function choices($id,$key){
  $tax=self::TAX[$key];$terms=get_terms(['taxonomy'=>$tax,'hide_empty'=>false]);$selected=wp_get_object_terms($id,$tax,['fields'=>'ids']);
  if(is_wp_error($terms))$terms=[];if(is_wp_error($selected))$selected=[];
  echo '<div class="cel-field"><label>'.esc_html(self::LABELS[$key]).'</label><div class="cel-lib-choices">';
  foreach($terms as $t)echo '<label><input type="checkbox" name="cel_lib['.esc_attr($key).'][]" value="'.(int)$t->term_id.'"'.checked(in_array((int)$t->term_id,array_map('intval',$selected),true),true,false).'> '.esc_html($t->name).'</label>';
  echo '</div><input type="text" name="cel_lib_new['.esc_attr($key).']" placeholder="'.esc_attr($key==='evenement'?'Créer un nouvel événement':'Ajouter une valeur réutilisable (facultatif)').'"></div>';
 }
 static function identity($post){
  wp_nonce_field('cel_core_save','cel_core_nonce');
  echo '<div class="cel-field-grid cel-library-identity">';
  foreach(self::TAX as $key=>$tax)self::choices($post->ID,$key);
  echo '<div class="cel-field"><label>Visibilité sur le site</label><label><input type="checkbox" name="cel_feature_home" value="1"'.checked(get_post_meta($post->ID,'_cel_feature_home',true),'1',false).'> Mettre en avant sur l’accueil</label></div></div>';
 }
 static function save($id,$post){
  if(!isset($_POST['cel_fields_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cel_fields_nonce'])),'cel_fields_save')||!current_user_can('edit_post',$id)||wp_is_post_revision($id)||(defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE))return;
  foreach(self::TAX as $key=>$tax){
   $ids=array_map('absint',(array)($_POST['cel_lib'][$key]??[]));
   $ids=array_values(array_filter($ids,fn($i)=>term_exists($i,$tax)));
   $new=sanitize_text_field(wp_unslash($_POST['cel_lib_new'][$key]??''));
   if($new&&current_user_can('manage_categories')){$t=self::term($tax,$new);if($t)$ids[]=$t;}
   wp_set_object_terms($id,array_unique($ids),$tax);
  }
 }
 static function display($id,$key){
  $terms=wp_get_object_terms($id,self::TAX[$key],['fields'=>'names']);if(is_wp_error($terms)||!$terms)return (string)get_post_meta($id,'_cel_f_'.$key,true);
  if($key!=='age_lecture')return implode(', ',$terms);
  $ranges=[];$others=[];foreach($terms as $t){if(preg_match('/^(\d+)\s*[-–]\s*(\d+)$/u',$t,$m))$ranges[]=[(int)$m[1],(int)$m[2]];else $others[]=$t;}
  usort($ranges,fn($a,$b)=>$a[0]<=>$b[0]);$merged=[];foreach($ranges as $r){$i=count($merged)-1;if($i>=0&&$r[0]<=$merged[$i][1])$merged[$i][1]=max($merged[$i][1],$r[1]);else $merged[]=$r;}
  return implode(', ',array_merge(array_map(fn($r)=>$r[0].'–'.$r[1].' ans',$merged),$others));
 }
 static function filter($q){
  if(is_admin()||!$q->is_main_query()||!$q->is_post_type_archive('cel_livre'))return;
  $filters=['relation'=>'AND'];foreach(self::TAX as $key=>$tax){$slug=sanitize_title(wp_unslash($_GET['filtre_'.$key]??''));if($slug)$filters[]=['taxonomy'=>$tax,'field'=>'slug','terms'=>[$slug]];}
  if(count($filters)>1)$q->set('tax_query',$filters);
 }
 static function filter_form(){
  echo '<form class="cel-library-filters" method="get" action="'.esc_url(get_post_type_archive_link('cel_livre')).'">';
  foreach(self::TAX as $key=>$tax){$terms=get_terms(['taxonomy'=>$tax,'hide_empty'=>true]);if(is_wp_error($terms)||!$terms)continue;$current=sanitize_title(wp_unslash($_GET['filtre_'.$key]??''));echo '<label>'.esc_html(self::LABELS[$key]).'<select name="'.esc_attr('filtre_'.$key).'"><option value="">Tous</option>';foreach($terms as $t)echo '<option value="'.esc_attr($t->slug).'"'.selected($current,$t->slug,false).'>'.esc_html($t->name).'</option>';echo '</select></label>';}
  echo '<button type="submit">Filtrer</button><a href="'.esc_url(get_post_type_archive_link('cel_livre')).'">Réinitialiser</a></form>';
 }
}
Celestin_Library::init();
