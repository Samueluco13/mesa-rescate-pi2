<?php
if (!defined('ABSPATH')) { exit; }

function ra_table($suffix) { global $wpdb; return $wpdb->prefix . 'ra_' . $suffix; }

function ra_install_schema() {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $collate = $wpdb->get_charset_collate();
    $donations = ra_table('donations');
    $requests = ra_table('requests');
    $events = ra_table('events');
    dbDelta("CREATE TABLE $donations (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        title varchar(180) NOT NULL,
        description text NOT NULL,
        category varchar(32) NOT NULL,
        quantity decimal(12,3) NOT NULL,
        unit varchar(24) NOT NULL,
        merchant_name varchar(180) NOT NULL,
        donor_id bigint(20) unsigned NOT NULL,
        author_id bigint(20) unsigned NOT NULL,
        pickup_address varchar(255) NOT NULL,
        pickup_area varchar(120) NOT NULL,
        pickup_latitude decimal(10,6) DEFAULT NULL,
        pickup_longitude decimal(10,6) DEFAULT NULL,
        deadline datetime NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'available',
        origin_channel varchar(12) NOT NULL DEFAULT 'web',
        assisted tinyint(1) NOT NULL DEFAULT 0,
        is_demo tinyint(1) NOT NULL DEFAULT 0,
        created_at datetime NOT NULL,
        volunteer_id bigint(20) unsigned DEFAULT NULL,
        reserved_at datetime DEFAULT NULL,
        recipient_name varchar(180) DEFAULT NULL,
        picked_up_at datetime DEFAULT NULL,
        delivered_at datetime DEFAULT NULL,
        responsible_name varchar(180) DEFAULT NULL,
        delivery_notes text DEFAULT NULL,
        closed_by bigint(20) unsigned DEFAULT NULL,
        closed_at datetime DEFAULT NULL,
        PRIMARY KEY  (id),
        KEY available (status,deadline),
        KEY donor (donor_id),
        KEY volunteer (volunteer_id)
    ) ENGINE=InnoDB $collate;");
    dbDelta("CREATE TABLE $requests (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        actor_id bigint(20) unsigned NOT NULL,
        request_key varchar(80) NOT NULL,
        payload_hash char(64) NOT NULL,
        action_name varchar(32) NOT NULL,
        donation_id bigint(20) unsigned NOT NULL DEFAULT 0,
        result_json longtext NOT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY actor_request (actor_id,request_key)
    ) ENGINE=InnoDB $collate;");
    dbDelta("CREATE TABLE $events (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        donation_id bigint(20) unsigned NOT NULL,
        actor_id bigint(20) unsigned NOT NULL,
        event_name varchar(24) NOT NULL,
        from_status varchar(20) DEFAULT NULL,
        to_status varchar(20) NOT NULL,
        request_id bigint(20) unsigned DEFAULT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY donation (donation_id)
    ) ENGINE=InnoDB $collate;");
    update_option('ra_schema_version', RA_VERSION);
}

function ra_activate() {
    ra_install_schema();
    add_role('ra_donor', 'Comercio donante', array('read' => true, 'ra_create' => true));
    add_role('ra_volunteer', 'Voluntario', array('read' => true, 'ra_reserve' => true));
    add_role('ra_coordinator', 'Coordinación', array('read' => true, 'ra_create' => true, 'ra_reserve' => true, 'ra_deliver' => true, 'ra_coordinate' => true));
    $admin = get_role('administrator');
    if ($admin) { foreach (array('ra_create','ra_reserve','ra_deliver','ra_coordinate') as $cap) { $admin->add_cap($cap); } }
    $page = (int) get_option('ra_app_page_id');
    if (!$page || !get_post($page)) {
        $page = wp_insert_post(array('post_type'=>'page','post_title'=>'Rescate de Alimentos','post_name'=>'rescates','post_content'=>'[rescate_app]','post_status'=>'publish'));
        if (!is_wp_error($page)) { update_option('ra_app_page_id', $page); }
    }
}

function ra_can($capability) { return is_user_logged_in() && (current_user_can('manage_options') || current_user_can('ra_' . $capability)); }
function ra_capabilities() {
    return array('create'=>ra_can('create'), 'reserve'=>ra_can('reserve'), 'deliver'=>ra_can('deliver'), 'coordinate'=>ra_can('coordinate'));
}
function ra_user_info() {
    $u = wp_get_current_user();
    $role = ra_can('coordinate') ? 'coordinator' : (ra_can('create') ? 'donor' : (ra_can('reserve') ? 'volunteer' : 'participant'));
    return array('id'=>(int)$u->ID, 'name'=>$u->display_name, 'role'=>$role);
}
function ra_defaults() {
    return array('brand_name'=>'Rescate de Alimentos', 'tagline'=>'Fundación Mesa Común', 'accent_color'=>'#d64114', 'logo_id'=>0, 'hero_title'=>'Buena comida. Nuevas oportunidades.', 'hero_text'=>'Conecta los alimentos que sobran con quienes más los necesitan. Un rescate empieza contigo.', 'city'=>'Cali, Colombia', 'support_email'=>'');
}
function ra_settings() {
    $s = wp_parse_args(get_option('ra_settings', array()), ra_defaults());
    $s['logo_url'] = $s['logo_id'] ? (wp_get_attachment_image_url((int)$s['logo_id'], 'medium') ?: '') : '';
    unset($s['logo_id']);
    return $s;
}
function ra_now() { return gmdate('Y-m-d H:i:s'); }
function ra_iso($date) { return $date ? str_replace(' ', 'T', $date) . 'Z' : null; }
function ra_error($message, $status=400, $code='ra_invalid', $fields=array()) {
    return new WP_Error($code, $message, array('status'=>$status, 'fields'=>$fields));
}
function ra_private_error() { return ra_error('No es posible consultar esta donación', 404, 'ra_not_found'); }
function ra_db_error() { return ra_error('No pudimos guardar el cambio. Reintenta con la misma solicitud.', 503, 'ra_storage'); }
function ra_transactional_storage() {
    global $wpdb;
    static $checked = null;
    if ($checked !== null) { return $checked; }
    // The official WordPress SQLite integration supplies transactional tables.
    if ((defined('DB_ENGINE') && strtolower((string)DB_ENGINE) === 'sqlite') || defined('SQLITE_DB_DROPIN_VERSION') || is_a($wpdb, 'WP_SQLite_DB')) { return $checked = true; }
    $tables = $wpdb->get_results($wpdb->prepare('SHOW TABLE STATUS WHERE Name IN (%s,%s,%s)',ra_table('donations'),ra_table('requests'),ra_table('events')));
    if (!is_array($tables) || count($tables) !== 3) { return $checked = false; }
    foreach ($tables as $table) { if (strtolower($table->Engine ?? '') !== 'innodb') { return $checked = false; } }
    return $checked = true;
}
function ra_parse_date($value) {
    if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2}(\.\d{1,6})?)?(Z|[+-]\d{2}:\d{2})$/', $value)) { return false; }
    try {
        $d = new DateTimeImmutable($value);
        $errors = DateTimeImmutable::getLastErrors();
        if ($errors && ($errors['warning_count'] || $errors['error_count'])) { return false; }
        return $d->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    } catch (Exception $e) { return false; }
}
/** Optional publisher-confirmed pin; no automatic geocoding or inferred location. */
function ra_pickup_coordinates($latitude, $longitude) {
    $missing_lat = $latitude === null || $latitude === '';
    $missing_lng = $longitude === null || $longitude === '';
    // Keep omitted coordinates out of the creation hash for pre-map retries.
    if ($missing_lat && $missing_lng) { return array(); }
    $errors = array();
    if ($missing_lat) { $errors['pickup_latitude'] = 'Indica la latitud y la longitud del mismo punto, o deja ambas vacías.'; }
    if ($missing_lng) { $errors['pickup_longitude'] = 'Indica la latitud y la longitud del mismo punto, o deja ambas vacías.'; }
    foreach (array('pickup_latitude'=>array($latitude,90),'pickup_longitude'=>array($longitude,180)) as $field=>$spec) {
        $value = $spec[0];
        if ($value === null || $value === '') { continue; }
        if (is_bool($value) || !is_scalar($value) || !is_numeric($value) || !is_finite((float)$value) || abs((float)$value) > $spec[1]) {
            $errors[$field] = $field === 'pickup_latitude' ? 'La latitud debe ser un número entre -90 y 90.' : 'La longitud debe ser un número entre -180 y 180.';
        }
    }
    if ($errors) { return ra_error('Revisa la ubicación de recogida.',400,'ra_validation',$errors); }
    $lat = round((float)$latitude,6);
    $lng = round((float)$longitude,6);
    return array('pickup_latitude'=>$lat == 0 ? 0.0 : $lat,'pickup_longitude'=>$lng == 0 ? 0.0 : $lng);
}

function ra_readable($row) {
    if (!$row || !is_user_logged_in()) { return false; }
    $uid = get_current_user_id();
    if (ra_can('coordinate')) { return true; }
    if (ra_can('create') && (int)$row->donor_id === $uid) { return true; }
    return ra_can('reserve') && ((int)$row->volunteer_id === $uid || ($row->status === 'available' && $row->deadline > ra_now()));
}
function ra_get_row($id) {
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . ra_table('donations') . ' WHERE id = %d', $id));
}
function ra_serialize($row, $history=false) {
    global $wpdb;
    $data = array(
        'id'=>(int)$row->id, 'title'=>$row->title, 'description'=>$row->description,
        'category'=>$row->category, 'quantity'=>(float)$row->quantity, 'unit'=>$row->unit,
        'merchant_name'=>$row->merchant_name, 'pickup_address'=>$row->pickup_address,
        'pickup_area'=>$row->pickup_area, 'deadline'=>ra_iso($row->deadline),
        'pickup_latitude'=>isset($row->pickup_latitude) ? (float)$row->pickup_latitude : null,
        'pickup_longitude'=>isset($row->pickup_longitude) ? (float)$row->pickup_longitude : null,
        'created_at'=>ra_iso($row->created_at), 'status'=>$row->status, 'is_demo'=>(bool)$row->is_demo,
    );
    $related = ra_can('coordinate') || (int)$row->donor_id === get_current_user_id() || (int)$row->volunteer_id === get_current_user_id();
    if ($related) {
        $volunteer = $row->volunteer_id ? get_userdata((int)$row->volunteer_id) : false;
        $data += array('origin_channel'=>$row->origin_channel,'assisted'=>(bool)$row->assisted,'donor_id'=>(int)$row->donor_id,'author_id'=>(int)$row->author_id,'volunteer_id'=>$row->volunteer_id ? (int)$row->volunteer_id : null,'volunteer_name'=>$volunteer ? $volunteer->display_name : null,'reserved_at'=>ra_iso($row->reserved_at));
        if ($row->status === 'delivered') {
            $delivery = array('recipient_name'=>$row->recipient_name,'picked_up_at'=>ra_iso($row->picked_up_at),'delivered_at'=>ra_iso($row->delivered_at),'responsible_name'=>$row->responsible_name,'notes'=>$row->delivery_notes,'closed_by'=>(int)$row->closed_by,'closed_at'=>ra_iso($row->closed_at));
            $data['delivery'] = $delivery;
            $data += $delivery;
        }
    }
    if ($history) {
        $events = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . ra_table('events') . ' WHERE donation_id = %d ORDER BY id ASC', $row->id));
        $data['history'] = array_map(function ($e) use ($related) {
            $entry = array('event'=>$e->event_name,'status'=>$e->event_name,'from_status'=>$e->from_status,'to_status'=>$e->to_status,'created_at'=>ra_iso($e->created_at));
            if ($related) { $u = get_userdata((int)$e->actor_id); $entry['actor_name'] = $u ? $u->display_name : 'Usuario'; }
            return $entry;
        }, $events);
    }
    return $data;
}

/** All writes run in a transaction. A unique actor/key claim serializes retries.
 * The donation row itself is the unique reservation and delivery record.
 */
function ra_mutate($key, $action, $id, $payload, $callback) {
    global $wpdb;
    if (!ra_transactional_storage()) { return ra_error('La base de datos necesita almacenamiento transaccional. Contacta al administrador.',503,'ra_storage_engine'); }
    if (!is_string($key) || !preg_match('/^[A-Za-z0-9_-]{16,80}$/', $key)) {
        return ra_error('La solicitud necesita un identificador válido. Actualiza la página y reintenta.', 400, 'ra_request_key');
    }
    $actor = get_current_user_id();
    $hash = hash('sha256', wp_json_encode(array($action, (int)$id, $payload)));
    $table = ra_table('requests');
    $lookup = function () use ($wpdb,$table,$actor,$key,$hash) {
        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE actor_id=%d AND request_key=%s", $actor,$key));
        if (!$existing) { return null; }
        if (!hash_equals($existing->payload_hash, $hash)) { return ra_error('Esta solicitud ya se usó con datos distintos. Inicia una nueva operación.', 409, 'ra_key_conflict'); }
        $result = json_decode($existing->result_json, true);
        return is_array($result) ? rest_ensure_response($result) : ra_db_error();
    };
    $previous = $lookup();
    if ($previous !== null) { return $previous; }
    if ($wpdb->query('START TRANSACTION') === false) { return ra_db_error(); }
    $suppress = $wpdb->suppress_errors(true);
    $claim = $wpdb->insert($table,array('actor_id'=>$actor,'request_key'=>$key,'payload_hash'=>$hash,'action_name'=>$action,'donation_id'=>(int)$id,'result_json'=>'','created_at'=>ra_now()),array('%d','%s','%s','%s','%d','%s','%s'));
    $wpdb->suppress_errors($suppress);
    if ($claim === false) {
        $wpdb->query('ROLLBACK');
        $previous = $lookup();
        return $previous !== null ? $previous : ra_db_error();
    }
    $request_id = $wpdb->insert_id;
    $result = $callback($request_id);
    if (is_wp_error($result)) { $wpdb->query('ROLLBACK'); return $result; }
    $actual_id = (int)$result['id'];
    $saved = $wpdb->update($table,array('donation_id'=>$actual_id,'result_json'=>wp_json_encode($result)),array('id'=>$request_id),array('%d','%s'),array('%d'));
    if ($saved === false || $wpdb->query('COMMIT') === false) { $wpdb->query('ROLLBACK'); return ra_db_error(); }
    return rest_ensure_response($result);
}

function ra_event($id, $name, $request_id=null) {
    global $wpdb;
    $from = array('available'=>null,'reserved'=>'available','delivered'=>'reserved');
    return $wpdb->insert(ra_table('events'), array('donation_id'=>$id,'actor_id'=>get_current_user_id(),'event_name'=>$name,'from_status'=>$from[$name] ?? null,'to_status'=>$name,'request_id'=>$request_id,'created_at'=>ra_now()),array('%d','%d','%s','%s','%s','%d','%s')) !== false;
}
