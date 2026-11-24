<?php if(!defined('ABSPATH'))exit;
$options=Celestin_Home::options();
$links=['Personnages'=>['url'=>Celestin_Home::url('cel_personnage')],'Mes petites activités'=>['url'=>Celestin_Home::url('cel_coloriage')],'Les livres'=>['url'=>Celestin_Home::url('cel_livre')],'Mini-histoires'=>['url'=>Celestin_Home::url('cel_histoire')]];
?>
<footer class="cel-footer cel-footer-fox"><div aria-hidden="true" class="cel-fox-divider" style="position:absolute;bottom:calc(100% - 2px);left:50%;transform:translateX(-50%);width:min(52vw,800px);aspect-ratio:4/1;z-index:20;pointer-events:none;background:#e98932;"></div>
 <div class="cel-footer-columns">
 <div class="cel-footer-brand"><a href="<?php echo esc_url(home_url('/')); ?>" aria-label="Accueil — Célestin le renardeau"><?php
   // Le logo spécifique du pied de page est facultatif : à défaut, reprendre celui de l'en-tête.
   $footer_logo=Celestin_Home::image('footer_logo') ?: Celestin_Home::image('logo');
   if($footer_logo) echo '<img class="cel-footer-logo" src="'.esc_url($footer_logo).'" alt="Célestin le renardeau" loading="lazy">';
   else echo '<span class="cel-script">Célestin<small>le renardeau</small></span>';
 ?></a><p class="cel-footer-dedication" style="margin:10px 0 0;color:#633619;font-size:13px;line-height:1.5;font-style:italic">By Elyse</p></div>
 <div><h3>Explorer</h3><a href="<?php echo esc_url(home_url('/')); ?>">Accueil</a><?php foreach($links as $label=>$v): ?><a href="<?php echo esc_url($v['url']); ?>"><?php echo esc_html($label); ?></a><?php endforeach; ?></div>
 <div><h3>À propos</h3><p>L’univers de Célestin et toutes ses petites aventures.</p><?php $origin_url=$options['origin_link']??'';if(!$origin_url){foreach(['origine','origines','les-origines-de-celestin','origine-de-celestin'] as $slug){$origin_page=get_page_by_path($slug);if($origin_page&&$origin_page->post_status==='publish'){$origin_url=get_permalink($origin_page);break;}}}if($origin_url): ?><a href="<?php echo esc_url($origin_url); ?>">Les origines de Célestin →</a><?php endif; ?></div>
 <div><h3>La newsletter de Fleuriville</h3><p>Retrouve les nouvelles histoires, les livres et les activités !</p><?php if($options['newsletter']): ?><a class="cel-button" href="<?php echo esc_url($options['newsletter']); ?>">S’inscrire →</a><?php else: ?><p class="cel-footer-note">Bientôt disponible</p><?php endif; ?></div>
 </div>
 <div class="cel-legal">© <?php echo esc_html(date_i18n('Y')); ?> Célestin le renardeau · Tous droits réservés</div>
</footer>