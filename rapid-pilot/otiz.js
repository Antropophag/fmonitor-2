import { enhanceSelects } from '/pilot/assets/shlz-behaviors.js';

enhanceSelects(document);

(() => {
  const tables = document.querySelectorAll('.fm2-otiz-object-row > td > details');
  tables.forEach((details) => details.addEventListener('toggle', () => {
    if (!details.open) return;
    tables.forEach((other) => { if (other !== details) other.open = false; });
  }));

  document.querySelectorAll('.fm2-otiz-close form').forEach((form) => {
    form.addEventListener('submit', (event) => {
      const discipline = Number.parseFloat(form.elements.discipline.value.replace(',', '.')) || 0;
      if (discipline <= 0) {
        event.preventDefault();
        form.elements.discipline.setCustomValidity('Укажите сумму дисциплинарного удержания больше нуля.');
        form.elements.discipline.reportValidity();
      }
    });
    form.elements.discipline.addEventListener('input', () => form.elements.discipline.setCustomValidity(''));
  });


})();

// Keep the original request identity until publication has a definite response.
(() => {
  const csrf = document.querySelector('[name="csrfToken"]')?.value;
  if (!csrf) return;
  const key = `fm2.otiz.publication.${csrf}`;
  try {
    if (new URL(location.href).searchParams.get('created') === '1') sessionStorage.removeItem(key);
    const form = document.querySelector('form[action="/pilot/otiz/calculate"]');
    if (!form) return;
    const saved = JSON.parse(sessionStorage.getItem(key) || 'null');
    if (saved) { form.elements.operationId.value = saved.operationId; form.elements.reportDate.value = saved.reportDate; }
    form.addEventListener('submit', () => {
      const prior = JSON.parse(sessionStorage.getItem(key) || 'null');
      if (prior && prior.reportDate !== form.elements.reportDate.value) form.elements.operationId.value = crypto.randomUUID();
      sessionStorage.setItem(key, JSON.stringify({operationId: form.elements.operationId.value, reportDate: form.elements.reportDate.value}));
    });
  } catch { /* The server-issued form identity still supports resubmission. */ }
})();
