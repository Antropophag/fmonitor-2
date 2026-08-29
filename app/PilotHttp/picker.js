(() => {
  const root = document.querySelector('[data-installer-picker]');
  if (!root) return;

  const template = root.querySelector('[data-picker-data]');
  const selection = root.querySelector('[data-picker-selection]');
  const inputs = root.querySelector('[data-picker-inputs]');
  const dialog = root.querySelector('.fm2-picker-dialog');
  const search = root.querySelector('[data-picker-search]');
  const results = root.querySelector('[data-picker-results]');
  const meta = root.querySelector('[data-picker-meta]');
  const count = root.querySelector('[data-picker-count]');
  const people = Array.from(template.content.querySelectorAll('[data-id]'), (node) => ({
    id: node.dataset.id,
    name: node.dataset.name,
    tab: node.dataset.tab,
    position: node.dataset.position,
    busy: node.dataset.busy,
    selected: node.dataset.selected === '1'
  }));
  const selected = new Map(people.filter((person) => person.selected).map((person) => [person.id, person]));
  const normalize = (value) => value.toLocaleLowerCase('ru-RU').replace(/\s+/g, ' ').trim();

  function renderSelection() {
    selection.replaceChildren();
    inputs.replaceChildren();
    if (selected.size === 0) {
      const empty = document.createElement('span');
      empty.className = 'fm2-picker-selection-empty';
      empty.textContent = 'Монтажники ещё не выбраны';
      selection.append(empty);
    }
    selected.forEach((person) => {
      const chip = document.createElement('button');
      chip.type = 'button';
      chip.className = 'fm2-picker-chip';
      chip.setAttribute('aria-label', `Убрать ${person.name}`);
      chip.textContent = `${person.name} · ${person.tab}  ×`;
      chip.addEventListener('click', () => {
        selected.delete(person.id);
        renderSelection();
        renderResults();
      });
      selection.append(chip);

      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'installerTabIds[]';
      input.value = person.id;
      inputs.append(input);
    });
    count.textContent = `Выбрано: ${selected.size}`;
  }

  function resultButton(person) {
    const button = document.createElement('button');
    const isSelected = selected.has(person.id);
    button.type = 'button';
    button.className = `fm2-picker-result${isSelected ? ' fm2-picker-result--selected' : ''}`;
    button.setAttribute('aria-pressed', String(isSelected));

    const mark = document.createElement('span');
    mark.className = 'fm2-picker-result-mark';
    mark.textContent = isSelected ? '✓' : '+';
    const text = document.createElement('span');
    text.className = 'fm2-picker-result-text';
    const name = document.createElement('strong');
    name.textContent = person.name;
    const details = document.createElement('span');
    details.textContent = `Таб. ${person.tab} · ${person.position}`;
    text.append(name, details);
    button.append(mark, text);
    if (person.busy) {
      const busy = document.createElement('span');
      busy.className = 'fm2-picker-result-busy';
      busy.textContent = `Закреплён до ${person.busy}`;
      button.append(busy);
    }
    button.addEventListener('click', () => {
      if (isSelected) selected.delete(person.id);
      else selected.set(person.id, person);
      renderSelection();
      renderResults();
    });
    return button;
  }

  function renderResults() {
    results.replaceChildren();
    const query = normalize(search.value);
    if (query.length < 2) {
      meta.textContent = 'Введите минимум 2 символа';
      return;
    }
    const compactTab = query.replace(/\D/g, '');
    const matches = people.filter((person) =>
      normalize(person.name).includes(query) || (compactTab.length >= 2 && person.tab.includes(compactTab))
    );
    const visible = matches.slice(0, 20);
    meta.textContent = matches.length > 20 ? `Найдено ${matches.length}. Показаны первые 20` : `Найдено: ${matches.length}`;
    if (visible.length === 0) {
      const empty = document.createElement('p');
      empty.className = 'fm2-picker-empty';
      empty.textContent = 'Ничего не найдено. Проверьте ФИО или табельный номер.';
      results.append(empty);
      return;
    }
    visible.forEach((person) => results.append(resultButton(person)));
  }

  search.addEventListener('input', renderResults);
  dialog.addEventListener('toggle', () => {
    if (dialog.matches(':popover-open')) search.focus();
  });
  renderSelection();
})();
