<?php if(!defined('ABSPATH'))exit;
$icons=['home'=>'<path fill="#65bfd8" d="M5 14L16 5l11 9v15H5z"/><path fill="#e77a32" d="M2 14L16 2l14 12-3 3L16 7 5 17z"/><path fill="#fff0bb" d="M13 19h7v10h-7z"/>',
'book'=>'<path fill="#f5bd2b" d="M2 5q8-3 14 3v22Q8 24 2 27zM30 5q-8-3-14 3v22q8-6 14-3z"/><path fill="none" stroke="#d49417" d="M16 8v22"/>',
'audio'=>'<path d="M5 20V14a11 11 0 0122 0v6" fill="none" stroke="#2387ac" stroke-width="5"/><rect x="2" y="17" width="8" height="13" rx="3" fill="#56bfe0"/><rect x="22" y="17" width="8" height="13" rx="3" fill="#56bfe0"/>',
'activity'=>'<path fill="#f39a35" d="M16 2C-1 2-3 26 12 30c7 2 4-7 9-8 12-2 15-20-5-20z"/><circle cx="8" cy="12" r="3" fill="#55a8d1"/><circle cx="15" cy="7" r="3" fill="#fff2bd"/><circle cx="23" cy="11" r="3" fill="#df5152"/><circle cx="24" cy="17" r="3" fill="#76ad55"/>',
'place'=>'<path fill="#f27b30" d="M16 2a12 12 0 00-12 12c0 8 12 17 12 17s12-9 12-17A12 12 0 0016 2z"/><circle cx="16" cy="14" r="5" fill="#fff8e7"/>',
'people'=>'<circle cx="7" cy="9" r="5" fill="#58a9c7"/><circle cx="25" cy="9" r="5" fill="#79b65f"/><path fill="#58a9c7" d="M0 29V22a7 7 0 0114 0v7z"/><path fill="#79b65f" d="M18 29V22a7 7 0 0114 0v7z"/><circle cx="16" cy="7" r="6" fill="#e89a3f"/><path fill="#e89a3f" d="M7 30V22a9 9 0 0118 0v8z"/>',
'search'=>'<circle cx="13" cy="13" r="10" stroke="#30251d" stroke-width="3" fill="none"/><path d="M21 21l9 9" stroke="#30251d" stroke-width="4" stroke-linecap="round"/>'];
$items=[
 ['Accueil',home_url('/'),'home',is_front_page()],
 ['Les livres',Celestin_Home::url('cel_livre'),'book',is_singular('cel_livre')||is_post_type_archive('cel_livre')],
 ['Mini-histoires',Celestin_Home::url('cel_histoire'),'audio',is_singular('cel_histoire')||is_post_type_archive('cel_histoire')],
 ['Mes petites activités',Celestin_Home::url('cel_coloriage'),'activity',is_singular('cel_coloriage')||is_post_type_archive('cel_coloriage')],
];
?><nav class="cel-nav cel-nav-illustrated" aria-label="Navigation principale">
<?php foreach($items as [$label,$url,$icon,$active]): ?>
<a href="<?php echo esc_url($url); ?>" <?php if($active)echo 'class="is-active" aria-current="page"'; ?>><span><?php echo esc_html($label); ?></span></a>
<?php endforeach; ?>
<div class="cel-town-nav cel-town-menu">
<button type="button" class="cel-town-trigger<?php echo (is_post_type_archive(['cel_lieu','cel_personnage'])||is_singular(['cel_lieu','cel_personnage']))?' is-active':''; ?>" aria-expanded="false" aria-controls="cel-town-submenu"><span>Fleuriville</span><svg viewBox="0 0 16 16" width="13" height="13" aria-hidden="true"><path d="m4 6 4 4 4-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
<div id="cel-town-submenu" class="cel-town-submenu" hidden><a href="<?php echo esc_url(Celestin_Home::url('cel_personnage')); ?>">Les personnages</a><a href="<?php echo esc_url(add_query_arg('vue','galerie',Celestin_Home::url('cel_lieu'))); ?>">Galerie photo</a><a href="<?php echo esc_url(add_query_arg('vue','lieux',Celestin_Home::url('cel_lieu'))); ?>">Les lieux</a></div></div>
</nav>
<style>
body .cel-interior-header .cel-town-menu{position:relative;display:flex;align-items:center;flex-shrink:0}
body .cel-interior-header .cel-town-trigger{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:7px 9px;background:transparent;border:0;border-radius:9px;color:#583219;font:600 15px/1.2 'Baloo 2',sans-serif;cursor:pointer;white-space:nowrap}
body .cel-interior-header .cel-town-trigger svg{width:13px!important;height:13px!important;flex:none;filter:none;transition:transform .2s;display:block}
body .cel-interior-header .cel-town-trigger[aria-expanded=true] svg{transform:rotate(180deg)}
body .cel-interior-header .cel-town-trigger:hover,body .cel-interior-header .cel-town-trigger[aria-expanded=true],body .cel-interior-header .cel-town-trigger.is-active{background:#e98932;color:white}
body .cel-interior-header .cel-town-submenu{position:absolute;top:100%;left:0;min-width:190px;background:#fffdfa;border:1px solid #ead5bb;padding:6px;border-radius:12px;box-shadow:0 8px 22px #63361922;z-index:1001}
body .cel-interior-header .cel-town-submenu[hidden]{display:none!important}
body .cel-interior-header .cel-town-submenu a{display:block!important;padding:10px!important;border-radius:8px;color:#633619!important}
body .cel-interior-header .cel-town-submenu a:hover,body .cel-interior-header .cel-town-submenu a:focus-visible{background:#e98932!important;color:#fff!important;text-decoration:none!important}
@media(max-width:600px){body .cel-interior-header .cel-town-trigger{font-size:13px;padding:6px}body .cel-interior-header .cel-town-submenu{position:fixed;left:auto;right:16px;top:var(--cel-town-top,140px)}}
body .cel-interior-header nav.cel-nav a,body .cel-interior-header .cel-town-trigger{text-decoration:none!important}
body .cel-interior-header nav.cel-nav a:hover,body .cel-interior-header nav.cel-nav a:hover span,body .cel-interior-header .cel-town-trigger:hover,body .cel-interior-header .cel-town-trigger:hover span{color:#fff!important;text-decoration:none!important}
</style>
<script>
(function(){
 const menu=document.querySelector('.cel-town-menu');if(!menu)return;
 const button=menu.querySelector('.cel-town-trigger'),sub=menu.querySelector('.cel-town-submenu');let opened=false;
 function setOpen(value){opened=value;button.setAttribute('aria-expanded',String(value));sub.hidden=!value;if(value)sub.style.setProperty('--cel-town-top',button.getBoundingClientRect().bottom+'px');}
 menu.addEventListener('pointerenter',e=>{if(e.pointerType==='mouse')setOpen(true)});
 menu.addEventListener('pointerleave',e=>{if(e.pointerType==='mouse'&&!menu.contains(document.activeElement))setOpen(false);else if(e.pointerType==='mouse'&&document.activeElement===button){button.blur();setOpen(false)}});
 button.addEventListener('click',e=>{if(e.detail&&matchMedia('(hover:hover)').matches&&opened)return;setOpen(!opened)});
 menu.addEventListener('focusout',e=>{if(!menu.contains(e.relatedTarget)&&!menu.matches(':hover'))setOpen(false)});
 menu.addEventListener('keydown',e=>{if(e.key==='Escape'){setOpen(false);button.focus()}else if(e.key==='ArrowDown'&&e.target===button){e.preventDefault();setOpen(true);sub.querySelector('a').focus()}});
 document.addEventListener('pointerdown',e=>{if(!menu.contains(e.target))setOpen(false)});
})();
</script>
