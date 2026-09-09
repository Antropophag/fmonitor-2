(() => {
  const root = document.documentElement;
  const storageKey = 'fmonitor-rapid-pilot-ready';
  let shouldShow = true;

  if (window.location.pathname === '/pilot/login') {
    try {
      sessionStorage.removeItem(storageKey);
    } catch (_) {
      // Storage may be disabled; there is no launch state to reset.
    }
    return;
  }

  try {
    shouldShow = sessionStorage.getItem(storageKey) !== '1';
  } catch (_) {
    shouldShow = true;
  }

  if (!shouldShow) return;
  root.classList.add('fm2-preload-enabled');

  const started = performance.now();
  let finishing = false;

  const finish = () => {
    if (finishing) return;
    finishing = true;

    const wait = Math.max(0, 820 - (performance.now() - started));
    window.setTimeout(() => {
      const loader = document.querySelector('[data-fm2-preloader]');
      if (!loader) {
        root.classList.remove('fm2-preload-enabled');
        return;
      }

      loader.classList.add('is-leaving');
      try {
        sessionStorage.setItem(storageKey, '1');
      } catch (_) {
        // Storage may be disabled; the loader still completes normally.
      }

      window.setTimeout(() => {
        root.classList.remove('fm2-preload-enabled');
        loader.remove();
      }, 260);
    }, wait);
  };

  if (document.readyState === 'complete') finish();
  else window.addEventListener('load', finish, { once: true });

  window.setTimeout(finish, 4000);
})();
