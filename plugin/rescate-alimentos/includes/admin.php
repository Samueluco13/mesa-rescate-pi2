<?php
if (!defined('ABSPATH')) { exit; }

add_action('admin_menu', function () {
    add_menu_page('Rescate de Alimentos','Rescate de Alimentos','manage_options','rescate-alimentos','ra_admin_overview','dashicons-carrot',26);
    add_submenu_page('rescate-alimentos','Personalización','Personalización','manage_options','rescate-personalizacion','ra_admin_settings');
});
add_action('admin_init', function () {
    register_setting('ra_settings_group','ra_settings',array('type'=>'array','sanitize_callback'=>'ra_sanitize_settings','default'=>ra_defaults()));
});
add_action('admin_enqueue_scripts', function ($hook) {
    if (strpos($hook,'rescate') === false) { return; }
    wp_enqueue_style('ra-admin',RA_URL . 'assets/admin.css',array(),ra_asset_version('assets/admin.css'));
    if (strpos($hook,'personalizacion') !== false) {
        wp_enqueue_media();
        wp_enqueue_script('ra-admin',RA_URL . 'assets/admin.js',array('jquery'),ra_asset_version('assets/admin.js'),true);
    }
});
add_action('admin_post_ra_seed_demo','ra_admin_seed_demo');

function ra_sanitize_settings($input) {
    $defaults = ra_defaults();
    $result = array();
    if (!is_array($input)) { return $defaults; }
    foreach (array('brand_name'=>80,'tagline'=>120,'hero_title'=>160,'hero_text'=>240,'city'=>80) as $key=>$max) {
        $value = isset($input[$key]) && is_scalar($input[$key]) ? sanitize_text_field($input[$key]) : $defaults[$key];
        $result[$key] = function_exists('mb_substr') ? mb_substr($value,0,$max) : substr($value,0,$max);
    }
    if (!$result['brand_name']) { $result['brand_name'] = $defaults['brand_name']; }
    $result['accent_color'] = isset($input['accent_color']) && is_string($input['accent_color']) ? (sanitize_hex_color($input['accent_color']) ?: $defaults['accent_color']) : $defaults['accent_color'];
    $result['support_email'] = isset($input['support_email']) && is_string($input['support_email']) ? sanitize_email($input['support_email']) : '';
    $logo = isset($input['logo_id']) ? absint($input['logo_id']) : 0;
    $result['logo_id'] = $logo && wp_attachment_is_image($logo) ? $logo : 0;
    return $result;
}

function ra_admin_shell_start($title) {
    echo '<div class="wrap ra-admin"><div class="ra-admin-head"><div><p class="ra-admin-kicker">Fundación Mesa Común</p><h1>' . esc_html($title) . '</h1></div>';
    $url = get_permalink(get_option('ra_app_page_id'));
    if ($url) { echo '<a class="button button-primary" href="' . esc_url($url) . '" target="_blank" rel="noopener">Abrir la webapp</a>'; }
    echo '</div>';
}

function ra_admin_overview() {
    if (!current_user_can('manage_options')) { return; }
    global $wpdb;
    ra_admin_shell_start('Operación de rescates');
    if (isset($_GET['demo']) && $_GET['demo'] === 'created') { echo '<div class="notice notice-success"><p>Se añadieron seis donaciones ficticias identificadas como demostración. No representan una operación real.</p></div>'; }
    if (isset($_GET['demo']) && $_GET['demo'] === 'error') { echo '<div class="notice notice-error"><p>No se completó la carga de demostración. Inténtalo de nuevo.</p></div>'; }
    $table = ra_table('donations');
    $counts = $wpdb->get_results("SELECT status, COUNT(*) AS total FROM $table GROUP BY status",OBJECT_K);
    echo '<div class="ra-admin-stats">';
    foreach (array('available'=>'Disponibles','reserved'=>'Reservadas','delivered'=>'Entregadas') as $s=>$label) { echo '<div><strong>' . esc_html(isset($counts[$s]) ? $counts[$s]->total : 0) . '</strong><span>' . esc_html($label) . '</span></div>'; }
    echo '</div><h2>Últimas donaciones</h2><p>La coordinación registra la recepción y cierra las entregas desde la webapp. Las donaciones vencidas conservan su estado histórico y dejan de ofrecerse.</p>';
    $rows = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC LIMIT 100");
    echo '<table class="widefat striped"><thead><tr><th>ID</th><th>Alimento y origen</th><th>Cantidad</th><th>Recogida</th><th>Hora límite</th><th>Estado</th></tr></thead><tbody>';
    foreach ($rows as $row) {
        $status = array('available'=>'Disponible','reserved'=>'Reservada','delivered'=>'Entregada')[$row->status] ?? $row->status;
        $units = array('kg'=>'kg','portions'=>'porciones','units'=>'unidades','liters'=>'litros');
        echo '<tr><td>#' . esc_html($row->id) . '</td><td><strong>' . esc_html($row->title) . '</strong>' . ($row->is_demo ? ' <span class="ra-admin-demo">Demostración</span>' : '') . '<br>' . esc_html($row->merchant_name) . '</td><td>' . esc_html((float)$row->quantity . ' ' . ($units[$row->unit] ?? $row->unit)) . '</td><td>' . esc_html($row->pickup_address) . '</td><td>' . esc_html(wp_date('d M Y, H:i',strtotime($row->deadline . ' UTC'))) . '</td><td>' . esc_html($status) . '</td></tr>';
    }
    if (!$rows) { echo '<tr><td colspan="6">Todavía no hay donaciones. Publica la primera desde la webapp o carga datos ficticios para una demostración.</td></tr>'; }
    echo '</tbody></table><div class="ra-admin-panel"><h2>Acceso del equipo</h2><p>Asigna un rol en <a href="' . esc_url(admin_url('users.php')) . '">Usuarios de WordPress</a>: <strong>Comercio donante</strong> registra y consulta sus donaciones; <strong>Voluntario</strong> reserva ofertas y consulta sus rescates; <strong>Coordinación</strong> registra solicitudes asistidas y confirma entregas. Los administradores tienen acceso a toda la operación.</p><p>No hay registro público automático. Cada participante requiere una cuenta autorizada.</p></div>';
    echo '<div class="ra-admin-panel"><h2>Datos para demostración</h2><p>Carga seis donaciones ficticias vigentes para probar el recorrido. Cada registro lleva la marca «Demostración». Esta acción no crea cuentas ni añade donaciones reales.</p><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="ra_seed_demo">';
    wp_nonce_field('ra_seed_demo');
    submit_button('Cargar donaciones de demostración','secondary','submit',false);
    echo '</form></div><p>Versión ' . esc_html(RA_VERSION) . '. Personaliza el nombre, el logotipo y los textos en <a href="' . esc_url(admin_url('admin.php?page=rescate-personalizacion')) . '">Personalización</a>.</p></div>';
}

function ra_admin_settings() {
    if (!current_user_can('manage_options')) { return; }
    $s = wp_parse_args(get_option('ra_settings',array()),ra_defaults());
    ra_admin_shell_start('Personaliza tu webapp');
    settings_errors();
    echo '<p>Estos cambios se reflejan en la webapp y no requieren editar el tema ni el código.</p><form method="post" action="options.php">';
    settings_fields('ra_settings_group');
    echo '<table class="form-table" role="presentation">';
    foreach (array('brand_name'=>'Nombre de la plataforma','tagline'=>'Nombre de la fundación o lema','hero_title'=>'Título principal','hero_text'=>'Texto de bienvenida','city'=>'Ciudad de operación','support_email'=>'Correo de contacto') as $key=>$label) {
        $type = $key === 'support_email' ? 'email' : 'text';
        echo '<tr><th scope="row"><label for="ra_' . esc_attr($key) . '">' . esc_html($label) . '</label></th><td><input class="regular-text" type="' . esc_attr($type) . '" id="ra_' . esc_attr($key) . '" name="ra_settings[' . esc_attr($key) . ']" value="' . esc_attr($s[$key]) . '"></td></tr>';
    }
    echo '<tr><th scope="row"><label for="ra_accent_color">Color principal</label></th><td><input type="color" id="ra_accent_color" name="ra_settings[accent_color]" value="' . esc_attr($s['accent_color']) . '"><p class="description">Elige un tono oscuro para mantener el texto de los botones legible.</p></td></tr>';
    echo '<tr><th scope="row">Logotipo</th><td><input type="hidden" id="ra_logo_id" name="ra_settings[logo_id]" value="' . esc_attr($s['logo_id']) . '"><div id="ra_logo_preview">';
    if ($s['logo_id']) { echo wp_get_attachment_image((int)$s['logo_id'],'thumbnail'); }
    echo '</div><button type="button" class="button" id="ra_choose_logo">Elegir imagen</button> <button type="button" class="button-link-delete" id="ra_remove_logo">Quitar logotipo</button><p class="description">Selecciona una imagen PNG, WebP o JPEG de la biblioteca.</p></td></tr></table>';
    submit_button('Guardar personalización');
    echo '</form></div>';
}

function ra_admin_seed_demo() {
    if (!current_user_can('manage_options')) { wp_die('No autorizado.',403); }
    check_admin_referer('ra_seed_demo');
    global $wpdb;
    $items = array(
        array('Canasta de verduras frescas','vegetables',12,'kg','Huerta de demostración','Calle 5 # 24-10','San Fernando',180),
        array('Pan artesanal del día','bakery',24,'units','Panadería de demostración','Carrera 34 # 3-26','San Fernando',150),
        array('Frutas de temporada','fruit',8,'kg','Frutería de demostración','Calle 9 # 42-18','Tequendama',210),
        array('Almuerzos preparados','prepared',18,'portions','Cocina de demostración','Carrera 66 # 13-20','El Limonar',90),
        array('Lácteos refrigerados','dairy',10,'units','Mercado de demostración','Calle 16 # 52-30','El Ingenio',240),
        array('Canasta de despensa','pantry',6,'kg','Despensa de demostración','Carrera 39 # 6-45','Champagnat',300),
    );
    $ok = $wpdb->query('START TRANSACTION') !== false;
    foreach ($items as $item) {
        if (!$ok) { break; }
        $ok = $wpdb->insert(ra_table('donations'),array('title'=>$item[0],'description'=>'Registro ficticio para probar la aplicación. No corresponde a una donación real.','category'=>$item[1],'quantity'=>$item[2],'unit'=>$item[3],'merchant_name'=>$item[4],'donor_id'=>get_current_user_id(),'author_id'=>get_current_user_id(),'pickup_address'=>$item[5],'pickup_area'=>$item[6],'deadline'=>gmdate('Y-m-d H:i:s',time()+$item[7]*60),'status'=>'available','origin_channel'=>'web','assisted'=>0,'is_demo'=>1,'created_at'=>ra_now())) !== false;
        if ($ok) { $ok = ra_event($wpdb->insert_id,'available'); }
    }
    if ($ok) { $ok = $wpdb->query('COMMIT') !== false; }
    if (!$ok) { $wpdb->query('ROLLBACK'); }
    wp_safe_redirect(add_query_arg('demo',$ok ? 'created' : 'error',admin_url('admin.php?page=rescate-alimentos')));
    exit;
}
