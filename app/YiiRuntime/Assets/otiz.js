// Keep one request identity until publication has a definite response.
(() => {
  const csrf = document.querySelector('[name="_csrf"]')?.value;
  if (!csrf) return;
  const key = 'fm2.otiz.publication';
  try {
    if (new URL(location.href).searchParams.get('created') === '1') sessionStorage.removeItem(key);
    const form = document.querySelector('form[action="/pilot/otiz/calculate"]');
    if (!form) return;
    const saved = JSON.parse(sessionStorage.getItem(key) || 'null');
    if (saved) {
      form.elements.operationId.value = saved.operationId;
      form.elements.reportDate.value = saved.reportDate;
    }
    form.addEventListener('submit', () => {
      const prior = JSON.parse(sessionStorage.getItem(key) || 'null');
      if (prior && prior.reportDate !== form.elements.reportDate.value) form.elements.operationId.value = crypto.randomUUID();
      sessionStorage.setItem(key, JSON.stringify({operationId: form.elements.operationId.value, reportDate: form.elements.reportDate.value}));
    });
  } catch { /* The server-issued identity still supports ordinary resubmission. */ }
})();
