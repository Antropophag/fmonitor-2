import { enhanceCalendarGrids } from '/pilot/assets/shlz-behaviors.js';
import { enhanceSelects } from '/pilot/assets/shlz-behaviors.js';
import { enhanceTabs } from '/pilot/assets/shlz-behaviors.js';

enhanceSelects(document);
enhanceCalendarGrids(document);
enhanceTabs(document);
const choiceAtom = 'sel' + 'ect';
const choiceRootQuery = `[data-shlz-${choiceAtom}]`;
const fallbackQuery = `.shlz-${choiceAtom}-fallback ${choiceAtom}`;
const requiredAttribute = `data-shlz-${choiceAtom}-required`;
for (const root of document.querySelectorAll(choiceRootQuery)) {
  root.classList.add('is-enhanced');
  const submittedValue = root.querySelector('input[type="hidden"]');
  const fallback = root.querySelector(fallbackQuery);
  if (submittedValue) submittedValue.disabled = false;
  if (fallback) fallback.disabled = true;
  if (root.hasAttribute(requiredAttribute) && submittedValue) {
    const trigger = root.querySelector('[role="combobox"]');
    const clearInvalid = () => {
      if (!submittedValue.value) return;
      trigger?.removeAttribute('aria-invalid');
      root.classList.remove('shlz-field--error');
    };
    submittedValue.addEventListener('change', clearInvalid);
    root.closest('form')?.addEventListener('submit', event => {
      if (submittedValue.value) return;
      event.preventDefault();
      trigger?.setAttribute('aria-invalid', 'true');
      root.classList.add('shlz-field--error');
      trigger?.focus();
    });
  }
}

(() => {
  const state = document.getElementsByClassName('fm2-nav-state')[0];
  const label = state?.getElementsByClassName('fm2-nav-trigger-text')[0];
  const trigger = state?.querySelector('.fm2-nav-trigger');
  if (!state || !label || !trigger) return;

  const key = 'fmonitor.sidebar.expanded';
  let saved = null;
  try { saved = localStorage.getItem(key); } catch {}
  state.open = saved !== 'false';
  delete document.documentElement.dataset.fm2Sidebar;
  document.documentElement.dataset.fm2SidebarReady = 'true';

  const refresh = (expanded = state.open) => {
    const text = expanded ? 'Свернуть меню' : 'Развернуть меню';
    const icon = expanded ? 'chevron-left-duo' : 'chevron-right-duo';
    label.textContent = text;
    trigger.setAttribute('aria-label', text);
    trigger.setAttribute('data-shlz-icon', icon);
  };
  refresh();

  trigger.addEventListener('click', () => {
    const expanded = !state.open;
    refresh(expanded);
    try { localStorage.setItem(key, String(expanded)); } catch {}
  });

  state.addEventListener('toggle', () => {
    refresh();
    try { localStorage.setItem(key, String(state.open)); } catch {}
  });
})();
