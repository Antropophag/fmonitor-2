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
  getAttributeNames() { return Array.from(this.attributes.keys()); }
  removeAttribute(name) { this.attributes.delete(name); if (name === "hidden") this.hidden = false; }
  append(...nodes) { for (const node of nodes) { node.parentNode = this; this.children.push(node); } }
  replaceChildren(...nodes) { this.children = []; this.append(...nodes); }
  cloneNode() { const copy = new Node(this.tagName); copy.className = this.className; copy.textContent = this.textContent; return copy; }
  addEventListener(type, listener) { if (!this.listeners.has(type)) this.listeners.set(type, []); this.listeners.get(type).push(listener); }
  dispatch(type, init = {}) { const event = { type, key: init.key, target: this, defaultPrevented: false, preventDefault() { this.defaultPrevented = true; } }; for (const listener of this.listeners.get(type) || []) listener(event); return event; }
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

function fixture(records, provenanceRows = null) {
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
    querySelector: (selector) => selector === "[data-installer-picker]" ? root : (selector === "[data-picker-provenance]" ? provenanceRows : null),
    createElement: (tag) => new Node(tag),
    getElementById: (id) => registry.get(id) || null,
  };
  return { root, template, selection, modalSelection, inputs, dialog, opener, fallback, search, results, meta, count };
}

function execute(source, records, provenanceRows = null) {
  const ui = fixture(records, provenanceRows);
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
  new TextNode("\t\n\r "),
  record({ id: "2088", name: "Петров Пётр", tab: "002088", position: "Монтажник", busy: "", selected: "0" }),
];
for (let i = 1; i <= 22; i++) base.push(record({ id: String(3000 + i), name: `Тест ${String(i).padStart(2, "0")}`, tab: String(3000 + i).padStart(6, "0"), position: "Монтажник", busy: "", selected: "0" }));
base.push(record({ id: "999999", name: "Я".repeat(300), tab: "999999", position: "Ю".repeat(160), busy: "", selected: "0" }));

const ui = execute(source, base);
equal([ui.opener.hidden, ui.dialog.hidden, ui.fallback.hidden], [false, true, true], "successful initialization atomically enables picker and hides fallback");
equal(ui.inputs.children.length, 0, "initial hidden IDs");
const mixedPeople = [record({ id: "1042", name: "Иванов", tab: "001042", position: "Монтажник", busy: "", selected: "0" }), record({ id: "2088", name: "Петров", tab: "002088", position: "Монтажник", busy: "", selected: "0" })];
const provenanceData = [{ id: "1042", name: "Иванов", source: "one_c", updatedAt: "2026-08-27T18:15:00+03:00" }, { id: "2088", name: "Петров", source: "alternate", updatedAt: "2026-08-27T17:15:00+03:00" }];
function provenanceRow(data, tag = "li") { const row = new Node(tag); row.setAttribute("data-id", data.id); row.setAttribute("data-source", data.source); row.setAttribute("data-updated-at", data.updatedAt); row.textContent = `${data.name} · Источник кадровых данных: ${data.source} · Актуально на: ${data.updatedAt}`; return row; }
function provenanceList(rows = provenanceData.map((data) => provenanceRow(data))) { const list = new Node("ul"); list.childNodes = rows;list.children = rows.filter((row) => row.nodeType === 1);for (const row of rows) row.parentNode = list;return list; }
const mixedProvenance = provenanceList();
equal(mixedProvenance.children.map((row) => [row.tagName, row.getAttributeNames().sort(), row.dataset.id, row.textContent]), [["LI", ["data-id", "data-source", "data-updated-at"], "1042", "Иванов · Источник кадровых данных: one_c · Актуально на: 2026-08-27T18:15:00+03:00"], ["LI", ["data-id", "data-source", "data-updated-at"], "2088", "Петров · Источник кадровых данных: alternate · Актуально на: 2026-08-27T17:15:00+03:00"]], "positive provenance exact rows cardinality order attributes IDs and text");
const mixedUi = execute(source, mixedPeople, mixedProvenance);
equal(mixedProvenance.hidden, true, "validated mixed provenance fallback list hidden after initialization");
mixedUi.search.value = "иванов"; mixedUi.search.dispatch("input");
const mixedText = mixedUi.results.children[0].children[1];
equal(mixedText.children[2].className, "fm2-picker-result-provenance", "dynamic result provenance follows name and details");
equal(mixedText.children[2].textContent, "Источник кадровых данных: one_c · Актуально на: 2026-08-27T18:15:00+03:00", "dynamic result exact associated provenance");
mixedUi.search.value = "петров"; mixedUi.search.dispatch("input");const secondMixedText = mixedUi.results.children[0].children[1];equal(secondMixedText.children[2].className, "fm2-picker-result-provenance", "second dynamic provenance follows name and details");equal(secondMixedText.children[2].textContent, "Источник кадровых данных: alternate · Актуально на: 2026-08-27T17:15:00+03:00", "second dynamic result exact associated provenance");
const provenanceMutations = [];
provenanceMutations.push(provenanceList([provenanceRow(provenanceData[0])]));
provenanceMutations.push(provenanceList([...provenanceData.map((data) => provenanceRow(data)), provenanceRow({ id: "3000", name: "Лишний", source: "x", updatedAt: "2026-08-27T16:00:00+03:00" })]));
provenanceMutations.push(provenanceList([...provenanceData].reverse().map((data) => provenanceRow(data))));
provenanceMutations.push(provenanceList([provenanceRow(provenanceData[0]), new TextNode("forbidden"), provenanceRow(provenanceData[1])]));
for (const mutate of [
  (row) => row.setAttribute("data-extra", "x"),
  (row) => { row.attributes.delete("data-source"); delete row.dataset.source; },
  (row) => { row.tagName = "DIV"; },
  (row) => row.append(new Node("b")),
  (row) => { row.textContent += " forbidden"; },
  (row) => { row.dataset.id = "9999"; row.attributes.set("data-id", "9999"); },
  (row) => { row.dataset.source = "wrong"; row.attributes.set("data-source", "wrong"); },
  (row) => { row.dataset.updatedAt = "wrong"; row.attributes.set("data-updated-at", "wrong"); },
]) { const rows = provenanceData.map((data) => provenanceRow(data));mutate(rows[1]);provenanceMutations.push(provenanceList(rows)); }
for (const [index, invalid] of provenanceMutations.entries()) { const rejected = execute(source, mixedPeople, invalid);equal([rejected.opener.hidden, rejected.fallback.hidden, invalid.hidden, rejected.inputs.children.length], [true, false, false, 0], `malformed provenance association ${index + 1} stays atomically fail closed`); }
const equalNameTie = execute(source, [
  record({ id: "99", name: "Одинаковое Имя", tab: "000099", position: "Монтажник", busy: "", selected: "0" }),
  record({ id: "100", name: "Одинаковое Имя", tab: "000100", position: "Монтажник", busy: "", selected: "0" }),
]);
equal([equalNameTie.opener.hidden, equalNameTie.fallback.hidden], [false, true], "client accepts equal-name numeric-ID ascending tie order");

ui.search.value = " \tИвАНОВ\r\n иВаН "; ui.search.dispatch("input");
equal(ui.results.children.length, 1, "exact whitespace and ru-RU lowercase name normalization");
equal(ui.results.children[0].getAttribute("aria-pressed"), "false", "initial result pressed state");
equal(ui.results.children[0].getAttribute("aria-label"), "Выбрать ИВАНОВ\t  Иван", "result accessible select name");

ui.search.value = "😀"; ui.search.dispatch("input");
equal(ui.meta.textContent, "Введите минимум 2 символа", "Unicode code-point minimum");
equal(ui.results.children.length, 0, "one supplementary code point has no results");

ui.search.value = "١٠"; ui.search.dispatch("input");
equal(ui.results.children.length, 1, "zero-result grammar has one direct child");
equal(ui.results.children[0].tagName, "P", "zero-result child is p");
equal(ui.results.children[0].textContent, "Ничего не найдено. Проверьте ФИО или табельный номер.", "zero-result exact copy");
equal(ui.meta.textContent, "Найдено: 0", "non-ASCII decimal digits do not enter tab query");
const tabUi = execute(source, base);
tabUi.search.value = "10"; tabUi.search.dispatch("input");
equal(tabUi.results.children.length, 2, "ASCII digit substring matches both literal six-digit tabs");
tabUi.results.children[0].dispatch("click");
tabUi.results.children[1].dispatch("click");
equal(tabUi.inputs.children.map((input) => input.value), ["1042", "3010"], "ASCII tab matches preserve literal fixture ID order");

ui.search.value = "монтажник"; ui.search.dispatch("input");
equal(ui.results.children.length, 1, "position is excluded from search and produces zero-result p");
equal(ui.results.children[0].tagName, "P", "position exclusion exact zero result");

ui.search.value = "тест"; ui.search.dispatch("input");
equal(ui.results.children.length, 20, "results capped at twenty");
equal(ui.meta.textContent, "Найдено 22. Показаны первые 20", "bounded result meta");

ui.search.value = "иванов иван"; ui.search.dispatch("input");
const first = ui.results.children[0]; first.dispatch("click");
equal(ui.inputs.children.map((input) => [input.type, input.name, input.value]), [["hidden", "installerTabIds[]", "1042"]], "selection synchronizes exact hidden ID");
equal(ui.count.textContent, "Выбрано: 1", "selection live count");
equal([ui.selection.children.length, ui.modalSelection.children.length], [1, 1], "both selection summaries update");
equal([ui.selection.children[0].getAttribute("aria-label"), ui.modalSelection.children[0].getAttribute("aria-label")], ["Убрать ИВАНОВ\t  Иван", "Убрать ИВАНОВ\t  Иван"], "both summaries expose removal name");
ok(!ui.selection.children[0].textContent.includes("Монтажник") && !ui.selection.children[0].textContent.includes("занят"), "chip excludes position and busy metadata");
equal(ui.results.children[0].getAttribute("aria-pressed"), "true", "selected result pressed state");
equal(ui.results.children[0].getAttribute("aria-label"), "Убрать ИВАНОВ\t  Иван", "selected result accessible remove name");
equal(document.activeElement, ui.search, "result rerender returns focus to search");

ui.opener.dispatch("click");
equal(ui.opener.getAttribute("aria-expanded"), "true", "opener expanded state");
equal(document.activeElement, ui.search, "open focuses search");
ui.dialog.dispatch("keydown", { key: "Escape" });
equal(ui.opener.getAttribute("aria-expanded"), "false", "Escape collapses picker");
equal(document.activeElement, ui.opener, "Escape returns focus to opener");
ui.search.focus();
const tab = ui.dialog.dispatch("keydown", { key: "Tab" });
equal(tab.defaultPrevented, false, "Tab is not trapped or prevented");
equal(document.activeElement, ui.search, "client does not replace native Tab focus order");
ui.selection.children[0].dispatch("click");
equal(ui.inputs.children.length, 0, "chip removal deletes hidden installer ID");
equal([ui.selection.children.length, ui.modalSelection.children.length], [1, 1], "both summaries return to their empty state");
equal([ui.selection.children[0].tagName, ui.modalSelection.children[0].tagName], ["SPAN", "SPAN"], "both post-removal summaries contain non-button placeholders");
equal([ui.selection.children[0].className, ui.modalSelection.children[0].className], ["fm2-picker-selection-empty", "fm2-picker-selection-empty"], "both post-removal placeholders use exact class");
equal([ui.selection.children[0].textContent, ui.modalSelection.children[0].textContent], ["Монтажники ещё не выбраны", "Пока никого"], "both post-removal placeholders use exact literals");
equal([ui.selection.children[0].getAttribute("aria-label"), ui.modalSelection.children[0].getAttribute("aria-label")], [null, null], "post-removal summaries contain no remove accessible name");
ok(![...ui.selection.children, ...ui.modalSelection.children].some((child) => child.tagName === "BUTTON" || child.getAttribute("aria-label") === "Убрать ИВАНОВ\t  Иван"), "selected remove chip disappears from both summaries");
equal(ui.count.textContent, "Выбрано: 0", "removal updates live count");
equal(document.activeElement, ui.opener, "chip removal returns focus to picker opener");

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
  [record({ id: "100", name: "Одинаковое Имя", tab: "000100", position: "Монтажник", busy: "", selected: "0" }), record({ id: "99", name: "Одинаковое Имя", tab: "000099", position: "Монтажник", busy: "", selected: "0" })],
];
malformedSets[1][0].textContent = "forbidden";
const extraAttribute = record({ id: "1042", name: "Иванов", tab: "001042", position: "Монтажник", busy: "", selected: "0" });extraAttribute.setAttribute("data-extra", "x");malformedSets.push([extraAttribute]);
const nested = record({ id: "1042", name: "Иванов", tab: "001042", position: "Монтажник", busy: "", selected: "0" });nested.append(new Node("b"));malformedSets.push([nested]);
malformedSets.push([record({ id: "1042", name: "Я".repeat(301), tab: "001042", position: "Монтажник", busy: "", selected: "0" })]);
malformedSets.push([record({ id: "1042", name: "Иванов", tab: "001042", position: "Ю".repeat(161), busy: "", selected: "0" })]);
malformedSets.push(Array.from({ length: 501 }, (_, index) => record({ id: String(index + 1), name: `Монтажник ${String(index + 1).padStart(3, "0")}`, tab: String(index + 1).padStart(6, "0"), position: "Монтажник", busy: "", selected: "0" })));
for (const records of malformedSets) {
  const rejected = execute(source, records);
  equal([rejected.opener.hidden, rejected.dialog.hidden, rejected.fallback.hidden], [true, true, false], "invalid delivered data stays fail closed");
  equal(rejected.inputs.children.length, 0, "invalid delivered data creates no hidden IDs");
}

ok(!source.includes("innerHTML"), "picker must not use innerHTML");
process.stdout.write("prepare picker client contract: PASS\n");
