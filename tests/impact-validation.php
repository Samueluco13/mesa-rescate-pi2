<?php
if (!defined('ABSPATH') || !defined('RA_IMPACT_TEST') || !RA_IMPACT_TEST) { exit; }
global $wpdb;
$roles = array('coord'=>'ra_coordinator','volunteer'=>'ra_volunteer','receiver'=>'ra_receiver','donor'=>'ra_donor');
$users = array();
foreach ($roles as $name=>$role) {
    $id = wp_insert_user(array('user_login'=>'impact_test_'.$name,'user_pass'=>wp_generate_password(),'role'=>$role));
    if (is_wp_error($id)) { throw new RuntimeException($id->get_error_message()); }
    $users[$name] = $id;
}
$insert = function ($changes) use ($wpdb,$users) {
    $data = array_replace(array('title'=>'Alimentos ficticios','description'=>'Fixture aislado','category'=>'fruit','quantity'=>2.75,
        'unit'=>'kg','merchant_name'=>'Comercio ficticio','donor_id'=>$users['donor'],'author_id'=>$users['donor'],
        'pickup_address'=>'Punto ficticio','pickup_area'=>'Cali','deadline'=>'2026-11-13 12:00:00','created_at'=>'2026-11-11 00:00:00',
        'status'=>'delivered','delivered_at'=>'2026-11-12 05:00:00','recipient_name'=>'Fundación Árbol & Luz','assigned_recipient_id'=>$users['receiver']),$changes);
    if ($wpdb->insert(ra_table('donations'),$data) === false) { throw new RuntimeException($wpdb->last_error); }
};
$insert(array());
$insert(array('unit'=>'portions','quantity'=>3,'delivered_at'=>'2026-11-13 04:59:59','recipient_name'=>'Fundación Árbol'));
$insert(array('unit'=>'units','quantity'=>12,'assigned_recipient_id'=>null,'recipient_name'=>'Mesa Norte'));
$insert(array('unit'=>'liters','quantity'=>5,'assigned_recipient_id'=>null,'recipient_name'=>'Mesa Norte'));
$insert(array('unit'=>'kg','quantity'=>1.25,'assigned_recipient_id'=>null,'recipient_name'=>''));
$insert(array('status'=>'reserved','quantity'=>500));
$insert(array('delivered_at'=>'2026-11-12 04:59:59','quantity'=>500));
$insert(array('delivered_at'=>'2026-11-13 05:00:00','quantity'=>500));
$insert(array('is_demo'=>1,'quantity'=>11));
$insert(array('title'=>'PRUEBA Entrega antigua','quantity'=>20));
$insert(array('title'=>'[PRUEBA] Entrega anterior','quantity'=>30));
$insert(array('title'=>'[LOCAL] Entrega local','quantity'=>40));
$call = function ($role,$params=array()) use ($users) {
    wp_set_current_user($users[$role] ?? 0);
    $r = new WP_REST_Request('GET','/rescate/v1/impact');
    $r->set_query_params(array_replace(array('from'=>'2026-11-12','to'=>'2026-11-12'),$params));
    $res = rest_do_request($r);
    return array($res->get_status(),$res->get_data());
};
[$code,$report] = $call('coord');
$cases = array(
    'permisos'=> $code===200 && $call('guest')[0]===401 && $call('volunteer')[0]===403 && $call('receiver')[0]===403 && $call('donor')[0]===403,
    'fechas'=> $call('coord',array('from'=>'2026-02-30'))[0]===400 && $call('coord',array('to'=>'2026-11-11'))[0]===400,
    'limites_colombia_y_solo_entregadas'=>($report['totals']['deliveries']??null)===5,
    'medidas_separadas'=> (float)$report['totals']['kg']===4.0 && (float)$report['totals']['portions']===3.0 && (float)$report['totals']['liters']===5.0 && (float)$report['totals']['units']===12.0,
    'beneficiarios_estimados'=> $report['totals']['estimated']===11,
    'organizaciones'=>count($report['organizations'])===2 && $report['without_org']===1 && max(array_column($report['organizations'],'deliveries'))===2,
    'incluir_pruebas'=> $call('coord',array('demo'=>'1'))[1]['totals']['deliveries']===9,
    'vacio'=> $call('coord',array('from'=>'2026-10-01','to'=>'2026-10-01'))[1]['totals']['estimated']===null,
);
echo wp_json_encode(array('passed'=>count(array_filter($cases)),'failed'=>count($cases)-count(array_filter($cases)),'cases'=>$cases,'report'=>$report));
