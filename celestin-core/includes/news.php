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
  echo '<div class="wrap"><p><a href="'.esc_url(admin_url('admin.php?page=celestin-design&tab=nouveautes')).'">Gérer les nouveautés dans l’apparence de l’accueil</a></p></div>';
 }
 static function selection(){
  if(!current_user_can('manage_options'))return;
  echo '<div class="cel-news-admin"><style>.cel-news-admin{color:#633619}.cel-news-admin h1{font-weight:800;color:#633619;font-size:28px}.cel-news-admin .cel-news-columns{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-top:24px;align-items:start}.cel-news-admin .cel-news-group{background:#fff8ef;border:1px solid #ead5bb;border-radius:14px;padding:14px;min-width:0}.cel-news-admin .cel-news-group h2{font-size:17px;color:#633619;margin:0 0 15px}.cel-news-admin .cel-news-items{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,135px),1fr));gap:10px}.cel-news-admin label:has(input:checked){border-color:#e98932!important;box-shadow:0 0 0 1px #e98932}.cel-news-admin input[type=checkbox]:checked:before{filter:sepia(1) saturate(5)}.cel-news-admin .button-primary{background:#e98932;border-color:#d47b29;border-radius:20px;padding:4px 18px;font-weight:700}@media(max-width:1100px){.cel-news-admin .cel-news-columns{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:600px){.cel-news-admin .cel-news-columns{grid-template-columns:1fr}}</style><h1>Les nouveautés</h1><p>Choisis les contenus publiés à afficher dans le carrousel de la page d’accueil. Les fiches restent accessibles normalement, même si tu les décoches.</p>';
  if(isset($_GET['saved']))echo '<div class="notice notice-success is-dismissible"><p>Sélection enregistrée.</p></div>';

  $order=array_values(array_map(fn($p)=>(int)$p->ID,Celestin_Home::cards()));
  echo '<div class="cel-news-order"><h2>Ordre du carrousel</h2><p>Maintiens la poignée ⠿ à gauche et déplace-la vers une autre ligne, ou utilise les flèches. La première carte sera affichée en premier.</p><input type="hidden" name="cel_news_order" id="cel-news-order-value" value="'.esc_attr(implode(',',$order)).'"><div id="cel-news-order-list"></div><p id="cel-news-order-empty">Sélectionne des contenus ci-dessous.</p><button type="submit" class="button button-primary" style="margin-top:12px">Enregistrer l’ordre des nouveautés</button></div>';
  echo '<div class="cel-news-columns">';
  foreach(self::TYPES as $type=>$label){
   $items=get_posts(['post_type'=>$type,'post_status'=>'publish','posts_per_page'=>-1,'orderby'=>'date','order'=>'DESC']);
   echo '<section class="cel-news-group"><h2>'.esc_html($label).'</h2><div class="cel-news-items">';
   if(!$items)echo '<p>Aucun contenu publié.</p>';
   foreach($items as $p){
    echo '<label style="display:flex;flex-direction:column;gap:9px;padding:12px;background:white;border:1px solid #e5d4c0;border-radius:12px;cursor:pointer"><span style="height:130px;background:#fff7eb;display:flex;align-items:center;justify-content:center;overflow:hidden">';
    echo has_post_thumbnail($p)?get_the_post_thumbnail($p,'medium',['style'=>'width:100%;height:100%;object-fit:contain']):'<span>Illustration à venir</span>';
    echo '</span><span><input type="checkbox" name="cel_news_ids[]" value="'.(int)$p->ID.'"'.checked(get_post_meta($p->ID,'_cel_en_nouveaute',true),'1',false).'> '.esc_html($p->post_title).'</span></label>';
   }
   echo '</div></section>';
  }
  echo '</div>';
  echo '</div>';

  echo '<style>.cel-news-order{background:#fff8ef;border:1px solid #ead5bb;border-radius:14px;padding:16px;margin-top:20px}.cel-news-order-row{display:flex;align-items:center;gap:12px;background:#fff;border:1px solid #ead5bb;border-radius:10px;padding:8px 12px;margin:7px 0;cursor:grab}.cel-news-order-row img{width:52px;height:52px;object-fit:contain;border-radius:6px}.cel-news-order-row strong{flex:1}.cel-news-order-row button{border-color:#e4c4a1;color:#77431f;background:#fff7eb;border-radius:8px}.cel-news-order-row.dragging{opacity:.45}</style>';
  echo '<script type="application/json" id="cel-news-order-data">'.wp_json_encode($order).'</script>';
  ?>
  <script>
  function initCelNewsOrder(){
    const root=document.querySelector('.cel-news-admin');if(!root)return;
    const list=root.querySelector('#cel-news-order-list'),hidden=root.querySelector('#cel-news-order-value'),empty=root.querySelector('#cel-news-order-empty');
    const inputs=Array.from(root.querySelectorAll('input[name="cel_news_ids[]"]'));
    let order=JSON.parse(root.querySelector('#cel-news-order-data').textContent).map(String),drag=null;
    function sync(){hidden.value=order.join(',');empty.hidden=order.length>0;}
    function render(){
      const active=inputs.filter(x=>x.checked).map(x=>x.value);
      order=order.filter(id=>active.includes(id));active.forEach(id=>{if(!order.includes(id))order.push(id)});
      list.replaceChildren();
      order.forEach((id,index)=>{
        const input=inputs.find(x=>x.value===id),label=input.closest('label'),row=document.createElement('div');row.className='cel-news-order-row';row.draggable=true;row.dataset.id=id;
        const grip=document.createElement('span');grip.textContent='⠿ '+(index+1);grip.className='cel-news-grip';grip.style.cssText='cursor:grab;touch-action:none;user-select:none;font-size:22px;padding:8px';row.append(grip);row.draggable=false;
        const image=label.querySelector('img');if(image){const thumb=image.cloneNode(true);thumb.draggable=false;row.append(thumb)}
        const title=document.createElement('strong');title.textContent=input.parentElement.textContent.trim();row.append(title);
        [-1,1].forEach(step=>{const button=document.createElement('button');button.type='button';button.className='button';button.textContent=step<0?'↑':'↓';button.setAttribute('aria-label',step<0?'Monter '+title.textContent:'Descendre '+title.textContent);button.disabled=index+step<0||index+step>=order.length;button.onclick=()=>{[order[index],order[index+step]]=[order[index+step],order[index]];render()};row.append(button)});
        grip.addEventListener('pointerdown',e=>{
          if(e.button!==0)return;e.preventDefault();grip.setPointerCapture(e.pointerId);
          const startY=e.clientY;let target=id;row.classList.add('dragging');grip.style.cursor='grabbing';
          function move(ev){
            if(Math.abs(ev.clientY-startY)<5)return;
            const rows=Array.from(list.querySelectorAll('.cel-news-order-row'));
            const hit=rows.find(el=>{const rect=el.getBoundingClientRect();return ev.clientY>=rect.top&&ev.clientY<=rect.bottom});
            if(hit)target=hit.dataset.id;
            rows.forEach(el=>el.style.outline=el.dataset.id===target&&target!==id?'2px solid #e98932':'');
          }
          function finish(ev){
            grip.removeEventListener('pointermove',move);grip.removeEventListener('pointerup',finish);grip.removeEventListener('pointercancel',cancel);
            if(grip.hasPointerCapture(e.pointerId))grip.releasePointerCapture(e.pointerId);
            if(ev.type==='pointerup'&&target!==id){const from=order.indexOf(id),to=order.indexOf(target);order.splice(from,1);order.splice(to,0,id);}
            render();
          }
          function cancel(ev){finish(ev)}
          grip.addEventListener('pointermove',move);grip.addEventListener('pointerup',finish);grip.addEventListener('pointercancel',cancel);
        });
        list.append(row);
      });sync();
    }
    inputs.forEach(input=>input.addEventListener('change',render));
    const form=root.closest('form');if(form)form.addEventListener('submit',sync);
    render();
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',initCelNewsOrder);else initCelNewsOrder();
  </script>
  <?php

 }
 static function save(){
  if(!current_user_can('manage_options'))wp_die('Accès refusé');
  check_admin_referer('cel_save_news');
  self::save_selection((array)($_POST['cel_news_ids']??[]));
  wp_safe_redirect(admin_url('admin.php?page=celestin-design&tab=nouveautes&saved=1'));exit;
 }
 static function save_selection(array $selected): void {
  $ids=array_values(array_unique(array_filter(array_map('absint',$selected))));
  if(isset($_POST['cel_news_order'])&&is_string($_POST['cel_news_order'])){
   $order=array_values(array_unique(array_filter(array_map('absint',explode(',',wp_unslash($_POST['cel_news_order']))))));
   $order=array_values(array_intersect($order,$ids));
   if($order||!$ids)update_option('cel_news_order',array_values(array_unique(array_merge($order,$ids))),false);
  }
  foreach(get_posts(['post_type'=>array_keys(self::TYPES),'post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids']) as $id){
   if(current_user_can('edit_post',$id))update_post_meta($id,'_cel_en_nouveaute',in_array($id,$ids,true)?'1':'0');
  }

 }
}
Celestin_News::init();
