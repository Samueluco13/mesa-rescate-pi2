<?php
/** Isolated local test fixture preparation. Never install in a public directory. */
if (!defined('ABSPATH') || !defined('RA_RACE_TEST') || !RA_RACE_TEST) { throw new RuntimeException('Isolated test environment required.'); }
$previous=json_decode(file_get_contents(__DIR__.'/results-local.json'),true);
$users=$previous['fixture_ids']['users'];
$credentials=[];
foreach ([$users['volunteer_a'],$users['volunteer_b']] as $uid) {
    $expires=time()+3600;
    $token=WP_Session_Tokens::get_instance($uid)->create($expires);
    $cookie=wp_generate_auth_cookie($uid,$expires,'logged_in',$token);
    $_COOKIE[LOGGED_IN_COOKIE]=$cookie;
    wp_set_current_user($uid);
    $credentials[]=['actor_id'=>$uid,'cookie'=>LOGGED_IN_COOKIE.'='.$cookie,'nonce'=>wp_create_nonce('wp_rest')];
}
wp_set_current_user($users['donor']);
$ids=[];
for($i=0;$i<10;$i++) {
    $r=new WP_REST_Request('POST','/rescate/v1/donations');
    $r->set_header('Content-Type','application/json');
    $r->set_body(wp_json_encode(['request_key'=>'http_race_'.$i.'_'.time(),'title'=>'Prueba HTTP concurrente ficticia '.$i,'category'=>'vegetables','quantity'=>2,'unit'=>'kg','merchant_name'=>'Comercio ficticio de pruebas','pickup_address'=>'Punto de prueba ficticio','pickup_area'=>'Cali','deadline'=>gmdate('c',time()+7200)]));
    $result=rest_do_request($r);$data=$result->get_data();
    if($result->get_status()!==200){throw new RuntimeException(wp_json_encode($data));}
    $ids[]=$data['id'];$GLOBALS['wpdb']->update(ra_table('donations'),['is_demo'=>1],['id'=>$data['id']]);
}
file_put_contents(__DIR__.'/.race-auth.json',json_encode(['credentials'=>$credentials,'donations'=>$ids]));
echo json_encode(['prepared'=>count($ids),'donations'=>$ids]);
