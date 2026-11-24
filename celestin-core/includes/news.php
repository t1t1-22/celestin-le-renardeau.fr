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
  $order=array_values(array_map(fn($p)=>(int)$p->ID,Celestin_Home::cards()));
  ?>
  <style>
  .cel-news-admin{color:#633619}.cel-news-admin h2,.cel-news-admin h3{color:#633619}
  .cel-news-layout{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(0,1fr);gap:22px;align-items:start;margin-top:20px}
  .cel-news-order,.cel-news-library{background:#fff8ef;border:1px solid #ead5bb;border-radius:16px;padding:18px;min-width:0}
  .cel-news-order-row,.cel-news-choice{display:flex;align-items:center;gap:10px;background:#fff;border:1px solid #ead5bb;border-radius:10px;padding:8px;margin:8px 0}
  .cel-news-order-row img,.cel-news-choice img{width:56px!important;height:56px!important;object-fit:contain;border-radius:6px;flex:none}
  .cel-news-order-row strong,.cel-news-choice span{flex:1;min-width:0;overflow-wrap:anywhere}
  .cel-news-order-row button,.cel-news-admin .cel-news-filter{border:1px solid #e4c4a1;color:#77431f;background:#fff7eb;border-radius:8px;padding:5px 10px;cursor:pointer}
  .cel-news-admin .cel-news-filter.active,.cel-news-admin .cel-news-save{background:#e98932!important;color:white!important;border-color:#d47b29!important}
  .cel-news-admin .cel-news-save{border-radius:10px;padding:7px 15px;margin-top:12px;font-weight:700}
  .cel-news-order-row.dragging{opacity:.55}.cel-news-choice{cursor:pointer}.cel-news-choice input{accent-color:#e98932}
  .cel-news-choice:has(input:checked){border-color:#e98932;background:#fff3e3}
  .cel-news-admin .cel-news-filters{display:flex;flex-wrap:wrap;gap:6px;margin:10px 0}
  .cel-news-admin .cel-news-search{width:100%;border:1px solid #e4c4a1;border-radius:8px;margin-bottom:8px}
  .cel-news-admin [hidden]{display:none!important}.cel-news-library .cel-news-items{max-height:580px;overflow:auto}
  .cel-news-choice small{display:block;color:#94603c}.cel-news-order-row .cel-news-grip{color:#a7622b}
  @media(max-width:900px){.cel-news-layout{grid-template-columns:1fr}}
  </style>
  <div class="cel-news-admin">
  <h2>Les nouveautés de l’accueil</h2><p>Compose ton carrousel et choisis dans quel ordre les contenus apparaissent.</p>
  <div class="cel-news-layout">
  <section class="cel-news-order"><h3>Slides affichés <span id="cel-news-count"></span></h3><p>Déplace les lignes avec la poignée ou les flèches.</p>
  <input type="hidden" name="cel_news_order" id="cel-news-order-value" value="<?php echo esc_attr(implode(',',$order)); ?>">
  <div id="cel-news-order-list"></div><p id="cel-news-order-empty">Ajoute des contenus depuis la bibliothèque.</p>
  <button type="submit" class="button button-primary cel-news-save">Enregistrer les nouveautés</button></section>
  <section class="cel-news-library"><h3>Contenus disponibles</h3><input class="cel-news-search" type="search" placeholder="Rechercher un contenu…" aria-label="Rechercher un contenu">
  <div class="cel-news-filters"><button type="button" class="cel-news-filter active" data-type="">Tous</button>
  <?php foreach(self::TYPES as $type=>$label): ?><button type="button" class="cel-news-filter" data-type="<?php echo esc_attr($type); ?>"><?php echo esc_html($label); ?></button><?php endforeach; ?></div>
  <div class="cel-news-items">
  <?php foreach(self::TYPES as $type=>$label): foreach(get_posts(['post_type'=>$type,'post_status'=>'publish','posts_per_page'=>-1,'orderby'=>'date','order'=>'DESC']) as $p): ?>
  <label class="cel-news-choice" data-type="<?php echo esc_attr($type); ?>">
  <?php echo get_the_post_thumbnail($p,'thumbnail'); ?>
  <span><strong><?php echo esc_html($p->post_title); ?></strong><small><?php echo esc_html($label); ?></small></span>
  <input type="checkbox" name="cel_news_ids[]" value="<?php echo (int)$p->ID; ?>" <?php checked(get_post_meta($p->ID,'_cel_en_nouveaute',true),'1'); ?> aria-label="<?php echo esc_attr('Ajouter '.$p->post_title); ?>">
  </label>
  <?php endforeach; endforeach; ?>
  </div></section></div>
  <script type="application/json" id="cel-news-order-data"><?php echo wp_json_encode($order); ?></script>
  </div>
  <?php
  ?>
  <script>
  function initCelNewsOrder(){
    const root=document.querySelector('.cel-news-admin');if(!root)return;
    const list=root.querySelector('#cel-news-order-list'),hidden=root.querySelector('#cel-news-order-value'),empty=root.querySelector('#cel-news-order-empty');
    const inputs=Array.from(root.querySelectorAll('input[name="cel_news_ids[]"]'));
    let order=JSON.parse(root.querySelector('#cel-news-order-data').textContent).map(String),drag=null;
    function sync(){hidden.value=order.join(',');empty.hidden=order.length>0;root.querySelector('#cel-news-count').textContent='('+order.length+')';}
    function render(){
      const active=inputs.filter(x=>x.checked).map(x=>x.value);
      order=order.filter(id=>active.includes(id));active.forEach(id=>{if(!order.includes(id))order.push(id)});
      list.replaceChildren();
      order.forEach((id,index)=>{
        const input=inputs.find(x=>x.value===id),label=input.closest('label'),row=document.createElement('div');row.className='cel-news-order-row';row.draggable=true;row.dataset.id=id;
        const grip=document.createElement('span');grip.textContent='⠿ '+(index+1);grip.className='cel-news-grip';grip.style.cssText='cursor:grab;touch-action:none;user-select:none;font-size:22px;padding:8px';row.append(grip);row.draggable=false;
        const image=label.querySelector('img');if(image){const thumb=image.cloneNode(true);thumb.draggable=false;row.append(thumb)}
        const title=document.createElement('strong');title.textContent=label.querySelector('strong').textContent.trim();row.append(title);
        [-1,1].forEach(step=>{const button=document.createElement('button');button.type='button';button.className='button';button.textContent=step<0?'↑':'↓';button.setAttribute('aria-label',step<0?'Monter '+title.textContent:'Descendre '+title.textContent);button.disabled=index+step<0||index+step>=order.length;button.onclick=()=>{[order[index],order[index+step]]=[order[index+step],order[index]];render()};row.append(button)});
        const remove=document.createElement('button');remove.type='button';remove.textContent='×';remove.setAttribute('aria-label','Retirer '+title.textContent);remove.onclick=()=>{input.checked=false;render()};row.append(remove);
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
    let type='';const search=root.querySelector('.cel-news-search');
    function filter(){const q=search.value.toLocaleLowerCase().trim();root.querySelectorAll('.cel-news-choice').forEach(el=>{el.hidden=!!((type&&el.dataset.type!==type)||!el.textContent.toLocaleLowerCase().includes(q))})}
    root.querySelectorAll('.cel-news-filter').forEach(btn=>btn.addEventListener('click',()=>{type=btn.dataset.type;root.querySelectorAll('.cel-news-filter').forEach(b=>b.classList.toggle('active',b===btn));filter()}));search.addEventListener('input',filter);
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
