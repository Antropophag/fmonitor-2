(() => {
  'use strict';
  const form = document.querySelector('[data-selection-picker]');
  if (!form) return;
  const dialog = form.querySelector('[data-installer-dialog]');
  const search = dialog.querySelector('[data-installer-search]');
  const results = dialog.querySelector('[data-installer-results]');
  const status = dialog.querySelector('[data-installer-status]');
  const more = dialog.querySelector('[data-installer-more]');
  const main = form.querySelector('[data-main-selection]');
  const modalSelection = dialog.querySelector('[data-modal-selection]');
  const count = dialog.querySelector('[data-picker-count]');
  const selected = new Map();
  let page = 1;
  let query = '';
  let controller = null;
  let timer = null;
  let opener = null;
  let generation = 0;
  let retryAppend = false;

  form.querySelectorAll('[data-selected-installer]').forEach(node => selected.set(node.dataset.tabId, {
    tabId: node.dataset.tabId, fullName: node.dataset.fullName, position: node.dataset.position,
    source: node.dataset.source, updatedAt: node.dataset.updatedAt,
  }));

  const icon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 7 10 10M17 7 7 17"/></svg>';
  const chip = (item, withInput) => {
    const node = document.createElement('span'); node.className = 'fm2-picker-chip';
    const name = document.createElement('span'); name.textContent = item.fullName;
    const tab = document.createElement('small'); tab.textContent = `№ ${String(item.tabId).padStart(6, '0')}`;
    const remove = document.createElement('button'); remove.type = 'button'; remove.dataset.removeInstaller = '';
    remove.setAttribute('aria-label', `Убрать ${item.fullName}`); remove.innerHTML = icon;
    node.append(name, tab, remove);
    if (withInput) { const input = document.createElement('input'); input.type = 'hidden'; input.name = 'installerTabIds[]'; input.value = item.tabId; node.append(input); }
    remove.addEventListener('click', () => { selected.delete(String(item.tabId)); renderSelections(); renderResultsSelection(); });
    return node;
  };
  const renderSelections = () => {
    main.replaceChildren(); modalSelection.replaceChildren();
    if (!selected.size) { const empty = document.createElement('span'); empty.className = 'fm2-picker-selection-empty'; empty.textContent = 'Монтажники ещё не выбраны'; main.append(empty); }
    selected.forEach(item => { main.append(chip(item, true)); modalSelection.append(chip(item, false)); });
    count.textContent = `Выбрано: ${selected.size}`;
  };
  const renderResultsSelection = () => results.querySelectorAll('[data-result-id]').forEach(node => {
    const input = node.querySelector('input'); input.checked = selected.has(node.dataset.resultId);
  });
  const resultNode = item => {
    const label = document.createElement('label'); label.className = 'fm2-picker-result'; label.dataset.resultId = String(item.tabId);
    const input = document.createElement('input'); input.type = 'checkbox'; input.className = 'shlz-checkbox'; input.checked = selected.has(String(item.tabId));
    const copy = document.createElement('span'); const strong = document.createElement('strong'); strong.textContent = item.fullName;
    const detail = document.createElement('small'); detail.textContent = `${item.position} · № ${String(item.tabId).padStart(6, '0')}`;
    const provenance = document.createElement('small'); provenance.textContent = `Источник: ${item.source} · Актуально на: ${item.updatedAt}`;
    copy.append(strong, detail, provenance); label.append(input, copy);
    input.addEventListener('change', () => { const id = String(item.tabId); if (input.checked) selected.set(id, {...item, tabId: id}); else selected.delete(id); renderSelections(); renderResultsSelection(); });
    return label;
  };
  const load = async append => {
    if (query.length < 2) return;
    controller?.abort(); controller = new AbortController(); const signal = controller.signal;
    const requestedQuery = query; const requestedPage = page; const requestedGeneration = generation;
    retryAppend = append; status.textContent = 'Ищем монтажников…'; more.hidden = true; more.disabled = true;
    try {
      const url = `${form.dataset.searchUrl}?q=${encodeURIComponent(requestedQuery)}&page=${requestedPage}`;
      const response = await fetch(url, {credentials: 'same-origin', headers: {'Accept': 'application/json'}, signal});
      if (!response.ok) throw new Error(); const data = await response.json();
      if (requestedGeneration !== generation || requestedQuery !== query || requestedPage !== page) return;
      if (!append) results.replaceChildren(); data.items.forEach(item => results.append(resultNode(item)));
      status.textContent = results.children.length ? `Показано: ${results.children.length}` : 'Ничего не найдено. Проверьте запрос.';
      more.hidden = !data.hasMore; more.disabled = false;
    } catch (error) {
      if (error.name === 'AbortError') return;
      if (!append) results.replaceChildren(); status.textContent = 'Не удалось загрузить список. Проверьте связь и повторите поиск.'; more.hidden = false; more.disabled = false; more.textContent = 'Повторить';
    }
  };
  const searchChanged = () => {
    clearTimeout(timer); controller?.abort(); generation += 1; query = search.value.trim(); page = 1; more.textContent = 'Показать ещё';
    if (query.length < 2) { controller?.abort(); results.replaceChildren(); more.hidden = true; status.textContent = 'Введите минимум 2 символа'; return; }
    timer = setTimeout(() => load(false), 250);
  };
  form.querySelector('[data-dialog-open]').addEventListener('click', event => { opener = event.currentTarget; dialog.showModal(); renderSelections(); search.focus(); });
  dialog.querySelector('[data-dialog-close]').addEventListener('click', () => dialog.close());
  dialog.querySelector('[data-dialog-apply]').addEventListener('click', () => dialog.close());
  dialog.addEventListener('close', () => opener?.focus());
  search.addEventListener('input', searchChanged);
  more.addEventListener('click', () => { if (more.textContent === 'Повторить') load(retryAppend); else { page += 1; load(true); } });
  renderSelections();
})();
