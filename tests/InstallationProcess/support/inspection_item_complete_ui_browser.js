"use strict";

const fs = require("node:fs");
const vm = require("node:vm");

const fixture = JSON.parse(fs.readFileSync(0, "utf8"));
const listeners = new Map();
const sent = [];
const persisted = [];
let queuedAtFirstSend = null;
let acceptedRevision = 0;

class Classes {
    toggle() {}
    add() {}
    remove() {}
    contains() { return false; }
}

class Element {
    constructor(name = "element") {
        this.name = name;
        this.dataset = {};
        this.disabled = false;
        this.hidden = false;
        this.classList = new Classes();
        this.textContent = "";
        this.childNodes = [{textContent: "Пункт"}];
        this.files = [];
    }
    addEventListener(type, listener) {
        listeners.set(`${this.name}:${type}`, listener);
    }
    async activate(type = "click") {
        if (this.disabled) return;
        const listener = listeners.get(`${this.name}:${type}`);
        if (listener) await listener({target: this, preventDefault() {}});
    }
    querySelector(selector) { return element(`${this.name}>${selector}`); }
    querySelectorAll(selector) {
        if (selector === "[data-check-item]") return this.items || [];
        if (selector === "[data-photo-input]") return this.photos || [];
        if (selector === "[data-installer-cancel]") return [];
        if (selector === "input:checked") return [];
        return [];
    }
    setAttribute() {}
    replaceChildren() {}
    append() {}
    focus() {}
    scrollIntoView() {}
    showModal() { this.open = true; }
    close() { this.open = false; }
    closest() { return section; }
}

const elements = new Map();
function element(name) {
    if (!elements.has(name)) elements.set(name, new Element(name));
    return elements.get(name);
}

const root = element("root");
Object.assign(root.dataset, fixture.rootDataset);
const section = element("section");
section.dataset.checkSection = "1";
section.dataset.sectionWeight = "100";
const itemCount = fixture.bulkItemCount || 1;
const items = Array.from({length: itemCount}, (_, index) => {
    const current = element(`item-${index}`);
    current.dataset.checkItem = String(28 + index);
    current.dataset.weight = "1";
    const currentToggle = element(`item-${index}>.fm2-check-toggle`);
    currentToggle.disabled = !fixture.controls.item;
    const currentInstaller = element(`item-${index}>[data-installer-edit]`);
    currentInstaller.disabled = !fixture.controls.installer;
    current.querySelector = selector => selector === ".fm2-check-toggle" ? currentToggle :
        selector === "[data-installer-edit]" ? currentInstaller : element(`item-${index}>${selector}`);
    return current;
});
const item = items[0];
const toggle = element("item-0>.fm2-check-toggle");
const installer = element("item-0>[data-installer-edit]");
const photo = element("photo");
photo.disabled = !fixture.controls.photo;
const bulk = element("section>[data-check-all]");
bulk.disabled = !fixture.controls.bulk;
section.items = items;
section.photos = [photo];

section.querySelector = selector => selector === "[data-check-all]" ? bulk :
    element(`section>${selector}`);
root.querySelector = selector => {
    if (selector === "[data-bulk-dialog]") return element("bulk-dialog");
    if (selector === "[data-installer-dialog]") return element("installer-dialog");
    return element(`root>${selector}`);
};
root.querySelectorAll = selector => selector === "[data-check-section]" ? [section] : [];

function idbResult(value) {
    const request = {};
    queueMicrotask(() => { request.result = value; request.onsuccess?.(); });
    return request;
}
const stores = {
    meta: new Map(), operations: new Map(), photoBlobs: new Map(),
};
if (fixture.deviceId) stores.meta.set("deviceInstallationId", fixture.deviceId);
for (const operation of fixture.initialOperations || []) stores.operations.set(operation.id, {...operation});
const database = {
    objectStoreNames: {contains: name => Object.hasOwn(stores, name)},
    transaction(name) {
        return {objectStore() {
            return {
                get: key => idbResult(stores[name].get(key)),
                getAll: () => idbResult([...stores[name].values()]),
                put(value, key) {
                    stores[name].set(key ?? value.id, value);
                    if (name === "operations") persisted.push({...value});
                    return idbResult(undefined);
                },
            };
        }};
    },
};
const openRequest = {};
const indexedDB = {open() {
    queueMicrotask(() => { openRequest.result = database; openRequest.onsuccess?.(); });
    return openRequest;
}};

const context = {
    document: {
        querySelector: selector => selector === "[data-checklist]" ? root : null,
        createElement: name => new Element(`created-${name}`),
    },
    window: {isSecureContext: false, addEventListener() {}},
    navigator: {onLine: true}, indexedDB,
    crypto: require("node:crypto").webcrypto,
    TextDecoder, TextEncoder, Uint8Array, AbortSignal, URL, Intl, Date,
    atob: value => Buffer.from(value, "base64").toString("binary"),
    btoa: value => Buffer.from(value, "binary").toString("base64"),
    sessionStorage: {getItem() { return null; }, setItem() {}, removeItem() {}},
    location: {href: "https://pilot.example/pilot/objects/4512/checklist"},
    fetch: async (url, options) => {
        sent.push({url, body: options.body ? JSON.parse(options.body) : null});
        if (queuedAtFirstSend === null) queuedAtFirstSend = stores.operations.size;
        await new Promise(resolve => setTimeout(resolve, 2));
        const status = fixture.networkOutcomes?.[sent.length - 1] || "accepted";
        if (status === "accepted") acceptedRevision += 1;
        return {json: async () => ({status, revision: acceptedRevision, message: status === "accepted" ? "" : "synthetic stop"})};
    },
    setTimeout, clearTimeout, queueMicrotask, console,
};

vm.runInNewContext(fixture.source, context, {filename: "checklist.js"});

setTimeout(async () => {
    await bulk.activate();
    if (fixture.activateBulkConfirm) await element("bulk-dialog>[data-bulk-confirm]").activate();
    await installer.activate();
    await photo.activate("change");
    await toggle.activate();
    await new Promise(resolve => setTimeout(resolve, 20));
    process.stdout.write(JSON.stringify({
        persistedTypes: persisted.map(operation => operation.type),
        sentTypes: sent.map(request => request.body?.type).filter(Boolean),
        sentBodies: sent.map(request => request.body).filter(Boolean),
        sentBaseRevisions: sent.map(request => request.body?.baseRevision).filter(Number.isInteger),
        queuedAtFirstSend,
        finalOperationStatuses: [...stores.operations.values()].map(operation => operation.status),
        storedBaseRevisions: [...stores.operations.values()].map(operation => operation.baseRevision),
        syncBannerState: element("root>[data-sync-banner]").dataset.state,
    }));
}, 20);
