(() => {
  const tables = document.querySelectorAll('.fm2-otiz-table details');
  tables.forEach((details) => details.addEventListener('toggle', () => {
    if (!details.open) return;
    tables.forEach((other) => { if (other !== details) other.open = false; });
  }));

  document.querySelectorAll('.fm2-otiz-close form').forEach((form) => {
    form.addEventListener('submit', (event) => {
      const values = ['paid', 'discipline', 'deadline'].map((name) => Number.parseFloat(form.elements[name].value.replace(',', '.')) || 0);
      if (values.reduce((sum, value) => sum + value, 0) <= 0) {
        event.preventDefault();
        form.elements.paid.setCustomValidity('Укажите выплату или удержание больше нуля.');
        form.elements.paid.reportValidity();
      }
    });
    form.elements.paid.addEventListener('input', () => form.elements.paid.setCustomValidity(''));
  });
})();
