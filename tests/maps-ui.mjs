import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';
const source=readFileSync(new URL('../plugin/rescate-alimentos/assets/map-tools.js',import.meta.url),'utf8');
let scriptRequests=0,locationRequests=0;
const context={window:{RA_CONFIG:{assetsUrl:'https://example.test/plugin/assets/'}},navigator:{onLine:true,geolocation:{getCurrentPosition(){locationRequests++;}}},URL,console,document:{createElement(){scriptRequests++;throw new Error('Maps must remain lazy');},getElementById(){return null;}}};
vm.runInNewContext(source,context);
const maps=context.window.RA_PICKUP_MAPS;
const settings={city:'Cali, Colombia'};
const legacy={id:7,title:'Prueba',merchant_name:'Comercio <seguro>',pickup_address:'Calle 5 # 2-10 & local 2',pickup_area:'Centro',status:'delivered'};
test('Loading the module and rendering plans do not load map resources or GPS',()=>{maps.planHTML(legacy,settings);maps.pickerHTML({});assert.equal(scriptRequests,0);assert.equal(locationRequests,0);});
test('Legacy records retain address search without a fabricated point',()=>{const html=maps.planHTML(legacy,settings);assert.match(html,/dirección aún no está ubicada/);assert.doesNotMatch(html,/data-ra-map-action="load"/);const url=new URL(maps.mapsUrl('directions',legacy,settings));assert.equal(url.searchParams.get('destination'),'Calle 5 # 2-10 & local 2, Centro, Cali, Colombia');assert.equal(url.searchParams.get('api'),'1');assert.equal(url.searchParams.has('origin'),false);});
test('Confirmed coordinates preserve zero and replace textual directions destination',()=>{const item={...legacy,pickup_latitude:0,pickup_longitude:0};assert.deepEqual(Array.from(maps.coords(item)),[0,0]);const url=new URL(maps.mapsUrl('directions',item,settings,'walking'));assert.equal(url.searchParams.get('destination'),'0.000000,0.000000');assert.equal(url.searchParams.get('travelmode'),'walking');assert.match(maps.planHTML(item,settings),/Mostrar mapa/);});
test('Unpaired, nonfinite or out of range coordinates never produce a point',()=>{for(const [lat,lng] of [[null,-76],[3,null],[91,-76],[3,-181],['Cali',-76],[Infinity,-76]])assert.equal(maps.coords({pickup_latitude:lat,pickup_longitude:lng}),null);});
test('Only supported explicitly selected travel modes are sent',()=>{for(const mode of ['walking','bicycling','driving'])assert.equal(new URL(maps.mapsUrl('directions',legacy,settings,mode)).searchParams.get('travelmode'),mode);assert.equal(new URL(maps.mapsUrl('directions',legacy,settings,'helicopter')).searchParams.has('travelmode'),false);});
test('Demo donations offer an explicitly illustrative city map without real routes or copying a pickup address',()=>{for(const pin of [{},{pickup_latitude:40,pickup_longitude:-3}]){const item={...legacy,is_demo:true,...pin};const html=maps.planHTML(item,{city:'Otra ciudad'});assert.match(html,/Ver mapa de ejemplo/);assert.match(html,/Mapa de ejemplo · Cali/);assert.match(html,/Vista ilustrativa de la ciudad\. No ubica al comercio ni representa una recogida real\./);assert.match(html,/data-ra-map-action="load"/);assert.doesNotMatch(html,/google\.com|data-ra-map-action="copy"|ra-plan-directions|Otra ciudad/);assert.equal(maps.mapsUrl('directions',item,settings),'https://www.openstreetmap.org/#map=13/3.4516/-76.532');}});
test('Dynamic names and addresses are escaped in plan markup',()=>{const html=maps.planHTML({...legacy,merchant_name:'<img src=x onerror=alert(1)>',pickup_address:'<script>alert(1)</script>'},settings);assert.doesNotMatch(html,/<script>|<img src=x/);assert.match(html,/&lt;script&gt;/);});
test('Optional pin fields remain optional and initially blank',()=>{const html=maps.fieldHTML({});assert.match(html,/name="pickup_latitude" value=""/);assert.match(html,/name="pickup_longitude" value=""/);assert.doesNotMatch(html,/required/);assert.match(html,/Marcar punto en mapa/);});
test('Attaching a pickup plan keeps external services dormant until interaction',()=>{let listeners=0;maps.mountPlan({querySelector(){return null;},addEventListener(){listeners++;}},legacy,settings);assert.equal(listeners,2);assert.equal(scriptRequests,0);assert.equal(locationRequests,0);});
test('Picker requires visible map tiles and recovers after tile failure before confirming',async()=>{
  const tileEvents={},mapEvents={};
  let zoom=5,center={lat:4.5709,lng:-74.2973},confirmed=null;
  const map={zoomControl:{setPosition(){}},setView(point,z){center={lat:point[0],lng:point[1]};zoom=z;return this;},getCenter(){return center;},wrapLatLng(value){return value;},getZoom(){return zoom;},on(name,callback){name.split(' ').forEach(event=>mapEvents[event]=callback);return this;},panTo(point){center=point;},remove(){},getContainer(){return nodes.map;}};
  const layer={on(event,callback){tileEvents[event]=callback;return this;},addTo(){return this;}};
  context.window.L={map(){return map;},tileLayer(){return layer;}};
  context.document.getElementById=id=>id==='ra-leaflet-css'?{sheet:{},dataset:{raMapReady:'1'}}:null;
  const nodes={status:{textContent:''},confirm:{disabled:true},loading:{hidden:false},retry:{hidden:true},map:{},latitude:{value:''},longitude:{value:''}};
  let click;
  const section={isConnected:true,querySelector(selector){return ({'.ra-picker-status':nodes.status,'[data-ra-picker=confirm]':nodes.confirm,'.ra-picker-loading':nodes.loading,'[data-ra-picker=retry]':nodes.retry,'.ra-picker-map':nodes.map,'#ra-picker-latitude':nodes.latitude,'#ra-picker-longitude':nodes.longitude})[selector];},addEventListener(name,callback){if(name==='click')click=callback;}};
  await maps.mountPicker(section,{},point=>confirmed=point);
  map.setView([3.4516,-76.532],17);mapEvents.moveend();
  assert.equal(nodes.confirm.disabled,true,'Zoom alone must not enable confirmation before tiles');
  tileEvents.tileerror();assert.equal(nodes.retry.hidden,false);assert.equal(nodes.confirm.disabled,true);assert.match(nodes.status.textContent,/No pudimos cargar/);
  await click({target:{closest(){return {dataset:{raPicker:'confirm'}};}}});assert.equal(confirmed,null);
  tileEvents.loading();tileEvents.tileload();tileEvents.load();assert.equal(nodes.confirm.disabled,false);assert.equal(nodes.retry.hidden,true);
  await click({target:{closest(){return {dataset:{raPicker:'confirm'}};}}});assert.deepEqual(Array.from(confirmed),[3.4516,-76.532]);
  assert.equal(locationRequests,0,'The picker must not request device location automatically');
});


// A small DOM/Leaflet boundary harness verifies lazy mounting without a browser.
function planHarness(item,{preloaded=true,online=true}={}) {
  const created=[],frames=[],mapsCreated=[],layers=[],requests={gps:0,clipboard:0,marker:0,circle:0};
  function element(tag='div') {return {tagName:tag,dataset:{},isConnected:true,textContent:'',hidden:false,disabled:false,children:[],append(...nodes){this.children.push(...nodes);},appendChild(node){this.children.push(node);},remove(){this.removed=true;this.isConnected=false;}};}
  const mapNode=element(),status=element(),feedback=element(),retry=element('button'),action=element('button');
  const statusClasses=new Set();status.classList={add:value=>statusClasses.add(value),contains:value=>statusClasses.has(value)};
  action.dataset.raMapAction='load';retry.dataset.raMapAction='load';retry.hidden=true;
  const slot={innerHTML:'',querySelector(selector){return selector==='.ra-route-map'?mapNode:selector==='.ra-map-status'?status:retry;},appendChild(){}};
  const listeners={};
  const section={isConnected:true,contains:node=>node===mapNode,querySelector(selector){return selector==='.ra-plan-feedback'?feedback:selector==='.ra-plan-map-slot'?slot:selector==='[data-ra-map-action=load]'?(slot.innerHTML?retry:action):null;},addEventListener(name,callback){listeners[name]=callback;}};
  const L={map(node){const map={node,point:null,zoom:null,invalidated:0,zoomControl:{setPosition(){}},setView(point,zoom){this.point=Array.from(point);this.zoom=zoom;return this;},getContainer(){return node;},invalidateSize(options){this.invalidated++;this.invalidateOptions=options;},remove(){this.removed=true;}};mapsCreated.push(map);return map;},tileLayer(){const callbacks={};const layer={on(event,callback){(callbacks[event] ||= []).push(callback);return this;},emit(event){for(const callback of callbacks[event]||[])callback();},addTo(){return this;}};layers.push(layer);return layer;},circleMarker(){requests.circle++;return {addTo(){return this;}};},divIcon(options){return options;},marker(){requests.marker++;return {addTo(){return this;},bindPopup(){return this;}};}};
  const runtime={window:{RA_CONFIG:{assetsUrl:'https://example.test/plugin/assets/',assetVersions:{leafletJs:'test-js',leafletCss:'test-css'}},requestAnimationFrame(callback){frames.push(callback);}},navigator:{onLine:online,geolocation:{getCurrentPosition(){requests.gps++;}},clipboard:{async writeText(){requests.clipboard++;}}},URL,console,document:{getElementById(id){return created.find(node=>node.id===id&&!node.removed)||null;},createElement:element,head:{appendChild(node){created.push(node);}}}};
  if(preloaded){runtime.window.L=L;created.push({...element('link'),id:'ra-leaflet-css',sheet:{}});}
  vm.runInNewContext(source,runtime);
  runtime.window.RA_PICKUP_MAPS.mountPlan(section,item,settings);
  return {runtime,L,created,frames,mapsCreated,layers,requests,section,slot,status,feedback,action,retry,click(control=action){return listeners.click({target:{closest(){return control;}}});},change(value){return listeners.change({target:{id:'ra-route-transport',value}});}};
}

test('Demo map mounts public Cali view with or without supplied demo coordinates and never adds a pickup marker or GPS',async()=>{
  for(const pin of [{},{pickup_latitude:40,pickup_longitude:-3}]){
    const h=planHarness({...legacy,is_demo:true,...pin});
    assert.equal(h.mapsCreated.length,0,'No map is mounted before a click');
    await h.click();
    assert.deepEqual(h.mapsCreated[0].point,[3.4516,-76.532]);assert.equal(h.mapsCreated[0].zoom,13);
    assert.equal(h.requests.marker,0);assert.equal(h.requests.circle,0);assert.equal(h.requests.gps,0);
    assert.match(h.slot.innerHTML,/aria-label="Mapa de ejemplo · Cali"/);assert.match(h.slot.innerHTML,/Ampliar mapa de ejemplo/);
    assert.match(h.slot.innerHTML,/openstreetmap\.org\/#map=13\/3\.4516\/-76\.532/);assert.doesNotMatch(h.slot.innerHTML,/google\.com|marker=|mlat=|mlon=/);
    h.layers[0].emit('load');assert.match(h.status.textContent,/Mapa de ejemplo · Cali/);assert.doesNotMatch(h.status.textContent,/punto fue indicado/);
    await h.click({dataset:{raMapAction:'copy'}});assert.equal(h.requests.clipboard,0);
    assert.equal(h.mapsCreated[0].invalidated,0);for(const frame of h.frames)frame();assert.equal(h.mapsCreated[0].invalidated,1);
  }
});

test('Real pickup map retains its saved point, marker and directions behavior',async()=>{
  const h=planHarness({...legacy,pickup_latitude:3.42,pickup_longitude:-76.51});await h.click();
  assert.deepEqual(h.mapsCreated[0].point,[3.42,-76.51]);assert.equal(h.mapsCreated[0].zoom,16);
  assert.equal(h.requests.marker,1);assert.equal(h.requests.circle,1);assert.equal(h.requests.gps,0);
  assert.match(h.slot.innerHTML,/google\.com/);assert.doesNotMatch(h.slot.innerHTML,/Ampliar mapa de ejemplo/);
  h.layers[0].emit('load');assert.match(h.status.textContent,/punto fue indicado/);
});

test('Demo tile failures offer retry and avoid suggesting a real route',async()=>{
  const h=planHarness({...legacy,is_demo:true});await h.click();h.layers[0].emit('tileerror');
  assert.equal(h.retry.hidden,false);assert.match(h.status.textContent,/mapa de ejemplo no cargó/);assert.doesNotMatch(h.status.textContent,/ruta|ubicación en Maps/);
  await h.click(h.retry);assert.equal(h.mapsCreated[0].removed,true);assert.equal(h.mapsCreated.length,2);assert.equal(h.requests.marker,0);
});

test('An offline demo keeps its map action available without a real pickup-address message',async()=>{
  const h=planHarness({...legacy,is_demo:true},{online:false});await h.click();
  assert.equal(h.mapsCreated.length,0);assert.equal(h.action.disabled,false);assert.match(h.feedback.textContent,/Sin conexión.*mapa de ejemplo/);assert.doesNotMatch(h.feedback.textContent,/dirección sigue disponible/);
});

test('Leaflet waits for local CSS and recovers when the stylesheet initially fails',async()=>{
  const h=planHarness({...legacy,is_demo:true},{preloaded:false});const first=h.click();
  const css1=h.created.find(node=>node.tagName==='link'),script=h.created.find(node=>node.tagName==='script');
  assert.match(css1.href,/ver=test-css/);assert.match(script.src,/ver=test-js/);
  h.runtime.window.L=h.L;script.onload();css1.onerror();await first;
  assert.equal(h.mapsCreated.length,0);assert.equal(h.action.disabled,false);assert.equal(css1.removed,true);
  const second=h.click();const css2=h.created.filter(node=>node.tagName==='link').at(-1);assert.notEqual(css1,css2);assert.equal(h.mapsCreated.length,0);
  css2.onload();await second;assert.equal(h.mapsCreated.length,1);
});

test('Leaflet script rejection is reset so a second explicit click can load a valid map',async()=>{
  const h=planHarness({...legacy,is_demo:true},{preloaded:false});const first=h.click();
  h.created.find(node=>node.tagName==='link').onload();const script1=h.created.find(node=>node.tagName==='script');script1.onerror();await first;
  assert.equal(h.mapsCreated.length,0);assert.equal(h.action.disabled,false);
  const second=h.click();const script2=h.created.filter(node=>node.tagName==='script').at(-1);assert.notEqual(script1,script2);
  h.runtime.window.L=h.L;script2.onload();await second;assert.equal(h.mapsCreated.length,1);assert.equal(h.requests.gps,0);
});
