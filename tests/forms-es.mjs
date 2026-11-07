import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../plugin/rescate-alimentos/assets/form-validation.js', import.meta.url), 'utf8');
const context = { window: {} };
vm.runInNewContext(source, context);
const validation = context.window.RAValidation;
const now = Date.parse('2026-11-07T15:00:00Z'); // 10:00 in Colombia.
const donation = { title: 'Verduras frescas', merchant_name: 'Tienda del barrio', pickup_address: 'Calle 1 # 2-3', category: 'vegetables', quantity: '1.125', unit: 'kg', deadline: '2026-11-07T11:00', food_safe: true };
const delivery = { recipient_name: 'Fundación Mesa Común', responsible_name: 'Ana', picked_up_at: '2026-11-07T09:00', delivered_at: '2026-11-07T09:30', received_confirmed: true };
const fields = value => Object.keys(value);

test('A valid donation accepts each supported category and unit without errors', () => {
  for (const category of ['vegetables', 'fruit', 'bakery', 'prepared', 'dairy', 'pantry']) {
    for (const unit of ['kg', 'portions', 'units', 'liters']) assert.deepEqual(fields(validation.validateDonation({ ...donation, category, unit }, now)), []);
  }
});

test('One submit reports every blank, whitespace-only and unchecked donation field', () => {
  const errors = validation.validateDonation({ title: '  ', merchant_name: '\n\t', pickup_address: ' ', quantity: '', category: '', unit: '', deadline: '', food_safe: false }, now);
  assert.deepEqual(fields(errors).sort(), ['category', 'deadline', 'food_safe', 'merchant_name', 'pickup_address', 'quantity', 'title', 'unit']);
  assert.match(errors.title, /solo espacios/);
  assert.match(errors.food_safe, /Confirma.*buen estado/);
});

test('Quantity limits and three decimal precision match the donation rules', () => {
  for (const quantity of ['0.001', '0.029', '1.125', '1000000', '1e-3', '1000e-6', '1.125000']) assert.equal(validation.validateDonation({ ...donation, quantity }, now).quantity, undefined, quantity);
  for (const quantity of ['0', '-2', '0.0009']) assert.match(validation.validateDonation({ ...donation, quantity }, now).quantity, /al menos 0\.001/);
  assert.match(validation.validateDonation({ ...donation, quantity: '1000000.001' }, now).quantity, /no puede superar/);
  assert.match(validation.validateDonation({ ...donation, quantity: '1.0001' }, now).quantity, /tres decimales/);
  assert.match(validation.validateDonation({ ...donation, quantity: '1.0000000001' }, now).quantity, /tres decimales/);
  for (const quantity of ['uno', '1,2', 'Infinity', '0x10', 'NaN']) assert.match(validation.validateDonation({ ...donation, quantity }, now).quantity, /numérica/);
});

test('A deadline uses Colombia time, must be future and rejects impossible dates', () => {
  assert.match(validation.validateDonation({ ...donation, deadline: '2026-11-07T10:00' }, now).deadline, /ya pasó/);
  assert.equal(validation.validateDonation({ ...donation, deadline: '2026-11-07T10:01' }, now).deadline, undefined);
  for (const deadline of ['2026-02-30T12:00', '2026-11-07T25:00', '2026-11-07', 'no es fecha']) assert.match(validation.validateDonation({ ...donation, deadline }, now).deadline, /válidas/);
});

test('Assisted publication checks its channel and text length while normal donations do not require a channel', () => {
  assert.equal(validation.validateDonation(donation, now, false).origin_channel, undefined);
  assert.match(validation.validateDonation(donation, now, true).origin_channel, /Selecciona/);
  for (const origin_channel of ['web', 'phone', 'sms']) assert.equal(validation.validateDonation({ ...donation, origin_channel }, now, true).origin_channel, undefined);
  assert.match(validation.validateDonation({ ...donation, pickup_address: 'a'.repeat(256), description: 'a'.repeat(1801) }, now).pickup_address, /255 caracteres/);
});

test('Delivery requires the recipient, registering person, both dates and explicit receipt confirmation', () => {
  assert.deepEqual(fields(validation.validateDelivery(delivery, now)), []);
  const errors = validation.validateDelivery({ recipient_name: '  ', responsible_name: '', picked_up_at: '', delivered_at: '', received_confirmed: false }, now);
  assert.deepEqual(fields(errors).sort(), ['delivered_at', 'picked_up_at', 'received_confirmed', 'recipient_name', 'responsible_name']);
  assert.match(errors.received_confirmed, /recepción/);
});

test('Delivery dates report future pickup and future delivery separately', () => {
  const errors = validation.validateDelivery({ ...delivery, picked_up_at: '2026-11-07T11:00', delivered_at: '2026-11-07T12:00' }, now);
  assert.match(errors.picked_up_at, /recogida no puede estar en el futuro/);
  assert.match(errors.delivered_at, /entrega no puede estar en el futuro/);
});

test('A reversed delivery identifies both inconsistent fields; equal past times are valid', () => {
  const errors = validation.validateDelivery({ ...delivery, picked_up_at: '2026-11-07T09:45' }, now);
  assert.match(errors.picked_up_at, /posterior a la entrega/);
  assert.match(errors.delivered_at, /anterior a la recogida/);
  assert.deepEqual(fields(validation.validateDelivery({ ...delivery, picked_up_at: delivery.delivered_at }, now)), []);
});

test('Lookup accepts a positive ID with an optional hash and rejects blank, zero, decimals, letters and unsafe numbers', () => {
  for (const value of ['24', '#24', '  #24  ']) assert.equal(validation.validateLookup(value).id, 24);
  assert.match(validation.validateLookup(' ').fields.donation_id, /Escribe el identificador/);
  for (const value of ['0', '#0', '-2', '2.3', '2abc', '##24', '1e3', '9007199254740993']) {
    assert.equal(validation.validateLookup(value).id, null, value);
    assert.match(validation.validateLookup(value).fields.donation_id, /entero mayor que cero/);
  }
});

test('REST invalid-param errors translate both params and detailed constraints without leaking English', () => {
  const error = validation.normalizeApiError({ code: 'rest_invalid_param', message: 'Invalid parameter(s): quantity, deadline', data: { params: { quantity: 'quantity is not of type number.', deadline: 'Invalid date.' }, details: { quantity: { code: 'rest_invalid_type', message: 'quantity is not of type number.' } } } }, 400);
  assert.match(error.message, /servidor rechazó/);
  assert.match(error.fields.quantity, /número entre/);
  assert.match(error.fields.deadline, /fecha y una hora válidas/);
  assert.doesNotMatch(JSON.stringify(error), /Invalid parameter|not of type|Invalid date/);
});

test('REST missing-param errors list each missing known field and do not expose unknown field internals', () => {
  const error = validation.normalizeApiError({ code: 'rest_missing_callback_param', message: 'Missing parameter(s): title, quantity', data: { params: ['title', 'quantity', 'internal_secret_name'] } }, 400);
  assert.match(error.fields.title, /nombre del alimento/);
  assert.match(error.fields.quantity, /Escribe la cantidad/);
  assert.deepEqual(fields(error.fields).sort(), ['quantity', 'title']);
});

test('Known application errors retain their specific Spanish messages and per-field instructions', () => {
  const error = validation.normalizeApiError({ code: 'ra_validation', message: 'Revisa los datos de la entrega.', data: { fields: { delivered_at: 'La entrega debe ser posterior o igual a la recogida.' } } }, 400);
  assert.equal(error.message, 'Revisa los datos de la entrega.');
  assert.equal(error.fields.delivered_at, 'La entrega debe ser posterior o igual a la recogida.');
});

test('English session, JSON, permissions and unknown server exceptions become actionable Spanish errors', () => {
  const cases = [
    ['rest_cookie_invalid_nonce', 403, /sesión venció/], ['rest_invalid_json', 400, /leer la solicitud/],
    ['rest_cannot_create', 403, /permiso/], ['unknown_exception', 500, /misma solicitud/],
    ['rest_no_route', 404, /identificador y los permisos/], ['unknown', 429, /Espera un momento/], ['ra_unknown_exception', 500, /misma solicitud/]
  ];
  for (const [code, status, pattern] of cases) {
    const error = validation.normalizeApiError({ code, message: 'Internal English failure: stack trace' }, status);
    assert.match(error.message, pattern); assert.doesNotMatch(error.message, /English|stack trace/);
    assert.equal(error.uncertain, status >= 500);
  }
});

function appHarness() {
  const listeners = {}, storage = new Map(), nodes = {}, inputs = {};
  let focusCount = 0;
  const mount = { addEventListener(name, listener) { listeners[name] = listener; } };
  const runtime = {
    window: { RA_CONFIG: {}, addEventListener() {} }, navigator: { onLine: true },
    document: { getElementById(id) { return id === 'rescate-app' ? mount : nodes[id] || null; }, addEventListener() {} },
    sessionStorage: { getItem(name) { return storage.get(name) ?? null; }, setItem(name, value) { storage.set(name, value); }, removeItem(name) { storage.delete(name); } },
    URL, Intl, setTimeout, clearTimeout, AbortController,
    FormData: class { constructor(form) { this.form = form; } forEach(callback) { Object.entries(this.form.values).forEach(([key, value]) => { if (typeof value !== 'boolean') callback(value, key); }); } }
  };
  vm.runInNewContext(source, runtime);
  let app = readFileSync(new URL('../plugin/rescate-alimentos/assets/app.js', import.meta.url), 'utf8');
  // Replace only startup so form events, pending-retry logic and DOM boundary run without a browser or network.
  app = app.replace('  start();', '  window.__formTest = {publish, deliver, validateForm, showFormError, updateFieldErrors, field, state};');
  vm.runInNewContext(app, runtime);
  function form(id, values) {
    const result = { id, values, dataset: {}, button: {}, fieldset: {}, elements: { namedItem(name) { return inputs[name] || null; } },
      querySelectorAll(selector) { return selector === '[aria-invalid]' ? Object.values(inputs).filter(input => input.attributes['aria-invalid']) : Object.entries(nodes).filter(([id]) => id.startsWith('ra-error-')).map(([, node]) => node); },
      querySelector(selector) {
        if (selector === '[type=submit]') return this.button;
        if (selector === 'fieldset') return this.fieldset;
        if (selector.includes('[aria-invalid=true]')) return Object.values(inputs).find(input => input.attributes['aria-invalid']);
        const checkbox = /\[name=(\w+)\]:checked/.exec(selector); return checkbox ? values[checkbox[1]] ? inputs[checkbox[1]] : null : null;
      }
    };
    Object.keys(values).forEach(name => {
      inputs[name] = { id: 'ra-' + name, name, type: typeof values[name] === 'boolean' ? 'checkbox' : 'text', attributes: { 'aria-describedby': 'ra-help-' + name + ' ra-error-' + name },
        setAttribute(name, value) { this.attributes[name] = value; }, getAttribute(name) { return this.attributes[name] || ''; }, removeAttribute(name) { delete this.attributes[name]; }, focus() { focusCount++; }, closest() { return result; } };
      nodes['ra-error-' + name] = { textContent: '' };
    });
    nodes['ra-form-error'] = { innerHTML: '' }; nodes['ra-lookup-error'] = { innerHTML: '' }; nodes['ra-dialog-error'] = { innerHTML: '' };
    return result;
  }
  return { runtime, form, inputs, nodes, listeners, storage, get focusCount() { return focusCount; } };
}

test('Publishing invalid fields shows all Spanish errors, focuses the first field and preserves help descriptions and values', async () => {
  const h = appHarness();
  const form = h.form('ra-donation-form', { ...donation, title: ' ', quantity: '0', food_safe: false });
  await h.runtime.window.__formTest.publish(form);
  assert.equal(h.focusCount, 1);
  for (const name of ['title', 'quantity', 'food_safe']) {
    assert.equal(h.inputs[name].attributes['aria-invalid'], 'true');
    assert.match(h.nodes['ra-error-' + name].textContent, /\S/);
    assert.equal(h.inputs[name].attributes['aria-describedby'], 'ra-help-' + name + ' ra-error-' + name);
  }
  assert.match(h.nodes['ra-form-error'].innerHTML, /Nombre del alimento.*Cantidad disponible.*Confirmación del estado/s);
  assert.equal(form.values.title, ' '); assert.equal(form.values.quantity, '0');
  assert.equal(h.storage.has('rescate-0-create-pending'), false, 'Invalid submissions must not consume an idempotency key');
});

test('Correcting a field clears its inline error without clearing another field or stealing focus', () => {
  const h = appHarness(); const form = h.form('ra-donation-form', { ...donation, title: ' ', quantity: '0' });
  h.runtime.window.__formTest.showFormError(form, { message: 'Revisa los campos indicados.', fields: { title: 'Escribe el alimento.', quantity: 'La cantidad debe ser mayor que cero.' } }, 'ra-form-error');
  form.values.title = 'Verduras'; h.runtime.window.__formTest.updateFieldErrors(form, h.inputs.title);
  assert.equal(h.inputs.title.attributes['aria-invalid'], undefined);
  assert.equal(h.nodes['ra-error-title'].textContent, '');
  assert.equal(h.inputs.quantity.attributes['aria-invalid'], 'true');
  assert.equal(h.focusCount, 1);
  form.values.quantity = '1.5'; h.runtime.window.__formTest.updateFieldErrors(form, h.inputs.quantity);
  assert.equal(h.nodes['ra-form-error'].innerHTML, '');
  assert.equal(h.inputs.quantity.attributes['aria-invalid'], undefined);
  assert.match(h.inputs.title.attributes['aria-describedby'], /ra-help-title/);
});

test('The lookup submit event reports an invalid ID and does not silently ignore it', () => {
  const h = appHarness(); const form = h.form('ra-lookup-form', { donation_id: 'abc' }); let prevented = false;
  h.listeners.submit({ target: form, preventDefault() { prevented = true; } });
  assert.equal(prevented, true);
  assert.match(h.nodes['ra-error-donation_id'].textContent, /entero mayor que cero/);
  assert.equal(h.focusCount, 1);
});

test('A pending publication retries the original payload and key even when its deadline has since passed', async () => {
  const h = appHarness();
  const payload = { title: 'Solicitud original', deadline: '2026-01-01T09:00:00-05:00', request_key: 'same-create-request-12345' };
  const pending = { form: { ...donation, deadline: '2026-01-01T09:00' }, payload };
  const form = h.form('ra-donation-form', { ...donation, title: 'Texto sin confirmar' });
  h.storage.set('rescate-0-create-pending', JSON.stringify(pending));
  let sent;
  h.runtime.fetch = async (_url, options) => { sent = JSON.parse(options.body); return { ok: false, status: 503, async json() { return { code: 'unknown_server_failure', message: 'Internal database error' }; } }; };
  await h.runtime.window.__formTest.publish(form);
  assert.deepEqual(sent, payload);
  assert.equal(h.storage.get('rescate-0-create-pending'), JSON.stringify(pending));
  assert.equal(form.fieldset.disabled, true);
  assert.equal(form.button.disabled, false);
  assert.equal(form.button.textContent, 'Reintentar publicación');
  assert.match(h.nodes['ra-form-error'].innerHTML, /misma solicitud/);
  assert.doesNotMatch(h.nodes['ra-form-error'].innerHTML, /Internal database/);
});

test('A pending delivery retries the original data and keeps its key after an uncertain server response', async () => {
  const h = appHarness(); const item = { id: 45 };
  h.runtime.window.__formTest.state.dialog = { item };
  const payload = { recipient_name: 'Fundación original', request_key: 'same-delivery-request-12345' };
  const pending = { form: delivery, payload };
  const form = h.form('ra-delivery-form', { ...delivery, received_confirmed: false });
  h.storage.set('rescate-0-deliver-45', JSON.stringify(pending));
  let sent;
  h.runtime.fetch = async (_url, options) => { sent = JSON.parse(options.body); return { ok: false, status: 503, async json() { return { code: 'ra_storage', message: 'No pudimos guardar el cambio. Reintenta con la misma solicitud.' }; } }; };
  await h.runtime.window.__formTest.deliver(form);
  assert.deepEqual(sent, payload);
  assert.equal(h.storage.get('rescate-0-deliver-45'), JSON.stringify(pending));
  assert.equal(form.fieldset.disabled, true); assert.equal(form.button.disabled, false);
  assert.equal(form.button.textContent, 'Reintentar cierre');
});
