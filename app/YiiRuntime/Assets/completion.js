const warning = 'Результат сохранения не подтверждён. Проверьте актуальные документы перед повторной отправкой.';
const active = new WeakSet();

try {
  if (!window.location.hash && sessionStorage.getItem('fm2.completion.redirect') === '1') history.replaceState(null, '', '#completion');
  sessionStorage.removeItem('fm2.completion.redirect');
} catch {}
for (const history of document.querySelectorAll('#completion .fm2-detail-group')) history.open = true;

const unlock = form => {
  active.delete(form);
  for (const button of form.querySelectorAll('button[type="submit"], input[type="submit"]')) button.disabled = false;
};

const showUnknown = form => {
  let message = form.querySelector('[data-completion-unknown]');
  if (!message) {
    message = document.createElement('p');
    message.className = 'fm2-completion-error';
    message.dataset.completionUnknown = '';
    message.setAttribute('role', 'alert');
    form.prepend(message);
  }
  message.textContent = warning;
  unlock(form);
};

document.addEventListener('submit', async event => {
  const form = event.target.closest?.('form[data-completion-form]');
  if (!form) return;
  event.preventDefault();
  if (active.has(form)) return;
  active.add(form);
  for (const button of form.querySelectorAll('button[type="submit"], input[type="submit"]')) button.disabled = true;
  try {
    const body = new URLSearchParams();
    for (const [name, value] of new FormData(form)) {
      if (typeof value === 'string') body.append(name, value);
    }
    const response = await fetch(form.getAttribute('action'), {
      method: 'POST',
      body,
      credentials: 'same-origin',
      headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'},
      redirect: 'follow',
    });
    if (response.redirected && response.ok) {
      try { sessionStorage.setItem('fm2.completion.redirect', '1'); } catch {}
      window.location.assign(response.url);
      return;
    }
    const type = response.headers.get('content-type') || '';
    if (![409, 422].includes(response.status) || !type.toLowerCase().includes('text/html')) {
      showUnknown(form);
      return;
    }
    const parsed = new DOMParser().parseFromString(await response.text(), 'text/html');
    const replacement = parsed.querySelector('#completion');
    const current = document.querySelector('#completion');
    if (!replacement || !current) {
      showUnknown(form);
      return;
    }
    current.replaceWith(replacement);
    const panel = replacement.closest('[role="tabpanel"]');
    const tab = panel?.id ? document.querySelector(`[aria-controls="${CSS.escape(panel.id)}"]`) : null;
    if (panel && tab) {
      for (const sibling of panel.parentElement.querySelectorAll(':scope > [role="tabpanel"]')) sibling.hidden = sibling !== panel;
      for (const candidate of tab.parentElement.querySelectorAll('[role="tab"]')) {
        candidate.setAttribute('aria-selected', candidate === tab ? 'true' : 'false');
        candidate.tabIndex = candidate === tab ? 0 : -1;
      }
    }
    const target = replacement.querySelector('[data-completion-focus="true"]');
    target?.scrollIntoView({block: 'center'});
    target?.focus({preventScroll: true});
  } catch {
    showUnknown(form);
  }
});
