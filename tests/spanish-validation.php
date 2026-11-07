<?php
/**
 * Isolated Spanish error/field regression suite. Never a public endpoint.
 * Activate the plugin in a disposable WordPress instance, define
 * RA_SPANISH_TEST=true, then require this file. All fixtures are removed.
 * No mail is sent and all fixture addresses use example.invalid.
 */
if (!defined('ABSPATH') || !defined('RA_SPANISH_TEST') || RA_SPANISH_TEST !== true) {
    throw new RuntimeException('Run only inside isolated WordPress with RA_SPANISH_TEST=true.');
}

(function () {
    require_once ABSPATH . 'wp-admin/includes/template.php';
    global $wpdb, $wp_settings_errors;
    $original_user = get_current_user_id();
    $missing_option = '__ra_spanish_missing_' . wp_rand();
    $original_settings = get_option('ra_settings', $missing_option);
    $original_errors = $wp_settings_errors ?? null;
    $run = 'es_' . substr(hash('sha256', uniqid('', true) . wp_rand()), 0, 12);
    $cases = array();
    $users = array();
    $donations = array();
    $attachment = 0;
    $operation = 0;
    $started = microtime(true);
    $english_locale = function () { return 'en_US'; };
    $record = function ($id, $description, $pass, $observed=array()) use (&$cases) {
        $cases[] = array('id'=>$id, 'description'=>$description, 'passed'=>(bool)$pass, 'observed'=>$observed);
    };
    // Match human prose only, never stable API codes, JSON keys or endpoint names.
    $spanish = function ($message) {
        return is_string($message) && trim(wp_strip_all_tags($message)) !== ''
            && !preg_match('/\b(?:please|invalid|incorrect|username|password|cookies?|nonce|failed|failure|required|database|connection|unknown|permission|parameter|must|should|cannot)\b/i', wp_strip_all_tags($message));
    };
    $info = function ($response) {
        return array('status'=>$response['status'], 'code'=>$response['body']['code'] ?? null, 'message'=>$response['body']['message'] ?? null, 'fields'=>$response['body']['data']['fields'] ?? array());
    };
    $key = function ($label) use ($run, &$operation) { return $run . '_' . ++$operation . '_' . $label; };
    $call = function ($actor, $method, $path, $data=array()) {
        wp_set_current_user((int)$actor);
        $request = new WP_REST_Request($method, '/rescate/v1/' . ltrim($path, '/'));
        $request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));
        $request->set_header('Content-Type', 'application/json');
        $request->set_body(wp_json_encode($data));
        $response = rest_do_request($request);
        // rest_do_request does not execute the HTTP server's post-dispatch hook.
        $response = apply_filters('rest_post_dispatch', $response, rest_get_server(), $request);
        return array('status'=>$response->get_status(), 'body'=>$response->get_data());
    };

    add_filter('locale', $english_locale, PHP_INT_MAX);
    add_filter('determine_locale', $english_locale, PHP_INT_MAX);
    try {
        if (!function_exists('ra_localize_rest_errors') || !function_exists('ra_localize_login_errors') || !function_exists('ra_sanitize_settings')) {
            throw new RuntimeException('The active plugin must include Spanish REST, login and settings validation.');
        }
        rest_get_server();
        $record('ES-LOCALE', 'Spanish application messages are tested with WordPress locale en_US', get_locale() === 'en_US', array('locale'=>get_locale()));
        $request = new WP_REST_Request('POST', '/rescate/v1/donations');
        $server = rest_get_server();
        $synthetic = function ($body, $status, $route=null) use ($request, $server) {
            $r = $route === null ? $request : new WP_REST_Request('POST', $route);
            return ra_localize_rest_errors(new WP_REST_Response($body, $status), $server, $r);
        };
        $nonce = $synthetic(array('code'=>'rest_cookie_invalid_nonce','message'=>'Cookie check failed','data'=>array('status'=>403)), 403);
        $body = $nonce->get_data();
        $record('ES-REST-NONCE', 'Expired-session core error is Spanish without changing API code or HTTP status', $nonce->get_status() === 403 && ($body['code'] ?? '') === 'rest_cookie_invalid_nonce' && ($body['data']['status'] ?? 0) === 403 && $spanish($body['message'] ?? ''), $body);

        $foreign_body = array('code'=>'rest_invalid_param','message'=>'Invalid parameter(s): title','data'=>array('status'=>400,'params'=>array('title'=>'title is required.')));
        $foreign = $synthetic($foreign_body, 400, '/wp/v2/posts');
        $record('ES-REST-SCOPE', 'Localization leaves unrelated WordPress REST namespaces untouched', $foreign->get_status() === 400 && $foreign->get_data() === $foreign_body, $foreign->get_data());

        $invalid_params = $synthetic(array(
            'code'=>'rest_invalid_param',
            'message'=>'Invalid parameter(s): quantity, title',
            'data'=>array(
                'status'=>400,
                'params'=>array('quantity'=>'quantity is not of type number.','title'=>'title is required.'),
                'details'=>array(
                    'quantity'=>array('code'=>'rest_invalid_type','message'=>'quantity is not of type number.','data'=>array('param'=>'quantity')),
                    'title'=>array('code'=>'rest_missing_callback_param','message'=>'title is required.','data'=>array('param'=>'title')),
                ),
            ),
        ), 400);
        $body = $invalid_params->get_data();
        $fields = $body['data']['fields'] ?? array();
        $param_prose = array_merge(array_values($body['data']['params'] ?? array()), array_column($body['data']['details'] ?? array(), 'message'));
        $all_param_spanish = count($param_prose) === 4;
        foreach ($param_prose as $message) { $all_param_spanish = $all_param_spanish && $spanish($message); }
        $record('ES-REST-FIELDS', 'Core parameter errors expose Spanish quantity/title fields and translate nested prose', $invalid_params->get_status() === 400 && ($body['code'] ?? '') === 'rest_invalid_param' && $spanish($body['message'] ?? '') && isset($fields['quantity'], $fields['title']) && $spanish($fields['quantity']) && $spanish($fields['title']) && $all_param_spanish, $body);

        $invalid_json = $synthetic(array('code'=>'rest_invalid_json','message'=>'Invalid JSON body passed.','data'=>array('status'=>400,'json_error_code'=>4,'json_error_message'=>'Syntax error')), 400);
        $body = $invalid_json->get_data();
        $record('ES-REST-JSON', 'Malformed JSON returns an actionable Spanish error at HTTP 400', $invalid_json->get_status() === 400 && ($body['code'] ?? '') === 'rest_invalid_json' && $spanish($body['message'] ?? '') && !preg_match('/syntax error/i', wp_json_encode($body)), $body);

        $unknown = $synthetic(array('code'=>'unknown_plugin_failure','message'=>'Database connection failed: internal-example.invalid','data'=>array('status'=>500)), 500);
        $body = $unknown->get_data();
        $record('ES-REST-FALLBACK', 'Unexpected server errors use Spanish recovery text without leaking original internal prose', $unknown->get_status() === 500 && ($body['code'] ?? '') === 'unknown_plugin_failure' && $spanish($body['message'] ?? '') && strpos(wp_json_encode($body),'internal-example.invalid') === false, $body);

        $existing_body = array('code'=>'ra_validation','message'=>'Revisa los campos indicados.','data'=>array('status'=>400,'fields'=>array('quantity'=>'La cantidad debe ser mayor que cero.','title'=>'Escribe el alimento que vas a donar.')));
        $existing = $synthetic($existing_body, 400);
        $record('ES-REST-EXISTING', 'Existing project validation fields remain intact', $existing->get_status() === 400 && $existing->get_data() === $existing_body, $existing->get_data());

        $login_messages = array();
        foreach (array('empty_username'=>'The username field is empty.','empty_password'=>'The password field is empty.','invalid_username'=>'Unknown username. Check again or try your email address.','incorrect_password'=>'The password you entered for the username test is incorrect.') as $code=>$message) {
            $error = new WP_Error($code, $message, array('fixture'=>$run));
            $localized = ra_localize_login_errors($error);
            $login_messages[$code] = $localized->get_error_message($code);
            $record('ES-LOGIN-' . $code, 'Login error is Spanish and preserves its code and data', $localized instanceof WP_Error && $localized->get_error_codes() === array($code) && $localized->get_error_data($code) === array('fixture'=>$run) && $spanish($login_messages[$code]), array('code'=>$code,'message'=>$login_messages[$code]));
        }
        $record('ES-LOGIN-PRIVACY', 'Unknown account and incorrect password use the same public message', $login_messages['invalid_username'] === $login_messages['incorrect_password']);

        foreach (array('donor'=>'ra_donor','volunteer'=>'ra_volunteer','coordinator'=>'ra_coordinator') as $name=>$role) {
            $id = wp_insert_user(array('user_login'=>$run . '_' . $name,'user_pass'=>wp_generate_password(30,true,true),'user_email'=>$run . '_' . $name . '@example.invalid','display_name'=>'Prueba ficticia en español ' . $name,'role'=>$role));
            if (is_wp_error($id)) { throw new RuntimeException($id->get_error_message()); }
            $users[$name] = (int)$id;
        }
        $attachment = wp_insert_attachment(array('post_title'=>'Logotipo ficticio de prueba ' . $run,'post_mime_type'=>'image/png','post_status'=>'inherit','guid'=>'https://example.invalid/' . $run . '.png'));
        if (is_wp_error($attachment) || !$attachment) { throw new RuntimeException('Could not create isolated image attachment fixture.'); }
        $before_settings = array_merge(ra_defaults(), array('brand_name'=>'Marca conservada','support_email'=>'previo@example.invalid','accent_color'=>'#246135','logo_id'=>(int)$attachment));
        update_option('ra_settings', $before_settings);
        $wp_settings_errors = array();
        $invalid_settings = array_merge($before_settings, array('brand_name'=>'   ','support_email'=>'correo inválido','accent_color'=>'#gggggg','logo_id'=>2147483647,'tagline'=>'Lema válido actualizado'));
        $sanitized = ra_sanitize_settings($invalid_settings);
        $setting_errors = get_settings_errors('ra_settings');
        foreach (array('brand_name','support_email','accent_color','logo_id') as $field) {
            $matches = array_filter($setting_errors, function ($error) use ($field, $spanish) {
                return ($error['type'] ?? '') === 'error' && strpos((string)($error['code'] ?? ''), $field) !== false && $spanish($error['message'] ?? '');
            });
            $record('ES-ADMIN-' . $field, 'Invalid personalization field keeps the saved value and names the field in a Spanish settings error', ($sanitized[$field] ?? null) === $before_settings[$field] && count($matches) > 0, array('field'=>$field,'value'=>$sanitized[$field] ?? null,'errors'=>array_values($matches)));
        }
        $record('ES-ADMIN-SELECTIVE', 'Other valid personalization fields are still accepted', ($sanitized['tagline'] ?? '') === 'Lema válido actualizado');
        $valid_settings = array_merge($before_settings,array('brand_name'=>'Marca nueva válida','support_email'=>'nuevo@example.invalid','accent_color'=>'#123abc','logo_id'=>0));
        $sanitized = ra_sanitize_settings($valid_settings);
        $record('ES-ADMIN-VALID', 'Valid email, brand, color and explicit logo removal are accepted', $sanitized['brand_name'] === $valid_settings['brand_name'] && $sanitized['support_email'] === $valid_settings['support_email'] && $sanitized['accent_color'] === '#123abc' && $sanitized['logo_id'] === 0);

        $base = array('title'=>'Donación ficticia ' . $run,'description'=>'Prueba aislada sin alimentos reales.','category'=>'vegetables','quantity'=>2,'unit'=>'kg','merchant_name'=>'Comercio ficticio','pickup_address'=>'Dirección ficticia, Cali','pickup_area'=>'Zona ficticia','deadline'=>gmdate('Y-m-d\TH:i:s\Z',time()+21600),'origin_channel'=>'web');
        $create = function ($changes, $label) use ($base, $call, $key, &$users, &$donations, $wpdb) {
            $response = $call($users['donor'],'POST','donations',array_merge($base,$changes,array('request_key'=>$key($label))));
            if ($response['status'] === 200 && !empty($response['body']['id'])) {
                $id = (int)$response['body']['id'];
                $donations[] = $id;
                $wpdb->update(ra_table('donations'),array('is_demo'=>1),array('id'=>$id),array('%d'),array('%d'));
            }
            return $response;
        };
        foreach (array('blank'=>'','text'=>'tres','negative'=>-1,'zero'=>0,'too_large'=>1000000.001,'precision'=>1.2345,'array'=>array(1)) as $label=>$value) {
            $response = $create(array('quantity'=>$value), 'quantity_' . $label);
            $message = $response['body']['data']['fields']['quantity'] ?? '';
            $record('ES-QUANTITY-' . $label, 'Invalid quantity is rejected without silent correction and identifies its Spanish field', $response['status'] === 400 && ($response['body']['code'] ?? '') === 'ra_validation' && $spanish($message), $info($response));
        }
        $response = $create(array('title'=>str_repeat('á',181)), 'long_title');
        $record('ES-TEXT-LENGTH', 'A title over 180 characters is rejected with a Spanish title error rather than silently truncated', $response['status'] === 400 && $spanish($response['body']['data']['fields']['title'] ?? ''), $info($response));
        $response = $create(array('quantity'=>0.001), 'valid_precision');
        $id = (int)($response['body']['id'] ?? 0);
        $record('ES-QUANTITY-VALID', 'The minimum valid three-decimal quantity remains accepted', $response['status'] === 200 && abs((float)($response['body']['quantity'] ?? 0)-0.001) < 0.0000001, $info($response));
        if (!$id) { throw new RuntimeException('Could not create delivery fixture.'); }
        $reserved = $call($users['volunteer'],'POST','donations/' . $id . '/reserve',array('request_key'=>$key('reserve')));
        if ($reserved['status'] !== 200) { throw new RuntimeException('Could not reserve delivery fixture.'); }
        $delivery = array('recipient_name'=>'Organización ficticia','responsible_name'=>'Responsable ficticio','picked_up_at'=>gmdate('Y-m-d\TH:i:s\Z',time()-120),'delivered_at'=>gmdate('Y-m-d\TH:i:s\Z',time()-60),'notes'=>'No representa una entrega real.');
        foreach (array('future_pickup'=>array('picked_up_at'=>gmdate('Y-m-d\TH:i:s\Z',time()+600),'delivered_at'=>gmdate('Y-m-d\TH:i:s\Z',time()+660),'_field'=>'picked_up_at','_word'=>'futuro'),'reversed_delivery'=>array('delivered_at'=>gmdate('Y-m-d\TH:i:s\Z',time()-300),'_field'=>'delivered_at','_word'=>'recogida')) as $label=>$changes) {
            $field = $changes['_field']; $word = $changes['_word']; unset($changes['_field'], $changes['_word']);
            $response = $call($users['coordinator'],'POST','donations/' . $id . '/deliver',array_merge($delivery,$changes,array('request_key'=>$key($label))));
            $message = $response['body']['data']['fields'][$field] ?? '';
            $record('ES-DELIVERY-' . $label, 'Invalid delivery date identifies the field and explains the date relationship in Spanish', $response['status'] === 400 && $spanish($message) && stripos($message,$word) !== false && ra_get_row($id)->status === 'reserved', $info($response));
        }
    } catch (Throwable $error) {
        $record('HARNESS-ERROR', 'Suite completes without an unexpected exception', false, array('type'=>get_class($error),'message'=>$error->getMessage()));
    } finally {
        foreach (array_unique($donations) as $id) {
            $wpdb->delete(ra_table('events'),array('donation_id'=>(int)$id),array('%d'));
            $wpdb->delete(ra_table('requests'),array('donation_id'=>(int)$id),array('%d'));
            $wpdb->delete(ra_table('donations'),array('id'=>(int)$id),array('%d'));
        }
        // Rejected writes may leave isolated idempotency request rows without a donation.
        foreach ($users as $id) { $wpdb->delete(ra_table('requests'),array('actor_id'=>(int)$id),array('%d')); }
        if ($attachment && !is_wp_error($attachment)) { wp_delete_attachment($attachment,true); }
        require_once ABSPATH . 'wp-admin/includes/user.php';
        foreach ($users as $id) { wp_delete_user($id); }
        if ($original_settings === $missing_option) { delete_option('ra_settings'); } else { update_option('ra_settings',$original_settings); }
        $wp_settings_errors = $original_errors;
        remove_filter('locale',$english_locale,PHP_INT_MAX);
        remove_filter('determine_locale',$english_locale,PHP_INT_MAX);
        wp_set_current_user($original_user);
    }
    $passed = count(array_filter($cases,function ($case) { return $case['passed']; }));
    echo wp_json_encode(array('suite'=>'Spanish validation and recovery errors','executed_at_utc'=>gmdate('c'),'duration_seconds'=>round(microtime(true)-$started,3),'wordpress_version'=>get_bloginfo('version'),'plugin_version'=>defined('RA_VERSION') ? RA_VERSION : null,'locale_under_test'=>'en_US','passed'=>$passed,'failed'=>count($cases)-$passed,'cases'=>$cases,'fixtures_removed'=>true,'limitations'=>array('Exercises real WordPress PHP APIs in isolation; native browser validation and screen-reader behavior require separate browser checks.')),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . "\n";
})();
