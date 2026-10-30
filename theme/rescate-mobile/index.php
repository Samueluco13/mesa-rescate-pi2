<?php
if (!defined('ABSPATH')) { exit; }
get_header();
if (shortcode_exists('rescate_app')) {
    echo do_shortcode('[rescate_app]');
} else {
    ?>
    <section class="ra-theme-fallback">
        <h1><?php esc_html_e('Rescate de Alimentos', 'rescate-mobile'); ?></h1>
        <p><?php esc_html_e('Activa el plugin Rescate de Alimentos para empezar a coordinar donaciones.', 'rescate-mobile'); ?></p>
        <?php if (current_user_can('activate_plugins')) : ?>
            <a href="<?php echo esc_url(admin_url('plugins.php')); ?>"><?php esc_html_e('Administrar plugins', 'rescate-mobile'); ?></a>
        <?php endif; ?>
    </section>
    <?php
}
get_footer();
