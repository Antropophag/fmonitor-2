(() => {
  'use strict';
  const form = document.querySelector('[data-original-upload-form]');
  if (!form) return;
  const field = name => form.elements.namedItem(name);
  const fields = form.querySelector('[data-original-fields]');
  const button = form.querySelector('[data-original-submit]');
  const status = form.querySelector('[data-original-status]');
  const fileInput = field('original');
  const fileDrop = form.querySelector('[data-file-drop]');
  const fileName = form.querySelector('[data-file-name]');
  let busy = false;
  const renew = () => { if (!busy) field('requestId').value = crypto.randomUUID(); };
  const messages = {
    future_document_date: 'Дата распоряжения не может быть в будущем.',
    composition_not_confirmed: 'Подтвердите соответствие оригинала выбранному составу.',
    invalid_command: 'Проверьте дату и причину исправления.',
    not_pdf: 'Выберите PDF-файл подписанного оригинала.',
    invalid_pdf: 'Не удалось прочитать PDF. Выберите корректный файл.',
    unsafe_pdf: 'PDF содержит недопустимое активное содержимое. Выберите другой файл.',
    stale_revision: 'Оригинал уже исправлен. Обновите форму перед новой загрузкой.',
    authorization_denied: 'Недостаточно полномочий для этого действия.',
    ACCESS_DENIED: 'Недостаточно полномочий для этого действия.',
    CSRF_INVALID: 'Срок действия формы истёк. Откройте её заново.',
    REQUEST_TOO_LARGE: 'Размер PDF не должен превышать 20 МиБ.',
  };
  const metadata = file => ({
    csrfToken: field('csrfToken').value, requestId: field('requestId').value,
    mode: field('mode').value, documentDate: field('documentDate').value,
    compositionConfirmed: field('compositionConfirmed').checked,
    rootOriginalId: field('rootOriginalId').value || null,
    targetRevisionId: field('targetRevisionId').value || null,
    expectedCurrentRevisionId: field('expectedCurrentRevisionId').value || null,
    correctionReason: field('correctionReason').value || null, originalFilename: file.name,
  });
  const encode = value => {
    const bytes = new TextEncoder().encode(JSON.stringify(value));
    let binary = ''; for (const byte of bytes) binary += String.fromCharCode(byte);
    return btoa(binary);
  };
  fields.disabled = false;
  fileInput.addEventListener('change', () => {
    const file = fileInput.files[0];
    fileDrop.classList.toggle('fm2-file-drop--selected', Boolean(file));
    fileName.textContent = file ? file.name : 'PDF, не более 20 МиБ';
  });
  form.addEventListener('input', renew);
  form.addEventListener('change', renew);
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (busy || !form.reportValidity()) return;
    const file = field('original').files[0];
    if (!file) { status.textContent = 'Выберите один PDF-файл.'; return; }
    const data = metadata(file);
    busy = true; fields.disabled = true; button.disabled = true;
    status.textContent = 'Оригинал загружается…';
    let newIntent = false;
    try {
      const result = await fetch(form.action, {
        method: 'POST', credentials: 'same-origin', redirect: 'error',
        headers: { 'Content-Type': 'application/pdf', 'X-FMonitor-Original': encode(data) }, body: file,
      });
      const value = await result.json();
      const success = (result.status === 201 && value.status === 'accepted') || (result.status === 200 && value.status === 'replayed');
      if (success && value.requestId === data.requestId && value.reasonCode === null && value.retryable === false
        && typeof value.rootOriginalId === 'string' && typeof value.currentRevisionId === 'string'
        && Number.isInteger(value.revisionNumber) && value.revisionNumber > 0) {
        location.assign(form.dataset.returnUrl); return;
      }
      newIntent = result.status >= 400 && result.status < 500;
      status.textContent = newIntent ? (messages[value.reasonCode || value.error] || 'Не удалось принять оригинал. Проверьте форму и повторите загрузку.')
        : 'Не удалось подтвердить сохранение. Повторите загрузку с тем же файлом и данными.';
    } catch {
      status.textContent = 'Не удалось подтвердить сохранение. Проверьте связь и повторите загрузку.';
    } finally {
      busy = false; fields.disabled = false; button.disabled = false;
      if (newIntent) renew();
    }
  });
})();
