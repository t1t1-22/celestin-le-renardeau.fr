<?php
/**
 * Présentation du site Célestin — indépendante du thème Astra.
 * Les illustrations sont choisies dans WordPress > Célestin Core > Apparence du site.
 */
if (!defined('ABSPATH')) exit;

final class Celestin_Home {
    const OPTION = 'celestin_home_design';
    static function init(): void {
        add_filter('template_include', [__CLASS__, 'template'], 99);
        add_filter('get_site_icon_url',[__CLASS__,'site_icon'],20,3);
        add_action('admin_menu', [__CLASS__, 'admin'], 20);
        add_action('admin_enqueue_scripts', [__CLASS__, 'admin_assets']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'assets']);
        add_action('admin_post_celestin_save_design', [__CLASS__, 'save_design']);
    }
    static function defaults(): array {
        return [
            'logo'=>0, 'footer_logo'=>0, 'hero'=>0, 'footer'=>0, 'story'=>0, 'book'=>0,
            'character'=>0, 'city'=>0, 'activity'=>0,
            'favicon_spring'=>0, 'favicon_summer'=>0, 'favicon_autumn'=>0, 'favicon_winter'=>0,
            'headline'=>'Pars à l’aventure à Fleuriville',
            'hero_title_size'=>64, 'hero_text_size'=>18, 'hero_x'=>50, 'hero_y'=>50, 'hero_width'=>680, 'hero_text_location'=>'overlay',
            'hero_intro'=>'Bienvenue dans l’univers de Célestin', 'hero_description'=>'Des histoires à lire, des aventures à écouter et plein de petites activités à découvrir.', 'hero_button'=>'Découvrir l’univers', 'hero_link'=>'#cel-decouvrir',
            'origin_title'=>'Il était une fois… Célestin',
            'origin_description'=>'Derrière chaque aventure se cache une histoire. Découvre les origines de Célestin le renardeau et de son petit univers.',
            'origin_button'=>'Découvrir son histoire','origin_link'=>'',
            'welcome'=>'Un petit coin de nature plein de charme où Célestin vit de grandes et de petites aventures avec Octet, son fidèle compagnon.',
            'newsletter'=>'','facebook'=>'','instagram'=>'','pinterest'=>''
        ];
    }
    static function options(): array {
        $saved = get_option(self::OPTION, []);
        return wp_parse_args(is_array($saved)?$saved:[], self::defaults());
    }
    // Le portrait original de la fiche personnage reste la référence.
    static function portrait_icon_id(): int {
        static $id=null;
        if($id!==null)return $id;
        $id=0;
        $posts=get_posts(['post_type'=>'cel_personnage','post_status'=>'publish','posts_per_page'=>-1,'orderby'=>'ID','order'=>'ASC']);
        foreach($posts as $post){
            $first=strtolower(trim(remove_accents((string)get_post_meta($post->ID,'_cel_f_prenom',true))));
            $title=strtolower(trim(remove_accents($post->post_title)));
            if($first!=='celestin'&&($first!==''||!preg_match('/^celestin(?:\\s|$)/',$title)))continue;
            $portrait=absint(get_post_meta($post->ID,'_cel_section_image_identite',true))?:get_post_thumbnail_id($post->ID);
            if($portrait&&wp_attachment_is_image($portrait)){$id=(int)$portrait;break;}
        }
        return $id;
    }
    static function site_icon($url,$size,$blog_id=0): string {
        if($blog_id&&(int)$blog_id!==get_current_blog_id())return (string)$url;
        $month=(int)wp_date('n');
        $season=$month>=3&&$month<=5?'spring':($month>=6&&$month<=8?'summer':($month>=9&&$month<=11?'autumn':'winter'));
        $options=self::options();
        $attachment=absint($options['favicon_'.$season]??0);
        if(!$attachment||!wp_attachment_is_image($attachment))$attachment=self::portrait_icon_id();
        if(!$attachment)return (string)$url;
        $image=wp_get_attachment_image_url($attachment,[(int)$size,(int)$size]);
        return $image?esc_url_raw($image):(string)$url;
    }
    static function image(string $key, string $size='full'): string {
        $o = self::options();
        $id = absint($o[$key] ?? 0);
        return $id ? (string) wp_get_attachment_image_url($id,$size) : '';
    }
    static function render_image(string $key, string $alt, string $classes=''): string {
        $url=self::image($key);
        if (!$url) return '<div class="cel-illustration-placeholder ' . esc_attr($classes) . '"><span aria-hidden="true">✦</span><span>' . esc_html($alt) . '</span></div>';
        return '<img class="' . esc_attr($classes) . '" src="' . esc_url($url) . '" alt="' . esc_attr($alt) . '" loading="lazy">';
    }
    static function admin(): void {
        add_submenu_page('celestin-core','Apparence du site','Apparence du site','manage_options','celestin-design',[__CLASS__,'settings']);
    }
    static function admin_assets($hook): void {
        if (sanitize_key($_GET['page'] ?? '') !== 'celestin-design' || !current_user_can('manage_options')) return;
        wp_enqueue_media();
        wp_enqueue_script('celestin-media', plugins_url('../assets/admin.js',__FILE__), ['jquery'], '0.4.84', true);
        wp_enqueue_style('celestin-admin', plugins_url('../assets/admin.css',__FILE__), [], '0.4.84');
    }
    static function image_setting(string $key, string $label, array $options): void {
        $id = absint($options[$key] ?? 0);
        $url = $id ? wp_get_attachment_image_url($id, 'medium') : '';
        echo '<div class="cel-media-setting"><h3>' . esc_html($label) . '</h3>';
        echo '<input type="hidden" class="cel-media-id" name="design[' . esc_attr($key) . ']" value="' . $id . '">';
        echo '<img class="cel-media-preview" src="' . esc_url($url ?: '') . '" alt="" style="display:' . ($url ? 'block' : 'none') . '">';
        echo '<div class="cel-media-actions"><button type="button" class="button cel-media-choose">Choisir une image</button> <button type="button" class="button cel-media-remove">Retirer</button></div></div>';
    }
    static function text_setting(string $key, string $label, array $options, bool $multiline=false): void {
        echo '<div class="cel-text-setting"><label for="cel_' . esc_attr($key) . '"><strong>' . esc_html($label) . '</strong></label>';
        if ($multiline) echo '<textarea rows="4" name="design[' . esc_attr($key) . ']" id="cel_' . esc_attr($key) . '">' . esc_textarea($options[$key] ?? '') . '</textarea>';
        else echo '<input type="text" name="design[' . esc_attr($key) . ']" id="cel_' . esc_attr($key) . '" value="' . esc_attr($options[$key] ?? '') . '">';
        echo '</div>';
    }
    const EVENT_OPTION='celestin_home_events';
    static function event_settings(): void {
        $cfg=get_option(self::EVENT_OPTION,[]);
        $active=absint($cfg['active']??0);
        $terms=get_terms(['taxonomy'=>Celestin_Library::TAX['evenement'],'hide_empty'=>false]);
        if(is_wp_error($terms))$terms=[];
        $types=['news'=>'Slider des nouveautés','cel_livre'=>'Livre mis en avant','cel_histoire'=>'Mini-histoires audio','cel_coloriage'=>'Activités'];
        $labels=['cel_livre'=>'Livre','cel_histoire'=>'Mini-histoire audio','cel_coloriage'=>'Activité'];
        $limits=['news'=>8,'cel_livre'=>1,'cel_histoire'=>3,'cel_coloriage'=>4];
        $posts=get_posts(['post_type'=>array_keys($labels),'post_status'=>'publish','posts_per_page'=>-1,'orderby'=>'date','order'=>'DESC']);
        $library=[];
        foreach($posts as $post){
            $ids=wp_get_object_terms($post->ID,Celestin_Library::TAX['evenement'],['fields'=>'ids']);
            $library[]=['id'=>(int)$post->ID,'title'=>get_the_title($post),'type'=>$post->post_type,'label'=>$labels[$post->post_type],'image'=>get_the_post_thumbnail_url($post,'thumbnail')?:'','events'=>is_wp_error($ids)?[]:array_map('intval',$ids)];
        }
        $profiles=[];
        foreach($terms as $term){
            $id=(int)$term->term_id;$p=$cfg['profiles'][$id]??[];
            foreach($types as $key=>$label){
                $profiles[$id][$key]=['mode'=>isset($p['sections'])&&empty($p['sections'][$key])?'standard':($p['modes'][$key]??$p['mode']??'auto'),'ids'=>array_values(array_map('absint',(array)($p['manual'][$key]??[])))];
            }
        }
        ?>
        <style>
        .cel-event-admin{color:#633619}.cel-event-admin h2,.cel-event-admin h3{color:#633619}.cel-event-top{display:flex;flex-wrap:wrap;gap:20px;align-items:end;padding:16px;background:#fff8ef;border:1px solid #ead5bb;border-radius:12px;margin:16px 0}.cel-event-top label{display:grid;gap:5px}.cel-event-top select{min-width:210px}.cel-event-tabs,.cel-event-filters{display:flex;flex-wrap:wrap;gap:6px;margin:12px 0}.cel-event-admin button.cel-event-tab,.cel-event-admin button.cel-event-filter{border:1px solid #e4c4a1;color:#77431f;background:#fff7eb;border-radius:8px;padding:7px 12px;cursor:pointer}.cel-event-admin button.is-current{background:#e98932;color:white;border-color:#d47b29}.cel-event-layout{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(0,1fr);gap:20px}.cel-event-panel{background:#fff8ef;border:1px solid #ead5bb;border-radius:16px;padding:18px;min-width:0}.cel-event-row{display:flex;align-items:center;gap:9px;background:white;border:1px solid #ead5bb;border-radius:10px;margin:8px 0;padding:8px}.cel-event-row img{width:54px;height:54px;object-fit:contain;border-radius:6px;flex:none}.cel-event-row .cel-event-name{flex:1;min-width:0;overflow-wrap:anywhere}.cel-event-name small{display:block;color:#94603c}.cel-event-row button{flex:none}.cel-event-row.is-selected{border-color:#e98932;background:#fff3e3}.cel-event-library{max-height:520px;overflow:auto}.cel-event-search{width:100%;margin:8px 0}.cel-event-admin [hidden]{display:none!important}.cel-event-grip{cursor:grab;touch-action:none;color:#a7622b;font-size:20px;user-select:none}.cel-event-row.dragging{opacity:.5}@media(max-width:900px){.cel-event-layout{grid-template-columns:1fr}}
        </style>
        <div class="cel-event-admin">
        <h2>Temps forts de l’année</h2><p>Prépare chaque événement indépendamment de ton accueil standard. Seul ton choix enregistré dans « Accueil actif » change la vitrine publique.</p>
        <div class="cel-event-top">
        <label><strong>Accueil actif</strong><select name="cel_event[active]"><option value="0">Accueil standard</option>
        <?php foreach($terms as $term): ?><option value="<?php echo (int)$term->term_id; ?>" <?php selected($active,$term->term_id); ?>><?php echo esc_html($term->name); ?></option><?php endforeach; ?>
        </select></label>
        <label><strong>Événement à préparer</strong><select class="cel-event-edit">
        <?php foreach($terms as $term): ?><option value="<?php echo (int)$term->term_id; ?>" <?php selected($active,$term->term_id); ?>><?php echo esc_html($term->name); ?></option><?php endforeach; ?>
        </select></label><span>Préparer un événement ne l’active pas.</span></div>
        <input type="hidden" name="cel_event[json]" class="cel-event-json">
        <div class="cel-event-tabs"><?php foreach($types as $key=>$label): ?><button type="button" class="cel-event-tab" data-key="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></button><?php endforeach; ?></div>
        <p><label><strong>Mode de cette rubrique</strong> <select class="cel-event-mode"><option value="auto">Automatique : contenus liés à l’événement</option><option value="manual">Manuel : choisir et ordonner</option><option value="standard">Conserver l’accueil standard</option></select></label></p>
        <div class="cel-event-layout">
        <section class="cel-event-panel"><h3 class="cel-event-selected-title">Contenus affichés</h3><p class="cel-event-help"></p><div class="cel-event-selected"></div><button type="submit" class="button button-primary" style="background:#e98932;border-color:#d47b29;margin-top:12px">Enregistrer la configuration</button></section>
        <section class="cel-event-panel"><h3>Contenus disponibles</h3><input type="search" class="cel-event-search" placeholder="Rechercher un contenu…"><div class="cel-event-filters"><button type="button" class="cel-event-filter is-current" data-filter="event">Cet événement</button><button type="button" class="cel-event-filter" data-filter="all">Tous les contenus</button></div><div class="cel-event-library"></div></section>
        </div><p>En l’absence de contenu disponible, la rubrique conserve sa sélection standard.</p>
        <script type="application/json" class="cel-event-data"><?php echo wp_json_encode(['profiles'=>$profiles,'library'=>$library,'limits'=>$limits],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?></script>
        </div>
        <script>
        (function(){
          const root=document.querySelector('.cel-event-admin');if(!root)return;
          const data=JSON.parse(root.querySelector('.cel-event-data').textContent),profiles=data.profiles,library=data.library;
          const edit=root.querySelector('.cel-event-edit'),mode=root.querySelector('.cel-event-mode'),search=root.querySelector('.cel-event-search'),left=root.querySelector('.cel-event-selected'),right=root.querySelector('.cel-event-library'),hidden=root.querySelector('.cel-event-json');
          let key='news',filter='event';
          function current(){return profiles[edit.value]&&profiles[edit.value][key]}
          function eligible(p){return key==='news'||p.type===key}
          function eventMatch(p){return p.events.includes(Number(edit.value))}
          function sync(){hidden.value=JSON.stringify(profiles)}
          function node(tag,cls,txt){const el=document.createElement(tag);if(cls)el.className=cls;if(txt!==undefined)el.textContent=txt;return el}
          function button(txt,action,disabled){const b=node('button','button',txt);b.type='button';b.disabled=!!disabled;b.onclick=action;return b}
          function row(p){const r=node('div','cel-event-row');if(p.image){const img=node('img');img.src=p.image;img.alt='';r.append(img)}const name=node('div','cel-event-name');name.append(node('strong','',p.title),node('small','',p.label));r.append(name);return r}
          function render(){
            sync();const state=current();left.replaceChildren();right.replaceChildren();if(!state)return;
            mode.value=state.mode;
            root.querySelectorAll('.cel-event-tab').forEach(b=>b.classList.toggle('is-current',b.dataset.key===key));
            root.querySelectorAll('.cel-event-filter').forEach(b=>b.classList.toggle('is-current',b.dataset.filter===filter));
            const matches=library.filter(p=>eligible(p)&&eventMatch(p));
            const ids=state.mode==='manual'?state.ids:state.mode==='auto'?matches.slice(0,data.limits[key]).map(p=>p.id):[];
            const visible=ids.map(id=>library.find(p=>p.id===id&&eligible(p))).filter(Boolean);
            root.querySelector('.cel-event-selected-title').textContent='Contenus affichés ('+visible.length+')';
            root.querySelector('.cel-event-help').textContent=state.mode==='manual'?'Déplace les contenus avec la poignée ou les flèches. Maximum : '+data.limits[key]:state.mode==='auto'?'Sélection automatique, du plus récent au plus ancien.':'La sélection standard sera utilisée.';
            if(!visible.length)left.append(node('p','','Aucun contenu sélectionné. La rubrique standard reste disponible.'));
            visible.forEach((p,i)=>{
              const r=row(p);
              if(state.mode==='manual'){
                const grip=node('span','cel-event-grip','⠿ '+(i+1));r.prepend(grip);r.dataset.id=p.id;
                grip.addEventListener('pointerdown',e=>{
                  if(e.button!==0)return;e.preventDefault();grip.setPointerCapture(e.pointerId);let target=p.id;r.classList.add('dragging');
                  function move(ev){const hit=Array.from(left.children).find(el=>{const rect=el.getBoundingClientRect();return el.dataset.id&&ev.clientY>=rect.top&&ev.clientY<=rect.bottom});if(hit)target=Number(hit.dataset.id)}
                  function finish(ev){grip.removeEventListener('pointermove',move);grip.removeEventListener('pointerup',finish);grip.removeEventListener('pointercancel',finish);if(ev.type==='pointerup'&&target!==p.id){const from=state.ids.indexOf(p.id),to=state.ids.indexOf(target);state.ids.splice(from,1);state.ids.splice(to,0,p.id)}render()}
                  grip.addEventListener('pointermove',move);grip.addEventListener('pointerup',finish);grip.addEventListener('pointercancel',finish);
                });
                [-1,1].forEach(step=>r.append(button(step<0?'↑':'↓',()=>{const j=state.ids.indexOf(p.id),k=state.ids.indexOf(visible[i+step].id);[state.ids[j],state.ids[k]]=[state.ids[k],state.ids[j]];render()},i+step<0||i+step>=visible.length)));
                r.append(button('×',()=>{state.ids=state.ids.filter(id=>id!==p.id);render()}));
              }left.append(r);
            });
            const q=search.value.toLocaleLowerCase().trim();
            const available=library.filter(p=>eligible(p)&&(filter==='all'||eventMatch(p))&&(p.title+' '+p.label).toLocaleLowerCase().includes(q));
            if(!available.length)right.append(node('p','','Aucun contenu disponible pour ce filtre.'));
            available.forEach(p=>{
              const r=row(p),checked=ids.includes(p.id);r.classList.toggle('is-selected',checked);
              const cb=node('input');cb.type='checkbox';cb.checked=checked;cb.disabled=state.mode!=='manual'||(!checked&&state.ids.length>=data.limits[key]);cb.setAttribute('aria-label','Sélectionner '+p.title);
              cb.onchange=()=>{if(cb.checked){if(!state.ids.includes(p.id)&&state.ids.length<data.limits[key])state.ids.push(p.id)}else state.ids=state.ids.filter(id=>id!==p.id);render()};r.append(cb);right.append(r);
            });
          }
          edit.onchange=()=>{search.value='';filter='event';render()};mode.onchange=()=>{const state=current();if(!state)return;if(mode.value==='manual'&&state.mode==='auto'&&!state.ids.length)state.ids=library.filter(p=>eligible(p)&&eventMatch(p)).slice(0,data.limits[key]).map(p=>p.id);state.mode=mode.value;render()};
          root.querySelectorAll('.cel-event-tab').forEach(b=>b.onclick=()=>{key=b.dataset.key;search.value='';render()});
          root.querySelectorAll('.cel-event-filter').forEach(b=>b.onclick=()=>{filter=b.dataset.filter;render()});
          search.oninput=render;root.closest('form').addEventListener('submit',sync);render();
        })();
        </script>
        <?php
    }
    static function save_events(array $raw): void {
        $tax=Celestin_Library::TAX['evenement'];$active=absint($raw['active']??0);
        if($active&&!term_exists($active,$tax))$active=0;
        $old=get_option(self::EVENT_OPTION,[]);$out=['active'=>$active,'profiles'=>$old['profiles']??[]];
        $profiles=json_decode((string)($raw['json']??''),true);
        if(is_array($profiles))foreach($profiles as $id=>$p){
            $id=absint($id);if(!$id||!term_exists($id,$tax)||!is_array($p))continue;
            $v=['sections'=>[],'manual'=>[],'modes'=>[]];
            foreach(['news'=>8,'cel_livre'=>1,'cel_histoire'=>3,'cel_coloriage'=>4] as $key=>$limit){
                $state=$p[$key]??[];$mode=$state['mode']??'auto';if(!in_array($mode,['auto','manual','standard'],true))$mode='auto';
                $v['modes'][$key]=$mode;$v['sections'][$key]=$mode!=='standard';
                $ids=array_values(array_unique(array_filter(array_map('absint',(array)($state['ids']??[])))));
                $ids=array_values(array_filter($ids,fn($pid)=>get_post_status($pid)==='publish'&&in_array(get_post_type($pid),$key==='news'?['cel_livre','cel_histoire','cel_coloriage']:[$key],true)));
                $v['manual'][$key]=array_slice($ids,0,$limit);
            }
            $out['profiles'][$id]=$v;
        }
        update_option(self::EVENT_OPTION,$out,false);
    }
    static function event_posts(string $key,int $limit): array {
        if(is_admin()||!is_front_page())return [];
        $cfg=get_option(self::EVENT_OPTION,[]);$id=absint($cfg['active']??0);if(!$id)return [];
        $p=$cfg['profiles'][$id]??[];if(empty($p['sections'][$key]))return [];
        $mode=$p['modes'][$key]??$p['mode']??'auto';if($mode==='standard')return [];
        $args=['post_type'=>$key==='news'?['cel_livre','cel_histoire','cel_coloriage']:$key,'post_status'=>'publish','posts_per_page'=>$limit,'orderby'=>'date','order'=>'DESC'];
        if($mode==='manual'){
            $ids=array_filter(array_map('absint',(array)($p['manual'][$key]??[])));if(!$ids)return [];
            $args['post__in']=$ids;$args['orderby']='post__in';
        }else $args['tax_query']=[['taxonomy'=>Celestin_Library::TAX['evenement'],'field'=>'term_id','terms'=>[$id]]];
        return get_posts($args);
    }
    static function featured_settings(): void {
        echo '<h2 style="margin-top:32px">Contenus mis en avant</h2><p>Les mini-histoires affichent automatiquement les huit dernières publications, ou jusqu’à trois histoires choisies en mode manuel. Choisis également jusqu’à quatre activités dans leur ordre d’affichage. Tous les personnages publiés sont affichés automatiquement dans un ordre aléatoire. Les nouveautés sont gérées dans leur propre onglet.</p><div class="cel-feature-settings">';
        foreach(Celestin_Core::TYPES as $type=>$config){
            if($type==='cel_personnage')continue;
            $current=self::featured($type,$type==='cel_histoire'?3:($type==='cel_personnage'?8:($type==='cel_coloriage'?4:1)));
            $id=$current?(int)$current[0]->ID:0;
            $posts=get_posts(['post_type'=>$type,'post_status'=>'publish','posts_per_page'=>-1,'orderby'=>'title','order'=>'ASC']);
            echo '<div class="cel-feature-setting"><label for="featured_'.esc_attr($type).'"><strong>'.esc_html($config[0]).'</strong></label>';
            if($type==='cel_histoire'){
                echo '<p><label>Mode d’affichage <select name="design[story_mode]"><option value="auto"'.selected(get_option('cel_home_story_mode','auto'),'auto',false).'>Automatique — dernières publications</option><option value="manual"'.selected(get_option('cel_home_story_mode','auto'),'manual',false).'>Manuel — sélection ci-dessous</option></select></label></p><p>La sélection ci-dessous est conservée et utilisée en mode manuel.</p>';
            }
            if(in_array($type,['cel_histoire','cel_personnage','cel_coloriage'],true)){
                $max=$type==='cel_histoire'?3:($type==='cel_coloriage'?4:8);
                for($i=0;$i<$max;$i++){
                    $chosen=isset($current[$i])?(int)$current[$i]->ID:0;
                    echo '<label for="featured_'.$type.'_'.$i.'">'.($type==='cel_histoire'?'Histoire':($type==='cel_coloriage'?'Activité':'Personnage')).' '.($i+1).'</label><select id="featured_'.$type.'_'.$i.'" name="design[featured]['.$type.'][]"><option value="0">Aucun contenu</option>';
                    foreach($posts as $p)echo '<option value="'.(int)$p->ID.'"'.selected($chosen,$p->ID,false).'>'.esc_html($p->post_title).'</option>';
                    echo '</select>';
                }
                echo '</div>';continue;
            }
            if($id&&has_post_thumbnail($id))echo get_the_post_thumbnail($id,'thumbnail',['class'=>'cel-feature-thumb']);
            echo '<select id="featured_'.esc_attr($type).'" name="design[featured]['.esc_attr($type).']"><option value="0">Aucun contenu</option>';
            foreach($posts as $p)echo '<option value="'.(int)$p->ID.'"'.selected($id,$p->ID,false).'>'.esc_html($p->post_title).'</option>';
            echo '</select></div>';
        }
        echo '</div>';
    }
    static function settings(): void {
        if (!current_user_can('manage_options')) return;
        $o = self::options();
        $tabs = [
            'identite' => ['Identité visuelle', 'dashicons-admin-appearance'],
            'reseaux' => ['Réseaux sociaux', 'dashicons-share'],
            'accueil' => ['Page d’accueil', 'dashicons-admin-home'],
            'rubriques' => ['Contenus mis en avant', 'dashicons-screenoptions'],
            'nouveautes' => ['Nouveautés', 'dashicons-megaphone'],
            'evenements' => ['Temps forts', 'dashicons-calendar-alt'],
            'fleuriville' => ['Origines & pied de page', 'dashicons-location-alt'],
            'newsletter' => ['Newsletter', 'dashicons-email-alt']
        ];
        $active = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'identite';
        if (!isset($tabs[$active])) $active = 'identite';
        echo '<div class="wrap cel-design-admin"><h1>Apparence de la page d’accueil</h1>';
        echo '<p class="cel-admin-intro">Personnalise les images et les textes de ton site. Tes illustrations restent dans la médiathèque WordPress.</p>';
        if (isset($_GET['saved'])) echo '<div class="notice notice-success is-dismissible"><p>Les paramètres ont été enregistrés.</p></div>';
        echo '<form id="cel-design-form" action="' . esc_url(admin_url('admin-post.php')) . '" method="post">';
        echo '<input type="hidden" name="action" value="celestin_save_design">';
        echo '<input type="hidden" name="cel_active_tab" id="cel-active-tab" value="' . esc_attr($active) . '">';
        wp_nonce_field('celestin_save_design');
        echo '<nav class="cel-design-tabs" role="tablist" aria-label="Catégories des paramètres">';
        foreach ($tabs as $key => [$title, $icon]) {
            echo '<button type="button" class="cel-design-tab' . ($active === $key ? ' is-active' : '') .
                '" id="cel-tab-' . esc_attr($key) . '" role="tab" aria-controls="cel-panel-' . esc_attr($key) . '" aria-selected="' .
                ($active === $key ? 'true' : 'false') . '" tabindex="' . ($active === $key ? '0' : '-1') .
                '" data-cel-tab="' . esc_attr($key) . '"><span class="dashicons ' . esc_attr($icon) . '" aria-hidden="true"></span>' .
                esc_html($title) . '</button>';
        }
        echo '</nav>';

        echo '<section id="cel-panel-identite" class="cel-design-panel" role="tabpanel" aria-labelledby="cel-tab-identite"' . ($active !== 'identite' ? ' hidden' : '') . '>';
        echo '<h2>Identité visuelle</h2><p>Le logo en haut du site est aussi utilisé dans le pied de page si aucun autre n’est sélectionné.</p>';
        self::image_setting('logo', 'Logo principal (haut du site)', $o);
        self::image_setting('footer_logo', 'Logo alternatif pour le pied de page (facultatif)', $o);
        echo '<h2>Icône du site</h2><p>Par défaut, la photo d’identité de la fiche personnage de Célestin est utilisée automatiquement. Tu peux ajouter tes propres illustrations saisonnières (facultatif). L’icône change selon le mois et conserve le portrait original si aucune variante n’est choisie.</p>';
        $portrait=self::portrait_icon_id();
        if($portrait)echo '<div style="margin:12px 0">'.wp_get_attachment_image($portrait,[80,80],false,['alt'=>'Portrait de référence de Célestin']).'<p>Portrait utilisé par défaut</p></div>';
        else echo '<p>Le portrait sera utilisé dès que la fiche de Célestin sera publiée avec une photo d’identité.</p>';
        foreach(['spring'=>'Printemps','summer'=>'Été','autumn'=>'Automne','winter'=>'Hiver'] as $season=>$label)self::image_setting('favicon_'.$season,'Icône — '.$label.' (facultatif)',$o);
        echo '</section>';
        echo '<section id="cel-panel-reseaux" class="cel-design-panel" role="tabpanel" aria-labelledby="cel-tab-reseaux"' . ($active !== 'reseaux' ? ' hidden' : '') . '>';
        echo '<h2>Réseaux sociaux</h2><p>Les liens renseignés sont utilisés dans l’en-tête du site.</p>';
        foreach(['facebook'=>'Facebook','instagram'=>'Instagram','pinterest'=>'Pinterest'] as $key=>$label)self::text_setting($key,'Lien '.$label,$o);
        echo '</section>';

        echo '<section id="cel-panel-accueil" class="cel-design-panel" role="tabpanel" aria-labelledby="cel-tab-accueil"' . ($active !== 'accueil' ? ' hidden' : '') . '>';
        echo '<h2>Page d’accueil</h2><p>Personnalise la grande illustration d’ouverture et son titre.</p>';
        self::image_setting('hero', 'Illustration principale de l’accueil (sans texte)', $o);
        self::text_setting('hero_intro', 'Accroche', $o);
        self::text_setting('headline', 'Grand titre de l’accueil', $o);
        self::text_setting('hero_description', 'Description', $o, true);
        self::text_setting('hero_button', 'Texte du bouton', $o);
        self::text_setting('hero_link', 'Lien du bouton', $o);
        echo '<h2 style="margin-top:28px">Taille et position du texte</h2><p>Déplace le bloc de texte sur ton illustration ou affiche-le sous l’image.</p>';
        foreach(['hero_title_size'=>['Taille maximale du titre (px)',24,100],'hero_text_size'=>['Taille de la description (px)',12,32],'hero_x'=>['Position horizontale (%) : gauche → droite',0,100],'hero_y'=>['Position verticale (%) : haut → bas',0,100],'hero_width'=>['Largeur maximale du bloc de texte (px)',240,1100]] as $key=>[$label,$min,$max]){
            echo '<div class="cel-text-setting"><label for="cel_'.esc_attr($key).'"><strong>'.esc_html($label).'</strong></label><input type="number" id="cel_'.esc_attr($key).'" name="design['.esc_attr($key).']" min="'.$min.'" max="'.$max.'" value="'.esc_attr((string)$o[$key]).'" style="max-width:180px"></div>';
        }
        echo '<div class="cel-text-setting"><label for="cel_hero_text_location"><strong>Emplacement du texte</strong></label><select id="cel_hero_text_location" name="design[hero_text_location]"><option value="overlay"'.selected($o['hero_text_location'],'overlay',false).'>Sur l’image</option><option value="below"'.selected($o['hero_text_location'],'below',false).'>Sous l’image</option></select></div>';

        echo '</section>';

        echo '<section id="cel-panel-rubriques" class="cel-design-panel" role="tabpanel" aria-labelledby="cel-tab-rubriques"' . ($active !== 'rubriques' ? ' hidden' : '') . '>';
        self::featured_settings();
        echo '</section>';

        echo '<section id="cel-panel-nouveautes" class="cel-design-panel" role="tabpanel" aria-labelledby="cel-tab-nouveautes"' . ($active !== 'nouveautes' ? ' hidden' : '') . '>';
        echo '<input type="hidden" name="cel_news_present" value="1">';
        Celestin_News::selection();
        echo '</section>';

        echo '<section id="cel-panel-evenements" class="cel-design-panel" role="tabpanel" aria-labelledby="cel-tab-evenements"' . ($active !== 'evenements' ? ' hidden' : '') . '>';
        self::event_settings();
        echo '</section>';
        echo '<section id="cel-panel-fleuriville" class="cel-design-panel" role="tabpanel" aria-labelledby="cel-tab-fleuriville"' . ($active !== 'fleuriville' ? ' hidden' : '') . '>';
        echo '<h2>Les origines de Célestin</h2><p>Configure la section juste avant le pied de page. Le texte reste à gauche des oreilles.</p>';
        self::image_setting('city', 'Carte illustrée de Fleuriville', $o);
        self::image_setting('footer', 'Image de fond facultative', $o);
        self::text_setting('origin_title', 'Titre de la section', $o);
        self::text_setting('origin_description', 'Description', $o, true);
        self::text_setting('origin_button', 'Texte du bouton', $o);
        self::text_setting('origin_link', 'Lien vers la page des origines (URL)', $o);
        echo '</section>';

        echo '<section id="cel-panel-newsletter" class="cel-design-panel" role="tabpanel" aria-labelledby="cel-tab-newsletter"' . ($active !== 'newsletter' ? ' hidden' : '') . '>';
        echo '<h2>Newsletter</h2><p>Si le lien est vide, le site affiche « Bientôt disponible ».</p>';
        self::text_setting('newsletter', 'Lien vers le formulaire de newsletter (facultatif)', $o);
        echo '</section>';

        echo '<div class="cel-design-actions">';
        submit_button('Enregistrer les paramètres', 'primary', 'submit', false);
        echo '<span>Les modifications de tous les onglets sont enregistrées ensemble.</span></div>';
        echo '</form></div>';
    }
    static function save_design(): void {
        if (!current_user_can('manage_options')) wp_die('Accès refusé.');
        check_admin_referer('celestin_save_design');
        $raw=isset($_POST['design'])&&is_array($_POST['design'])?wp_unslash($_POST['design']):[];
        $out=self::options();
        foreach(['logo','footer_logo','hero','footer','story','book','character','city','activity'] as $key) $out[$key]=absint($raw[$key]??$out[$key]??0);
        foreach(['spring','summer','autumn','winter'] as $season){
            $key='favicon_'.$season;
            if(!array_key_exists($key,$raw))continue;
            $image=absint($raw[$key]);
            $out[$key]=$image&&wp_attachment_is_image($image)?$image:0;
        }
        foreach(['headline','welcome','hero_intro','hero_description','hero_button','origin_title','origin_description','origin_button'] as $key) $out[$key]=sanitize_textarea_field($raw[$key]??$out[$key]);
        $out['origin_link']=esc_url_raw($raw['origin_link']??$out['origin_link']);
        $out['hero_link']=esc_url_raw($raw['hero_link']??'#cel-decouvrir');
        foreach(['hero_title_size'=>[24,100],'hero_text_size'=>[12,32],'hero_x'=>[0,100],'hero_y'=>[0,100],'hero_width'=>[240,1100]] as $key=>[$min,$max]){
            $out[$key]=max($min,min($max,(int)($raw[$key]??$out[$key])));
        }
        $out['hero_text_location']=($raw['hero_text_location']??'overlay')==='below'?'below':'overlay';

        $out['newsletter']=esc_url_raw($raw['newsletter']??'');
        foreach(['facebook','instagram','pinterest'] as $key)$out[$key]=esc_url_raw($raw[$key]??'',['http','https']);
        $previous=get_option(self::OPTION,[]);
        $out['featured']=is_array($previous)?($previous['featured']??[]):[];
        if(isset($raw['featured'])&&is_array($raw['featured'])){
            foreach(Celestin_Core::TYPES as $type=>$config){
                if(in_array($type,['cel_histoire','cel_personnage','cel_coloriage'],true)){
                    $ids=array_unique(array_map('absint',(array)($raw['featured'][$type]??[])));
                    $out['featured'][$type]=array_slice(array_values(array_filter($ids,fn($id)=>$id&&get_post_type($id)===$type&&get_post_status($id)==='publish')),0,$type==='cel_histoire'?3:($type==='cel_coloriage'?4:8));
                    continue;
                }
                $id=absint($raw['featured'][$type]??0);
                $out['featured'][$type]=$id&&get_post_type($id)===$type&&get_post_status($id)==='publish'?$id:0;
            }
        }
        update_option(self::OPTION,$out);
        if(isset($raw['story_mode']))update_option('cel_home_story_mode',$raw['story_mode']==='manual'?'manual':'auto',false);
        if(isset($_POST['cel_event'])&&is_array($_POST['cel_event']))self::save_events(wp_unslash($_POST['cel_event']));
        if(isset($_POST['cel_news_present']))Celestin_News::save_selection((array)($_POST['cel_news_ids']??[]));
        $allowed_tabs = ['identite', 'reseaux', 'accueil', 'rubriques', 'nouveautes', 'evenements', 'fleuriville', 'newsletter'];
        $active = isset($_POST['cel_active_tab']) ? sanitize_key(wp_unslash($_POST['cel_active_tab'])) : 'identite';
        if (!in_array($active, $allowed_tabs, true)) $active = 'identite';
        wp_safe_redirect(add_query_arg(['saved' => '1', 'tab' => $active], admin_url('admin.php?page=celestin-design')));
        exit;
    }
    static function assets(): void {
        if (!is_front_page()) return;
        wp_enqueue_style('celestin-home', plugins_url('../assets/home.css',__FILE__), [], '0.4.85');
        wp_enqueue_script('celestin-home', plugins_url('../assets/home.js',__FILE__), [], '0.4.85', true);
    }
    static function template(string $template): string {
        if (is_front_page() && !is_admin() && !is_feed() && !is_embed() && !is_preview()) return __DIR__.'/front-page.php';
        return $template;
    }
    // Les rubriques de l’accueil utilisent exclusivement les fiches cochées
    // « Afficher cette fiche sur la page d’accueil ».
    static function featured(string $type, int $limit=8): array {
        if (!in_array($type, array_keys(Celestin_Core::TYPES), true)) return [];
        if(in_array($type,['cel_livre','cel_histoire','cel_coloriage'],true)){
            $event=self::event_posts($type,$type==='cel_livre'?1:($type==='cel_histoire'?min(3,max(1,$limit)):min(4,max(1,$limit))));if($event)return $event;
        }
        $saved=self::options()['featured']??[];
        if(is_array($saved)&&array_key_exists($type,$saved)){
            $ids=array_unique(array_map('absint',(array)$saved[$type]));$result=[];
            foreach($ids as $id){$p=$id?get_post($id):null;if($p&&$p->post_type===$type&&$p->post_status==='publish')$result[]=$p;}
            return array_slice($result,0,$type==='cel_histoire'?max(1,min(3,$limit)):($type==='cel_personnage'?max(1,min(8,$limit)):($type==='cel_coloriage'?max(1,min(4,$limit)):1)));
        }
        return get_posts([
            'post_type' => $type,
            'post_status' => 'publish',
            'posts_per_page' => $type==='cel_histoire'?max(1,min(3,$limit)):($type==='cel_personnage'?max(1,min(8,$limit)):($type==='cel_coloriage'?max(1,min(4,$limit)):1)),
            'meta_key' => '_cel_feature_home',
            'meta_value' => '1',
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
    }
    static function latest(string $type, bool $featured=true): ?WP_Post {
        if($featured) {
            $items=self::featured($type,1);
        } else {
            $items=get_posts(['post_type'=>$type,'post_status'=>'publish','posts_per_page'=>1,'orderby'=>'date','order'=>'DESC']);
        }
        return $items?$items[0]:null;
    }
    // La publication alimente automatiquement l’accueil standard. Le mode événement reste prioritaire.
    static function story_cards(): array {
        $event=self::event_posts('cel_histoire',3);if($event)return $event;
        if(get_option('cel_home_story_mode','auto')==='manual')return self::featured('cel_histoire',3);
        return get_posts(['post_type'=>'cel_histoire','post_status'=>'publish','posts_per_page'=>8,'orderby'=>['date'=>'DESC','ID'=>'DESC']]);
    }
    static function cards(bool $manual=false): array {
        if(!$manual){
            $event=self::event_posts('news',8);if($event)return $event;
            if(get_option('cel_home_news_mode','auto')!=='manual')return get_posts(['post_type'=>['cel_coloriage','cel_personnage','cel_livre','cel_histoire'],'post_status'=>'publish','posts_per_page'=>8,'orderby'=>['date'=>'DESC','ID'=>'DESC'],'suppress_filters'=>false]);
        }
        $posts=get_posts([
            'post_type' => ['cel_coloriage', 'cel_personnage', 'cel_livre', 'cel_histoire'],
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_key' => '_cel_en_nouveaute',
            'meta_value' => '1',
            'orderby' => 'date',
            'order' => 'DESC',
            'suppress_filters' => false,
        ]);
        $order=array_map('absint',(array)get_option('cel_news_order',[]));
        if(!$order)return $posts;
        $by_id=[];foreach($posts as $post)$by_id[$post->ID]=$post;
        $sorted=[];foreach($order as $id){if(isset($by_id[$id])){$sorted[]=$by_id[$id];unset($by_id[$id]);}}
        return array_merge($sorted,array_values($by_id));
    }
    static function url(string $type): string {
        $url=get_post_type_archive_link($type);
        return $url?:home_url('/');
    }
    static function story_text(?WP_Post $story): string {
        if(!$story) return 'Retrouve bientôt les premières mini-histoires de Célestin, à écouter et à lire en famille.';
        $structured = class_exists('Celestin_Fields') ? Celestin_Fields::value($story->ID, 'texte') : '';
        return $structured ?: wp_strip_all_tags($story->post_content);
    }
}
Celestin_Home::init();
