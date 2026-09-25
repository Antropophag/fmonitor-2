import assert from 'node:assert/strict';
import vm from 'node:vm';
import {readFileSync} from 'node:fs';

// YII2-INSPECTION-JOURNEY-001 A7: the offline worker owns checklist
// documents, never unrelated pilot navigations.
class MemoryCache {
  constructor() { this.values = new Map(); }
  key(value) { return typeof value === 'string' ? value : value.url; }
  async put(key, value) { this.values.set(this.key(key), value.clone()); }
  async match(key) { return this.values.get(this.key(key))?.clone(); }
  async delete(key) { return this.values.delete(this.key(key)); }
}

const stores = new Map();
const listeners = new Map();
const caches = {
  async open(name) {
    if (!stores.has(name)) stores.set(name, new MemoryCache());
    return stores.get(name);
  },
  async keys() { return [...stores.keys()]; },
  async delete(name) { return stores.delete(name); },
};

let offline = true;
let skipWaitingCalls = 0;
let claimCalls = 0;
const fetch = async input => {
  if (offline) throw new TypeError('offline');
  const url = new URL(typeof input === 'string' ? input : input.url, 'https://pilot.test');
  return new Response(`<main data-checklist data-user-id="17" data-path="${url.pathname}"></main>`, {
    headers: {'content-type': 'text/html; charset=UTF-8'},
  });
};
const self = {
  location: new URL('https://pilot.test/pilot/assets/checklist-sw.js'),
  clients: {claim: async () => { claimCalls++; }},
  skipWaiting: async () => { skipWaitingCalls++; },
  addEventListener: (name, handler) => listeners.set(name, handler),
};
vm.runInNewContext(
  readFileSync(new URL('../../app/YiiRuntime/Assets/checklist-sw.js', import.meta.url), 'utf8'),
  {self, caches, fetch, URL, Response, Request, TypeError},
);

const lifecycle = async type => {
  let work;
  listeners.get(type)({waitUntil(value) { work = value; }});
  await work;
};
const preservedPath = '/pilot/objects/9999/checklist';
await (await caches.open('fmonitor2-checklist-doc-v7-17')).put(
  preservedPath,
  new Response('<main data-checklist data-user-id="17"></main>', {headers: {'content-type': 'text/html'}}),
);
await (await caches.open('fmonitor2-checklist-doc-v6-17')).put(preservedPath, new Response('stale'));
await lifecycle('install');
await lifecycle('activate');
assert.equal(skipWaitingCalls, 1, 'updated worker requests immediate activation');
assert.equal(claimCalls, 1, 'updated worker claims existing clients');
assert.ok(await (await caches.open('fmonitor2-checklist-doc-v7-17')).match(preservedPath), 'current checklist cache survives worker update');
assert.equal(stores.has('fmonitor2-checklist-doc-v6-17'), false, 'stale checklist generation is removed');

const navigation = async path => {
  let response;
  listeners.get('fetch')({
    request: {url: `https://pilot.test${path}`, method: 'GET', mode: 'navigate'},
    respondWith(value) { response = value; },
  });
  return {controlled: response !== undefined, response: response === undefined ? undefined : await response};
};

for (const path of [
  '/pilot/',
  '/pilot/login',
  '/pilot/objects',
  '/pilot/objects/4512',
  '/pilot/construction-control',
  '/pilot/installers',
  '/pilot/dashboard',
  '/pilot/objects/4512/checklist/operations',
  '/pilot/objects/4512/checklist/history',
  '/pilot/objects/0/checklist',
  '/pilot/objects/04512/checklist',
  '/pilot/construction-control/objects/4512/checklist/operations',
  '/pilot/construction-control/objects/4512/checklist/history',
  '/pilot/construction-control/objects/0/checklist',
  '/pilot/construction-control/objects/04512/checklist',
]) {
  assert.equal((await navigation(path)).controlled, false, `${path} must remain browser-owned`);
}

for (const path of [
  '/pilot/objects/4512/checklist',
  '/pilot/construction-control/objects/4512/checklist',
]) {
  const result = await navigation(path);
  assert.equal(result.controlled, true, `${path} must remain worker-controlled`);
  assert.equal(result.response.status, 503, `${path} retains offline fallback`);
}

offline = false;
const online = await navigation('/pilot/objects/4512/checklist');
assert.equal(online.response.status, 200, 'online checklist remains available through worker');

console.log('PASS: YII2-INSPECTION-JOURNEY-001 checklist-only worker navigation');
