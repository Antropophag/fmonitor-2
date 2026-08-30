(() => {
  const state = document.querySelector('.fm2-nav-state');
  const label = state?.querySelector('.fm2-nav-trigger-text');
  if (!state || !label) return;

  const key = 'fmonitor.sidebar.expanded';
  let saved = null;
  try { saved = localStorage.getItem(key); } catch {}
  state.open = saved !== 'false';

  const update = () => {
    label.textContent = state.open ? 'Свернуть меню' : 'Развернуть меню';
  };
  update();

  state.addEventListener('toggle', () => {
    update();
    try { localStorage.setItem(key, String(state.open)); } catch {}
  });
})();
