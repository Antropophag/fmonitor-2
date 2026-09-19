(() => {
  const state = document.getElementsByClassName('fm2-nav-state')[0];
  const label = state?.getElementsByClassName('fm2-nav-trigger-text')[0];
  const trigger = state?.querySelector('.fm2-nav-trigger');
  if (!state || !label || !trigger) return;

  const key = 'fmonitor.sidebar.expanded';
  let saved = null;
  try { saved = localStorage.getItem(key); } catch {}
  state.open = saved !== 'false';

  const refresh = () => {
    const expanded = state.open;
    const text = expanded ? 'Свернуть меню' : 'Развернуть меню';
    const icon = expanded ? 'chevron-left-duo' : 'chevron-right-duo';
    label.textContent = text;
    trigger.setAttribute('aria-label', text);
    trigger.setAttribute('data-shlz-icon', icon);
  };
  refresh();

  state.addEventListener('toggle', () => {
    refresh();
    try { localStorage.setItem(key, String(state.open)); } catch {}
  });
})();
