import assert from 'node:assert/strict';
import vm from 'node:vm';
import {readFileSync} from 'node:fs';

class MemoryCache {
  constructor(){this.values=new Map()}
  key(value){return typeof value==='string'?value:value.url}
  async put(key,value){this.values.set(this.key(key),value.clone())}
  async match(key){const value=this.values.get(this.key(key));return value?.clone()}
  async delete(key){return this.values.delete(this.key(key))}
}
const stores=new Map(),listeners=new Map();
const caches={
  async open(name){if(!stores.has(name))stores.set(name,new MemoryCache());return stores.get(name)},
  async keys(){return [...stores.keys()]},
  async delete(name){return stores.delete(name)},
};
let currentUser='17',offline=false;
const fetch=async input=>{
  const url=new URL(typeof input==='string'?input:input.url,'https://pilot.test');
  if(offline)throw new TypeError('offline');
  if(url.pathname.startsWith('/pilot/assets/'))return new Response('asset',{headers:{'content-type':'text/javascript'}});
  if(url.pathname==='/pilot/logout')return new Response('',{status:303,headers:{location:'/pilot/login'}});
  return new Response(`<main data-control-queue data-user-id="${currentUser}"></main>`,{headers:{'content-type':'text/html; charset=UTF-8'}});
};
const self={location:new URL('https://pilot.test/pilot/assets/checklist-sw.js'),clients:{claim:async()=>{}},skipWaiting:async()=>{},addEventListener:(name,handler)=>listeners.set(name,handler)};
vm.runInNewContext(readFileSync(new URL('../app/PilotHttp/checklist-sw.js',import.meta.url),'utf8'),{self,caches,fetch,URL,Response,Request,TypeError});
const waitEvent=async(type,event)=>{let work;listeners.get(type)({...event,waitUntil:value=>{work=value},respondWith:value=>{work=value}});return await work};
const checklist='https://pilot.test/pilot/construction-control/objects/4512/checklist';

await waitEvent('message',{data:{type:'CACHE_CHECKLIST',url:checklist}});
assert.equal(await (await stores.get('fmonitor2-checklist-shell-v7').match('/pilot/__offline-active-user__')).text(),'17');
assert.ok(await stores.get('fmonitor2-checklist-doc-v7-17').match('/pilot/construction-control/objects/4512/checklist'));

currentUser='23';
await waitEvent('fetch',{request:{url:'https://pilot.test/pilot/construction-control',method:'GET',mode:'navigate'}});
assert.equal(stores.has('fmonitor2-checklist-doc-v7-17'),false,'a successful authenticated user change purges the prior document cache');
offline=true;
const stale=await waitEvent('fetch',{request:{url:checklist,method:'GET',mode:'navigate'}});
assert.equal(stale.status,503,'a different user can never receive the previous user checklist while offline');

offline=false;
await waitEvent('message',{data:{type:'CACHE_CHECKLIST',url:checklist}});
offline=true;
const own=await waitEvent('fetch',{request:{url:checklist,method:'GET',mode:'navigate'}});
assert.equal(own.status,200);
assert.match(await own.text(),/data-user-id="23"/);

offline=false;
await waitEvent('fetch',{request:new Request('https://pilot.test/pilot/logout',{method:'POST'})});
assert.equal(stores.has('fmonitor2-checklist-doc-v7-23'),false,'logout purges checklist documents');
console.log('Checklist offline namespace behavior OK.');
