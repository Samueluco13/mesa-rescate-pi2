<?php
/**
 * Plugin Name: Rescate de Alimentos
 * Description: Donaciones, reservas exclusivas y entregas trazables para la Fundación Mesa Común.
 * Version: 2.0.1
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * Author: Moreno and Company Technology
 * License: GPL-2.0-or-later
 * Text Domain: rescate-alimentos
 */
if (!defined('ABSPATH')) { exit; }
define('RA_VERSION', '2.0.1');
define('RA_DIR', plugin_dir_path(__FILE__));
define('RA_URL', plugin_dir_url(__FILE__));
require_once RA_DIR . 'includes/core.php';
require_once RA_DIR . 'includes/api.php';
require_once RA_DIR . 'includes/admin.php';
register_activation_hook(__FILE__, 'ra_activate');
add_action('plugins_loaded', function () {
    if (get_option('ra_schema_version') !== RA_VERSION) {
        ra_install_schema();
        add_action('wp_loaded', 'ra_purge_app_page_cache');
    }
});
add_action('rest_api_init', 'ra_register_routes');
add_filter('rest_post_dispatch', 'ra_rest_no_store', 100, 3);
add_action('template_redirect', function () {
    $post = get_post();
    if ((is_singular() && $post && has_shortcode($post->post_content, 'rescate_app')) || get_template() === 'rescate-mobile') {
        ra_send_private_headers();
    }
}, 0);
add_action('update_option_ra_settings', 'ra_purge_app_page_cache', 10, 0);
add_shortcode('rescate_app', 'ra_render_app');
add_action('wp_enqueue_scripts', function () {
    if (get_template() === 'rescate-mobile') { ra_enqueue_app(); return; }
    if (!is_singular()) { return; }
    $post = get_post();
    if ($post && has_shortcode($post->post_content, 'rescate_app')) { ra_enqueue_app(); }
});

function ra_enqueue_app() {
    static $enqueued = false;
    if ($enqueued) { return; }
    $enqueued = true;
    wp_enqueue_style('ra-app', RA_URL . 'assets/app.css', array(), ra_asset_version('assets/app.css'));
    wp_enqueue_script('ra-map-tools', RA_URL . 'assets/map-tools.js', array(), ra_asset_version('assets/map-tools.js'), true);
    wp_enqueue_script('ra-app', RA_URL . 'assets/app.js', array('ra-map-tools'), ra_asset_version('assets/app.js', array('assets/map-tools.js')), true);
    $user = wp_get_current_user();
    $page_url = get_permalink(get_option('ra_app_page_id')) ?: home_url('/');
    wp_localize_script('ra-map-tools', 'RA_CONFIG', array(
        'apiUrl' => esc_url_raw(rest_url('rescate/v1/')),
        'nonce' => wp_create_nonce('wp_rest'),
        'assetsUrl' => RA_URL . 'assets/',
        'loginUrl' => wp_login_url($page_url),
        'logoutUrl' => wp_logout_url($page_url),
        'user' => $user->exists() ? ra_user_info() : null,
        'capabilities' => ra_capabilities(),
        'settings' => ra_settings(),
        'timezone' => wp_timezone_string(),
        'appVersion' => RA_VERSION,
        'cacheVersion' => ra_asset_version('assets/app.js', array('assets/map-tools.js', 'assets/app.css')),
        'assetVersions' => array(
            'leafletJs' => ra_asset_version('assets/vendor/leaflet/leaflet.js'),
            'leafletCss' => ra_asset_version('assets/vendor/leaflet/leaflet.css'),
        ),
    ));
}

/** Stable per-content versions invalidate changed files without a global purge. */
function ra_asset_version($relative, $dependencies=array()) {
    $parts = array(RA_VERSION);
    foreach (array_merge(array($relative), $dependencies) as $file) {
        $path = RA_DIR . $file;
        $parts[] = $file . ':' . (is_file($path) && is_readable($path) ? hash_file('sha256', $path) : 'missing');
    }
    return RA_VERSION . '-' . substr(hash('sha256', implode('|', $parts)), 0, 12);
}

function ra_private_cache_headers($vary='') {
    $values = preg_split('/\s*,\s*/', (string)$vary, -1, PREG_SPLIT_NO_EMPTY);
    if (!in_array('cookie', array_map('strtolower', $values), true)) { $values[] = 'Cookie'; }
    return array(
        'Cache-Control'=>'no-store, no-cache, must-revalidate, max-age=0, private',
        'Pragma'=>'no-cache',
        'Expires'=>'Wed, 11 Jan 1984 05:00:00 GMT',
        'Vary'=>implode(', ', $values),
    );
}

function ra_mark_response_private() {
    if (!defined('DONOTCACHEPAGE')) { define('DONOTCACHEPAGE', true); }
    // Official LSCWP hook: affects this app response, not site-wide caching.
    do_action('litespeed_control_set_nocache', 'Rescate de Alimentos contains session and current operational data');
}

function ra_send_private_headers() {
    ra_mark_response_private();
    if (headers_sent()) { return; }
    nocache_headers();
    $vary = '';
    foreach (headers_list() as $header) {
        if (stripos($header, 'Vary:') === 0) { $vary .= ($vary ? ', ' : '') . trim(substr($header, 5)); }
    }
    foreach (ra_private_cache_headers($vary) as $name=>$value) { header($name . ': ' . $value); }
}

function ra_rest_no_store($response, $server, $request) {
    $route = $request->get_route();
    if ($route !== '/rescate/v1' && strpos($route, '/rescate/v1/') !== 0) { return $response; }
    ra_mark_response_private();
    $vary = '';
    foreach ($response->get_headers() as $name=>$value) {
        if (strtolower($name) === 'vary') { $vary = $value; }
    }
    foreach (ra_private_cache_headers($vary) as $name=>$value) { $response->header($name, $value); }
    return $response;
}

/** Purge only the app entry URLs after an upgrade or brand-settings change. */
function ra_purge_app_page_cache() {
    $urls = array();
    $page = (int)get_option('ra_app_page_id');
    if ($page && get_post($page)) {
        $url = get_permalink($page);
        if ($url) { $urls[] = $url; }
    }
    if (get_template() === 'rescate-mobile' || ($page && (int)get_option('page_on_front') === $page)) { $urls[] = home_url('/'); }
    foreach (array_unique($urls) as $url) { do_action('litespeed_purge_url', $url); }
}

function ra_render_app() {
    ra_send_private_headers();
    ra_enqueue_app();
    ob_start();
    $template = RA_DIR . 'templates/app.php';
    if (file_exists($template)) { include $template; }
    else { echo '<main id="rescate-app"><p>Cargando Rescate de Alimentos…</p></main>'; }
    return ob_get_clean();
}
