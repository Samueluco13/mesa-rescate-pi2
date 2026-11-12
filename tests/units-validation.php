<?php
/** Isolated unit-label acceptance suite; never load through a public endpoint. */
if (!defined('ABSPATH') || !defined('RA_UNITS_TEST') || RA_UNITS_TEST !== true) {
    throw new RuntimeException('Run only inside isolated WordPress with RA_UNITS_TEST=true.');
}

(function () {
    global $wpdb;
    $original_user = get_current_user_id();
    $run = 'units_' . substr(hash('sha256',uniqid('',true) . wp_rand()),0,12);
    $users = array(); $donations = array(); $cases = array(); $operation = 0;
    $started = microtime(true);
    $record = function ($id,$description,$pass,$observed=array()) use (&$cases) {
        $cases[] = array('id'=>$id,'description'=>$description,'passed'=>(bool)$pass,'observed'=>$observed);
    };
    $key = function ($label) use ($run,&$operation) { return $run . '_' . ++$operation . '_' . $label; };
    $call = function ($actor,$method,$path,$data=array()) {
        wp_set_current_user((int)$actor);
        $request = new WP_REST_Request($method,'/rescate/v1/' . ltrim($path,'/'));
        $request->set_header('X-WP-Nonce',wp_create_nonce('wp_rest'));
        if ($method === 'GET') { $request->set_query_params($data); }
        else { $request->set_header('Content-Type','application/json'); $request->set_body(wp_json_encode($data)); }
        $response = rest_do_request($request);
        $response = apply_filters('rest_post_dispatch',$response,rest_get_server(),$request);
        return array('status'=>$response->get_status(),'body'=>$response->get_data());
    };
    $info = function ($response) {
        return array('status'=>$response['status'],'code'=>$response['body']['code'] ?? null,'id'=>$response['body']['id'] ?? null,'quantity'=>$response['body']['quantity'] ?? null,'unit'=>$response['body']['unit'] ?? null,'unit_label'=>$response['body']['unit_label'] ?? null,'fields'=>$response['body']['data']['fields'] ?? array());
    };
    $has_label = function ($response,$label) {
        return $response['status'] === 200 && ($response['body']['unit_label'] ?? null) === $label && (float)($response['body']['quantity'] ?? 0) === 3.0;
    };
    try {
        if (!function_exists('ra_table') || !function_exists('ra_register_routes')) { throw new RuntimeException('Activate Rescate de Alimentos before this suite.'); }
        rest_get_server();
        foreach (array('donor'=>'ra_donor','volunteer'=>'ra_volunteer','coordinator'=>'ra_coordinator') as $name=>$role) {
            $id = wp_insert_user(array('user_login'=>$run . '_' . $name,'user_pass'=>wp_generate_password(30,true,true),'user_email'=>$run . '_' . $name . '@example.invalid','display_name'=>'Prueba ficticia de unidades ' . $name,'role'=>$role));
            if (is_wp_error($id)) { throw new RuntimeException($id->get_error_message()); }
            $users[$name] = (int)$id;
        }
        $base = array('title'=>'Donación ficticia ' . $run,'description'=>'Prueba aislada sin alimentos reales.','category'=>'fruit','quantity'=>3,'unit'=>'units','merchant_name'=>'Comercio ficticio de pruebas','pickup_address'=>'Dirección ficticia, Cali','pickup_area'=>'Zona ficticia','deadline'=>gmdate('Y-m-d\TH:i:s\Z',time()+21600),'origin_channel'=>'web');
        $create = function ($label,$changes=array()) use ($base,$call,$key,&$users,&$donations,$wpdb) {
            $payload = array_merge($base,$changes,array('request_key'=>$key($label)));
            $response = $call($users['donor'],'POST','donations',$payload);
            if ($response['status'] === 200 && !empty($response['body']['id'])) {
                $id = (int)$response['body']['id']; $donations[] = $id;
                $wpdb->update(ra_table('donations'),array('is_demo'=>1),array('id'=>$id),array('%d'),array('%d'));
            }
            return array('response'=>$response,'payload'=>$payload);
        };
        $main = $create('manzanas',array('unit_label'=>'manzanas'));
        $response = $main['response']; $main_id = (int)($response['body']['id'] ?? 0);
        $record('UNIT-CREATE','Three countable items retain their optional custom label on creation',$has_label($response,'manzanas'),$info($response));
        if (!$main_id) { throw new RuntimeException('Could not create the labeled-unit fixture.'); }
        $response = $call($users['donor'],'GET','donations/' . $main_id);
        $record('UNIT-READ','Owner detail returns the saved quantity, unit and item label',$has_label($response,'manzanas') && ($response['body']['unit'] ?? '') === 'units',$info($response));
        $list = $call($users['volunteer'],'GET','donations',array('scope'=>'available','search'=>$run));
        $listed = array_values(array_filter($list['body']['items'] ?? array(),function ($item) use ($main_id) { return (int)$item['id'] === $main_id; }));
        $record('UNIT-LIST','Available offers include the saved item label',$list['status'] === 200 && count($listed) === 1 && ($listed[0]['unit_label'] ?? '') === 'manzanas' && (float)$listed[0]['quantity'] === 3.0,array('matching_items'=>$listed));
        $retry = $call($users['donor'],'POST','donations',$main['payload']);
        $event_count = (int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . ra_table('events') . ' WHERE donation_id=%d AND event_name=%s',$main_id,'available'));
        $record('UNIT-RETRY','Repeating a labeled creation returns its original ID and single creation event',$has_label($retry,'manzanas') && (int)$retry['body']['id'] === $main_id && $event_count === 1,$info($retry));
        $changed = $call($users['donor'],'POST','donations',array_merge($main['payload'],array('unit_label'=>'naranjas')));
        $record('UNIT-RETRY-CONFLICT','Reusing the same key with another item label conflicts without changing the donation',$changed['status'] === 409 && ($changed['body']['code'] ?? '') === 'ra_key_conflict' && ra_get_row($main_id)->unit_label === 'manzanas',$info($changed));
        $response = $call($users['volunteer'],'POST','donations/' . $main_id . '/reserve',array('request_key'=>$key('reserve')));
        $record('UNIT-RESERVE','Reservation preserves the count and item label',$has_label($response,'manzanas') && ($response['body']['status'] ?? '') === 'reserved',$info($response));
        $delivery = array('recipient_name'=>'Organización ficticia de prueba','responsible_name'=>'Responsable ficticio','picked_up_at'=>gmdate('Y-m-d\TH:i:s\Z',time()-120),'delivered_at'=>gmdate('Y-m-d\TH:i:s\Z',time()-60),'notes'=>'Entrega simulada, no real.','request_key'=>$key('deliver'));
        $response = $call($users['coordinator'],'POST','donations/' . $main_id . '/deliver',$delivery);
        $record('UNIT-DELIVER','Confirmed delivery preserves the original count and item label',$has_label($response,'manzanas') && ($response['body']['status'] ?? '') === 'delivered',$info($response));
        $plain = $create('one_unit',array('quantity'=>1)); $response = $plain['response'];
        $record('UNIT-OPTIONAL','One unit without a label remains accepted and omits an empty label from JSON',$response['status'] === 200 && (float)$response['body']['quantity'] === 1.0 && !array_key_exists('unit_label',$response['body']),$info($response));
        $before_invalid = (int)$wpdb->get_var('SELECT COUNT(*) FROM ' . ra_table('donations'));
        foreach (array('fraction'=>0.5,'zero'=>0,'negative'=>-1) as $label=>$quantity) {
            $attempt = $create('invalid_count_' . $label,array('quantity'=>$quantity)); $response = $attempt['response'];
            $message = $response['body']['data']['fields']['quantity'] ?? '';
            $record('UNIT-COUNT-' . $label,'Countable units reject nonpositive or fractional counts with a Spanish quantity error',$response['status'] === 400 && ($response['body']['code'] ?? '') === 'ra_validation' && is_string($message) && $message !== '' && !preg_match('/\b(?:invalid|required|must|number|quantity)\b/i',$message),$info($response));
        }
        foreach (array('long'=>str_repeat('a',61),'array'=>array('manzanas'),'boolean'=>true) as $label=>$value) {
            $attempt = $create('invalid_label_' . $label,array('unit_label'=>$value)); $response = $attempt['response'];
            $record('UNIT-LABEL-' . $label,'Invalid item-label input is rejected with its field instead of silently altered',$response['status'] === 400 && !empty($response['body']['data']['fields']['unit_label']),$info($response));
        }
        $attempt = $create('label_on_weight',array('unit'=>'kg','unit_label'=>'manzanas')); $response = $attempt['response'];
        $record('UNIT-LABEL-SCOPE','A custom item label is rejected when the selected unit is kilograms',$response['status'] === 400 && !empty($response['body']['data']['fields']['unit_label']),$info($response));
        $after_invalid = (int)$wpdb->get_var('SELECT COUNT(*) FROM ' . ra_table('donations'));
        $record('UNIT-NO-INVALID-WRITE','Rejected counts and labels create no donation records',$before_invalid === $after_invalid,array('before'=>$before_invalid,'after'=>$after_invalid));
        foreach (array('kg'=>0.125,'liters'=>1.5,'portions'=>2.5) as $unit=>$quantity) {
            $attempt = $create('fraction_' . $unit,array('unit'=>$unit,'quantity'=>$quantity)); $response = $attempt['response'];
            $record('UNIT-FRACTION-' . $unit,'Fractional quantities remain accepted for non-countable units',$response['status'] === 200 && ($response['body']['unit'] ?? '') === $unit && abs((float)($response['body']['quantity'] ?? 0)-$quantity) < 0.000001 && !array_key_exists('unit_label',$response['body']),$info($response));
        }
    } catch (Throwable $error) {
        $record('HARNESS-ERROR','Suite completes without an unexpected exception',false,array('type'=>get_class($error),'message'=>$error->getMessage()));
    } finally {
        foreach (array_unique($donations) as $id) {
            $wpdb->delete(ra_table('events'),array('donation_id'=>(int)$id),array('%d'));
            $wpdb->delete(ra_table('requests'),array('donation_id'=>(int)$id),array('%d'));
            $wpdb->delete(ra_table('donations'),array('id'=>(int)$id),array('%d'));
        }
        foreach ($users as $id) { $wpdb->delete(ra_table('requests'),array('actor_id'=>(int)$id),array('%d')); }
        require_once ABSPATH . 'wp-admin/includes/user.php';
        foreach ($users as $id) { wp_delete_user($id); }
        wp_set_current_user($original_user);
    }
    $passed = count(array_filter($cases,function ($case) { return $case['passed']; }));
    echo wp_json_encode(array('suite'=>'Countable units and optional item labels','executed_at_utc'=>gmdate('c'),'duration_seconds'=>round(microtime(true)-$started,3),'wordpress_version'=>get_bloginfo('version'),'plugin_version'=>defined('RA_VERSION') ? RA_VERSION : null,'passed'=>$passed,'failed'=>count($cases)-$passed,'cases'=>$cases,'fixtures_removed'=>true,'limitations'=>array('Uses isolated WordPress persistence; production migration and physical-device interface checks require separate verification.')),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . "\n";
})();
