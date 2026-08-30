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
