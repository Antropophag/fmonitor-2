"use strict";

const fs = require("fs");
const vm = require("vm");

class Node {
  constructor(tag = "div") {
    this.nodeType = 1;
    this.tagName = tag.toUpperCase();
    this.children = [];
    this.attributes = new Map();
    this.dataset = {};
    this.listeners = new Map();
    this.className = "";
    this.textContent = "";
    this.value = "";
    this.hidden = false;
    this.parentNode = null;
  }
  setAttribute(name, value) {
    this.attributes.set(name, String(value));
    if (name === "hidden") this.hidden = true;
    if (name.startsWith("data-")) this.dataset[name.slice(5).replace(/-([a-z])/g, (_, c) => c.toUpperCase())] = String(value);
  }
  getAttribute(name) { return this.attributes.has(name) ? this.attributes.get(name) : null; }
  removeAttribute(name) { this.attributes.delete(name); if (name === "hidden") this.hidden = false; }
  append(...nodes) { for (const node of nodes) { node.parentNode = this; this.children.push(node); } }
  replaceChildren(...nodes) { this.children = []; this.append(...nodes); }
  cloneNode() { const copy = new Node(this.tagName); copy.className = this.className; copy.textContent = this.textContent; return copy; }
  addEventListener(type, listener) { if (!this.listeners.has(type)) this.listeners.set(type, []); this.listeners.get(type).push(listener); }
  dispatch(type, init = {}) { const event = { type, key: init.key, target: this, preventDefault() {} }; for (const listener of this.listeners.get(type) || []) listener(event); }
  focus() { document.activeElement = this; }
  matches(selector) { return selector === ":popover-open" ? !this.hidden : false; }
  querySelector(selector) { return this.selectors?.get(selector) || null; }
  querySelectorAll(selector) {
    if (selector === "[data-id]") return this.children.filter((child) => child.dataset.id !== undefined);
    return [];
  }
  get classList() { return { toggle: () => {} }; }
}

class TextNode {
  constructor(data) { this.nodeType = 3; this.data = data; this.textContent = data; }
}

let document;

function record(data) {
  const node = new Node("span");
  for (const [name, value] of Object.entries(data)) node.setAttribute(`data-${name}`, value);
  return node;
}

function fixture(records) {
  const root = new Node();
  const template = new Node("template");
  template.content = new Node("fragment");
  template.content.childNodes = records;
  template.content.children = records.filter((node) => node.nodeType === 1);
  const selection = new Node();
  const modalSelection = new Node();
  const inputs = new Node();
  const dialog = new Node(); dialog.hidden = true;
  const opener = new Node("button"); opener.hidden = true; opener.setAttribute("aria-expanded", "false");
  const fallback = new Node("p"); fallback.hidden = false;
  const search = new Node("input");
  const results = new Node();
  const meta = new Node();
  const count = new Node();
  root.selectors = new Map([
    ["[data-picker-data]", template], ["[data-picker-selection]", selection],
    ["[data-picker-modal-selection]", modalSelection], ["[data-picker-inputs]", inputs],
    [".fm2-picker-dialog", dialog], ["[data-picker-dialog]", dialog],
    ["[data-picker-open]", opener], ["[data-picker-fallback]", fallback],
    ["[data-picker-search]", search], ["[data-picker-results]", results],
    ["[data-picker-meta]", meta], ["[data-picker-count]", count],
  ]);
  const registry = new Map([["installer-picker", dialog]]);
  document = {
    activeElement: null,
    querySelector: (selector) => selector === "[data-installer-picker]" ? root : null,
    createElement: (tag) => new Node(tag),
    getElementById: (id) => registry.get(id) || null,
  };
  return { root, template, selection, modalSelection, inputs, dialog, opener, fallback, search, results, meta, count };
}

function execute(source, records) {
  const ui = fixture(records);
  const context = { document, console, globalThis: null };
  context.globalThis = context;
  vm.runInNewContext(source, context, { filename: "picker.js" });
  return ui;
}

function equal(actual, expected, why) {
  if (JSON.stringify(actual) !== JSON.stringify(expected)) throw new Error(`${why}: expected ${JSON.stringify(expected)}, actual ${JSON.stringify(actual)}`);
}
function ok(value, why) { if (!value) throw new Error(why); }

const source = fs.readFileSync(process.argv[2], "utf8");
const base = [
  record({ id: "1042", name: "ИВАНОВ\t  Иван", tab: "001042", position: "Монтажник", busy: "", selected: "0" }),
  new TextNode("\n  "),
  record({ id: "2088", name: "Петров Пётр", tab: "002088", position: "Монтажник", busy: "", selected: "0" }),
];
for (let i = 1; i <= 22; i++) base.push(record({ id: String(3000 + i), name: `Тест ${String(i).padStart(2, "0")}`, tab: String(3000 + i).padStart(6, "0"), position: "Монтажник", busy: "", selected: "0" }));

const ui = execute(source, base);
equal([ui.opener.hidden, ui.dialog.hidden, ui.fallback.hidden], [false, true, true], "successful initialization atomically enables picker and hides fallback");
equal(ui.inputs.children.length, 0, "initial hidden IDs");

ui.search.value = " \tИвАНОВ\r\n иВаН "; ui.search.dispatch("input");
equal(ui.results.children.length, 1, "exact whitespace and ru-RU lowercase name normalization");
equal(ui.results.children[0].getAttribute("aria-pressed"), "false", "initial result pressed state");
equal(ui.results.children[0].getAttribute("aria-label"), "Выбрать ИВАНОВ\t  Иван", "result accessible select name");

ui.search.value = "😀"; ui.search.dispatch("input");
equal(ui.meta.textContent, "Введите минимум 2 символа", "Unicode code-point minimum");
equal(ui.results.children.length, 0, "one supplementary code point has no results");

ui.search.value = "١٠"; ui.search.dispatch("input");
equal(ui.results.children.length, 0, "non-ASCII decimal digits do not enter tab query");
ui.search.value = "10"; ui.search.dispatch("input");
equal(ui.results.children.length, 1, "ASCII digit substring matches six-digit tab");

ui.search.value = "тест"; ui.search.dispatch("input");
equal(ui.results.children.length, 20, "results capped at twenty");
equal(ui.meta.textContent, "Найдено 22. Показаны первые 20", "bounded result meta");

ui.search.value = "иванов иван"; ui.search.dispatch("input");
const first = ui.results.children[0]; first.dispatch("click");
equal(ui.inputs.children.map((input) => [input.type, input.name, input.value]), [["hidden", "installerTabIds[]", "1042"]], "selection synchronizes exact hidden ID");
equal(ui.count.textContent, "Выбрано: 1", "selection live count");
equal(ui.results.children[0].getAttribute("aria-pressed"), "true", "selected result pressed state");
equal(ui.results.children[0].getAttribute("aria-label"), "Убрать ИВАНОВ\t  Иван", "selected result accessible remove name");
equal(document.activeElement, ui.search, "result rerender returns focus to search");

ui.opener.dispatch("click");
equal(ui.opener.getAttribute("aria-expanded"), "true", "opener expanded state");
equal(document.activeElement, ui.search, "open focuses search");
ui.dialog.dispatch("keydown", { key: "Escape" });
equal(ui.opener.getAttribute("aria-expanded"), "false", "Escape collapses picker");
equal(document.activeElement, ui.opener, "Escape returns focus to opener");

const malformedSets = [
  [record({ id: "1042", name: "Иванов", tab: "001042", position: "Монтажник", busy: "", selected: "0" }), new Node("div")],
  [record({ id: "1042", name: "Иванов", tab: "001042", position: "Монтажник", busy: "", selected: "0" })],
  [record({ id: "1042", name: "Иванов", tab: "001042", position: "Монтажник", busy: "", selected: "0" }), new TextNode("forbidden")],
  [record({ id: "1042", name: "Иванов", tab: "001042", position: "Монтажник", busy: "занят", selected: "0" })],
  [record({ id: "0", name: "Иванов", tab: "000000", position: "Монтажник", busy: "", selected: "0" })],
  [record({ id: "1000000", name: "Иванов", tab: "1000000", position: "Монтажник", busy: "", selected: "0" })],
  [record({ id: "1042", name: "Иванов", tab: "001043", position: "Монтажник", busy: "", selected: "0" })],
  [record({ id: "1042", name: "Иванов", tab: "001042", position: "Монтажник", busy: "", selected: "x" })],
  [record({ id: "1042", name: "Иванов", tab: "001042", position: "Монтажник", selected: "0" })],
  [record({ id: "1042", name: " Иванов", tab: "001042", position: "Монтажник", busy: "", selected: "0" })],
  [record({ id: "1042", name: "Иванов", tab: "001042", position: "", busy: "", selected: "0" })],
  [record({ id: "1042", name: "Иванов", tab: "001042", position: "Монтажник", busy: "", selected: "0" }), record({ id: "1042", name: "Другой", tab: "001042", position: "Монтажник", busy: "", selected: "0" })],
  [record({ id: "2088", name: "Петров", tab: "002088", position: "Монтажник", busy: "", selected: "0" }), record({ id: "1042", name: "Иванов", tab: "001042", position: "Монтажник", busy: "", selected: "0" })],
];
malformedSets[1][0].textContent = "forbidden";
for (const records of malformedSets) {
  const rejected = execute(source, records);
  equal([rejected.opener.hidden, rejected.dialog.hidden, rejected.fallback.hidden], [true, true, false], "invalid delivered data stays fail closed");
  equal(rejected.inputs.children.length, 0, "invalid delivered data creates no hidden IDs");
}

ok(!source.includes("innerHTML"), "picker must not use innerHTML");
process.stdout.write("prepare picker client contract: PASS\n");
