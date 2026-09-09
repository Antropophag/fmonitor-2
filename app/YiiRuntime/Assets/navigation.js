(() => {
  const state = document.getElementsByClassName('fm2-nav-state')[0];
  const label = state?.getElementsByClassName('fm2-nav-trigger-text')[0];
  if (!state || !label) return;

  const key = 'fmonitor.sidebar.expanded';
  let saved = null;
  try { saved = localStorage.getItem(key); } catch {}
  state.open = saved !== 'false';

  const refresh = () => {
    label.textContent = state.open ? 'Свернуть меню' : 'Развернуть меню';
  };
  refresh();

  state.addEventListener('toggle', () => {
    refresh();
    try { localStorage.setItem(key, String(state.open)); } catch {}
  });
})();
