(function () {
  'use strict';

  const labels = {
    title: 'Nombre del alimento', category: 'Categoría', quantity: 'Cantidad disponible', unit: 'Cómo indicar la cantidad', unit_label: 'Qué estás contando',
    description: 'Detalles y conservación', merchant_name: 'Nombre del comercio', pickup_address: 'Dirección de recogida',
    pickup_area: 'Barrio o sector', deadline: 'Recoger antes de', origin_channel: 'Canal de la solicitud',
    from: 'Desde', to: 'Hasta',
    food_safe: 'Confirmación del estado de los alimentos', recipient_name: 'Organización que recibió',
    picked_up_at: 'Fecha y hora de recogida', delivered_at: 'Fecha y hora de entrega',
    responsible_name: 'Responsable que registra', notes: 'Observaciones de entrega',
    received_confirmed: 'Confirmación de recepción', donation_id: 'Identificador de la donación',
    id: 'Identificador de la donación', pickup_latitude: 'Latitud del punto de recogida',
    pickup_longitude: 'Longitud del punto de recogida', donor_id: 'Cuenta del comercio', request_key: 'Solicitud'
  };
  const requiredMessages = {
    title: 'Escribe el nombre del alimento; no puede contener solo espacios.',
    merchant_name: 'Escribe el nombre del comercio; no puede contener solo espacios.',
    pickup_address: 'Escribe la dirección donde se recogen los alimentos; no puede contener solo espacios.',
    quantity: 'Escribe la cantidad de alimentos que vas a compartir.',
    recipient_name: 'Escribe el nombre de la organización que recibió los alimentos; no puede contener solo espacios.',
    responsible_name: 'Escribe el nombre de la persona que registra la entrega; no puede contener solo espacios.',
    deadline: 'Completa la fecha y la hora límite de recogida.',
    picked_up_at: 'Completa la fecha y la hora en que se recogieron los alimentos.',
    delivered_at: 'Completa la fecha y la hora en que se entregaron los alimentos.',
    food_safe: 'Confirma que los alimentos están en buen estado y que la información de recogida es correcta.',
    received_confirmed: 'Confirma que verificaste las condiciones y la recepción con la organización receptora.',
    donation_id: 'Escribe el identificador de la donación, por ejemplo 24 o #24.'
  };
  const invalidMessages = {
    category: 'Selecciona una de las categorías de alimentos de la lista.',
    quantity: 'Escribe un número entre 0.001 y 1.000.000, con un máximo de tres decimales.',
    unit: 'Selecciona una de las unidades de medida de la lista.',
    origin_channel: 'Selecciona Web / presencial, Llamada telefónica o Mensaje SMS.',
    deadline: 'Completa una fecha y una hora válidas para el límite de recogida.',
    picked_up_at: 'Completa una fecha y una hora válidas para la recogida.',
    delivered_at: 'Completa una fecha y una hora válidas para la entrega.',
    donation_id: 'Usa un número entero mayor que cero, por ejemplo 24 o #24, sin letras ni decimales.',
    id: 'Usa un número entero mayor que cero para identificar la donación.',
    pickup_latitude: 'Marca de nuevo el punto de recogida: la latitud debe estar entre −90 y 90.',
    pickup_longitude: 'Marca de nuevo el punto de recogida: la longitud debe estar entre −180 y 180.',
    donor_id: 'Selecciona una cuenta de comercio válida.',
    request_key: 'Recarga la página y reintenta para generar una solicitud válida.'
  };
  const text = value => typeof value === 'string' || typeof value === 'number' ? String(value).trim() : '';
  const own = (object, name) => Object.prototype.hasOwnProperty.call(object, name);
  const length = value => Array.from(text(value)).length;
  const maxLengths = { title: 180, merchant_name: 180, pickup_address: 255, pickup_area: 120, description: 1800, recipient_name: 180, responsible_name: 180, notes: 1800, unit_label: 60 };

  function decimalPlaces(raw) {
    const [coefficient, exponent] = raw.toLowerCase().replace(/^[+-]/, '').split('e');
    const [whole, fraction = ''] = coefficient.split('.');
    const digits = whole + fraction;
    const trailingZeros = digits.length - digits.replace(/0+$/, '').length;
    return Math.max(0, fraction.length - trailingZeros - Number(exponent || 0));
  }

  function localDate(value) {
    const raw = text(value);
    const parts = /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})(?::(\d{2}))?$/.exec(raw);
    if (!parts) return null;
    const [year, month, day, hour, minute, second] = parts.slice(1).map(value => Number(value || 0));
    if (year < 1000 || month < 1 || month > 12 || day < 1 || hour > 23 || minute > 59 || second > 59) return null;
    const valueMs = Date.parse(raw + '-05:00');
    if (!Number.isFinite(valueMs)) return null;
    const local = new Date(valueMs - 5 * 60 * 60 * 1000);
    return local.getUTCFullYear() === year && local.getUTCMonth() + 1 === month && local.getUTCDate() === day ? valueMs : null;
  }

  function validateText(values, errors, required, optional) {
    required.forEach(name => { if (!text(values[name])) errors[name] = requiredMessages[name]; });
    required.concat(optional || []).forEach(name => {
      if (maxLengths[name] && length(values[name]) > maxLengths[name]) errors[name] = 'Usa un máximo de ' + maxLengths[name] + ' caracteres.';
    });
  }

  function validateDonation(values, now, assisted) {
    const errors = Object.create(null);
    validateText(values, errors, ['title', 'merchant_name', 'pickup_address'], ['description', 'pickup_area']);
    if (!['vegetables', 'fruit', 'bakery', 'prepared', 'dairy', 'pantry'].includes(values.category)) errors.category = invalidMessages.category;
    const raw = text(values.quantity);
    const amount = Number(raw);
    if (!raw) errors.quantity = requiredMessages.quantity;
    else if (!/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:e[+-]?\d+)?$/i.test(raw) || !Number.isFinite(amount)) errors.quantity = 'Escribe una cantidad numérica. Usa un punto para los decimales, por ejemplo 1.5.';
    else if (values.unit === 'units' && (!Number.isInteger(amount) || amount < 1)) errors.quantity = 'Para unidades o piezas, escribe un número entero mayor que cero, por ejemplo 12. Si vas a indicar peso, elige kilogramos (kg).';
    else if (amount < 0.001) errors.quantity = 'La cantidad debe ser de al menos 0.001; no puede ser cero ni negativa.';
    else if (amount > 1000000) errors.quantity = 'La cantidad no puede superar 1.000.000. Revisa el número o la unidad de medida.';
    else if (decimalPlaces(raw) > 3) errors.quantity = 'Usa un máximo de tres decimales para la cantidad, por ejemplo 1.125.';
    if (!['kg', 'portions', 'units', 'liters'].includes(values.unit)) errors.unit = invalidMessages.unit;
    if (values.unit === 'units' && length(values.unit_label) > 60) errors.unit_label = 'Usa un máximo de 60 caracteres para indicar qué estás contando.';
    const deadline = localDate(values.deadline);
    if (!text(values.deadline)) errors.deadline = requiredMessages.deadline;
    else if (deadline === null) errors.deadline = invalidMessages.deadline;
    else if (deadline <= now) errors.deadline = 'El límite de recogida ya pasó. Elige una fecha y una hora posteriores a este momento (hora de Colombia).';
    if (assisted && !['web', 'phone', 'sms'].includes(values.origin_channel)) errors.origin_channel = invalidMessages.origin_channel;
    if (values.food_safe !== true) errors.food_safe = requiredMessages.food_safe;
    return errors;
  }

  function validateDelivery(values, now) {
    const errors = Object.create(null);
    validateText(values, errors, ['recipient_name', 'responsible_name'], ['notes']);
    const pickup = localDate(values.picked_up_at);
    const delivered = localDate(values.delivered_at);
    ['picked_up_at', 'delivered_at'].forEach(name => {
      const date = name === 'picked_up_at' ? pickup : delivered;
      if (!text(values[name])) errors[name] = requiredMessages[name];
      else if (date === null) errors[name] = invalidMessages[name];
      else if (date > now) errors[name] = name === 'picked_up_at' ? 'La recogida no puede estar en el futuro. Indica cuándo ocurrió (hora de Colombia).' : 'La entrega no puede estar en el futuro. Indica cuándo ocurrió (hora de Colombia).';
    });
    if (pickup !== null && delivered !== null && pickup > delivered) {
      errors.picked_up_at = errors.picked_up_at || 'La recogida es posterior a la entrega. Corrige el horario de recogida o el de entrega.';
      errors.delivered_at = errors.delivered_at || 'La entrega es anterior a la recogida. Corrige el horario de entrega o el de recogida.';
    }
    if (values.received_confirmed !== true) errors.received_confirmed = requiredMessages.received_confirmed;
    return errors;
  }

  function validateLookup(value) {
    const raw = text(value);
    const id = /^#?[0-9]+$/.test(raw) ? Number(raw.replace(/^#/, '')) : NaN;
    const errors = Object.create(null);
    if (!raw) errors.donation_id = requiredMessages.donation_id;
    else if (!Number.isSafeInteger(id) || id <= 0) errors.donation_id = invalidMessages.donation_id;
    return { id: Number.isSafeInteger(id) && id > 0 ? id : null, fields: errors };
  }

  function fieldMessage(name, code, required) {
    if (required && requiredMessages[name]) return requiredMessages[name];
    if (/email/.test(code)) return 'Escribe una dirección de correo válida, por ejemplo nombre@dominio.com.';
    if (/uri|url/.test(code)) return 'Escribe una dirección web válida que empiece por https://.';
    if (invalidMessages[name]) return invalidMessages[name];
    if (/too_long|length/.test(code) && maxLengths[name]) return 'Usa un máximo de ' + maxLengths[name] + ' caracteres.';
    return 'Revisa este campo: el valor enviado no es válido. Corrígelo y vuelve a intentar.';
  }

  function normalizeApiError(payload, status) {
    const data = payload && typeof payload === 'object' ? payload : {};
    const code = text(data.code);
    const details = data.data && typeof data.data === 'object' ? data.data : {};
    const trusted = ['ra_auth', 'ra_role', 'ra_forbidden', 'ra_list_failed', 'ra_validation', 'ra_reserved', 'ra_expired', 'ra_state', 'ra_invalid', 'ra_not_found', 'ra_storage', 'ra_storage_engine', 'ra_request_key', 'ra_key_conflict'].includes(code);
    const fields = Object.create(null);
    const missing = code === 'rest_missing_callback_param';
    function add(name, value, detailCode) {
      if (!own(labels, name)) return;
      const message = Array.isArray(value) ? value.map(text).filter(Boolean).join(' ') : text(value);
      fields[name] = trusted && message ? message : fieldMessage(name, detailCode || code, missing);
    }
    if (details.fields && typeof details.fields === 'object') Object.keys(details.fields).forEach(name => add(name, details.fields[name]));
    if (Array.isArray(details.params)) details.params.forEach(name => add(String(name)));
    else if (details.params && typeof details.params === 'object') Object.keys(details.params).forEach(name => add(name, details.params[name]));
    if (details.details && typeof details.details === 'object') Object.keys(details.details).forEach(name => {
      const detail = details.details[name] || {};
      if (!trusted || !own(fields, name)) add(name, detail.message, text(detail.code));
    });
    let message;
    if (trusted && text(data.message)) message = text(data.message);
    else if (['rest_cookie_invalid_nonce', 'rest_not_logged_in', 'rest_cannot_access', 'rest_forbidden_context'].includes(code) || status === 401) message = 'Tu sesión venció o no se pudo validar. Vuelve a iniciar sesión y reintenta.';
    else if (status === 403) message = 'Tu cuenta no tiene permiso para esta acción. Contacta a la coordinación si necesitas acceso.';
    else if (code === 'rest_invalid_json') message = 'No se pudo leer la solicitud enviada. Recarga la página y vuelve a intentar.';
    else if (Object.keys(fields).length || ['rest_invalid_param', 'rest_missing_callback_param'].includes(code)) message = 'El servidor rechazó algunos datos. Revisa los campos indicados y vuelve a intentar.';
    else if (status === 404) message = 'No es posible consultar este recurso. Verifica el identificador y los permisos de tu cuenta.';
    else if (status === 409) message = 'El estado de la donación cambió. Actualiza los datos antes de volver a intentar.';
    else if (status === 429) message = 'Se recibieron demasiadas solicitudes. Espera un momento y vuelve a intentar.';
    else if (status >= 500) message = 'El servidor no pudo confirmar la operación. Reintenta con esta misma solicitud.';
    else message = 'No pudimos completar esta acción. Revisa los datos y vuelve a intentar.';
    return { message, fields, status, code, uncertain: status >= 500 };
  }

  window.RAValidation = { labels, validateDonation, validateDelivery, validateLookup, normalizeApiError };
})();
