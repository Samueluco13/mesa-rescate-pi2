/* Pickup planning: no geocoding, no background location, no live tracking. */
(function () {
  'use strict';
  const config = window.RA_CONFIG || {};
  const esc = value => String(value == null ? '' : value).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const paths = {
    route:'<circle cx="6" cy="5" r="2"/><circle cx="18" cy="19" r="2"/><path d="M8 5h7a4 4 0 0 1 0 8H9a3 3 0 0 0 0 6h7"/>',
    pin:'<path d="M20 10c0 6-8 11-8 11S4 16 4 10a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
    arrow:'<path d="M5 12h14m-6-6 6 6-6 6"/>',
    copy:'<rect x="8" y="8" width="12" height="13" rx="2"/><path d="M16 8V3H3v13h5"/>',
    locate:'<circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/><path d="M12 2v4m0 12v4M2 12h4m12 0h4"/>',
    map:'<path d="m3 5 6-2 6 2 6-2v16l-6 2-6-2-6 2V5ZM9 3v16M15 5v16"/>',
    check:'<path d="m5 12 4 4L19 6"/>',
    info:'<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/>'
  };
  function icon(name) { return '<svg viewBox="0 0 24 24" aria-hidden="true">' + paths[name] + '</svg>'; }
  function coords(item) {
    if (!item || item.pickup_latitude == null || item.pickup_longitude == null || item.pickup_latitude === '' || item.pickup_longitude === '') return null;
    const latitude = Number(item.pickup_latitude), longitude = Number(item.pickup_longitude);
    if (!Number.isFinite(latitude) || !Number.isFinite(longitude) || latitude < -90 || latitude > 90 || longitude < -180 || longitude > 180) return null;
    return [latitude, longitude];
  }
  function address(item, settings) {
    return [item.pickup_address, item.pickup_area, settings.city].filter(Boolean).map(value => String(value).trim()).filter(Boolean).join(', ');
  }
  function mapsUrl(kind, item, settings, transport) {
    if(item.is_demo)return DEMO_VIEW.url;
    const point = coords(item);
    const destination = point ? point.map(v => v.toFixed(6)).join(',') : address(item, settings);
    const url = new URL(kind === 'directions' ? 'https://www.google.com/maps/dir/' : 'https://www.google.com/maps/search/');
    url.searchParams.set('api', '1');
    url.searchParams.set(kind === 'directions' ? 'destination' : 'query', destination);
    if (kind === 'directions' && ['walking','bicycling','driving'].includes(transport)) url.searchParams.set('travelmode', transport);
    return url.href;
  }
  const DEMO_VIEW = {center:[3.4516,-76.532],zoom:13,url:'https://www.openstreetmap.org/#map=13/3.4516/-76.532'};
  const DEMO_DESCRIPTION = 'Vista ilustrativa de la ciudad. No ubica al comercio ni representa una recogida real.';
  function demoPlanHTML(item) {
    return '<section class="ra-pickup-plan" aria-labelledby="ra-plan-title"><div class="ra-plan-heading"><span class="ra-plan-symbol">' + icon('map') + '</span><div><h3 id="ra-plan-title">Mapa de ejemplo · Cali</h3><p>Explora una vista pública de la ciudad.</p></div><span class="ra-plan-tag">Demostración</span></div><p class="ra-plan-notice">' + DEMO_DESCRIPTION + '</p><div class="ra-plan-map-slot"><div class="ra-plan-map-intro"><span>' + icon('map') + '</span><div><strong>Cali, Colombia</strong><p>Sin ubicación de un comercio.</p></div><button type="button" class="ra-button ra-button-secondary" data-ra-map-action="load" aria-label="Ver mapa de ejemplo de ' + esc(item.title) + '">Ver mapa de ejemplo</button></div></div><p class="ra-plan-privacy">OpenStreetMap se conecta al abrir el mapa. No solicita tu ubicación.</p><p class="ra-plan-feedback" role="status" aria-live="polite"></p></section>';
  }
  function planHTML(item, settings) {
    if (item.is_demo) return demoPlanHTML(item);
    const point = coords(item);
    const demo = !!item.is_demo;
    const dest = address(item, settings);
    const usable = !demo && !!String(item.pickup_address || '').trim();
    return '<section class="ra-pickup-plan" aria-labelledby="ra-plan-title"><div class="ra-plan-heading"><span class="ra-plan-symbol">' + icon('route') + '</span><div><h3 id="ra-plan-title">Mapa y ruta de recogida</h3><p>Prepara tu llegada al comercio.</p></div><span class="ra-plan-tag">' + (demo ? 'Demostración' : point ? 'Punto indicado' : 'Por dirección') + '</span></div><ol class="ra-plan-steps"><li>Revisa el punto</li><li>Abre la ruta</li><li>Coordina la entrega</li></ol><div class="ra-plan-destination"><span class="ra-plan-destination-icon">' + icon('pin') + '</span><div><small>Tu destino de recogida</small><strong>' + esc(item.merchant_name || 'Comercio donante') + '</strong><p class="ra-plan-address">' + esc(dest || 'La dirección aún no está disponible.') + '</p></div>' + (dest ? '<button type="button" class="ra-plan-copy" data-ra-map-action="copy" aria-label="Copiar dirección de recogida">' + icon('copy') + '</button>' : '') + '</div>' + (demo ? '<p class="ra-plan-notice">Esta donación contiene datos ficticios. No se genera una ruta de recogida real.</p>' : !usable ? '<p class="ra-plan-notice">Falta una dirección de recogida. Confirma el punto con la coordinación antes de salir.</p>' : '<div class="ra-plan-map-slot">' + (point ? '<div class="ra-plan-map-intro"><span>' + icon('map') + '</span><div><strong>El punto que indicó el comercio</strong><p>Abre el mapa cuando lo necesites.</p></div><button type="button" class="ra-button ra-button-secondary" data-ra-map-action="load">Mostrar mapa</button></div>' : '<p class="ra-plan-notice">El comercio no fijó un punto en el mapa. La dirección aún no está ubicada: revisa la coincidencia en Maps antes de salir.</p><a class="ra-plan-search" href="' + esc(mapsUrl('search', item, settings)) + '" target="_blank" rel="noopener noreferrer">Buscar dirección en Maps ' + icon('arrow') + '</a>') + '</div><div class="ra-plan-route"><div class="ra-route-transport"><label for="ra-route-transport">Cómo te desplazas</label><select id="ra-route-transport" aria-label="Modo de transporte para la ruta"><option value="">Elegir en Maps</option><option value="walking">A pie</option><option value="bicycling">En bicicleta</option><option value="driving">En vehículo</option></select></div><a class="ra-button ra-plan-directions" href="' + esc(mapsUrl('directions',item,settings)) + '" target="_blank" rel="noopener noreferrer">Abrir ruta en Maps ' + icon('arrow') + '</a></div><p class="ra-plan-privacy">' + (point ? 'El mapa se conecta con OpenStreetMap al abrirlo. ' : '') + 'La ruta se abre en Google Maps; allí eliges el origen. No hay seguimiento en tiempo real.</p>') + '<p class="ra-plan-feedback" role="status" aria-live="polite"></p></section>';
  }
  const liveMaps = new Set();
  let leafletPromise,leafletCssPromise,leafletJsPromise;
  function loadLeaflet() {
    if (leafletPromise) return leafletPromise;
    const base = String(config.assetsUrl || '').replace(/\/$/,'') + '/vendor/leaflet/';
    if (!leafletCssPromise) {
      leafletCssPromise = new Promise((resolve,reject) => {
        const existing=document.getElementById('ra-leaflet-css');
        if (existing && (existing.sheet || existing.dataset.raMapReady==='1')) { resolve();return; }
        const css=existing || document.createElement('link');
        css.id='ra-leaflet-css';css.rel='stylesheet';css.href=base+'leaflet.css?ver='+encodeURIComponent(config.assetVersions && config.assetVersions.leafletCss || config.appVersion || '1.9.4');
        css.onload=()=>{css.dataset.raMapReady='1';resolve();};
        css.onerror=()=>{css.remove();reject(new Error('No pudimos cargar el estilo del mapa. Reintenta con conexión.'));};
        if(!existing)document.head.appendChild(css);
      }).catch(error=>{leafletCssPromise=null;throw error;});
    }
    if (!leafletJsPromise) {
      leafletJsPromise = new Promise((resolve,reject) => {
        if(window.L && window.L.map){resolve();return;}
        const script=document.createElement('script');script.id='ra-leaflet-js';script.async=true;
        script.src=base+'leaflet.js?ver='+encodeURIComponent(config.assetVersions && config.assetVersions.leafletJs || config.appVersion || '1.9.4');
        script.onload=()=>{if(window.L && window.L.map)resolve();else{script.remove();reject(new Error('No se pudo iniciar el mapa. Reintenta para cargarlo de nuevo.'));}};
        script.onerror=()=>{script.remove();reject(new Error('No pudimos cargar el mapa. Reintenta con conexión.'));};
        document.head.appendChild(script);
      }).catch(error=>{leafletJsPromise=null;throw error;});
    }
    leafletPromise=Promise.all([leafletCssPromise,leafletJsPromise]).then(()=>window.L).catch(error=>{leafletPromise=null;throw error;});
    return leafletPromise;
  }
  function tiles(map, status, demo) {
    const layer = window.L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom:19, minZoom:3, updateWhenIdle:true, keepBuffer:1,
      referrerPolicy:'strict-origin-when-cross-origin',
      attribution:'&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors'
    });
    let failed=false;
    layer.on('tileerror',()=>{failed=true;if(status){status.textContent=demo?'El mapa de ejemplo no cargó por completo. Revisa tu conexión y vuelve a abrirlo.':'El mapa no cargó por completo. Revisa tu conexión o abre la ubicación en Maps.';status.classList.add('is-error');}});
    layer.on('load',()=>{if(status && !failed)status.textContent=demo?'Mapa de ejemplo · Cali. '+DEMO_DESCRIPTION:'Mapa de OpenStreetMap. El punto fue indicado por quien publicó la donación.';});
    layer.addTo(map);
    return layer;
  }
  function makeMap(node, point, zoom, status, demo) {
    const map = window.L.map(node, {scrollWheelZoom:false,zoomControl:true,attributionControl:true,worldCopyJump:true,preferCanvas:true}).setView(point,zoom);
    map.zoomControl.setPosition('topright');
    map.raTileLayer=tiles(map,status,demo);liveMaps.add(map);
    if(typeof window.requestAnimationFrame==='function')window.requestAnimationFrame(()=>{if(node.isConnected!==false && typeof map.invalidateSize==='function')map.invalidateSize({pan:false});});
    return map;
  }
  function destroy(scope) {
    liveMaps.forEach(map=>{const container=map.getContainer();if(!scope || scope.contains(container)){map.remove();liveMaps.delete(map);}});
  }
  function mountPlan(section,item,settings) {
    if (!section) return;
    const demo=!!item.is_demo;
    const feedback=section.querySelector('.ra-plan-feedback');
    section.addEventListener('change',event=>{if(!demo && event.target.id==='ra-route-transport'){const a=section.querySelector('.ra-plan-directions');if(a)a.href=mapsUrl('directions',item,settings,event.target.value);}});
    section.addEventListener('click',async event=>{
      const action=event.target.closest('[data-ra-map-action]');if(!action)return;
      if(action.dataset.raMapAction==='copy'){
        if(demo)return;
        try { await navigator.clipboard.writeText(address(item,settings));feedback.textContent='Dirección copiada. Puedes compartirla para coordinar la recogida.'; }
        catch(_){feedback.textContent='No pudimos copiar automáticamente. Puedes seleccionar y copiar la dirección que aparece arriba.';}
        return;
      }
      if(action.dataset.raMapAction!=='load')return;
      if(!navigator.onLine){feedback.textContent=demo?'Sin conexión. Reintenta abrir el mapa de ejemplo cuando vuelvas a conectarte.':'Sin conexión. La dirección sigue disponible; reintenta abrir el mapa cuando vuelvas a conectarte.';return;}
      const point=demo?DEMO_VIEW.center:coords(item);if(!point)return;
      destroy(section);
      action.disabled=true;action.textContent=demo?'Abriendo mapa de ejemplo…':'Abriendo mapa…';
      let map=null;
      try{
        await loadLeaflet();if(!section.isConnected)return;
        const slot=section.querySelector('.ra-plan-map-slot');
        const viewUrl=demo?DEMO_VIEW.url:mapsUrl('search',item,settings);
        const mapLabel=demo?'Mapa de ejemplo · Cali':'Mapa del punto de recogida';
        slot.innerHTML='<div class="ra-route-map" role="region" aria-label="'+mapLabel+'"></div><p class="ra-map-status" role="status">'+(demo?'Cargando mapa de ejemplo de Cali…':'Cargando el punto indicado…')+'</p><a class="ra-map-larger" href="'+esc(viewUrl)+'" target="_blank" rel="noopener noreferrer">'+(demo?'Ampliar mapa de ejemplo':'Abrir ubicación en una ventana nueva')+' '+icon('arrow')+'</a><button type="button" class="ra-link-button" data-ra-map-action="load" hidden>Reintentar mapa</button>';
        const status=slot.querySelector('.ra-map-status');
        const retry=slot.querySelector('[data-ra-map-action=load]');
        map=makeMap(slot.querySelector('.ra-route-map'),point,demo?DEMO_VIEW.zoom:16,status,demo);
        map.raTileLayer.on('tileerror',()=>{retry.hidden=false;});
        map.raTileLayer.on('load',()=>{if(status.classList.contains('is-error'))retry.hidden=false;});
        if(!demo){
          window.L.circleMarker(point,{radius:9,color:'#FFFFFF',weight:3,fillColor:'#D64114',fillOpacity:1}).addTo(map);
          const popup=document.createElement('div');popup.className='ra-map-popup';const strong=document.createElement('strong');strong.textContent=item.merchant_name || 'Punto de recogida';const text=document.createElement('p');text.textContent=item.pickup_address;popup.append(strong,text);
          window.L.marker(point,{icon:window.L.divIcon({className:'ra-map-destination',html:icon('pin'),iconSize:[38,44],iconAnchor:[19,44]})}).addTo(map).bindPopup(popup);
          const extra=document.createElement('a');extra.className='ra-map-issue';extra.href='https://www.openstreetmap.org/fixthemap';extra.target='_blank';extra.rel='noopener noreferrer';extra.textContent='Reportar un problema del mapa';slot.appendChild(extra);
        }
      }catch(error){
        if(map){map.remove();liveMaps.delete(map);}
        if(section.isConnected){const retry=section.querySelector('[data-ra-map-action=load]') || action;retry.hidden=false;retry.disabled=false;retry.textContent=demo?'Reintentar mapa de ejemplo':'Reintentar mapa';action.disabled=false;feedback.textContent=error.message;}
      }
    });
  }
  function fieldHTML(item) {
    const point=coords(item);
    return '<div class="ra-pin-field ra-field-wide"><div class="ra-pin-field-heading"><span>'+icon('pin')+'</span><div><strong>Punto en el mapa <small>Opcional</small></strong><p id="ra-pin-summary">'+(point?'Punto confirmado en '+esc(point.map(v=>v.toFixed(6)).join(', '))+'.':'Ayuda al voluntario a encontrar la entrada correcta.')+'</p></div></div><input type="hidden" name="pickup_latitude" value="'+(point?point[0]:'')+'"><input type="hidden" name="pickup_longitude" value="'+(point?point[1]:'')+'"><div class="ra-pin-field-actions"><button type="button" class="ra-button ra-button-secondary" data-action="pickup-pin">'+icon('map')+(point?'Cambiar punto':'Marcar punto en mapa')+'</button><button type="button" class="ra-link-button" data-action="pickup-pin-clear"'+(!point?' hidden':'')+'>Quitar punto</button></div><p class="ra-pin-help">Solo guardamos el punto que confirmes. El mapa se abre con OpenStreetMap; no buscamos la dirección automáticamente.</p><span class="ra-field-error" id="ra-error-pickup_latitude"></span><span class="ra-field-error" id="ra-error-pickup_longitude"></span></div>';
  }
  function pickerHTML(item) {
    return '<div class="ra-detail-body ra-pin-picker"><h2 id="ra-dialog-title">Marca el punto de recogida</h2><p class="ra-confirm-copy">Mueve el mapa hasta la entrada del comercio y acerca la vista. El centro indica el punto que vas a confirmar.</p><div class="ra-picker-map-wrap"><div class="ra-picker-map" role="region" aria-label="Mapa para elegir el punto de recogida"></div><div class="ra-map-target" aria-hidden="true">'+icon('pin')+'</div><div class="ra-picker-loading"><span class="ra-loading-mark"></span><span>Abriendo OpenStreetMap…</span></div></div><p class="ra-picker-status" role="status" aria-live="polite">'+(coords(item)?'Recuperando el punto que ya habías indicado.':'Vista inicial de Colombia. Todavía no hay un punto confirmado.')+'</p><div class="ra-picker-tools"><button type="button" class="ra-button ra-button-secondary" data-ra-picker="locate">'+icon('locate')+'Centrar en mi ubicación</button><button type="button" class="ra-link-button" data-ra-picker="retry" hidden>Reintentar mapa</button></div><details class="ra-picker-coordinates"><summary>Introducir coordenadas conocidas</summary><div class="ra-form-grid"><div class="ra-field"><label for="ra-picker-latitude">Latitud</label><input id="ra-picker-latitude" type="number" min="-90" max="90" step="0.000001" inputmode="decimal" placeholder="Ej. 3.451600"></div><div class="ra-field"><label for="ra-picker-longitude">Longitud</label><input id="ra-picker-longitude" type="number" min="-180" max="180" step="0.000001" inputmode="decimal" placeholder="Ej. -76.532000"></div></div><button type="button" class="ra-link-button" data-ra-picker="coordinates">Ver estas coordenadas</button></details><p class="ra-pin-help">Tu ubicación solo se solicita al pulsar el botón y aceptar el permiso del navegador. Se usa para centrar el mapa; el punto no se guarda hasta que lo confirmes.</p><div class="ra-modal-buttons"><button type="button" class="ra-button ra-button-secondary" data-action="close-dialog">Volver al formulario</button><button type="button" class="ra-button" data-ra-picker="confirm" disabled>Confirmar este punto '+icon('check')+'</button></div></div>';
  }
  async function mountPicker(section,item,onConfirm) {
    if(!section)return;
    let map=null,selected=coords(item),locating=false,tileReady=false,tileFailed=false,loadedTiles=0;
    const status=section.querySelector('.ra-picker-status'),confirm=section.querySelector('[data-ra-picker=confirm]'),loading=section.querySelector('.ra-picker-loading'),retry=section.querySelector('[data-ra-picker=retry]');
    function visibleTiles() {if(!map)return false;const container=map.getContainer();if(!container.getBoundingClientRect)return loadedTiles>0;const box=container.getBoundingClientRect(),x=box.left+box.width/2,y=box.top+box.height/2;return Array.from(container.querySelectorAll('.leaflet-tile-loaded')).some(tile=>{const r=tile.getBoundingClientRect();return r.left<=x&&r.right>=x&&r.top<=y&&r.bottom>=y;});}
    function current() { if(!map)return null;const c=map.wrapLatLng(map.getCenter());return [Math.round(c.lat*1e6)/1e6,Math.round(c.lng*1e6)/1e6]; }
    function position() { if(!map)return;selected=current();confirm.disabled=map.getZoom()<15||!tileReady; if(tileFailed){status.textContent='No pudimos cargar las calles del mapa. Reintenta para revisar el punto antes de confirmarlo.';return;}if(!tileReady){status.textContent='Cargando las calles para revisar el punto…';return;}status.textContent=map.getZoom()<15?'Acerca el mapa hasta ver las calles (nivel 15 o superior). Aún no se guardó ningún punto.':'Punto por confirmar: '+selected.map(v=>v.toFixed(6)).join(', ')+'. Revisa la entrada antes de guardarlo.'; }
    async function start(){
      if(!navigator.onLine){loading.hidden=true;retry.hidden=false;status.textContent='Sin conexión. Puedes volver al formulario sin punto y marcarlo cuando tengas conexión.';return;}
      loading.hidden=false;retry.hidden=true;tileReady=false;tileFailed=false;loadedTiles=0;confirm.disabled=true;
      try{
        await loadLeaflet();if(!section.isConnected)return;
        map=makeMap(section.querySelector('.ra-picker-map'),selected||[4.5709,-74.2973],selected?17:5,null);
        map.raTileLayer.on('loading',()=>{tileReady=false;tileFailed=false;loadedTiles=0;confirm.disabled=true;});
        map.raTileLayer.on('tileload',()=>{loadedTiles++;tileReady=visibleTiles();if(tileReady){tileFailed=false;retry.hidden=true;position();}});
        map.raTileLayer.on('tileerror',()=>{tileReady=visibleTiles();if(!tileReady){tileFailed=true;confirm.disabled=true;retry.hidden=false;status.textContent='No pudimos cargar las calles del mapa. Reintenta antes de confirmar el punto.';}});
        map.raTileLayer.on('load',()=>{tileReady=visibleTiles();tileFailed=!tileReady;if(tileReady)retry.hidden=true;else retry.hidden=false;position();});
        loading.hidden=true;map.on('moveend zoomend',position);map.on('click',event=>{map.panTo(event.latlng,{animate:false});position();});position();
        if(!coords(item))status.textContent='Vista inicial de Colombia, sin punto confirmado. Acerca y mueve el mapa hasta el comercio.';
      }catch(error){loading.hidden=true;retry.hidden=false;status.textContent=error.message;}
    }
    section.addEventListener('click',async event=>{
      const control=event.target.closest('[data-ra-picker]');if(!control)return;
      const action=control.dataset.raPicker;
      if(action==='retry'){if(map){map.remove();liveMaps.delete(map);map=null;}start();}
      if(action==='confirm'&&map&&map.getZoom()>=15&&tileReady){onConfirm(current());}
      if(action==='coordinates'){
        const lat=section.querySelector('#ra-picker-latitude'),lng=section.querySelector('#ra-picker-longitude');
        const point=coords({pickup_latitude:lat.value,pickup_longitude:lng.value});
        if(!point){status.textContent='Indica ambas coordenadas: latitud entre −90 y 90, longitud entre −180 y 180.';lat.focus();return;}
        if(!map){status.textContent='Abre primero el mapa con conexión para revisar el punto antes de confirmarlo.';return;}
        map.setView(point,17,{animate:false});position();
      }
      if(action==='locate'&&!locating){
        if(!map){status.textContent='Abre primero el mapa con conexión.';return;}
        if(!navigator.geolocation){status.textContent='Este navegador no permite consultar la ubicación. Puedes mover el mapa o introducir coordenadas conocidas.';return;}
        locating=true;control.disabled=true;status.textContent='Esperando tu permiso para centrar el mapa…';
        navigator.geolocation.getCurrentPosition(positionResult=>{
          locating=false;if(!section.isConnected)return;control.disabled=false;map.setView([positionResult.coords.latitude,positionResult.coords.longitude],17,{animate:false});position();status.textContent='Vista centrada en tu dispositivo. Mueve el mapa si la entrada está en otro punto y confirma cuando sea correcto.';
        },error=>{locating=false;if(!section.isConnected)return;control.disabled=false;status.textContent=error.code===1?'No se concedió el permiso. Puedes mover el mapa manualmente; no se guardó tu ubicación.':'No pudimos obtener la ubicación. Mueve el mapa manualmente o introduce coordenadas conocidas.';},{enableHighAccuracy:false,timeout:10000,maximumAge:0});
      }
    });
    await start();
  }
  window.RA_PICKUP_MAPS={coords,planHTML,mountPlan,fieldHTML,pickerHTML,mountPicker,destroy,mapsUrl};
})();
