</main>
<footer class="site-footer"><div class="wrap footer-columns">
  <div class="footer-brand">
    <a href="<?php echo esc_url(home_url('/')); ?>"><img src="<?php echo esc_url(home_url('/images/logo.webp')); ?>" alt="Célestin le renardeau"></a>
    <p>Un petit monde illustré, doux et plein d'aventures à Fleurville.</p>
  </div>
  <div><h3>Explorer</h3>
    <a href="<?php echo clst_home_anchor('personnages'); ?>">Les personnages</a>
    <a href="<?php echo clst_home_anchor('coloriages'); ?>">Coloriages</a>
    <a href="<?php echo clst_home_anchor('livres'); ?>">Les livres</a>
    <a href="<?php echo clst_home_anchor('mini-histoires'); ?>">Mini-histoires</a>
  </div>
  <div><h3>À propos</h3>
    <a href="<?php echo clst_home_anchor('fleurville'); ?>">Fleurville</a>
    <a href="<?php echo esc_url(get_post_type_archive_link('celestin_personnage')); ?>">Les habitants</a>
    <a href="<?php echo esc_url(wp_login_url()); ?>">Administration</a>
  </div>
  <div><h3>L'univers de Célestin</h3><p>Des histoires à lire ensemble et des coloriages à imprimer.</p><small>© <?php echo esc_html(date('Y')); ?> Célestin le renardeau</small></div>
</div></footer>
<?php wp_footer(); ?>
</body></html>
