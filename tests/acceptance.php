<?php
/**
 * Isolated acceptance harness, never a public endpoint or production plugin file.
 * Load WordPress with the plugin active, define RA_ACCEPTANCE_TEST=true, then
 * require this file. Fixtures are fictitious, explicitly marked is_demo=1.
 * Set RA_ACCEPTANCE_CLEANUP=true to remove only this run's fixtures afterward.
 */
if (!defined('ABSPATH') || !defined('RA_ACCEPTANCE_TEST') || RA_ACCEPTANCE_TEST !== true) {
    throw new RuntimeException('Run only inside an isolated WordPress test environment with RA_ACCEPTANCE_TEST=true.');
}

(function () {
    global $wpdb;
    $original_user = get_current_user_id();
    $run = 'qa_' . substr(hash('sha256', uniqid('', true) . wp_rand()), 0, 14);
    $cases = array();
    $users = array();
    $donations = array();
    $operation = 0;
    $start = microtime(true);
    $fixture = array('run'=>$run, 'users'=>array(), 'donations'=>array(), 'fictitious'=>true);
    $record = function ($id, $description, $pass, $observed=array()) use (&$cases) {
        $cases[] = array('id'=>$id, 'description'=>$description, 'passed'=>(bool)$pass, 'observed'=>$observed);
    };
    $key = function ($label) use ($run, &$operation) {
        $operation++;
        return $run . '_' . $operation . '_' . preg_replace('/[^a-z0-9_-]/i', '_', $label);
    };
    $call = function ($actor, $method, $path, $data=array()) {
        wp_set_current_user((int)$actor);
        $request = new WP_REST_Request($method, '/rescate/v1/' . ltrim($path, '/'));
        $request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));
        if ($method === 'GET') {
            $request->set_query_params($data);
        } else {
            $request->set_header('Content-Type', 'application/json');
            $request->set_body(wp_json_encode($data));
        }
        $response = rest_do_request($request);
        return array('status'=>$response->get_status(), 'body'=>$response->get_data());
    };
    $response_info = function ($response) {
        return array('status'=>$response['status'], 'code'=>$response['body']['code'] ?? null, 'id'=>$response['body']['id'] ?? null, 'fields'=>$response['body']['data']['fields'] ?? array());
    };
    $count = function ($kind, $id) use ($wpdb) {
        if ($kind === 'donation') {
            return (int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . ra_table('donations') . ' WHERE id=%d', $id));
        }
        return (int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . ra_table('events') . ' WHERE donation_id=%d AND event_name=%s', $id, $kind));
    };

    try {
        if (!function_exists('ra_table') || !function_exists('ra_register_routes')) {
            throw new RuntimeException('Rescate de Alimentos must be active before running acceptance tests.');
        }
        foreach (array('ra_donor','ra_volunteer','ra_coordinator') as $role) {
            if (!get_role($role)) { throw new RuntimeException('Missing activated role: ' . $role); }
        }
        // rest_get_server initializes and registers the plugin routes once.
        rest_get_server();
        foreach (array('donor'=>'ra_donor','outsider'=>'ra_donor','volunteer_a'=>'ra_volunteer','volunteer_b'=>'ra_volunteer','coordinator'=>'ra_coordinator','subscriber'=>'subscriber') as $name=>$role) {
            $id = wp_insert_user(array(
                'user_login'=>$run . '_' . $name,
                'user_pass'=>wp_generate_password(30, true, true),
                'user_email'=>$run . '_' . $name . '@example.invalid',
                'display_name'=>'Prueba ficticia ' . $name,
                'role'=>$role,
            ));
            if (is_wp_error($id)) { throw new RuntimeException($id->get_error_message()); }
            $users[$name] = (int)$id;
        }
        $fixture['users'] = $users;
        $record('SETUP', 'Six separate fictitious WordPress accounts created; no mail sent', count($users) === 6, array('roles'=>array_keys($users)));

        $base = array(
            'title'=>$run . ' Canasta ficticia de verduras',
            'description'=>'Registro ficticio de aceptación; no representa alimentos ni una entrega real.',
            'category'=>'vegetables','quantity'=>12.5,'unit'=>'kg',
            'merchant_name'=>'Comercio ficticio ' . $run,
            'pickup_address'=>'Punto ficticio de prueba, Cali',
            'pickup_area'=>'San Fernando',
            'deadline'=>gmdate('Y-m-d\TH:i:s\Z', time() + 21600),
            'origin_channel'=>'web',
        );
        $create = function ($label, $overrides=array(), $actor=null) use (&$users,&$donations,$key,$base,$call,$wpdb) {
            $payload = array_merge($base, array('title'=>$base['title'] . ' ' . $label,'request_key'=>$key('create_' . $label)), $overrides);
            $response = $call($actor === null ? $users['donor'] : $actor, 'POST', 'donations', $payload);
            if ($response['status'] === 200 && !empty($response['body']['id'])) {
                $id = (int)$response['body']['id'];
                $donations[$label] = $id;
                $wpdb->update(ra_table('donations'), array('is_demo'=>1), array('id'=>$id), array('%d'), array('%d'));
            }
            return array('response'=>$response, 'payload'=>$payload);
        };

        $response = $call(0, 'GET', 'donations');
        $record('AUTH-01', 'Anonymous participants cannot read donation data', $response['status'] === 401, $response_info($response));
        $response = $call($users['subscriber'], 'GET', 'donations');
        $record('AUTH-02', 'Ordinary subscriber without a project role is denied', $response['status'] === 403, $response_info($response));
        $response = $call($users['volunteer_a'], 'POST', 'donations', $base + array('request_key'=>$key('volunteer_create')));
        $record('CP-03a', 'Volunteer cannot register donations', $response['status'] === 403, $response_info($response));

        $main = $create('main');
        $response = $main['response'];
        $main_id = (int)($response['body']['id'] ?? 0);
        $record('CP-01a', 'Valid donation persists with ID, available state and complete data', $response['status'] === 200 && $main_id > 0 && $response['body']['status'] === 'available' && (float)$response['body']['quantity'] === 12.5 && $response['body']['unit'] === 'kg' && $response['body']['donor_id'] === $users['donor'], $response_info($response));
        if (!$main_id) { throw new RuntimeException('Could not create base donation; dependent tests cannot run.'); }
        $response = $call($users['coordinator'], 'GET', 'donations/' . $main_id);
        $record('CP-01b', 'Donation recovered from separate authorized session with same origin and amount', $response['status'] === 200 && (int)$response['body']['id'] === $main_id && $response['body']['title'] === $main['payload']['title'] && (float)$response['body']['quantity'] === 12.5 && $response['body']['author_id'] === $users['donor'], $response_info($response));
        $response = $call($users['donor'], 'POST', 'donations', $main['payload']);
        $record('CP-01c', 'Create retry preserves original ID and a single creation event', $response['status'] === 200 && (int)$response['body']['id'] === $main_id && $count('donation',$main_id) === 1 && $count('available',$main_id) === 1, $response_info($response));
        $response = $call($users['donor'], 'POST', 'donations', array_merge($main['payload'], array('title'=>'Changed payload using the same key')));
        $record('IDEM-01', 'Create key reused with different data returns a conflict', $response['status'] === 409 && ($response['body']['code'] ?? '') === 'ra_key_conflict', $response_info($response));

        $invalid = array(
            'missing_title'=>array('title'=>'', '_field'=>'title'),
            'missing_merchant'=>array('merchant_name'=>'', '_field'=>'merchant_name'),
            'missing_pickup'=>array('pickup_address'=>'', '_field'=>'pickup_address'),
            'zero_quantity'=>array('quantity'=>0, '_field'=>'quantity'),
            'negative_quantity'=>array('quantity'=>-1, '_field'=>'quantity'),
            'array_quantity'=>array('quantity'=>array(1), '_field'=>'quantity'),
            'invalid_category'=>array('category'=>'unknown', '_field'=>'category'),
            'invalid_unit'=>array('unit'=>'invalid', '_field'=>'unit'),
            'past_deadline'=>array('deadline'=>gmdate('Y-m-d\TH:i:s\Z',time()-3600), '_field'=>'deadline'),
            'timezone_missing'=>array('deadline'=>gmdate('Y-m-d\TH:i:s',time()+3600), '_field'=>'deadline'),
            'invalid_date'=>array('deadline'=>'2026-02-30T12:00:00Z', '_field'=>'deadline'),
        );
        $before_invalid = (int)$wpdb->get_var('SELECT COUNT(*) FROM ' . ra_table('donations'));
        foreach ($invalid as $label=>$changes) {
            $field = $changes['_field']; unset($changes['_field']);
            $attempt = $create($label,$changes);
            $response = $attempt['response'];
            $record('CP-02-' . $label, 'Invalid registration rejected with a specific field error', $response['status'] === 400 && isset($response['body']['data']['fields'][$field]), $response_info($response));
        }
        $after_invalid = (int)$wpdb->get_var('SELECT COUNT(*) FROM ' . ra_table('donations'));
        $record('CP-02-no-write', 'Invalid submissions create no donation records', $after_invalid === $before_invalid, array('before'=>$before_invalid,'after'=>$after_invalid));
        $response = $call($users['donor'], 'POST', 'donations', array_merge($base,array('origin_channel'=>'phone','assisted'=>true,'request_key'=>$key('donor_assisted'))));
        $record('CP-03b', 'Donor cannot impersonate assisted coordinator registration', $response['status'] === 403, $response_info($response));
        $assisted = $create('assisted',array('origin_channel'=>'sms','assisted'=>true,'donor_id'=>$users['donor']),$users['coordinator']);
        $response = $assisted['response'];
        $assisted_id = (int)($response['body']['id'] ?? 0);
        $record('CP-03c', 'Coordinator assisted registration records merchant, author, channel and assisted flag separately', $response['status'] === 200 && $response['body']['donor_id'] === $users['donor'] && $response['body']['author_id'] === $users['coordinator'] && $response['body']['origin_channel'] === 'sms' && $response['body']['assisted'] === true, $response_info($response));
        $response = $call($users['donor'],'GET','donations/' . $assisted_id);
        $record('CP-03d', 'Linked donor can view assisted donation', $response['status'] === 200 && (int)$response['body']['id'] === $assisted_id, $response_info($response));
        $forged = $create('forged_owner',array('donor_id'=>$users['outsider']));
        $record('SEC-01', 'Donor-supplied owner ID cannot change ownership', $forged['response']['status'] === 200 && $forged['response']['body']['donor_id'] === $users['donor'], $response_info($forged['response']));
        $response = $call($users['outsider'],'GET','donations/' . $main_id);
        $private_response = $response;
        $missing_response = $call($users['outsider'],'GET','donations/2147483647');
        $record('CP-11a', 'Foreign and missing IDs return identical privacy-safe status, code and message', $private_response['status'] === 404 && $missing_response['status'] === 404 && $private_response['body'] === $missing_response['body'] && ($response['body']['message'] ?? '') === 'No es posible consultar esta donación', array('foreign'=>$response_info($private_response),'missing'=>$response_info($missing_response)));
        $response = $call($users['volunteer_a'],'GET','donations/' . $main_id);
        $record('SEC-02', 'Available offer does not disclose donor IDs, author or private volunteer details to unrelated volunteer', $response['status'] === 200 && !isset($response['body']['donor_id']) && !isset($response['body']['author_id']) && !isset($response['body']['volunteer_name']), $response_info($response));

        $available = $create('available');
        $expired = $create('expired');
        $expired_id = (int)($expired['response']['body']['id'] ?? 0);
        $wpdb->update(ra_table('donations'),array('deadline'=>gmdate('Y-m-d H:i:s',time()-60)),array('id'=>$expired_id),array('%s'),array('%d'));
        $reserved_fixture = $create('reserved');
        $reserved_fixture_id = (int)($reserved_fixture['response']['body']['id'] ?? 0);
        $call($users['volunteer_b'],'POST','donations/' . $reserved_fixture_id . '/reserve',array('request_key'=>$key('pre_reserved')));
        $deliverable = $create('deliverable');
        $deliverable_id = (int)($deliverable['response']['body']['id'] ?? 0);
        $reserve_key = $key('reserve_main');
        $first_reserve = $call($users['volunteer_a'],'POST','donations/' . $main_id . '/reserve',array('request_key'=>$reserve_key));
        $record('CP-06a', 'First volunteer receives confirmed reservation and holder', $first_reserve['status'] === 200 && ($first_reserve['body']['status'] ?? '') === 'reserved' && ($first_reserve['body']['volunteer_id'] ?? 0) === $users['volunteer_a'], $response_info($first_reserve));
        $second_reserve = $call($users['volunteer_b'],'POST','donations/' . $main_id . '/reserve',array('request_key'=>$key('second_reserve')));
        $record('CP-06b', 'Second volunteer receives 409 and no winner private data', $second_reserve['status'] === 409 && ($second_reserve['body']['message'] ?? '') === 'Esta donación ya fue reservada' && !isset($second_reserve['body']['volunteer_id']) && !isset($second_reserve['body']['volunteer_name']), $response_info($second_reserve));
        $retry = $call($users['volunteer_a'],'POST','donations/' . $main_id . '/reserve',array('request_key'=>$reserve_key));
        $record('CP-07', 'Reservation retry returns original result, holder and single reservation event', $retry['status'] === 200 && $retry['body'] === $first_reserve['body'] && $count('reserved',$main_id) === 1, $response_info($retry));
        $changed_key = $call($users['volunteer_a'],'POST','donations/' . $deliverable_id . '/reserve',array('request_key'=>$reserve_key));
        $record('IDEM-02', 'Reservation key reused for a different donation conflicts', $changed_key['status'] === 409 && ($changed_key['body']['code'] ?? '') === 'ra_key_conflict', $response_info($changed_key));
        $row = ra_get_row($main_id);
        $requests_count = (int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . ra_table('requests') . " WHERE donation_id=%d AND action_name='reserve'",$main_id));
        $record('CP-06-persisted', 'Persistence has one holder, one successful reserve request and one event', $row->status === 'reserved' && (int)$row->volunteer_id === $users['volunteer_a'] && $requests_count === 1 && $count('reserved',$main_id) === 1, array('holder'=>(int)$row->volunteer_id,'successful_requests'=>$requests_count,'events'=>$count('reserved',$main_id)));
        $response = $call($users['volunteer_b'],'GET','donations/' . $main_id);
        $record('CP-11b', 'Losing volunteer cannot inspect another volunteer reservation', $response['status'] === 404 && $response['body'] === $missing_response['body'], $response_info($response));
        $response = $call($users['volunteer_a'],'POST','donations/' . $expired_id . '/reserve',array('request_key'=>$key('expired_reserve')));
        $record('CP-04-expired', 'Server rejects an expired donation even when an old offer ID is used', $response['status'] === 409 && ($response['body']['code'] ?? '') === 'ra_expired' && ra_get_row($expired_id)->status === 'available', $response_info($response));
        $call($users['volunteer_a'],'POST','donations/' . $deliverable_id . '/reserve',array('request_key'=>$key('reserve_delivery')));

        $delivery = array('recipient_name'=>'Organización receptora ficticia ' . $run,'picked_up_at'=>gmdate('Y-m-d\TH:i:s\Z',time()-120),'delivered_at'=>gmdate('Y-m-d\TH:i:s\Z',time()-60),'responsible_name'=>'Responsable ficticio de recepción','notes'=>'Entrega simulada por aceptación automática. No real.');
        $response = $call($users['volunteer_a'],'POST','donations/' . $deliverable_id . '/deliver',$delivery + array('request_key'=>$key('volunteer_delivery')));
        $record('CP-09-unauthorized', 'Volunteer holder cannot close a delivery', $response['status'] === 403 && ra_get_row($deliverable_id)->status === 'reserved', $response_info($response));
        foreach (array(
            'missing_recipient'=>array('recipient_name'=>''),
            'missing_responsible'=>array('responsible_name'=>''),
            'missing_pickup'=>array('picked_up_at'=>''),
            'missing_delivery'=>array('delivered_at'=>''),
            'reversed_dates'=>array('picked_up_at'=>gmdate('Y-m-d\TH:i:s\Z',time()-30)),
            'future_dates'=>array('picked_up_at'=>gmdate('Y-m-d\TH:i:s\Z',time()+60),'delivered_at'=>gmdate('Y-m-d\TH:i:s\Z',time()+120)),
        ) as $label=>$changes) {
            $response = $call($users['coordinator'],'POST','donations/' . $deliverable_id . '/deliver',array_merge($delivery,$changes,array('request_key'=>$key($label))));
            $record('CP-09-' . $label, 'Invalid delivery rejected without changing reservation', $response['status'] === 400 && !empty($response['body']['data']['fields']) && ra_get_row($deliverable_id)->status === 'reserved', $response_info($response));
        }
        $unreserved_id = (int)($available['response']['body']['id'] ?? 0);
        $response = $call($users['coordinator'],'POST','donations/' . $unreserved_id . '/deliver',$delivery + array('request_key'=>$key('skip_reserve')));
        $record('STATE-01', 'Cannot skip directly from available to delivered', $response['status'] === 409 && ra_get_row($unreserved_id)->status === 'available', $response_info($response));
        $delivery_key = $key('delivery_success');
        $success_delivery = $call($users['coordinator'],'POST','donations/' . $deliverable_id . '/deliver',$delivery + array('request_key'=>$delivery_key));
        $body = $success_delivery['body'];
        $record('CP-08', 'Coordinator closes delivery, preserving origin, quantity, transporter and recording actor', $success_delivery['status'] === 200 && ($body['status'] ?? '') === 'delivered' && ($body['recipient_name'] ?? '') === $delivery['recipient_name'] && ($body['volunteer_id'] ?? 0) === $users['volunteer_a'] && ($body['closed_by'] ?? 0) === $users['coordinator'] && ($body['donor_id'] ?? 0) === $users['donor'] && (float)($body['quantity'] ?? 0) === 12.5 && ($body['unit'] ?? '') === 'kg', $response_info($success_delivery));
        $retry = $call($users['coordinator'],'POST','donations/' . $deliverable_id . '/deliver',$delivery + array('request_key'=>$delivery_key));
        $record('CP-09-retry', 'Exact delivery retry returns original result without a second delivery', $retry['status'] === 200 && $retry['body'] === $success_delivery['body'] && $count('delivered',$deliverable_id) === 1, $response_info($retry));
        $response = $call($users['coordinator'],'POST','donations/' . $deliverable_id . '/deliver',array_merge($delivery,array('notes'=>'Altered retry','request_key'=>$delivery_key)));
        $record('IDEM-03', 'Delivery key reused with changed data conflicts', $response['status'] === 409 && ($response['body']['code'] ?? '') === 'ra_key_conflict', $response_info($response));
        $response = $call($users['coordinator'],'POST','donations/' . $deliverable_id . '/deliver',array_merge($delivery,array('recipient_name'=>'Different recipient','request_key'=>$key('replace_delivery'))));
        $row = ra_get_row($deliverable_id);
        $record('CP-09-immutable', 'New submission cannot overwrite a confirmed delivery', $response['status'] === 409 && $row->recipient_name === $delivery['recipient_name'] && $count('delivered',$deliverable_id) === 1, $response_info($response));
        $delivery_requests = (int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . ra_table('requests') . " WHERE donation_id=%d AND action_name='deliver'",$deliverable_id));
        $record('CP-09-persisted', 'Persistence contains one delivered record, one close request and one close event', $count('donation',$deliverable_id) === 1 && $row->status === 'delivered' && $delivery_requests === 1 && $count('delivered',$deliverable_id) === 1, array('successful_requests'=>$delivery_requests,'events'=>$count('delivered',$deliverable_id)));

        $list = $call($users['volunteer_a'],'GET','donations',array('scope'=>'available','search'=>$run));
        $list_ids = array_map(function ($item) { return (int)$item['id']; }, $list['body']['items'] ?? array());
        $eligible_only = $list['status'] === 200 && in_array($unreserved_id,$list_ids,true);
        foreach ($list['body']['items'] ?? array() as $item) { $eligible_only = $eligible_only && $item['status'] === 'available' && strtotime($item['deadline']) > time(); }
        foreach (array($expired_id,$main_id,$reserved_fixture_id,$deliverable_id) as $excluded) { $eligible_only = $eligible_only && !in_array($excluded,$list_ids,true); }
        $record('CP-04', 'Only available and unexpired offers are listed; reserved, delivered and expired excluded', $eligible_only, array('status'=>$list['status'],'ids'=>$list_ids,'excluded'=>array($expired_id,$main_id,$reserved_fixture_id,$deliverable_id)));
        $response = $call($users['volunteer_a'],'GET','donations',array('scope'=>'available','search'=>$run . '_no_results'));
        $record('CP-05-empty', 'An empty successful list is a 200 with an empty items array', $response['status'] === 200 && isset($response['body']['items']) && $response['body']['items'] === array(), $response_info($response));
        $response = $call($users['volunteer_a'],'GET','donations',array('scope'=>'all'));
        $record('SEC-03', 'Volunteer cannot request the coordination list', $response['status'] === 403, $response_info($response));
        $response = $call($users['outsider'],'GET','donations',array('scope'=>'mine','search'=>$run));
        $record('SEC-04', 'Unrelated donor own-list excludes other merchants records', $response['status'] === 200 && $response['body']['items'] === array(), $response_info($response));
        $response = $call($users['volunteer_a'],'GET','donations',array('scope'=>'mine','search'=>$run));
        $my_ids = array_map(function ($item) { return (int)$item['id']; },$response['body']['items'] ?? array());
        $record('SEC-05', 'Volunteer own-list contains own reservations without another volunteer rescue', $response['status'] === 200 && in_array($main_id,$my_ids,true) && in_array($deliverable_id,$my_ids,true) && !in_array($reserved_fixture_id,$my_ids,true), array('status'=>$response['status'],'ids'=>$my_ids));
        foreach (array($unreserved_id=>'available',$main_id=>'reserved',$deliverable_id=>'delivered') as $id=>$state) {
            $response = $call($users['coordinator'],'GET','donations/' . $id);
            $row = ra_get_row($id);
            $history = array_column($response['body']['history'] ?? array(),'event');
            $record('CP-10-' . $state, 'Current lookup agrees with persisted status and transition history', $response['status'] === 200 && ($response['body']['status'] ?? '') === $state && $row->status === $state && end($history) === $state, array('status'=>$response['status'],'persisted'=>$row->status,'history'=>$history));
        }
        $audit_rows = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . ra_table('events') . ' WHERE donation_id=%d ORDER BY id ASC',$deliverable_id),ARRAY_A);
        $audit_ok = count($audit_rows) === 3 && array_column($audit_rows,'event_name') === array('available','reserved','delivered');
        $expected_actors = array($users['donor'],$users['volunteer_a'],$users['coordinator']);
        foreach ($audit_rows as $i=>$event) { $audit_ok = $audit_ok && (int)$event['actor_id'] === $expected_actors[$i] && !empty($event['created_at']); }
        $record('AUDIT-01', 'One timestamped transition event for creation, reservation and delivery with correct actors', $audit_ok, array('events'=>array_map(function($event){ return array('event'=>$event['event_name'],'actor'=>(int)$event['actor_id'],'created_at'=>$event['created_at']); },$audit_rows)));
        $linked_audit = !empty($audit_rows) && array_key_exists('from_status',$audit_rows[0]) && array_key_exists('to_status',$audit_rows[0]) && array_key_exists('request_id',$audit_rows[0]);
        if ($linked_audit) {
            $prior = array('', 'available','reserved');
            foreach ($audit_rows as $i=>$event) {
                $request = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . ra_table('requests') . ' WHERE id=%d',(int)$event['request_id']));
                $linked_audit = $linked_audit && ($event['from_status'] ?? '') === $prior[$i] && $event['to_status'] === $event['event_name'] && $request && (int)$request->donation_id === $deliverable_id && (int)$request->actor_id === $expected_actors[$i];
            }
        }
        $record('AUDIT-02', 'Events link previous/new state to the persisted successful operation', $linked_audit, array('columns'=>array_keys($audit_rows[0] ?? array())));
        $fixture['donations'] = $donations;
    } catch (Throwable $error) {
        $record('HARNESS-ERROR','Harness completed without an unexpected exception',false,array('type'=>get_class($error),'message'=>$error->getMessage()));
        $fixture['users'] = $users;
        $fixture['donations'] = $donations;
    } finally {
        $fixture['cleanup'] = 'retained_for_inspection';
        if (defined('RA_ACCEPTANCE_CLEANUP') && RA_ACCEPTANCE_CLEANUP === true && function_exists('ra_table')) {
            foreach (array_unique(array_values($donations)) as $id) {
                $wpdb->delete(ra_table('events'),array('donation_id'=>(int)$id),array('%d'));
                $wpdb->delete(ra_table('requests'),array('donation_id'=>(int)$id),array('%d'));
                $wpdb->delete(ra_table('donations'),array('id'=>(int)$id),array('%d'));
            }
            require_once ABSPATH . 'wp-admin/includes/user.php';
            foreach ($users as $id) { wp_delete_user($id); }
            $fixture['cleanup'] = 'removed_this_run_fixtures';
        }
        wp_set_current_user($original_user);
    }
    $passed = count(array_filter($cases,function ($case) { return $case['passed']; }));
    $report = array(
        'suite'=>'Rescate de Alimentos server acceptance',
        'executed_at_utc'=>gmdate('c'),
        'duration_seconds'=>round(microtime(true)-$start,3),
        'wordpress_version'=>get_bloginfo('version'),
        'php_version'=>PHP_VERSION,
        'plugin_version'=>defined('RA_VERSION') ? RA_VERSION : null,
        'passed'=>$passed,'failed'=>count($cases)-$passed,
        'cases'=>$cases,'fixture_ids'=>$fixture,
        'limitations'=>array('Sequential API arbitration is covered here; true simultaneous two-worker races are a separate required test.','Browser layout, offline UI, device measurements and independent human approval require separate evidence.','SQLite/Playground validation does not replace an InnoDB concurrency run on the target hosting.'),
    );
    echo wp_json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . "\n";
})();
