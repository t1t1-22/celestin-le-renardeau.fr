<?php if(!defined('ABSPATH'))exit;
$options=Celestin_Home::options();
if(empty($options['instagram']))$options['instagram']='https://www.instagram.com/p/Dc9A04JjPZq/';
$socials=[
 'facebook'=>['Facebook','<path fill="currentColor" d="M14 21v-8h3l.5-3H14V8c0-1 .4-2 2-2h2V3a20 20 0 0 0-3-.3c-3 0-5 1.8-5 5V10H7v3h3v8z"/>'],
 'instagram'=>['Instagram','<rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.5" cy="6.5" r="1.2" fill="currentColor"/>'],
 'pinterest'=>['Pinterest','<path fill="currentColor" d="M12 2a10 10 0 0 0-3.6 19.3c-.1-.8-.2-2 .1-3l1.3-5.5s-.3-.8-.3-1.8c0-1.7.9-3 2.2-3 1 0 1.4.7 1.4 1.5 0 1-.6 2.4-.9 3.8-.3 1.2.6 2.1 1.7 2.1 2 0 3.4-2.5 3.4-5.3 0-2.2-1.5-3.8-4.2-3.8-3.1 0-5 2.3-5 4.9 0 .9.3 1.5.6 2 .2.2.2.3.1.6l-.2.8c-.1.3-.3.4-.6.3C6.5 14.2 6 12.7 6 11c0-3 2.5-6.6 7.5-6.6 4 0 6.5 2.9 6.5 6 0 4.1-2.3 7.1-5.7 7.1-1.1 0-2.2-.6-2.6-1.3l-.7 2.8c-.3 1-.9 2-1.4 2.8A10 10 0 1 0 12 2z"/>']
];
?>
<header class="cel-interior-header">
 <div class="cel-contact-bar"><div class="cel-contact-inner"><a class="cel-contact-email" href="mailto:celestin-le-renardeau@gmail.com">celestin-le-renardeau@gmail.com</a><div class="cel-social-links" aria-label="Réseaux sociaux"><?php foreach($socials as $key=>[$label,$svg]): if(empty($options[$key]))continue; ?><a class="cel-social-<?php echo esc_attr($key); ?>" href="<?php echo esc_url($options[$key]); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr($label); ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><?php echo $svg; ?></svg></a><?php endforeach; ?></div></div></div>
 <div class="cel-interior-header-inner">
 <a class="cel-logo" href="<?php echo esc_url(home_url('/')); ?>" aria-label="Accueil — Célestin le renardeau"><?php if(Celestin_Home::image('logo'))echo '<img src="'.esc_url(Celestin_Home::image('logo')).'" alt="Célestin le renardeau">';else echo '<span class="cel-script">Célestin<small>le renardeau</small></span>'; ?></a>
 <?php require __DIR__.'/navigation.php'; ?>
 
 <form action="<?php echo esc_url(home_url('/')); ?>" method="get" class="cel-nav-search"><input type="search" name="s" aria-label="Rechercher sur le site" placeholder="Rechercher" required><button aria-label="Rechercher" type="submit"><svg viewBox="0 0 32 32" aria-hidden="true"><?php echo $icons['search']; ?></svg></button></form>
 </div>
</header>
