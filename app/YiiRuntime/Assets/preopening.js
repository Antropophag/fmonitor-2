(() => {
  'use strict';
  const form = document.querySelector('[data-selection-picker]');
  if (!form) return;
  const dialog = form.querySelector('[data-installer-dialog]');
  const search = dialog?.querySelector('[data-installer-search]');
  const results = dialog?.querySelector('[data-installer-results]');
  const status = dialog?.querySelector('[data-installer-status]');
  const more = dialog?.querySelector('[data-installer-more]');
  const main = form.querySelector('[data-main-selection]');
  const modal = dialog?.querySelector('[data-modal-selection]');
  const count = dialog?.querySelector('[data-picker-count]');
  if (!dialog || !search || !results || !status || !more || !main) return;

  const selected = new Map();
  let query = '', page = 1, generation = 0, timer = null, request = null, opener = null, retryAppend = false;
  form.querySelectorAll('[data-selected-installer]').forEach(node => selected.set(String(node.dataset.tabId), {
    tabId: String(node.dataset.tabId), fullName: node.dataset.fullName || '', position: node.dataset.position || '',
    source: node.dataset.source || '', updatedAt: node.dataset.updatedAt || '',
  }));

  const chip = (item, withInput) => {
    const node = document.createElement('span');
    node.className = 'fm2-picker-chip';
    Object.assign(node.dataset, {selectedInstaller: '', tabId: String(item.tabId), fullName: item.fullName,
      position: item.position || '', source: item.source || '', updatedAt: item.updatedAt || ''});
    const name = document.createElement('span'); name.textContent = item.fullName;
    const tab = document.createElement('small'); tab.textContent = `№ ${String(item.tabId).padStart(6, '0')}`;
    const remove = document.createElement('button'); remove.type = 'button'; remove.dataset.removeInstaller = '';
    remove.setAttribute('aria-label', `Убрать ${item.fullName}`); remove.textContent = '×';
    remove.addEventListener('click', () => { selected.delete(String(item.tabId)); renderSelections(); renderChecks(); });
    node.append(name, tab, remove);
    if (withInput) { const input = document.createElement('input'); input.type = 'hidden'; input.name = 'installerTabIds[]'; input.value = item.tabId; node.append(input); }
    return node;
  };
  const renderSelections = () => {
    main.replaceChildren(); modal?.replaceChildren();
    if (!selected.size) { const empty = document.createElement('span'); empty.className = 'fm2-picker-selection-empty'; empty.dataset.selectionEmpty = ''; empty.textContent = 'Монтажники ещё не выбраны'; main.append(empty); }
    selected.forEach(item => { main.append(chip(item, true)); modal?.append(chip(item, false)); });
    if (count) count.textContent = `Выбрано: ${selected.size}`;
  };
  const renderChecks = () => results.querySelectorAll('[data-result-id]').forEach(node => {
    const input = node.querySelector('input[type="checkbox"]'); if (input) input.checked = selected.has(String(node.dataset.resultId));
  });
  const resultNode = item => {
    const id = String(item.tabId), label = document.createElement('label'), input = document.createElement('input');
    label.className = 'fm2-picker-result'; label.dataset.resultId = id;
    input.type = 'checkbox'; input.className = 'shlz-checkbox'; input.checked = selected.has(id);
    const copy = document.createElement('span'), name = document.createElement('strong'), detail = document.createElement('small'), provenance = document.createElement('small');
    name.textContent = item.fullName; detail.textContent = `${item.position} · № ${id.padStart(6, '0')}`;
    provenance.textContent = `Источник: ${item.source} · Актуально на: ${item.updatedAt}`;
    copy.append(name, detail, provenance); label.append(input, copy);
    input.addEventListener('change', () => { if (input.checked) selected.set(id, {...item, tabId: id}); else selected.delete(id); renderSelections(); renderChecks(); });
    return label;
  };
  const load = async append => {
    if (query.length < 2) return;
    request?.abort(); request = new AbortController();
    const requestedQuery = query, requestedPage = page, requestedGeneration = generation;
    retryAppend = append; status.textContent = 'Ищем монтажников…'; more.hidden = true; more.disabled = true;
    try {
      const response = await fetch(`${form.dataset.searchUrl}?q=${encodeURIComponent(requestedQuery)}&page=${requestedPage}`, {credentials: 'same-origin', headers: {Accept: 'application/json'}, signal: request.signal});
      if (!response.ok) throw new Error('search_failed');
      const data = await response.json();
      if (requestedGeneration !== generation || requestedQuery !== query || requestedPage !== page) return;
      if (!append) results.replaceChildren(); data.items.forEach(item => results.append(resultNode(item)));
      status.textContent = results.children.length ? `Показано: ${results.children.length}` : 'Ничего не найдено. Проверьте запрос.';
      more.textContent = 'Показать ещё'; more.hidden = !data.hasMore; more.disabled = false;
    } catch (error) {
      if (error.name === 'AbortError') return;
      if (!append) results.replaceChildren(); status.textContent = 'Не удалось загрузить список. Проверьте связь и повторите поиск.';
      more.textContent = 'Повторить'; more.hidden = false; more.disabled = false;
    }
  };
  search.addEventListener('input', () => {
    clearTimeout(timer); request?.abort(); generation += 1; query = search.value.trim(); page = 1; more.textContent = 'Показать ещё';
    if (query.length < 2) { results.replaceChildren(); more.hidden = true; status.textContent = 'Введите минимум 2 символа'; return; }
    timer = setTimeout(() => load(false), 250);
  });
  form.querySelector('[data-dialog-open]')?.addEventListener('click', event => { opener = event.currentTarget; dialog.showModal(); renderSelections(); search.focus(); });
  dialog.querySelector('[data-dialog-close]')?.addEventListener('click', () => dialog.close());
  dialog.querySelector('[data-dialog-apply]')?.addEventListener('click', () => dialog.close());
  dialog.addEventListener('close', () => opener?.focus());
  more.addEventListener('click', () => { if (more.textContent === 'Повторить') load(retryAppend); else { page += 1; load(true); } });
  renderSelections();
})();

(() => {
  'use strict';
  const form = document.querySelector('[data-original-upload-form]');
  if (!form) return;
  const field = name => form.elements.namedItem(name), fields = form.querySelector('[data-original-fields]');
  const button = form.querySelector('[data-original-submit]'), status = form.querySelector('[data-original-status]'), fileInput = field('original');
  const fileDrop = form.querySelector('[data-file-drop]'), fileName = form.querySelector('[data-file-name]');
  if (!button || !status || !fileInput) return;
  let busy = false;
  const renew = () => { if (!busy && field('requestId')) field('requestId').value = crypto.randomUUID(); };
  const messages = {future_document_date: 'Дата распоряжения не может быть в будущем.', composition_not_confirmed: 'Подтвердите соответствие оригинала выбранному составу.', invalid_command: 'Проверьте дату и причину исправления.', not_pdf: 'Выберите PDF-файл подписанного оригинала.', invalid_pdf: 'Не удалось прочитать PDF. Выберите корректный файл.', unsafe_pdf: 'PDF содержит недопустимое активное содержимое. Выберите другой файл.', stale_revision: 'Оригинал уже исправлен. Обновите форму перед новой загрузкой.', authorization_denied: 'Недостаточно полномочий для этого действия.', ACCESS_DENIED: 'Недостаточно полномочий для этого действия.', CSRF_INVALID: 'Срок действия формы истёк. Откройте её заново.', REQUEST_TOO_LARGE: 'Размер PDF не должен превышать 20 МиБ.'};
  const metadata = file => ({csrfToken: field('csrfToken').value, requestId: field('requestId').value, mode: field('mode').value,
    documentDate: field('documentDate').value, compositionConfirmed: field('compositionConfirmed').checked,
    rootOriginalId: field('rootOriginalId').value || null, targetRevisionId: field('targetRevisionId').value || null,
    expectedCurrentRevisionId: field('expectedCurrentRevisionId').value || null, correctionReason: field('correctionReason').value || null,
    originalFilename: file.name});
  const encode = value => { const bytes = new TextEncoder().encode(JSON.stringify(value)); let binary = ''; for (const byte of bytes) binary += String.fromCharCode(byte); return btoa(binary); };
  const disable = value => { if (fields) fields.disabled = value; button.disabled = value; };
  disable(false);
  fileInput.addEventListener('change', () => { const file = fileInput.files[0]; fileDrop?.classList.toggle('fm2-file-drop--selected', Boolean(file)); if (fileName) fileName.textContent = file ? file.name : 'PDF, не более 20 МиБ'; });
  form.addEventListener('input', renew); form.addEventListener('change', renew);
  form.addEventListener('submit', async event => {
    event.preventDefault(); if (busy || !form.reportValidity()) return;
    const file = fileInput.files[0];
    if (!file) { status.textContent = 'Выберите один PDF-файл.'; return; }
    if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) { status.textContent = messages.not_pdf; return; }
    if (file.size > 20 * 1024 * 1024) { status.textContent = messages.REQUEST_TOO_LARGE; return; }
    const data = metadata(file); busy = true; disable(true); status.textContent = 'Оригинал загружается…'; let changedIntent = false, response = null;
    try {
      response = await fetch(form.action, {method: 'POST', credentials: 'same-origin', redirect: 'error', headers: {'Content-Type': 'application/pdf', 'X-CSRF-Token': data.csrfToken, 'X-FMonitor-Original': encode(data)}, body: file});
      const value = await response.json(), accepted = response.status === 201 && value.status === 'accepted', replayed = response.status === 200 && value.status === 'replayed';
      const receipt = value.requestId === data.requestId && value.reasonCode === null && value.retryable === false && typeof value.rootOriginalId === 'string' && typeof value.currentRevisionId === 'string' && Number.isInteger(value.revisionNumber) && value.revisionNumber > 0;
      if ((accepted || replayed) && receipt) { location.assign(form.dataset.returnUrl); return; }
      changedIntent = response.status >= 400 && response.status < 500;
      status.textContent = changedIntent ? (messages[value.reasonCode || value.error] || 'Не удалось принять оригинал. Проверьте форму и повторите загрузку.') : 'Не удалось подтвердить сохранение. Повторите загрузку с тем же файлом и данными.';
    } catch {
      changedIntent = Boolean(response && response.status >= 400 && response.status < 500);
      status.textContent = changedIntent ? 'Не удалось принять оригинал. Проверьте форму и повторите загрузку.' : 'Не удалось подтвердить сохранение. Проверьте связь и повторите загрузку.';
    }
    finally { busy = false; disable(false); if (changedIntent) renew(); }
  });
})();
