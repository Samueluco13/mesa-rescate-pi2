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
    for (const unit of ['kg', 'portions', 'units', 'liters']) assert.deepEqual(fields(validation.validateDonation({ ...donation, category, unit, quantity:unit === 'units' ? '12' : donation.quantity }, now)), []);
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

test('Units or pieces require a positive whole count; measured amounts keep their three-decimal precision', () => {
  for (const quantity of ['1','12','1000000']) assert.equal(validation.validateDonation({...donation,unit:'units',quantity},now).quantity,undefined);
  for (const quantity of ['0','-2','0.5','2.5']) assert.match(validation.validateDonation({...donation,unit:'units',quantity},now).quantity,/entero mayor que cero/);
  for (const unit of ['kg','portions','liters']) assert.equal(validation.validateDonation({...donation,unit,quantity:'2.125'},now).quantity,undefined);
});

test('The optional count label permits blank and custom plural words up to 60 characters only when counting units', () => {
  for (const unit_label of [undefined,'','  ','manzanas','panes','bolsas','a'.repeat(60)]) assert.equal(validation.validateDonation({...donation,unit:'units',quantity:'12',unit_label},now).unit_label,undefined);
  assert.match(validation.validateDonation({...donation,unit:'units',quantity:'12',unit_label:'a'.repeat(61)},now).unit_label,/60 caracteres/);
  assert.equal(validation.validateDonation({...donation,unit:'kg',unit_label:'a'.repeat(61)},now).unit_label,undefined);
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
  app = app.replace('  start();', '  window.__formTest = {publish, deliver, validateForm, showFormError, updateFieldErrors, updateQuantityControls, quantity, quantityFields, quantityPreview, donationPage, field, visibleItems, state};');
  vm.runInNewContext(app, runtime);
  function form(id, values) {
    const result = { id, values, dataset: {}, button: {}, fieldset: {}, elements: { namedItem(name) { return inputs[name] || null; } },
      querySelectorAll(selector) { return selector === '[aria-invalid]' ? Object.values(inputs).filter(input => input.attributes['aria-invalid']) : Object.entries(nodes).filter(([id]) => id.startsWith('ra-error-')).map(([, node]) => node); },
      querySelector(selector) {
        if (selector === '[type=submit]') return this.button;
        if (selector === 'fieldset') return this.fieldset;
        if (selector === 'label[for="ra-quantity"]') return nodes['ra-quantity-label'];
        if (selector.includes('[aria-invalid=true]')) return Object.values(inputs).find(input => input.attributes['aria-invalid']);
        const checkbox = /\[name=(\w+)\]:checked/.exec(selector); return checkbox ? values[checkbox[1]] ? inputs[checkbox[1]] : null : null;
      }
    };
    Object.keys(values).forEach(name => {
      inputs[name] = { id: 'ra-' + name, name, get value() { return values[name]; }, set value(value) { values[name]=value; }, type: typeof values[name] === 'boolean' ? 'checkbox' : 'text', attributes: { 'aria-describedby': 'ra-help-' + name + ' ra-error-' + name },
        setAttribute(name, value) { this.attributes[name] = value; }, getAttribute(name) { return this.attributes[name] || ''; }, removeAttribute(name) { delete this.attributes[name]; }, focus() { focusCount++; }, closest() { return result; } };
      nodes['ra-error-' + name] = { textContent: '' };
    });
    nodes['ra-form-error'] = { innerHTML: '' }; nodes['ra-lookup-error'] = { innerHTML: '' }; nodes['ra-dialog-error'] = { innerHTML: '' };
    nodes['ra-quantity-label']={textContent:''};nodes['ra-help-quantity']={textContent:''};nodes['ra-field-unit_label']={hidden:false};nodes['ra-quantity-preview']={textContent:''};
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

test('Disponibles excludes expired donations while Vencidas and Todos retain them', () => {
  const { runtime } = appHarness();
  const app = runtime.window.__formTest;
  app.state.page = 'mine';
  app.state.list = [
    {id:1,status:'available',deadline:'2000-01-01T12:00:00Z'},
    {id:2,status:'available',deadline:'2099-01-01T12:00:00Z'},
    {id:3,status:'reserved',deadline:'2000-01-01T12:00:00Z'}
  ];
  for (const [status, ids] of [['available',[2]],['expired',[1]],['all',[1,2,3]],['reserved',[3]]]) {
    app.state.status = status;
    assert.deepEqual(Array.from(app.visibleItems(), item => item.id), ids);
  }
});

test('A new form puts units before quantity, defaults to counting pieces and never invents a quantity', () => {
  const h=appHarness();const app=h.runtime.window.__formTest;
  app.state.capabilities.create=true;app.state.user={id:0,name:'Comercio de prueba'};
  const html=app.donationPage();
  assert.match(html,/<option value="units" selected>Unidades o piezas/);
  assert.ok(html.indexOf('id="ra-unit"')<html.indexOf('id="ra-quantity"'));
  assert.match(html,/id="ra-quantity"[^>]*value=""[^>]*min="1"[^>]*step="1"/);
  assert.match(html,/¿Qué estás contando\?/);
  assert.match(html,/Escribe la cantidad para ver cómo aparecerá/);
  assert.doesNotMatch(html,/Vas a publicar [0-9]/);
});

test('Changing measurement keeps quantity, custom words, focus and other draft fields; fractional units explain the problem without rounding', () => {
  const h=appHarness();const app=h.runtime.window.__formTest;
  const form=h.form('ra-donation-form',{...donation,quantity:'2.5',unit:'kg',unit_label:'manzanas',description:'Se conserva refrigerado',pickup_area:'Centro'});
  form.values.unit='units';h.listeners.change({target:h.inputs.unit});
  assert.equal(form.values.quantity,'2.5');assert.equal(form.values.title,donation.title);
  assert.equal(h.inputs.quantity.min,'1');assert.equal(h.inputs.quantity.step,'1');
  assert.equal(h.nodes['ra-field-unit_label'].hidden,false);
  assert.match(h.nodes['ra-error-quantity'].textContent,/entero mayor que cero/);
  assert.match(h.nodes['ra-quantity-preview'].textContent,/Revisa la cantidad/);
  assert.equal(h.focusCount,0);
  const saved=JSON.parse(h.storage.get('rescate-0-draft'));
  assert.equal(saved.quantity,'2.5');assert.equal(saved.unit_label,'manzanas');assert.equal(saved.description,'Se conserva refrigerado');
  form.values.unit='kg';h.listeners.change({target:h.inputs.unit});
  assert.equal(form.values.quantity,'2.5');assert.equal(form.values.unit_label,'manzanas');
  assert.equal(h.inputs.quantity.min,'0.001');assert.equal(h.inputs.quantity.step,'0.001');
  assert.equal(h.nodes['ra-field-unit_label'].hidden,true);assert.equal(h.nodes['ra-error-quantity'].textContent,'');
  assert.match(h.nodes['ra-quantity-preview'].textContent,/Vas a publicar 2,5 kg/);
  app.state.capabilities.create=true;
  const restored=app.donationPage();
  assert.match(restored,/<option value="kg" selected>/);
  assert.match(restored,/id="ra-quantity"[^>]*value="2\.5"/);
  assert.match(restored,/id="ra-unit_label"[^>]*value="manzanas"/);
});

test('Custom count words appear in quantities and previews while legacy quantities retain their usual unit', () => {
  const app=appHarness().runtime.window.__formTest;
  assert.equal(app.quantity({quantity:12,unit:'units',unit_label:'manzanas'}),'12 manzanas');
  assert.equal(app.quantity({quantity:20,unit:'units',unit_label:'panes'}),'20 panes');
  assert.equal(app.quantity({quantity:3,unit:'units',unit_label:'bolsas'}),'3 bolsas');
  assert.equal(app.quantity({quantity:12,unit:'units'}),'12 unidades');
  assert.equal(app.quantity({quantity:2.5,unit:'kg',unit_label:'manzanas'}),'2,5 kg');
  assert.equal(app.quantityPreview({quantity:12,unit:'units',unit_label:'manzanas'}),'Vas a publicar 12 manzanas.');
  assert.match(app.quantityFields({quantity:12,unit_label:'<img src=x>'},'units'),/Vas a publicar 12 &lt;img src=x&gt;\./);
  assert.doesNotMatch(app.quantityFields({quantity:12,unit_label:'<img src=x>'},'units'),/<img src=x>/);
});

test('Only a nonempty custom label for units enters a new payload; its draft and uncertain retry retain the exact value', async () => {
  for(const [unit,unit_label,expected] of [['units','  manzanas  ','manzanas'],['units','  ',undefined],['kg','manzanas',undefined]]) {
    const h=appHarness();const app=h.runtime.window.__formTest;
    const values={...donation,quantity:'12',unit,unit_label,description:'',pickup_area:'',deadline:'2099-11-07T11:00'};
    const form=h.form('ra-donation-form',values);let sent;
    h.runtime.fetch=async(_url,options)=>{sent=JSON.parse(options.body);return {ok:false,status:503,async json(){return {code:'ra_storage',message:'No pudimos guardar el cambio. Reintenta con la misma solicitud.'};}};};
    await app.publish(form);
    assert.equal(sent.unit_label,expected);
    assert.equal(Object.hasOwn(sent,'unit_label'),expected!==undefined);
    assert.equal(JSON.parse(h.storage.get('rescate-0-draft')).unit_label,unit_label);
    const first=JSON.stringify(sent);
    form.values.unit_label='Texto cambiado sin confirmar';
    await app.publish(form);
    assert.equal(JSON.stringify(sent),first,'The pending retry must not change the original payload or request key');
  }
});

test('An old pending fractional-unit request is described as conserved and retries its original payload without new validation', async () => {
  const h=appHarness();const app=h.runtime.window.__formTest;
  app.state.capabilities.create=true;app.state.user={id:0,name:'Comercio de prueba'};
  const values={...donation,unit:'units',quantity:'0.5',deadline:'2026-01-01T09:00'};
  const payload={title:donation.title,unit:'units',quantity:0.5,deadline:'2026-01-01T09:00:00-05:00',request_key:'legacy-fractional-request-12345'};
  const pending={form:values,payload};h.storage.set('rescate-0-create-pending',JSON.stringify(pending));
  const html=app.donationPage();
  assert.match(html,/Solicitud conservada: 0,5 unidades\. Reintentaremos con los mismos datos\./);
  assert.doesNotMatch(html,/Revisa la cantidad para ver el resumen/);
  assert.match(html,/<fieldset[^>]*disabled/);
  const form=h.form('ra-donation-form',values);let sent;
  h.runtime.fetch=async(_url,options)=>{sent=JSON.parse(options.body);return {ok:false,status:503,async json(){return {code:'ra_storage',message:'No pudimos guardar el cambio. Reintenta con la misma solicitud.'};}};};
  await app.publish(form);
  assert.deepEqual(sent,payload);
  assert.equal(h.storage.get('rescate-0-create-pending'),JSON.stringify(pending));
});

test('Known default units have singular forms while custom count names stay exactly as written', () => {
  const app=appHarness().runtime.window.__formTest;
  assert.equal(app.quantity({quantity:1,unit:'units'}),'1 unidad');
  assert.equal(app.quantity({quantity:1,unit:'portions'}),'1 porción');
  assert.equal(app.quantity({quantity:1,unit:'liters'}),'1 litro');
  assert.equal(app.quantity({quantity:1,unit:'kg'}),'1 kg');
  assert.equal(app.quantity({quantity:1,unit:'units',unit_label:'manzana'}),'1 manzana');
  assert.equal(app.quantity({quantity:1,unit:'units',unit_label:'manzanas'}),'1 manzanas');
  assert.match(app.quantityFields({quantity:1,unit_label:'manzana'},'units'),/Escribe el nombre tal como debe aparecer/);
  assert.doesNotMatch(app.quantityFields({quantity:1,unit_label:'manzana'},'units'),/Escríbelo en plural/);
});
