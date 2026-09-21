(() => {
  const root = document.documentElement;
  let collapsed = false;
  try {
    if (localStorage.getItem('fmonitor.sidebar.expanded') === 'false') {
      root.dataset.fm2Sidebar = 'collapsed';
      collapsed = true;
    }
  } catch {}

  if (!collapsed) return;
  const closeSidebar = () => {
    const state = document.querySelector('.fm2-nav-state');
    if (state instanceof HTMLDetailsElement) state.open = false;
  };
  const observer = new MutationObserver(closeSidebar);
  observer.observe(document, { childList: true, subtree: true });
  document.addEventListener('DOMContentLoaded', () => {
    closeSidebar();
    observer.disconnect();
  }, { once: true });
})();
