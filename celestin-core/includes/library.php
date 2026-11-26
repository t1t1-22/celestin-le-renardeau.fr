<?php
if(!defined('ABSPATH'))exit;
final class Celestin_Library {
 const TAX=['categorie'=>'cel_collection','collection'=>'cel_book_collection','mois'=>'cel_book_month','saison'=>'cel_book_season','age_lecture'=>'cel_book_age','evenement'=>'cel_book_event'];
 const LABELS=['categorie'=>'Thèmes','collection'=>'Collections','mois'=>'Mois','saison'=>'Saisons','age_lecture'=>'Tranches d’âge','evenement'=>'Événements'];
 const SEEDS=['mois'=>['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'],'saison'=>['Printemps','Été','Automne','Hiver'],'age_lecture'=>['0-3','3-6','6-8','8-12'],'evenement'=>['Nouvel An','Épiphanie','Chandeleur','Carnaval','Pâques','1er mai','Fête des mères','Fête des pères','Fête de la musique','Vacances d’été','Rentrée scolaire','Halloween','Saint-Nicolas','Noël']];
 static function init(){
  add_action('init',[__CLASS__,'register'],25);
  add_action('wp_ajax_cel_library_create_term',[__CLASS__,'create_term']);
  add_action('admin_init',[__CLASS__,'migrate']);
  add_action('save_post_cel_livre',[__CLASS__,'save'],30,2);
  add_action('save_post_cel_histoire',[__CLASS__,'save'],30,2);
  add_action('save_post_cel_coloriage',[__CLASS__,'save'],30,2);
  add_action('add_meta_boxes_cel_histoire',[__CLASS__,'clean_story'],100);
  add_action('add_meta_boxes_cel_livre',[__CLASS__,'clean'],100);
  add_action('pre_get_posts',[__CLASS__,'filter']);
 }
 static function register(){
  foreach(self::TAX as $key=>$tax)register_taxonomy($tax,['cel_livre','cel_histoire','cel_coloriage'],['labels'=>['name'=>self::LABELS[$key],'singular_name'=>self::LABELS[$key]],'public'=>true,'show_ui'=>true,'show_admin_column'=>false,'show_in_rest'=>true,'show_in_nav_menus'=>true,'hierarchical'=>true,'rewrite'=>['slug'=>'livres-'.$key],'meta_box_cb'=>false]);
 }
 static function term($tax,$name){
  $name=trim($name);if(!$name)return 0;
  $t=term_exists($name,$tax);if(!$t)$t=wp_insert_term($name,$tax);
  return is_wp_error($t)?0:(int)(is_array($t)?$t['term_id']:$t);
 }
 static function migrate(){
  if(!current_user_can('manage_options')||get_option('cel_library_schema')==='4')return;
  if(!in_array(get_option('cel_library_schema'),['2','3'],true)){
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
  }
  if(get_option('cel_library_schema')!=='3')foreach(get_posts(['post_type'=>'cel_histoire','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids']) as $id){
   $old=wp_get_object_terms($id,'cel_theme_cel_histoire',['fields'=>'names']);
   if(is_wp_error($old))continue;
   $ids=array_filter(array_map(fn($name)=>self::term(self::TAX['categorie'],$name),$old));
   if($ids)wp_set_object_terms($id,array_values($ids),self::TAX['categorie'],true);
  }
  update_option('cel_library_schema','4');flush_rewrite_rules(false);
 }
 static function clean(){
  foreach(['cel_news_box','cel_home_box','cel_illustration_help','postimagediv','postexcerpt','slugdiv','commentstatusdiv','commentsdiv','trackbacksdiv','authordiv','cel_collectiondiv','tagsdiv-cel_book_tags'] as $box)foreach(['side','normal','advanced'] as $where)remove_meta_box($box,'cel_livre',$where);
 }
 static function clean_story(){
  foreach(['cel_home_box','cel_theme_cel_histoirediv','tagsdiv-cel_tags_cel_histoire','cel_illustration_help','postimagediv','postexcerpt','slugdiv','commentstatusdiv','commentsdiv','trackbacksdiv','authordiv'] as $box)foreach(['side','normal','advanced'] as $where)remove_meta_box($box,'cel_histoire',$where);
 }
 static function create_term(){
  check_ajax_referer('cel_library_term','nonce');
  $key=sanitize_key($_POST['key']??'');
  if(!isset(self::TAX[$key])||!in_array($key,['categorie','collection','evenement'],true))wp_send_json_error('Type non autorisé.',400);
  $tax=self::TAX[$key];$obj=get_taxonomy($tax);
  if(!$obj||!current_user_can($obj->cap->manage_terms))wp_send_json_error('Accès refusé.',403);
  $name=trim(sanitize_text_field(wp_unslash($_POST['name']??'')));
  if(!$name||mb_strlen($name)>150)wp_send_json_error('Indique un nom de 1 à 150 caractères.',400);
  $id=self::term($tax,$name);$t=get_term($id,$tax);
  if(!$id||!$t||is_wp_error($t))wp_send_json_error('Création impossible.',400);
  wp_send_json_success(['id'=>$id,'name'=>$t->name]);
 }
 static function ordered_theme_ids($id): array {
  $ids=wp_get_object_terms($id,self::TAX['categorie'],['fields'=>'ids']);
  if(is_wp_error($ids)||!$ids)return [];
  $ids=array_map('intval',$ids);
  $saved=array_map('absint',(array)get_post_meta($id,'_cel_theme_order',true));
  return array_values(array_unique(array_merge(array_intersect($saved,$ids),$ids)));
 }
 static function ordered_themes($id): array {
  $names=[];
  foreach(self::ordered_theme_ids($id) as $term_id){
   $term=get_term($term_id,self::TAX['categorie']);
   if($term&&!is_wp_error($term))$names[]=$term->name;
  }
  return $names;
 }
 static function choices($id,$key){
  $tax=self::TAX[$key];$terms=get_terms(['taxonomy'=>$tax,'hide_empty'=>false]);$selected=wp_get_object_terms($id,$tax,['fields'=>'ids']);
  if(is_wp_error($terms))$terms=[];if(is_wp_error($selected))$selected=[];
  if(isset(self::SEEDS[$key])&&in_array($key,['mois','saison','age_lecture'],true)){
   $order=array_flip(self::SEEDS[$key]);usort($terms,fn($a,$b)=>($order[$a->name]??999)<=>($order[$b->name]??999));
  }
  $compact=in_array($key,['categorie','collection','evenement'],true);
  echo '<div class="cel-field cel-tax-picker" data-key="'.esc_attr($key).'" data-nonce="'.esc_attr(wp_create_nonce('cel_library_term')).'"><label>'.esc_html(self::LABELS[$key]).'</label>';
  if($key==='categorie')echo '<input type="hidden" name="cel_theme_order" class="cel-theme-order" value="'.esc_attr(implode(',',self::ordered_theme_ids($id))).'"><p class="description cel-theme-order-help">Déplace les thèmes pour définir leur ordre. Le premier est affiché sur la carte de l’accueil.</p>';
  if($compact){
   $by_id=[];foreach($terms as $term)$by_id[(int)$term->term_id]=$term->name;
   $chosen=$key==='categorie'?self::ordered_theme_ids($id):array_map('intval',$selected);
   $chosen=array_values(array_filter($chosen,fn($term_id)=>isset($by_id[$term_id])));
   echo '<div class="cel-tax-selected" aria-label="Valeurs sélectionnées">';
   foreach($chosen as $index=>$term_id){
    $name=$by_id[$term_id];
    if($key==='categorie'){
     echo '<span class="cel-tax-chip cel-theme-chip'.($index===0?' is-primary':'').'" data-term-id="'.(int)$term_id.'" draggable="true"><span class="cel-theme-grip" aria-hidden="true">⠿</span><span class="cel-theme-name">'.esc_html($name).'</span>';
     if($index===0)echo '<small class="cel-theme-primary">Principal</small>';
     echo '<button type="button" class="cel-theme-move" data-step="-1" aria-label="'.esc_attr('Avancer '.$name).'"'.disabled($index===0,true,false).'>←</button><button type="button" class="cel-theme-move" data-step="1" aria-label="'.esc_attr('Reculer '.$name).'"'.disabled($index===count($chosen)-1,true,false).'>→</button><button type="button" class="cel-theme-remove" aria-label="'.esc_attr('Retirer '.$name).'">×</button></span>';
    }else echo '<button type="button" class="cel-tax-chip" data-term-id="'.(int)$term_id.'" aria-label="'.esc_attr('Retirer '.$name).'">'.esc_html($name).' ×</button>';
   }
   if(!$chosen)echo '<span class="description">Aucune sélection</span>';
   $count=count($chosen);
   echo '</div><details class="cel-tax-details"><summary>Rechercher et sélectionner <span class="cel-tax-count">('.$count.' sélectionné'.($count>1?'s':'').')</span></summary><input type="search" class="cel-tax-search" maxlength="150" placeholder="Rechercher ou créer…" aria-label="Rechercher dans '.esc_attr(self::LABELS[$key]).'">';
  }
  echo '<div class="cel-lib-choices">';
  foreach($terms as $t)echo '<label><input type="checkbox" name="cel_lib['.esc_attr($key).'][]" value="'.(int)$t->term_id.'"'.checked(in_array((int)$t->term_id,array_map('intval',$selected),true),true,false).'> <span>'.esc_html($t->name).'</span></label>';
  echo '</div>';
  if($compact){
   echo '<p class="cel-tax-empty" hidden>Aucun résultat.</p><button type="button" class="button cel-tax-add" hidden>+ Ajouter</button><p class="cel-tax-status" role="status"></p></details>';
  }
  echo '</div>';
 }
 static function identity($post){
  wp_nonce_field('cel_core_save','cel_core_nonce');
 }
 static function categories($post){
  echo '<section class="cel-form-panel" data-panel="categories" hidden><h3>Catégories et classement</h3><p>Les thèmes, collections et événements sont partagés entre les livres, les mini-histoires et les activités. Enregistre la fiche pour conserver tes sélections.</p><div class="cel-field-grid cel-library-identity">';
  foreach(self::TAX as $key=>$tax)self::choices($post->ID,$key);
  echo '</div></section>';
 }
 static function save($id,$post){
  if(!isset($_POST['cel_fields_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cel_fields_nonce'])),'cel_fields_save')||!current_user_can('edit_post',$id)||wp_is_post_revision($id)||(defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE))return;
  foreach(self::TAX as $key=>$tax){
   $ids=array_map('absint',(array)($_POST['cel_lib'][$key]??[]));
   $ids=array_values(array_filter($ids,fn($i)=>term_exists($i,$tax)));
   $result=wp_set_object_terms($id,array_unique($ids),$tax);
   if($key==='categorie'&&!is_wp_error($result)&&isset($_POST['cel_theme_order'])&&is_string($_POST['cel_theme_order'])){
    $order=array_map('absint',explode(',',wp_unslash($_POST['cel_theme_order'])));
    update_post_meta($id,'_cel_theme_order',array_values(array_unique(array_merge(array_intersect($order,$ids),$ids))));
   }
  }
 }
 static function display($id,$key){
  $terms=$key==='categorie'?self::ordered_themes($id):wp_get_object_terms($id,self::TAX[$key],['fields'=>'names']);if(is_wp_error($terms)||!$terms)return (string)get_post_meta($id,'_cel_f_'.$key,true);
  if($key!=='age_lecture')return implode(', ',$terms);
  $ranges=[];$others=[];foreach($terms as $t){if(preg_match('/^(\d+)\s*[-–]\s*(\d+)$/u',$t,$m))$ranges[]=[(int)$m[1],(int)$m[2]];else $others[]=$t;}
  usort($ranges,fn($a,$b)=>$a[0]<=>$b[0]);$merged=[];foreach($ranges as $r){$i=count($merged)-1;if($i>=0&&$r[0]<=$merged[$i][1])$merged[$i][1]=max($merged[$i][1],$r[1]);else $merged[]=$r;}
  return implode(', ',array_merge(array_map(fn($r)=>$r[0].'–'.$r[1].' ans',$merged),$others));
 }
 static function filter($q){
  if(is_admin()||!$q->is_main_query()||!$q->is_post_type_archive(['cel_livre','cel_histoire','cel_coloriage']))return;
  $filters=['relation'=>'AND'];foreach(self::TAX as $key=>$tax){$slug=sanitize_title(wp_unslash($_GET['filtre_'.$key]??''));if($slug)$filters[]=['taxonomy'=>$tax,'field'=>'slug','terms'=>[$slug]];}
  if(count($filters)>1)$q->set('tax_query',$filters);
 }
 static function filter_form($type='cel_livre'){
  if(!in_array($type,['cel_livre','cel_histoire','cel_coloriage'],true))return;
  $ids=get_posts(['post_type'=>$type,'post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids']);
  echo '<form class="cel-library-filters" method="get" action="'.esc_url(get_post_type_archive_link($type)).'">';
  foreach(self::TAX as $key=>$tax){$terms=$ids?get_terms(['taxonomy'=>$tax,'hide_empty'=>false,'object_ids'=>$ids]):[];if(is_wp_error($terms)||!$terms)continue;$current=sanitize_title(wp_unslash($_GET['filtre_'.$key]??''));echo '<label>'.esc_html(self::LABELS[$key]).'<select name="'.esc_attr('filtre_'.$key).'"><option value="">Tous</option>';foreach($terms as $t)echo '<option value="'.esc_attr($t->slug).'"'.selected($current,$t->slug,false).'>'.esc_html($t->name).'</option>';echo '</select></label>';}
  echo '<button type="submit">Filtrer</button><a href="'.esc_url(get_post_type_archive_link($type)).'">Réinitialiser</a></form>';
 }
}
Celestin_Library::init();
