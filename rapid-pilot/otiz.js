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

  const register = document.querySelector('.fm2-otiz-register');
  if (register) {
    const search = register.querySelector('[data-otiz-search]');
    const state = register.querySelector('[data-otiz-state]');
    const rows = [...register.querySelectorAll('[data-otiz-row]')];
    const count = register.querySelector('[data-otiz-count]');
    const empty = register.querySelector('[data-otiz-empty]');
    const apply = () => {
      const query = search.value.trim().toLocaleLowerCase('ru-RU');
      let visible = 0;
      rows.forEach((row) => {
        const matches = (!query || row.dataset.search.includes(query)) && (!state.value || row.dataset.state === state.value);
        row.hidden = !matches;
        if (matches) visible += 1;
      });
      count.textContent = `${visible} ${visible % 10 === 1 && visible % 100 !== 11 ? 'объект' : (visible % 10 >= 2 && visible % 10 <= 4 && (visible % 100 < 10 || visible % 100 >= 20) ? 'объекта' : 'объектов')}`;
      empty.hidden = visible !== 0;
    };
    search.addEventListener('input', apply);
    state.addEventListener('change', apply);
  }
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
