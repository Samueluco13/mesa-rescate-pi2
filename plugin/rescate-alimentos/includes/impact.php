<?php
if (!defined('ABSPATH')) { exit; }

function ra_impact_routes() {
    register_rest_route('rescate/v1','/impact',array(
        'methods'=>'GET','permission_callback'=>function () { return ra_permission('coordinate'); },
        'callback'=>'ra_impact_report',
    ));
}

function ra_impact_day($value, DateTimeZone $timezone) {
    if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/D',$value)) { return null; }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d',$value,$timezone);
    return $date && $date->format('Y-m-d') === $value && (int)$date->format('Y') >= 1970 && (int)$date->format('Y') <= 9998 ? $date : null;
}

function ra_impact_report($request) {
    global $wpdb;
    $timezone = new DateTimeZone('America/Bogota');
    $utc = new DateTimeZone('UTC');
    $from = ra_impact_day($request->get_param('from'),$timezone);
    $to = ra_impact_day($request->get_param('to'),$timezone);
    $errors = array();
    if (!$from) { $errors['from'] = 'Selecciona una fecha inicial válida.'; }
    if (!$to) { $errors['to'] = 'Selecciona una fecha final válida.'; }
    if ($from && $to && $from > $to) { $errors['to'] = 'La fecha final debe ser igual o posterior a la inicial.'; }
    if ($errors) { return ra_error('Revisa las fechas del reporte.',400,'ra_validation',$errors); }
    $include_demo = $request->get_param('demo') === '1';
    $table = ra_table('donations');
    $where = $wpdb->prepare("status = 'delivered' AND delivered_at >= %s AND delivered_at < %s",
        $from->setTimezone($utc)->format('Y-m-d H:i:s'),$to->modify('+1 day')->setTimezone($utc)->format('Y-m-d H:i:s'));
    if (!$include_demo) {
        $where .= $wpdb->prepare(' AND is_demo = 0 AND UPPER(title) NOT LIKE %s AND UPPER(title) NOT LIKE %s AND UPPER(title) NOT LIKE %s',
            $wpdb->esc_like('[PRUEBA]').'%', $wpdb->esc_like('PRUEBA ').'%', $wpdb->esc_like('[LOCAL]').'%');
    }
    $rows = $wpdb->get_results("SELECT assigned_recipient_id, recipient_name, unit, COUNT(*) AS deliveries, SUM(quantity) AS amount
        FROM $table WHERE $where GROUP BY assigned_recipient_id, recipient_name, unit ORDER BY recipient_name, assigned_recipient_id, unit");
    if ($wpdb->last_error || !is_array($rows)) { return ra_error('No pudimos generar el reporte. Inténtalo de nuevo.',503,'ra_report_failed'); }

    $empty = array('deliveries'=>0,'kg'=>0,'portions'=>0,'units'=>0,'liters'=>0);
    $totals = $empty;
    $organizations = array();
    $without_org = 0;
    foreach ($rows as $row) {
        $count = (int)$row->deliveries;
        $totals['deliveries'] += $count;
        $unit = $row->unit;
        if (in_array($unit,array('kg','portions','units','liters'),true)) { $totals[$unit] += (float)$row->amount; }
        $name = trim(preg_replace('/\s+/u',' ',(string)$row->recipient_name));
        if ($name === '') { $without_org += $count; continue; }
        $name_key = function_exists('mb_strtolower') ? mb_strtolower($name,'UTF-8') : strtolower($name);
        $key = $row->assigned_recipient_id ? 'id:'.$row->assigned_recipient_id : 'name:'.$name_key;
        if (!isset($organizations[$key])) { $organizations[$key] = array('name'=>$name,'registered'=>(bool)$row->assigned_recipient_id) + $empty; }
        $organizations[$key]['deliveries'] += $count;
        if (in_array($unit,array('kg','portions','units','liters'),true)) { $organizations[$key][$unit] += (float)$row->amount; }
    }
    foreach (array('kg','portions','units','liters') as $unit) {
        $totals[$unit] = round($totals[$unit],3);
        foreach ($organizations as &$org) { $org[$unit] = round($org[$unit],3); }
        unset($org);
    }
    // The estimate describes serving equivalents, not identified people.
    $kg_per_serving = 0.5;
    $estimate = $totals['kg'] / $kg_per_serving + $totals['portions'];
    $totals['estimated'] = $estimate > 0 ? (int)floor(round($estimate,3)) : null;
    return rest_ensure_response(array(
        'from'=>$from->format('Y-m-d'),'to'=>$to->format('Y-m-d'),'demo'=>$include_demo,
        'totals'=>$totals,'organizations'=>array_values($organizations),'without_org'=>$without_org,
        'method'=>'Estimación: 1 porción registrada o 0,5 kg equivalen a 1 ración. Se cuentan raciones completas, no personas únicas. Piezas y litros no se convierten.',
        'scope'=>$include_demo ? 'Incluye registros de demostración y pruebas.' : 'Excluye demostraciones y títulos marcados como prueba o local.',
    ));
}
