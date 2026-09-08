(() => {
  const fileInput = document.querySelector('[data-file-input]');
  if (fileInput) {
    const drop = fileInput.closest('[data-file-drop]');
    const name = drop.querySelector('[data-file-name]');
    fileInput.addEventListener('change', () => {
      const file = fileInput.files[0];
      name.textContent = file ? `${file.name} · ${(file.size / 1048576).toLocaleString('ru-RU', { maximumFractionDigits: 1 })} МБ` : 'PDF, не более 25 МБ';
      drop.classList.toggle('fm2-file-drop--selected', Boolean(file));
    });
  }
  const root = document.querySelector('[data-installer-picker]');
  if (!root) return;
  const template = root.querySelector('[data-picker-data]');
  const selection = root.querySelector('[data-picker-selection]');
  const modalSelection = root.querySelector('[data-picker-modal-selection]');
  const inputs = root.querySelector('[data-picker-inputs]');
  const dialog = root.querySelector('.fm2-picker-dialog');
  const search = root.querySelector('[data-picker-search]');
  const results = root.querySelector('[data-picker-results]');
  const meta = root.querySelector('[data-picker-meta]');
  const count = root.querySelector('[data-picker-count]');
  const opener = root.querySelector('[data-picker-open]');
  const fallback = root.querySelector('[data-picker-fallback]');
  const provenance = document.querySelector('[data-picker-provenance]');
  const normalizeWhitespace = (value) => value.replace(/[\u0009-\u000D\u0020]+/g, ' ').replace(/^[\u0009-\u000D\u0020]+|[\u0009-\u000D\u0020]+$/g, '');
  const normalize = (value) => normalizeWhitespace(value).toLocaleLowerCase('ru-RU');
  const attributeNames = ['data-id', 'data-name', 'data-tab', 'data-position', 'data-busy', 'data-selected'];
  const compareCodePoints = (left, right) => { const a = Array.from(left, (character) => character.codePointAt(0)); const b = Array.from(right, (character) => character.codePointAt(0));
    for (let index = 0; index < Math.min(a.length, b.length); index += 1) if (a[index] !== b[index]) return a[index] - b[index]; return a.length - b.length; };
  function parsePeople() {
    if (!template || !template.content || !selection || !modalSelection || !inputs || !dialog || !search || !results || !meta || !count || !opener || !fallback) return null;
    const nodes = Array.from(template.content.childNodes || []);
    const records = [];
    const ids = new Set();
    for (const node of nodes) {
      if (node.nodeType === 3) { if (!/^[\u0009\u000A\u000D\u0020]*$/.test(node.data)) return null; continue; }
      if (node.nodeType !== 1 || node.tagName !== 'SPAN' || node.children.length !== 0 || node.textContent !== '') return null;
      const names = node.getAttributeNames().slice().sort();
      if (JSON.stringify(names) !== JSON.stringify(attributeNames.slice().sort())) return null;
      const { id, name, tab, position, busy, selected } = node.dataset;
      if (!/^[1-9][0-9]{0,5}$/.test(id) || !/^[0-9]{6}$/.test(tab) || Number(tab) !== Number(id) || busy !== '' || selected !== '0') return null;
      if (/^[\u0009-\u000D\u0020]|[\u0009-\u000D\u0020]$/.test(name) || /^[\u0009-\u000D\u0020]|[\u0009-\u000D\u0020]$/.test(position) || Array.from(name).length < 1 || Array.from(name).length > 300 || Array.from(position).length < 1 || Array.from(position).length > 160) return null;
      if (ids.has(id)) return null; ids.add(id);
      records.push({ id, name, tab, position, busy, selected: false });
      if (records.length > 500) return null;
    }
    for (let index = 1; index < records.length; index += 1) {
      const previous = records[index - 1]; const current = records[index];
      const nameOrder = compareCodePoints(previous.name, current.name);
      if (nameOrder > 0 || (nameOrder === 0 && Number(previous.id) >= Number(current.id))) return null;
    }
    return records;
  }
  const people = parsePeople();
  if (people === null) return;
  if (provenance) { const nodes = Array.from(provenance.childNodes || []); const rows = []; for (const node of nodes) { if (node.nodeType === 3) { if (!/^[\u0009\u000A\u000D\u0020]*$/.test(node.data)) return; continue; } rows.push(node); } if (rows.length !== people.length) return;
    for (let index = 0; index < rows.length; index += 1) { const row = rows[index]; const person = people[index];
      if (row.tagName !== 'LI' || row.children.length !== 0 || JSON.stringify(row.getAttributeNames().slice().sort()) !== JSON.stringify(['data-id', 'data-source', 'data-updated-at'])) return; const expected = `${person.name} · Источник кадровых данных: ${row.dataset.source} · Актуально на: ${row.dataset.updatedAt}`;
      if (row.dataset.id !== person.id || !row.dataset.source || !row.dataset.updatedAt || row.textContent !== expected) return; person.provenance = `Источник кадровых данных: ${row.dataset.source} · Актуально на: ${row.dataset.updatedAt}`; } }
  const selected = new Map();
  function renderSelection() {
    selection.replaceChildren(); modalSelection.replaceChildren(); inputs.replaceChildren();
    if (selected.size === 0) {
      const outsideEmpty = document.createElement('span');
      outsideEmpty.className = 'fm2-picker-selection-empty'; outsideEmpty.textContent = 'Монтажники ещё не выбраны';
      const modalEmpty = outsideEmpty.cloneNode();
      modalEmpty.textContent = 'Пока никого';
      selection.append(outsideEmpty); modalSelection.append(modalEmpty);
    }
    selected.forEach((person) => {
      const createChip = () => {
        const chip = document.createElement('button');
        chip.type = 'button'; chip.className = 'fm2-picker-chip';
        chip.setAttribute('aria-label', `Убрать ${person.name}`);
        chip.textContent = `${person.name} · ${person.tab}  ×`;
        chip.addEventListener('click', () => {
          selected.delete(person.id); renderSelection(); renderResults(); opener.focus();
        });
        return chip;
      };
      selection.append(createChip()); modalSelection.append(createChip());
      const input = document.createElement('input');
      input.type = 'hidden'; input.name = 'installerTabIds[]'; input.value = person.id;
      inputs.append(input);
    });
    count.textContent = `Выбрано: ${selected.size}`;
  }
  function resultButton(person) {
    const button = document.createElement('button');
    const isSelected = selected.has(person.id);
    button.type = 'button'; button.className = `fm2-picker-result${isSelected ? ' fm2-picker-result--selected' : ''}`;
    button.setAttribute('aria-pressed', String(isSelected));
    button.setAttribute('aria-label', `${isSelected ? 'Убрать' : 'Выбрать'} ${person.name}`);
    const mark = document.createElement('span');
    mark.className = 'fm2-picker-result-mark'; mark.textContent = isSelected ? '✓' : '+';
    const text = document.createElement('span');
    text.className = 'fm2-picker-result-text';
    const name = document.createElement('strong');
    name.textContent = person.name;
    const details = document.createElement('span');
    details.textContent = `Таб. ${person.tab} · ${person.position}`;
    text.append(name, details);
    if (person.provenance) { const provenanceText = document.createElement('span'); provenanceText.className = 'fm2-picker-result-provenance'; provenanceText.textContent = person.provenance; text.append(provenanceText); }
    button.append(mark, text);
    if (person.busy) {
      const busy = document.createElement('span');
      busy.className = 'fm2-picker-result-busy';
      busy.textContent = `Закреплён до ${person.busy}`;
      button.append(busy);
    }
    button.addEventListener('click', () => {
      if (isSelected) selected.delete(person.id); else selected.set(person.id, person);
      renderSelection(); renderResults(); search.focus();
    });
    return button;
  }
  function renderResults() {
    results.replaceChildren();
    const query = normalize(search.value);
    if (Array.from(query).length < 2) {
      meta.textContent = 'Введите минимум 2 символа';
      return;
    }
    const compactTab = Array.from(query).filter((character) => /[0-9]/.test(character)).join('');
    const matches = people.filter((person) =>
      normalize(person.name).includes(query) || (compactTab.length >= 2 && person.tab.includes(compactTab))
    );
    const visible = matches.slice(0, 20);
    meta.textContent = matches.length > 20 ? `Найдено ${matches.length}. Показаны первые 20` : `Найдено: ${matches.length}`;
    if (visible.length === 0) {
      const empty = document.createElement('p');
      empty.className = 'fm2-picker-empty'; empty.textContent = 'Ничего не найдено. Проверьте ФИО или табельный номер.';
      results.append(empty);
      return;
    }
    visible.forEach((person) => results.append(resultButton(person)));
  }

  search.addEventListener('input', renderResults);
  opener.addEventListener('click', () => {
    dialog.hidden = false; opener.setAttribute('aria-expanded', 'true'); search.focus();
  });
  dialog.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    dialog.hidden = true; opener.setAttribute('aria-expanded', 'false'); opener.focus();
  });
  renderSelection(); if (provenance) provenance.hidden = true; opener.hidden = false; fallback.hidden = true;
})();
