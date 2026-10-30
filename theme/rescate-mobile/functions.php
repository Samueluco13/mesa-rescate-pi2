<?php
/** Theme setup for the Rescate mobile app. */
if (!defined('ABSPATH')) { exit; }

function rescate_theme_asset_version($relative) {
    $path = get_template_directory() . '/' . ltrim($relative, '/');
    return is_file($path) ? substr(hash_file('sha256', $path), 0, 12) : '2.0.0';
}
add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array('search-form','gallery','caption','style','script'));
    add_theme_support('custom-logo');
    load_theme_textdomain('rescate-mobile', get_template_directory() . '/languages');
});
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('rescate-theme', get_stylesheet_uri(), array(), rescate_theme_asset_version('style.css'));
});
add_action('wp_head', function () {
    echo '<meta name="theme-color" content="#FFF2E4">';
    echo '<meta name="apple-mobile-web-app-capable" content="yes">';
    if (defined('RA_URL') && !has_site_icon()) {
        $mark = RA_URL . 'assets/images/rescate-mark.svg';
        $version = defined('RA_VERSION') ? RA_VERSION : '2.0.0';
        echo '<link rel="icon" type="image/svg+xml" href="' . esc_url(add_query_arg('ver', $version, $mark)) . '">';
    }
});
add_filter('body_class', function ($classes) { $classes[] = 'rescate-mobile-theme'; return $classes; });
add_action('login_enqueue_scripts', function () {
    wp_enqueue_style('rescate-login', get_template_directory_uri() . '/login.css', array(), rescate_theme_asset_version('login.css'));
    $settings = function_exists('ra_settings') ? ra_settings() : array();
    $logo = !empty($settings['logo_url']) ? $settings['logo_url'] : (defined('RA_URL') ? RA_URL . 'assets/images/rescate-mark.svg' : '');
    if ($logo) {
        $version = defined('RA_VERSION') ? RA_VERSION : '2.0.0';
        wp_add_inline_style('rescate-login', 'body.login{--ra-login-logo:url(' . wp_json_encode(add_query_arg('ver', $version, esc_url_raw($logo))) . ');}');
    }
});
add_filter('login_headertext', function () { return function_exists('ra_settings') ? ra_settings()['brand_name'] : 'Rescate de Alimentos'; });
add_filter('login_headerurl', function () { return home_url('/'); });
add_action('login_footer', function () { echo '<p class="ra-login-credit">Desarrollado por Moreno Technology</p>'; });
// The dedicated app keeps operational navigation separate from WordPress administration.
add_filter('show_admin_bar', '__return_false');
