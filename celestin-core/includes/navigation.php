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
 ['Fleuriville',Celestin_Home::url('cel_lieu'),'place',is_singular('cel_lieu')||is_post_type_archive('cel_lieu')],
 ['Les personnages',Celestin_Home::url('cel_personnage'),'people',is_singular('cel_personnage')||is_post_type_archive('cel_personnage')]
];
?><nav class="cel-nav cel-nav-illustrated" aria-label="Navigation principale">
<?php foreach($items as [$label,$url,$icon,$active]): ?>
<a href="<?php echo esc_url($url); ?>" <?php if($active)echo 'class="is-active" aria-current="page"'; ?>><span><?php echo esc_html($label); ?></span></a>
<?php endforeach; ?>
<form action="<?php echo esc_url(home_url('/')); ?>" method="get" class="cel-nav-search"><input type="search" name="s" aria-label="Rechercher sur le site" placeholder="Rechercher" required><button aria-label="Rechercher" type="submit"><svg viewBox="0 0 32 32" aria-hidden="true"><?php echo $icons['search']; ?></svg></button></form>
</nav>