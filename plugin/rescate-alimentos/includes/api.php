<?php
if (!defined('ABSPATH')) { exit; }

function ra_authorized() {
    if (!is_user_logged_in()) { return ra_error('Inicia sesión para continuar.', 401, 'ra_auth'); }
    if (!ra_can('create') && !ra_can('reserve') && !ra_can('coordinate')) { return ra_error('Tu cuenta necesita un rol de Rescate de Alimentos. Contacta a la coordinación.', 403, 'ra_role'); }
    return true;
}

function ra_permission($cap) {
    $auth = ra_authorized();
    if (is_wp_error($auth)) { return $auth; }
    return ra_can($cap) ? true : ra_error('Tu cuenta no tiene permiso para esta operación.', 403, 'ra_forbidden');
}

function ra_register_routes() {
    register_rest_route('rescate/v1', '/bootstrap', array('methods'=>'GET','permission_callback'=>'__return_true','callback'=>function () {
        return rest_ensure_response(array('user'=>is_user_logged_in() ? ra_user_info() : null,'capabilities'=>ra_capabilities(),'settings'=>ra_settings(),'rest_url'=>rest_url('rescate/v1/'),'nonce'=>is_user_logged_in() ? wp_create_nonce('wp_rest') : null,'server_time'=>gmdate('c'),'app_version'=>RA_VERSION));
    }));
    register_rest_route('rescate/v1', '/donations', array(
        array('methods'=>'GET','permission_callback'=>'ra_authorized','callback'=>'ra_list_donations'),
        array('methods'=>'POST','permission_callback'=>function () { return ra_permission('create'); },'callback'=>'ra_create_donation'),
    ));
    register_rest_route('rescate/v1', '/donations/(?P<id>\d+)', array('methods'=>'GET','permission_callback'=>'ra_authorized','callback'=>function ($r) {
        $row = ra_get_row((int)$r['id']);
        return ra_readable($row) ? rest_ensure_response(ra_serialize($row, true)) : ra_private_error();
    }));
    register_rest_route('rescate/v1', '/donations/(?P<id>\d+)/reserve', array('methods'=>'POST','permission_callback'=>function () { return ra_permission('reserve'); },'callback'=>'ra_reserve_donation'));
    register_rest_route('rescate/v1', '/donations/(?P<id>\d+)/deliver', array('methods'=>'POST','permission_callback'=>function () { return ra_permission('deliver'); },'callback'=>'ra_deliver_donation'));
}

function ra_list_donations($r) {
    global $wpdb;
    $table = ra_table('donations');
    $scope = sanitize_key($r->get_param('scope') ?: 'available');
    $where = array('1=1');
    $uid = get_current_user_id();
    if ($scope === 'available') {
        $where[] = $wpdb->prepare("status = 'available' AND deadline > %s", ra_now());
        if (!ra_can('coordinate') && !ra_can('reserve')) { $where[] = $wpdb->prepare('donor_id = %d', $uid); }
    } elseif ($scope === 'mine') {
        $where[] = $wpdb->prepare('(donor_id = %d OR volunteer_id = %d)', $uid, $uid);
    } elseif ($scope === 'all' && ra_can('coordinate')) {
        // Coordination sees the operation, including expired offers and closed rescues.
    } else {
        return ra_error('No tienes acceso a esta lista.', 403, 'ra_forbidden');
    }
    $category = $r->get_param('category');
    if (is_string($category) && $category !== '' && $category !== 'all') { $where[] = $wpdb->prepare('category = %s', sanitize_key($category)); }
    $search = $r->get_param('search');
    if (is_string($search) && $search !== '') {
        $term = '%' . $wpdb->esc_like(sanitize_text_field($search)) . '%';
        $where[] = $wpdb->prepare('(title LIKE %s OR merchant_name LIKE %s OR pickup_area LIKE %s)', $term, $term, $term);
    }
    $rows = $wpdb->get_results("SELECT * FROM $table WHERE " . implode(' AND ', $where) . ' ORDER BY CASE status WHEN \'available\' THEN 0 WHEN \'reserved\' THEN 1 ELSE 2 END, deadline ASC, id DESC LIMIT 200');
    if ($wpdb->last_error) { return ra_error('No pudimos actualizar las donaciones', 503, 'ra_list_failed'); }
    return rest_ensure_response(array('items'=>array_map('ra_serialize', $rows),'server_time'=>gmdate('c'),'limit'=>200));
}

function ra_input($r, $name, $max=255, $textarea=false) {
    $raw = $r->get_param($name);
    if ($raw === null) { return ''; }
    if (!is_scalar($raw)) { return ''; }
    $v = $textarea ? sanitize_textarea_field((string)$raw) : sanitize_text_field((string)$raw);
    return function_exists('mb_substr') ? mb_substr($v, 0, $max) : substr($v, 0, $max);
}

function ra_create_donation($r) {
    global $wpdb;
    $data = array(
        'title'=>ra_input($r,'title',180), 'description'=>ra_input($r,'description',2000,true),
        'category'=>ra_input($r,'category',32), 'quantity'=>$r->get_param('quantity'),
        'unit'=>ra_input($r,'unit',24), 'merchant_name'=>ra_input($r,'merchant_name',180),
        'pickup_address'=>ra_input($r,'pickup_address',255), 'pickup_area'=>ra_input($r,'pickup_area',120),
        'deadline'=>ra_parse_date($r->get_param('deadline')),
        'origin_channel'=>ra_input($r,'origin_channel',12) ?: 'web',
    );
    $errors = array();
    $coordinates = ra_pickup_coordinates($r->get_param('pickup_latitude'), $r->get_param('pickup_longitude'));
    if (is_wp_error($coordinates)) {
        $coordinate_error = $coordinates->get_error_data();
        $errors = $coordinate_error['fields'];
    } else {
        // Absent coordinates preserve the payload shape of existing retry keys.
        $data += $coordinates;
    }
    foreach (array('title'=>'Escribe el alimento que vas a donar.','merchant_name'=>'Escribe el nombre del comercio.','pickup_address'=>'Indica el punto de recogida.') as $field=>$message) { if (!$data[$field]) { $errors[$field]=$message; } }
    if (!in_array($data['category'],array('vegetables','fruit','bakery','prepared','dairy','pantry'),true)) { $errors['category']='Selecciona una categoría válida.'; }
    if (!is_scalar($data['quantity']) || !is_numeric($data['quantity']) || (float)$data['quantity'] < 0.001 || (float)$data['quantity'] > 1000000) { $errors['quantity']='Escribe una cantidad mayor que cero y no mayor de 1.000.000.'; }
    else { $data['quantity'] = round((float)$data['quantity'], 3); }
    if (!in_array($data['unit'],array('kg','portions','units','liters'),true)) { $errors['unit']='Selecciona una unidad válida.'; }
    if (!$data['deadline']) { $errors['deadline']='Indica una fecha y hora válidas con zona horaria.'; }
    if (!in_array($data['origin_channel'],array('web','phone','sms'),true)) { $errors['origin_channel']='Selecciona web, llamada o SMS.'; }
    if (!ra_can('coordinate') && ($data['origin_channel'] !== 'web' || $r->get_param('assisted'))) { return ra_error('Solo coordinación puede registrar solicitudes asistidas.',403,'ra_forbidden'); }
    $data['assisted'] = ra_can('coordinate') && ($data['origin_channel'] !== 'web' || (bool)$r->get_param('assisted')) ? 1 : 0;
    $data['donor_id'] = get_current_user_id();
    if (ra_can('coordinate') && $data['assisted']) {
        $donor = absint($r->get_param('donor_id'));
        if ($donor) {
            $u = get_userdata($donor);
            if (!$u || !user_can($u,'ra_create')) { $errors['donor_id']='Selecciona una cuenta de comercio válida.'; }
        }
        $data['donor_id'] = $donor;
    }
    if ($errors) { return ra_error('Revisa los campos indicados.',400,'ra_validation',$errors); }
    return ra_mutate($r->get_param('request_key'),'create',0,$data,function ($request_id) use ($data,$wpdb) {
        if ($data['deadline'] <= ra_now()) { return ra_error('La hora límite debe estar en el futuro.',400,'ra_validation',array('deadline'=>'Selecciona una fecha y hora futuras.')); }
        $save = $data + array('status'=>'available','author_id'=>get_current_user_id(),'created_at'=>ra_now());
        if ($wpdb->insert(ra_table('donations'),$save) === false) { return ra_db_error(); }
        $id = $wpdb->insert_id;
        if (!ra_event($id,'available',$request_id)) { return ra_db_error(); }
        $saved_row = ra_get_row($id);
        return $saved_row ? ra_serialize($saved_row, true) : ra_db_error();
    });
}

function ra_reserve_donation($r) {
    global $wpdb;
    $id = (int)$r['id'];
    return ra_mutate($r->get_param('request_key'),'reserve',$id,array(),function ($request_id) use ($wpdb,$id) {
        $table = ra_table('donations');
        // This single conditional write arbitrates concurrent volunteers. There is
        // exactly one claimant column on one primary-keyed donation row.
        $updated = $wpdb->query($wpdb->prepare("UPDATE $table SET status='reserved', volunteer_id=%d, reserved_at=%s WHERE id=%d AND status='available' AND volunteer_id IS NULL AND deadline>%s",get_current_user_id(),ra_now(),$id,ra_now()));
        if ($updated === false) { return ra_db_error(); }
        if ($updated !== 1) {
            $row = ra_get_row($id);
            if (!$row) { return ra_private_error(); }
            if ($row->status !== 'available') { return ra_error('Esta donación ya fue reservada',409,'ra_reserved'); }
            return ra_error('El plazo de recogida de esta donación ya terminó.',409,'ra_expired');
        }
        if (!ra_event($id,'reserved',$request_id)) { return ra_db_error(); }
        $saved_row = ra_get_row($id);
        return $saved_row ? ra_serialize($saved_row, true) : ra_db_error();
    });
}

function ra_deliver_donation($r) {
    global $wpdb;
    $id = (int)$r['id'];
    $data = array('recipient_name'=>ra_input($r,'recipient_name',180),'picked_up_at'=>ra_parse_date($r->get_param('picked_up_at')),'delivered_at'=>ra_parse_date($r->get_param('delivered_at')),'responsible_name'=>ra_input($r,'responsible_name',180),'notes'=>ra_input($r,'notes',2000,true));
    $errors = array();
    foreach (array('recipient_name'=>'Escribe la organización receptora.','responsible_name'=>'Indica quién recibió los alimentos.','picked_up_at'=>'Indica la fecha y hora de recogida.','delivered_at'=>'Indica la fecha y hora de entrega.') as $key=>$message) { if (!$data[$key]) { $errors[$key]=$message; } }
    if ($data['picked_up_at'] && $data['delivered_at'] && $data['picked_up_at'] > $data['delivered_at']) { $errors['delivered_at']='La entrega debe ser posterior o igual a la recogida.'; }
    if ($errors) { return ra_error('Revisa los datos de la entrega.',400,'ra_validation',$errors); }
    return ra_mutate($r->get_param('request_key'),'deliver',$id,$data,function ($request_id) use ($wpdb,$id,$data) {
        $errors = array();
        if ($data['picked_up_at'] > ra_now()) { $errors['picked_up_at']='La recogida no puede estar en el futuro.'; }
        if ($data['delivered_at'] > ra_now()) { $errors['delivered_at']='La entrega no puede estar en el futuro.'; }
        if ($errors) { return ra_error('Solo puedes registrar hechos que ya ocurrieron.',400,'ra_validation',$errors); }
        $table = ra_table('donations');
        $updated = $wpdb->query($wpdb->prepare("UPDATE $table SET status='delivered', recipient_name=%s, picked_up_at=%s, delivered_at=%s, responsible_name=%s, delivery_notes=%s, closed_by=%d, closed_at=%s WHERE id=%d AND status='reserved' AND volunteer_id IS NOT NULL",$data['recipient_name'],$data['picked_up_at'],$data['delivered_at'],$data['responsible_name'],$data['notes'],get_current_user_id(),ra_now(),$id));
        if ($updated === false) { return ra_db_error(); }
        if ($updated !== 1) {
            $row = ra_get_row($id);
            if (!$row) { return ra_private_error(); }
            return ra_error($row->status === 'delivered' ? 'Esta entrega ya fue registrada y no puede reemplazarse.' : 'Primero debe existir una reserva confirmada.',409,'ra_state');
        }
        if (!ra_event($id,'delivered',$request_id)) { return ra_db_error(); }
        $saved_row = ra_get_row($id);
        return $saved_row ? ra_serialize($saved_row, true) : ra_db_error();
    });
}
