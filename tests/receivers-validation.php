<?php
if (!defined('ABSPATH') || !defined('RA_RECEIVERS_TEST') || !RA_RECEIVERS_TEST) { exit; }
global $wpdb;
$users = array();
foreach (array('shop'=>'ra_donor','driver'=>'ra_volunteer','ops'=>'ra_coordinator','near'=>'ra_receiver','far'=>'ra_receiver','warm'=>'ra_receiver') as $name=>$role) {
    $users[$name] = wp_insert_user(array('user_login'=>'test_'.$name,'user_pass'=>wp_generate_password(),'role'=>$role,'display_name'=>'Receptora '.$name));
    if (is_wp_error($users[$name])) { throw new RuntimeException($users[$name]->get_error_message()); }
}
$call = function ($who,$method,$path,$body=array()) use ($users) {
    wp_set_current_user($users[$who]);
    $r = new WP_REST_Request($method,'/rescate/v1/'.$path);
    if ($method === 'GET') { $r->set_query_params($body); }
    else { $r->set_header('Content-Type','application/json'); $r->set_body(wp_json_encode($body)); }
    $res = rest_do_request($r);
    return array($res->get_status(),$res->get_data());
};
$cases = array();
$check = function ($label,$passed) use (&$cases) { $cases[$label] = (bool)$passed; };
$profile = array('active'=>true,'cold'=>true,'kg'=>10,'units'=>0,'liters'=>0,'portions'=>0,'latitude'=>3.45,'longitude'=>-76.53);
$check('save capacity and refrigeration', $call('near','POST','receiver',$profile)[0] === 200);
$check('far receiver', $call('far','POST','receiver',array_replace($profile,array('latitude'=>3.52)))[0] === 200);
$check('receiver without refrigeration', $call('warm','POST','receiver',array_replace($profile,array('cold'=>false)))[0] === 200);
$check('volunteer cannot change receiver profile', $call('driver','POST','receiver',$profile)[0] === 403);
$offer = array('title'=>'Fruta ficticia','category'=>'fruit','quantity'=>8,'unit'=>'kg','merchant_name'=>'Comercio ficticio','pickup_address'=>'Dirección ficticia','deadline'=>gmdate('c',time()+3600),'pickup_latitude'=>3.4516,'pickup_longitude'=>-76.532,'requires_cold'=>true,'request_key'=>'test_receivers_offer_1');
[$code,$first] = $call('shop','POST','donations',$offer);
$check('create with refrigeration requirement', $code === 200 && !empty($first['requires_cold']));
$id = $first['id'];
$check('reserve', $call('driver','POST',"donations/$id/reserve",array('request_key'=>'test_receivers_reserve_1'))[0] === 200);
$destination = "donations/$id/destination";
[$code,$suggestion] = $call('ops','GET',$destination);
$check('shortest compatible recipient suggested', $code === 200 && $suggestion['suggested_id'] === $users['near'] && array_column($suggestion['candidates'],'id') === array($users['near'],$users['far']));
$check('volunteer cannot assign', $call('driver','POST',$destination)[0] === 403);
$assignment = array('recipient'=>$users['near'],'arrival_at'=>gmdate('c',time()+1800),'request_key'=>'test_receivers_assign_1');
[$code,$assigned] = $call('ops','POST',$destination,$assignment);
$check('confirm destination', $code === 200 && ($assigned['destination']['name'] ?? null) === 'Receptora near');
$check('safe retry', wp_json_encode($call('ops','POST',$destination,$assignment)) === wp_json_encode(array($code,$assigned)));
[$code,$receiver] = $call('near','GET','receiver');
$notice = $receiver['notices'][0] ?? array();
$check('private advance notice has food, quantity, and ETA', $code === 200 && ($notice['title'] ?? null) === $offer['title'] && (float)($notice['quantity'] ?? 0) === 8.0 && strtotime($notice['arrival_at']) === strtotime($assignment['arrival_at']));
$check('other recipient sees no notice', $call('far','GET','receiver')[1]['notices'] === array());
[$code,$next] = $call('shop','POST','donations',array_replace($offer,array('request_key'=>'test_receivers_offer_2')));
$id = $next['id'];
$call('driver','POST',"donations/$id/reserve",array('request_key'=>'test_receivers_reserve_2'));
$destination = "donations/$id/destination";
$check('server rejects overbooking', $call('ops','POST',$destination,array_replace($assignment,array('request_key'=>'test_receivers_assign_2')))[0] === 409);
$call('far','POST','receiver',array_replace($profile,array('active'=>false)));
[$code,$suggestion] = $call('ops','GET',$destination);
$check('coordination receives no-match alert', $code === 200 && $suggestion['candidates'] === array() && !empty($suggestion['alert']));
$check('expired stays in history and leaves available', (function () use ($call,$wpdb,$offer) {
    [, $expired] = $call('shop','POST','donations',array_replace($offer,array('request_key'=>'test_receivers_offer_3')));
    $wpdb->update(ra_table('donations'),array('deadline'=>gmdate('Y-m-d H:i:s',time()-5)),array('id'=>$expired['id']));
    $available = $call('ops','GET','donations',array('scope'=>'available'))[1]['items'];
    $all = $call('ops','GET','donations',array('scope'=>'all'))[1]['items'];
    return !in_array($expired['id'],array_column($available,'id'),true) && in_array($expired['id'],array_column($all,'id'),true);
})());
echo wp_json_encode(array('passed'=>count(array_filter($cases)),'failed'=>count($cases)-count(array_filter($cases)),'cases'=>$cases));
