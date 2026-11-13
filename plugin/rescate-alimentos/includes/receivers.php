<?php
if (!defined('ABSPATH')) { exit; }

function ra_install_receivers($collate) {
    $table = ra_table('receivers');
    dbDelta("CREATE TABLE $table (
        user_id bigint(20) unsigned NOT NULL,
        active tinyint(1) NOT NULL DEFAULT 0,
        cold tinyint(1) NOT NULL DEFAULT 0,
        kg decimal(12,3) NOT NULL DEFAULT 0,
        liters decimal(12,3) NOT NULL DEFAULT 0,
        portions decimal(12,3) NOT NULL DEFAULT 0,
        units decimal(12,3) NOT NULL DEFAULT 0,
        latitude decimal(10,6) DEFAULT NULL,
        longitude decimal(10,6) DEFAULT NULL,
        PRIMARY KEY  (user_id)
    ) ENGINE=InnoDB $collate;");
    add_role('ra_receiver', 'Organización receptora', array('read'=>true,'ra_receive'=>true));
}

function ra_receiver_permission() {
    if (!is_user_logged_in()) { return ra_error('Inicia sesión para continuar.',401,'ra_auth'); }
    return current_user_can('ra_receive') ? true : ra_error('Esta sección es para organizaciones receptoras.',403,'ra_forbidden');
}

function ra_receiver_routes() {
    register_rest_route('rescate/v1','/receiver',array(
        array('methods'=>'GET','permission_callback'=>'ra_receiver_permission','callback'=>'ra_receiver_view'),
        array('methods'=>'POST','permission_callback'=>'ra_receiver_permission','callback'=>'ra_receiver_save'),
    ));
    foreach (array('GET'=>'ra_receiver_suggest','POST'=>'ra_receiver_assign') as $method=>$callback) {
        register_rest_route('rescate/v1','/donations/(?P<id>\d+)/destination',array(
            'methods'=>$method,'permission_callback'=>function () { return ra_permission('coordinate'); },'callback'=>$callback,
        ));
    }
}

function ra_receiver_view() {
    global $wpdb;
    $profile = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.ra_table('receivers').' WHERE user_id=%d',get_current_user_id()),ARRAY_A);
    $notices = $wpdb->get_results($wpdb->prepare('SELECT id,title,category,quantity,unit,unit_label,arrival_at,assigned_at,status FROM '.ra_table('donations').' WHERE assigned_recipient_id=%d ORDER BY assigned_at DESC,id DESC LIMIT 100',get_current_user_id()),ARRAY_A);
    if ($wpdb->last_error) { return ra_db_error(); }
    return rest_ensure_response(array('profile'=>$profile,'notices'=>array_map(function ($row) {
        $row['arrival_at'] = ra_iso($row['arrival_at']);
        $row['assigned_at'] = ra_iso($row['assigned_at']);
        return $row;
    },$notices)));
}

function ra_receiver_save($r) {
    global $wpdb;
    $units = array('kg'=>'kilos','liters'=>'litros','portions'=>'porciones','units'=>'piezas');
    $data = array('user_id'=>get_current_user_id());
    $errors = array();
    foreach ($units as $unit=>$label) {
        $v = $r->get_param($unit);
        if (!is_numeric($v) || !is_finite((float)$v) || $v < 0 || $v > 1000000 || abs((float)$v*1000-round((float)$v*1000)) > .000001 || ($unit === 'units' && floor((float)$v) != (float)$v)) {
            $errors[$unit] = "Indica una capacidad diaria válida en $label (0 si no recibes esta medida). Para piezas usa enteros.";
        } else { $data[$unit] = (float)$v; }
    }
    foreach (array('active','cold') as $key) {
        $value = $r->get_param($key);
        if (!is_bool($value)) { $errors[$key] = 'Selecciona sí o no.'; }
        else { $data[$key] = (int)$value; }
    }
    $point = ra_pickup_coordinates($r->get_param('latitude'),$r->get_param('longitude'));
    if (is_wp_error($point)) {
        foreach ($point->get_error_data()['fields'] as $key=>$message) { $errors[str_replace('pickup_','',$key)] = $message; }
    } else {
        $data['latitude'] = $point['pickup_latitude'] ?? null;
        $data['longitude'] = $point['pickup_longitude'] ?? null;
    }
    if ($errors) { return ra_error('Revisa los datos de recepción.',400,'ra_validation',$errors); }
    if ($wpdb->replace(ra_table('receivers'),$data) === false) { return ra_db_error(); }
    return ra_receiver_view();
}

function ra_receiver_remaining($profile, $unit, $arrival) {
    global $wpdb;
    $day = (new DateTimeImmutable($arrival,new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('America/Bogota'))->setTime(0,0);
    $start = $day->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    $end = $day->modify('+1 day')->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    $used = $wpdb->get_var($wpdb->prepare('SELECT COALESCE(SUM(quantity),0) FROM '.ra_table('donations').' WHERE assigned_recipient_id=%d AND unit=%s AND arrival_at >= %s AND arrival_at < %s',$profile->user_id,$unit,$start,$end));
    return $wpdb->last_error ? null : round((float)$profile->{$unit}-(float)$used,3);
}

function ra_receiver_minutes($row, $profile) {
    if ($row->pickup_latitude === null || $row->pickup_longitude === null || $profile->latitude === null || $profile->longitude === null) { return null; }
    $a = deg2rad((float)$row->pickup_latitude);
    $b = deg2rad((float)$profile->latitude);
    $d = deg2rad((float)$profile->longitude-(float)$row->pickup_longitude);
    $h = sin(($b-$a)/2)**2+cos($a)*cos($b)*sin($d/2)**2;
    $km = 12742*asin(sqrt(max(0,min(1,$h))));
    return max(5,(int)ceil($km*1.35/25*60));
}

function ra_receiver_suggest($r) {
    global $wpdb;
    $row = ra_get_row((int)$r['id']);
    if (!$row) { return ra_private_error(); }
    if ($row->status !== 'reserved' || $row->assigned_recipient_id || $row->is_demo) { return ra_error('La sugerencia está disponible para reservas confirmadas sin destino, fuera de demostración.',409,'ra_state'); }
    $profiles = $wpdb->get_results('SELECT * FROM '.ra_table('receivers').' WHERE active=1');
    if ($wpdb->last_error) { return ra_db_error(); }
    $candidates = array();
    foreach ($profiles as $profile) {
        $user = get_userdata((int)$profile->user_id);
        if (!$user || !user_can($user,'ra_receive') || ($row->requires_cold && !$profile->cold)) { continue; }
        $minutes = ra_receiver_minutes($row,$profile);
        $arrival = gmdate('Y-m-d H:i:s',time()+($minutes ?? 0)*60);
        $remaining = ra_receiver_remaining($profile,$row->unit,$arrival);
        if ($remaining === null) { return ra_db_error(); }
        if ($remaining < (float)$row->quantity) { continue; }
        $candidates[] = array('id'=>(int)$profile->user_id,'name'=>$user->display_name,'remaining'=>$remaining,'minutes'=>$minutes,'arrival_at'=>$minutes === null ? null : ra_iso($arrival));
    }
    usort($candidates,function ($a,$b) { return (($a['minutes'] ?? PHP_INT_MAX) <=> ($b['minutes'] ?? PHP_INT_MAX)) ?: ($a['id'] <=> $b['id']); });
    return rest_ensure_response(array('candidates'=>$candidates,'suggested_id'=>isset($candidates[0]['minutes']) ? $candidates[0]['id'] : null,'alert'=>$candidates ? null : 'No hay una asignación automática viable: ninguna organización activa tiene capacidad y refrigeración compatibles. Coordinación debe revisar el caso.'));
}

function ra_receiver_assign($r) {
    global $wpdb;
    $id = (int)$r['id'];
    $recipient = absint($r->get_param('recipient'));
    $arrival = ra_parse_date($r->get_param('arrival_at'));
    $errors = array();
    if (!$recipient) { $errors['recipient'] = 'Selecciona una organización receptora.'; }
    if (!$arrival) { $errors['arrival_at'] = 'Indica una fecha y hora estimadas de llegada.'; }
    if ($errors) { return ra_error('Revisa la asignación.',400,'ra_validation',$errors); }
    return ra_mutate($r->get_param('request_key'),'assign',$id,array($recipient,$arrival),function () use ($wpdb,$id,$recipient,$arrival) {
        if ($arrival <= ra_now()) { return ra_error('La llegada estimada debe estar en el futuro.',400,'ra_validation',array('arrival_at'=>'Indica una hora futura.')); }
        $receivers = ra_table('receivers');
        // Serialize assignments and profile changes for this receiver. The write
        // also acquires the lock on the SQLite development database.
        if ($wpdb->query($wpdb->prepare("UPDATE $receivers SET active=active WHERE user_id=%d",$recipient)) === false) { return ra_db_error(); }
        $profile = $wpdb->get_row($wpdb->prepare("SELECT * FROM $receivers WHERE user_id=%d",$recipient));
        $user = get_userdata($recipient);
        $row = ra_get_row($id);
        if (!$row) { return ra_private_error(); }
        if ($row->status !== 'reserved' || $row->assigned_recipient_id || $row->is_demo) { return ra_error('Esta reserva ya tiene destino o no se puede asignar.',409,'ra_state'); }
        $remaining = $profile ? ra_receiver_remaining($profile,$row->unit,$arrival) : 0;
        if ($remaining === null) { return ra_db_error(); }
        if (!$profile || !$profile->active || !$user || !user_can($user,'ra_receive') || ($row->requires_cold && !$profile->cold) || $remaining < (float)$row->quantity) {
            return ra_error('La organización ya no cumple las condiciones para esa fecha. Consulta de nuevo los destinos.',409,'ra_capacity');
        }
        $table = ra_table('donations');
        $updated = $wpdb->query($wpdb->prepare("UPDATE $table SET assigned_recipient_id=%d, assigned_recipient_name=%s, arrival_at=%s, assigned_at=%s WHERE id=%d AND status='reserved' AND assigned_recipient_id IS NULL",$recipient,ra_receiver_name($user),$arrival,ra_now(),$id));
        if ($updated === false) { return ra_db_error(); }
        if ($updated !== 1) { return ra_error('La reserva cambió. Consulta su estado.',409,'ra_state'); }
        return ra_serialize(ra_get_row($id),true);
    });
}

function ra_receiver_name($user) {
    return wp_html_excerpt($user->display_name,180,'');
}
