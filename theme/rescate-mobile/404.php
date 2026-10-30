<?php
if (!defined('ABSPATH')) { exit; }
get_header(); ?>
<section class="ra-theme-fallback"><h1><?php esc_html_e('Esta página no está disponible', 'rescate-mobile'); ?></h1><p><?php esc_html_e('Vuelve a la aplicación para consultar tus rescates.', 'rescate-mobile'); ?></p><a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Volver al inicio', 'rescate-mobile'); ?></a></section>
<?php get_footer();
