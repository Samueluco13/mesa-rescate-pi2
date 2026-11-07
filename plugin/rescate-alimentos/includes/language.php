<?php
/** Spanish messages for WordPress-owned errors on the app's own surfaces. */
if (!defined('ABSPATH')) { exit; }

function ra_rest_field_message($name, $required=false) {
    $messages = array(
        'title'=>'Escribe el nombre del alimento, con un máximo de 180 caracteres.',
        'description'=>'Escribe los detalles en texto, con un máximo de 2.000 caracteres.',
        'category'=>'Selecciona una categoría de alimentos de la lista.',
        'quantity'=>'Escribe una cantidad entre 0,001 y 1.000.000, con hasta tres decimales.',
        'unit'=>'Selecciona una unidad de medida de la lista.',
        'merchant_name'=>'Escribe el nombre del comercio, con un máximo de 180 caracteres.',
        'pickup_address'=>'Escribe la dirección de recogida, con un máximo de 255 caracteres.',
        'pickup_area'=>'Escribe el barrio o sector, con un máximo de 120 caracteres.',
        'deadline'=>'Selecciona una fecha y hora futuras para la recogida.',
        'pickup_latitude'=>'La latitud debe ser un número entre -90 y 90.',
        'pickup_longitude'=>'La longitud debe ser un número entre -180 y 180.',
        'origin_channel'=>'Selecciona un canal de la lista: web, llamada o SMS.',
        'recipient_name'=>'Escribe el nombre de la organización receptora.',
        'responsible_name'=>'Escribe el nombre de la persona que registra la entrega.',
        'picked_up_at'=>'Selecciona la fecha y hora en que se recogieron los alimentos.',
        'delivered_at'=>'Selecciona la fecha y hora en que se entregaron los alimentos.',
        'notes'=>'Escribe las observaciones en texto, con un máximo de 2.000 caracteres.',
        'donor_id'=>'Selecciona una cuenta de comercio válida.',
        'id'=>'Escribe el número de la donación, sin letras ni decimales.',
        'request_key'=>'Actualiza la página y vuelve a intentar la operación.',
    );
    return $messages[$name] ?? ($required ? 'Completa este campo obligatorio.' : 'El valor de este campo no es válido. Revísalo y vuelve a intentarlo.');
}

function ra_localize_rest_errors($response, $server, $request) {
    $route = $request->get_route();
    if (($route !== '/rescate/v1' && strpos($route, '/rescate/v1/') !== 0) || $response->get_status() < 400) { return $response; }
    $data = $response->get_data();
    if (!is_array($data)) { return $response; }
    $response->set_data(ra_spanish_rest_error_data($data, $response->get_status()));
    return $response;
}

function ra_spanish_rest_error_data($data, $status) {
    $code = isset($data['code']) && is_string($data['code']) ? $data['code'] : '';
    // Project errors already carry specific Spanish validation and field messages.
    if (strpos($code, 'ra_') !== 0) {
        $messages = array(
            'rest_cookie_invalid_nonce'=>'No pudimos validar tu sesión. Vuelve a iniciar sesión e intenta la operación de nuevo.',
            'rest_not_logged_in'=>'Inicia sesión para continuar con esta operación.',
            'rest_forbidden'=>'Tu cuenta no tiene permiso para esta operación. Contacta a la coordinación.',
            'rest_forbidden_context'=>'Tu cuenta no tiene permiso para consultar estos datos. Contacta a la coordinación.',
            'rest_cannot_create'=>'Tu cuenta no tiene permiso para publicar donaciones.',
            'rest_invalid_param'=>'Hay datos que no son válidos. Revisa los campos indicados.',
            'rest_missing_callback_param'=>'Faltan datos obligatorios. Completa los campos indicados.',
            'rest_invalid_json'=>'No pudimos leer los datos enviados. Revisa el formulario y vuelve a intentarlo.',
            'rest_no_route'=>'No encontramos esta operación. Actualiza la página y vuelve a intentarlo.',
        );
        $fallback = $status === 401 ? 'Inicia sesión para continuar.' : ($status === 403 ? 'No pudimos autorizar esta operación. Vuelve a iniciar sesión o contacta a la coordinación.' : ($status === 429 ? 'Se realizaron demasiados intentos. Espera unos minutos y vuelve a intentarlo.' : ($status >= 500 ? 'El servidor no pudo confirmar la operación. Reintenta con la misma solicitud.' : 'No pudimos completar la operación. Revisa los datos y vuelve a intentarlo.')));
        $data['message'] = $messages[$code] ?? $fallback;
        $info = isset($data['data']) && is_array($data['data']) ? $data['data'] : array('status'=>$status);
        $fields = array();
        foreach (array('params','details','fields') as $key) {
            if (empty($info[$key]) || !is_array($info[$key])) { continue; }
            foreach ($info[$key] as $name=>$unused) {
                if (is_string($name)) { $fields[$name] = ra_rest_field_message($name, $code === 'rest_missing_callback_param'); }
                elseif (is_string($unused)) { $fields[$unused] = ra_rest_field_message($unused, true); }
            }
        }
        if ($fields) {
            $info['fields'] = $fields;
            if (isset($info['params'])) { $info['params'] = $fields; }
            if (isset($info['details']) && is_array($info['details'])) {
                foreach ($info['details'] as $name=>&$detail) {
                    if (is_array($detail)) { $detail['message'] = $fields[$name] ?? ra_rest_field_message($name); }
                }
                unset($detail);
            }
        }
        if ($code === 'rest_invalid_json') { unset($info['json_error_message']); }
        $data['data'] = $info;
    }
    if (!empty($data['additional_errors']) && is_array($data['additional_errors'])) {
        foreach ($data['additional_errors'] as &$error) {
            if (is_array($error)) { $error = ra_spanish_rest_error_data($error, $status); }
        }
        unset($error);
    }
    return $data;
}

function ra_localize_login_errors($errors) {
    if (!is_wp_error($errors)) { return $errors; }
    $credentials = 'No pudimos iniciar sesión con esos datos. Revisa el usuario o correo y la contraseña.';
    $messages = array(
        'empty_username'=>'Escribe tu nombre de usuario o correo electrónico.',
        'empty_password'=>'Escribe tu contraseña.',
        'invalid_username'=>$credentials,
        'invalid_email'=>$credentials,
        'incorrect_password'=>$credentials,
        'invalidcombo'=>'Revisa el nombre de usuario o correo y vuelve a solicitar el enlace de recuperación.',
        'expiredkey'=>'El enlace de recuperación caducó. Solicita uno nuevo.',
        'invalidkey'=>'El enlace de recuperación no es válido. Solicita uno nuevo.',
        'password_reset_mismatch'=>'Las contraseñas no coinciden. Escríbelas de nuevo.',
        'empty_password_reset'=>'Escribe una contraseña nueva antes de continuar.',
        'expired_session'=>'Tu sesión caducó. Vuelve a iniciar sesión para continuar.',
        'loggedout'=>'Cerraste la sesión correctamente.',
        'confirm'=>'Revisa tu correo electrónico para continuar con la recuperación de tu cuenta.',
        'test_cookie'=>'El navegador bloqueó la cookie de sesión. Permite las cookies de este sitio y vuelve a intentarlo.',
        'test_cookie_fail'=>'No pudimos guardar la cookie de sesión. Permite las cookies de este sitio y vuelve a intentarlo.',
    );
    foreach ($messages as $code=>$message) {
        if (!$errors->get_error_messages($code)) { continue; }
        $data = $errors->get_error_data($code);
        $errors->remove($code);
        $errors->add($code, $message, $data);
    }
    return $errors;
}

/** Core login labels remain Spanish even when the browser requests English. */
function ra_login_text($translation, $text, $domain) {
    if ($domain !== 'default' || ($GLOBALS['pagenow'] ?? '') !== 'wp-login.php') { return $translation; }
    $labels = array(
        'Log In'=>'Acceder', 'Log in'=>'Acceder', 'Log Out'=>'Salir',
        'Username or Email Address'=>'Nombre de usuario o correo electrónico',
        'Username'=>'Nombre de usuario', 'Email Address'=>'Correo electrónico',
        'Password'=>'Contraseña', 'Remember Me'=>'Recuérdame',
        'Show password'=>'Mostrar contraseña', 'Hide password'=>'Ocultar contraseña',
        'Lost your password?'=>'¿Olvidaste tu contraseña?', 'Lost Password'=>'Recuperar contraseña',
        'Get New Password'=>'Solicitar enlace de recuperación',
        'Reset Password'=>'Restablecer contraseña', 'New password'=>'Contraseña nueva',
        'Save Password'=>'Guardar contraseña', 'Generate Password'=>'Generar contraseña',
        'Please enter your username or email address. You will receive an email message with instructions on how to reset your password.'=>'Escribe tu usuario o correo electrónico. Recibirás un mensaje con instrucciones para restablecer la contraseña.',
        'Check your email for the confirmation link, then visit the <a href="%s">login page</a>.'=>'Revisa el enlace de confirmación en tu correo y luego visita la <a href="%s">página de acceso</a>.',
        '&larr; Go to %s'=>'&larr; Volver a %s', 'Language'=>'Idioma', 'Change'=>'Cambiar',
    );
    return $labels[$text] ?? $translation;
}

function ra_enqueue_wordpress_forms() {
    wp_enqueue_style('ra-wordpress-forms', RA_URL . 'assets/wordpress-forms.css', array(), ra_asset_version('assets/wordpress-forms.css'));
    wp_enqueue_script('ra-wordpress-forms', RA_URL . 'assets/wordpress-forms.js', array(), ra_asset_version('assets/wordpress-forms.js'), true);
}
