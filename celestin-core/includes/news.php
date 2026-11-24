<?php
if(!defined('ABSPATH'))exit;
final class Celestin_News {
 const TYPES=['cel_livre'=>'Les livres','cel_coloriage'=>'Les activités','cel_histoire'=>'Mini-histoires audio','cel_personnage'=>'Les personnages'];
 static function init(){
  add_action('admin_menu',[__CLASS__,'menu'],30);
  add_action('admin_post_cel_save_news',[__CLASS__,'save']);
 }
 static function menu(){add_submenu_page('celestin-core','Nouveautés','Nouveautés','manage_options','celestin-news',[__CLASS__,'screen']);}
 static function screen(){
  if(!current_user_can('manage_options'))return;
  echo '<div class="wrap"><h1>Les nouveautés</h1><p>Choisis les contenus publiés à afficher dans le carrousel de la page d’accueil. Les fiches restent accessibles normalement, même si tu les décoches.</p>';
  if(isset($_GET['saved']))echo '<div class="notice notice-success is-dismissible"><p>Sélection enregistrée.</p></div>';
  echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="cel_save_news">';
  wp_nonce_field('cel_save_news');
  foreach(self::TYPES as $type=>$label){
   $items=get_posts(['post_type'=>$type,'post_status'=>'publish','posts_per_page'=>-1,'orderby'=>'date','order'=>'DESC']);
   echo '<h2>'.esc_html($label).'</h2><div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:14px;max-width:1100px">';
   if(!$items)echo '<p>Aucun contenu publié.</p>';
   foreach($items as $p){
    echo '<label style="display:flex;flex-direction:column;gap:9px;padding:12px;background:white;border:1px solid #e5d4c0;border-radius:12px;cursor:pointer"><span style="height:130px;background:#fff7eb;display:flex;align-items:center;justify-content:center;overflow:hidden">';
    echo has_post_thumbnail($p)?get_the_post_thumbnail($p,'medium',['style'=>'width:100%;height:100%;object-fit:contain']):'<span>Illustration à venir</span>';
    echo '</span><span><input type="checkbox" name="cel_news_ids[]" value="'.(int)$p->ID.'"'.checked(get_post_meta($p->ID,'_cel_en_nouveaute',true),'1',false).'> '.esc_html($p->post_title).'</span></label>';
   }
   echo '</div>';
  }
  submit_button('Enregistrer les nouveautés');echo '</form></div>';
 }
 static function save(){
  if(!current_user_can('manage_options'))wp_die('Accès refusé');
  check_admin_referer('cel_save_news');
  $ids=array_map('absint',(array)($_POST['cel_news_ids']??[]));
  foreach(get_posts(['post_type'=>array_keys(self::TYPES),'post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids']) as $id){
   if(current_user_can('edit_post',$id))update_post_meta($id,'_cel_en_nouveaute',in_array($id,$ids,true)?'1':'0');
  }
  wp_safe_redirect(admin_url('admin.php?page=celestin-news&saved=1'));exit;
 }
}
Celestin_News::init();
