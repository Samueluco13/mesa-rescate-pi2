<?php
/** Isolated pin-location checks; never expose as a public endpoint. */
if (!defined('ABSPATH') || !defined('RA_MAP_TEST') || RA_MAP_TEST !== true) {
    throw new RuntimeException('Run only in an isolated WordPress environment with RA_MAP_TEST=true.');
}

(function () {
    global $wpdb;
    $original_user = get_current_user_id();
    $cases = array();
    $users = array();
    $rows = array();
    $run = 'map_' . substr(hash('sha256',uniqid('',true)),0,14);
    $record = function ($id,$label,$passed,$observed=null) use (&$cases) {
        $cases[] = array('id'=>$id,'description'=>$label,'passed'=>(bool)$passed,'observed'=>$observed);
    };
    $call = function ($actor,$method,$path,$payload=array()) {
        wp_set_current_user($actor);
        $r = new WP_REST_Request($method,'/rescate/v1/' . $path);
        $r->set_header('X-WP-Nonce',wp_create_nonce('wp_rest'));
        $r->set_header('Content-Type','application/json');
        $r->set_body(wp_json_encode($payload));
        $response = rest_do_request($r);
        return array('status'=>$response->get_status(),'body'=>$response->get_data());
    };
    try {
        if (!function_exists('ra_pickup_coordinates')) { throw new RuntimeException('Map coordinates feature is not loaded.'); }
        foreach (array(
            'omitted'=>array(null,null,true),
            'empty'=>array('','',true),
            'valid_strings'=>array('3.451646','-76.531985',true),
            'zero'=>array(0,0,true),
            'bounds'=>array(-90,180,true),
            'missing_lat'=>array(null,-76,false),
            'missing_lng'=>array(3,'',false),
            'boolean'=>array(false,-76,false),
            'array'=>array(array(3),-76,false),
            'latitude_high'=>array(90.000001,-76,false),
            'longitude_low'=>array(3,-180.000001,false),
            'not_numeric'=>array('Cali',-76,false),
            'infinite'=>array(INF,-76,false),
            'nan'=>array(NAN,-76,false),
        ) as $name=>$spec) {
            $result = ra_pickup_coordinates($spec[0],$spec[1]);
            $record('PIN-VALIDATE-' . $name,'Optional coordinate validation: ' . $name,is_wp_error($result) !== $spec[2],is_wp_error($result) ? $result->get_error_data()['fields'] : $result);
        }
        $rounded = ra_pickup_coordinates('3.45164649','-76.53198551');
        $record('PIN-ROUND','Coordinates normalize to six decimal places',$rounded === array('pickup_latitude'=>3.451646,'pickup_longitude'=>-76.531986),$rounded);
        $record('PIN-LEGACY-SHAPE','Omitted coordinates add no keys to legacy creation hashes',ra_pickup_coordinates(null,null) === array());
        rest_get_server();
        foreach (array('donor'=>'ra_donor','volunteer'=>'ra_volunteer') as $label=>$role) {
            $user = wp_insert_user(array('user_login'=>$run . '_' . $label,'user_pass'=>wp_generate_password(32,true),'user_email'=>$run . '_' . $label . '@example.invalid','display_name'=>'Prueba ficticia ubicación ' . $label,'role'=>$role));
            if (is_wp_error($user)) { throw new RuntimeException($user->get_error_message()); }
            $users[$label] = (int)$user;
        }
        $base = array('title'=>'Prueba ficticia de ubicación ' . $run,'description'=>'Registro ficticio aislado; no es una recogida real.','category'=>'vegetables','quantity'=>2,'unit'=>'kg','merchant_name'=>'Comercio ficticio ' . $run,'pickup_address'=>'Dirección ficticia de prueba, Cali','pickup_area'=>'Prueba','deadline'=>gmdate('Y-m-d\TH:i:s\Z',time()+86400),'origin_channel'=>'web');
        $legacy = $base + array('request_key'=>$run . '_legacy');
        $created = $call($users['donor'],'POST','donations',$legacy);
        if (!empty($created['body']['id'])) { $rows[] = (int)$created['body']['id']; }
        $record('PIN-CREATE-OPTIONAL','Creation without a pin succeeds with null coordinates',$created['status'] === 200 && array_key_exists('pickup_latitude',$created['body']) && $created['body']['pickup_latitude'] === null && $created['body']['pickup_longitude'] === null,$created['status']);
        if (!empty($created['body']['id'])) {
            $id = (int)$created['body']['id'];
            $wpdb->update(ra_table('donations'),array('is_demo'=>1),array('id'=>$id));
            $before = ra_get_row($id);
            ra_install_schema();
            $after = ra_get_row($id);
            $record('PIN-MIGRATION','Repeated dbDelta preserves existing donation and its unlocated state',$before && $after && $after->title === $before->title && $after->pickup_latitude === null && $after->pickup_longitude === null);
            // Simulate the saved response produced before maps existed; the hash stays untouched.
            $old_result = $created['body'];
            unset($old_result['pickup_latitude'],$old_result['pickup_longitude']);
            $wpdb->update(ra_table('requests'),array('result_json'=>wp_json_encode($old_result)),array('actor_id'=>$users['donor'],'request_key'=>$legacy['request_key']));
            $replayed = $call($users['donor'],'POST','donations',$legacy);
            $record('PIN-LEGACY-REPLAY','A pre-map request still returns its original stored response',$replayed['status'] === 200 && $replayed['body'] === json_decode(wp_json_encode($old_result),true),$replayed['status']);
        }
        $located = $base + array('request_key'=>$run . '_located','pickup_latitude'=>'3.45164649','pickup_longitude'=>'-76.53198551');
        $created_pin = $call($users['donor'],'POST','donations',$located);
        if (!empty($created_pin['body']['id'])) { $rows[] = (int)$created_pin['body']['id']; }
        $record('PIN-CREATE-PERSIST','Publisher pin persists and returns rounded coordinates',$created_pin['status'] === 200 && $created_pin['body']['pickup_latitude'] === 3.451646 && $created_pin['body']['pickup_longitude'] === -76.531986,$created_pin['status']);
        if (!empty($created_pin['body']['id'])) {
            $pin_id = (int)$created_pin['body']['id'];
            $wpdb->update(ra_table('donations'),array('is_demo'=>1),array('id'=>$pin_id));
            $detail = $call($users['volunteer'],'GET','donations/' . $pin_id);
            $record('PIN-READ','Authorized volunteer reads the published pickup pin',$detail['status'] === 200 && $detail['body']['pickup_latitude'] === 3.451646 && $detail['body']['pickup_longitude'] === -76.531986,$detail['status']);
            $repeat = $call($users['donor'],'POST','donations',$located);
            $record('PIN-REPLAY','Same coordinates and request return the same donation',$repeat['status'] === 200 && $repeat['body']['id'] === $pin_id,$repeat['status']);
            $changed = $located;
            $changed['pickup_longitude'] = -76.6;
            $conflict = $call($users['donor'],'POST','donations',$changed);
            $record('PIN-HASH-CONFLICT','Changed coordinates with a reused request key conflict',$conflict['status'] === 409 && ($conflict['body']['code'] ?? '') === 'ra_key_conflict',$conflict['status']);
        }
        $count_before = (int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . ra_table('donations') . ' WHERE donor_id=%d',$users['donor']));
        foreach (array('pair'=>array('pickup_latitude'=>3),'range'=>array('pickup_latitude'=>91,'pickup_longitude'=>-76),'typed'=>array('pickup_latitude'=>true,'pickup_longitude'=>-76)) as $label=>$invalid) {
            $response = $call($users['donor'],'POST','donations',$invalid + $base + array('request_key'=>$run . '_' . $label));
            $record('PIN-API-' . $label,'REST rejects invalid coordinates: ' . $label,$response['status'] === 400 && !empty($response['body']['data']['fields']),$response['body']['data']['fields'] ?? null);
        }
        $count_after = (int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . ra_table('donations') . ' WHERE donor_id=%d',$users['donor']));
        $record('PIN-NO-INVALID-WRITES','Rejected coordinate inputs create no donations',$count_before === $count_after,array($count_before,$count_after));
        $denied = $call($users['volunteer'],'POST','donations',$base + array('request_key'=>$run . '_unauthorized','pickup_latitude'=>3.4,'pickup_longitude'=>-76.5));
        $record('PIN-PERMISSIONS','Volunteers cannot publish pickup coordinates',$denied['status'] === 403,$denied['status']);
    } catch (Throwable $e) {
        $record('PIN-HARNESS','Map checks execute without exceptions',false,$e->getMessage());
    } finally {
        wp_set_current_user($original_user);
        if (!defined('RA_MAP_TEST_CLEANUP') || RA_MAP_TEST_CLEANUP) {
            foreach ($rows as $id) {
                $wpdb->delete(ra_table('events'),array('donation_id'=>$id));
                $wpdb->delete(ra_table('donations'),array('id'=>$id));
            }
            foreach ($users as $id) { $wpdb->delete(ra_table('requests'),array('actor_id'=>$id)); }
            require_once ABSPATH . 'wp-admin/includes/user.php';
            foreach ($users as $id) { wp_delete_user($id); }
        }
    }
    $passed = count(array_filter($cases,function ($c) { return $c['passed']; }));
    echo wp_json_encode(array('suite'=>'Optional pickup coordinates','plugin_version'=>RA_VERSION,'passed'=>$passed,'failed'=>count($cases)-$passed,'cases'=>$cases),JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
})();
