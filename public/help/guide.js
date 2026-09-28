/* Read-only guide interactions. No application API, analytics, or persistent storage. */
(() => {
  'use strict';
  const root = document.body;
  const search = document.getElementById('guide-search');
  const clear = document.getElementById('guide-clear');
  const summary = document.getElementById('guide-search-summary');
  const count = document.getElementById('guide-search-count');
  const empty = document.getElementById('guide-empty');
  const chapters = Array.from(document.querySelectorAll('.guide-section'));
  const navLinks = Array.from(document.querySelectorAll('.guide-nav a'));
  const allDetails = Array.from(document.querySelectorAll('.guide-details'));
  const menuToggle = document.getElementById('guide-menu');
  const nav = document.getElementById('guide-sidebar');
  const shade = document.getElementById('guide-shade');
  const normalize = text => text.toLocaleLowerCase('ru-RU').replace(/ё/g, 'е').replace(/\s+/g, ' ').trim();
  const index = chapters.map(section => ({section, text: normalize(section.textContent)}));
  let searchOpened = new Set();
  let userDetailsState = new Map();
  let searching = false;
  let timeout;
  let toastTimer;
  const toast = message => {
    const el = document.getElementById('guide-toast');
    el.textContent = message; el.hidden = false;
    clearTimeout(toastTimer); toastTimer = setTimeout(() => { el.hidden = true; }, 3500);
  };
  const clearMarks = () => {
    document.querySelectorAll('.guide-section mark').forEach(mark => {
      const parent = mark.parentNode;
      mark.replaceWith(document.createTextNode(mark.textContent));
      parent.normalize();
    });
  };
  const highlight = (section, query) => {
    if (query.length < 2) return;
    const walker = document.createTreeWalker(section, NodeFilter.SHOW_TEXT, {
      acceptNode(node) {
        if (!node.parentElement || node.parentElement.closest('button,script,style,.guide-section-link')) return NodeFilter.FILTER_REJECT;
        return NodeFilter.FILTER_ACCEPT;
      }
    });
    const nodes = [];
    while (walker.nextNode()) nodes.push(walker.currentNode);
    let matches = 0;
    for (const node of nodes) {
      const text = node.nodeValue;
      // Direct text normalization preserves length for Russian Ё/Е and casing.
      const n = text.toLocaleLowerCase('ru-RU').replace(/ё/g, 'е');
      let pos = 0, found = n.indexOf(query), fragments = null;
      if (found < 0) continue;
      fragments = document.createDocumentFragment();
      while (found >= 0 && matches < 250) {
        fragments.append(document.createTextNode(text.slice(pos, found)));
        const mark = document.createElement('mark');
        mark.textContent = text.slice(found, found + query.length);
        fragments.append(mark); matches += 1; pos = found + query.length;
        found = n.indexOf(query, pos);
      }
      fragments.append(document.createTextNode(text.slice(pos)));
      node.replaceWith(fragments);
      if (matches >= 250) break;
    }
  };
  const runSearch = () => {
    clearMarks();
    const query = normalize(search.value);
    if (query && !searching) userDetailsState = new Map(allDetails.map(d => [d, d.open]));
    searchOpened.forEach(d => { d.open = userDetailsState.get(d) || false; });
    searchOpened = new Set();
    searching = query.length > 0;
    root.classList.toggle('searching', searching);
    summary.hidden = !searching; clear.hidden = !searching;
    document.getElementById('guide-search-key').hidden = searching;
    const tokens = query.split(' ').filter(Boolean);
    let found = 0;
    index.forEach(({section, text}) => {
      const matches = !searching || tokens.every(t => text.includes(t));
      section.hidden = !matches;
      if (matches) {
        found += 1;
        if (searching) {
          section.querySelectorAll('details').forEach(d => {
            if (tokens.some(t => normalize(d.textContent).includes(t))) { d.open = true; searchOpened.add(d); }
          });
          highlight(section, query);
        }
      }
    });
    navLinks.forEach(link => {
      const target = document.getElementById(link.hash.slice(1));
      link.hidden = Boolean(target?.hidden);
    });
    document.querySelectorAll('.guide-nav-group').forEach(group => { group.hidden = !Array.from(group.querySelectorAll('a')).some(a => !a.hidden); });
    ['guide-hero', 'guide-launch'].forEach(id => { document.getElementById(id).hidden = searching; });
    empty.hidden = !searching || found > 0;
    count.textContent = searching ? `Найдено разделов: ${found} из ${chapters.length}. Поиск: «${search.value.trim()}».` : '';
    if (!searching) userDetailsState.clear();
  };
  search.addEventListener('input', () => { clearTimeout(timeout); timeout = setTimeout(runSearch, 120); });
  const clearSearch = (focus = true) => { clearTimeout(timeout); search.value = ''; runSearch(); if (focus) search.focus(); };
  clear.addEventListener('click', () => clearSearch());
  document.getElementById('guide-empty-reset').addEventListener('click', () => clearSearch());
  const setMenu = opened => {
    root.classList.toggle('menu-open', opened); menuToggle.setAttribute('aria-expanded', String(opened));
    const mobile = window.matchMedia('(max-width: 900px)').matches;
    nav.inert = mobile && !opened;
    if (opened) nav.querySelector('a:not([hidden])')?.focus();
  };
  menuToggle.addEventListener('click', () => setMenu(!root.classList.contains('menu-open')));
  shade.addEventListener('click', () => { setMenu(false); menuToggle.focus(); });
  const adapt = () => {
    const mobile = window.matchMedia('(max-width: 900px)').matches;
    if (!mobile) root.classList.remove('menu-open');
    nav.inert = mobile && !root.classList.contains('menu-open');
    menuToggle.setAttribute('aria-expanded', String(root.classList.contains('menu-open')));
  };
  window.addEventListener('resize', adapt); adapt();
  document.addEventListener('keydown', event => {
    const editing = event.target.closest('input,textarea,[contenteditable="true"]');
    if (event.key === '/' && !editing) { event.preventDefault(); search.focus(); }
    if (event.key === 'Escape') {
      if (root.classList.contains('menu-open')) { setMenu(false); menuToggle.focus(); }
      else if (search.value) clearSearch();
    }
    if (event.key === 'Tab' && root.classList.contains('menu-open')) {
      const focusable = Array.from(nav.querySelectorAll('a:not([hidden]),button:not([disabled])')).filter(el => el.offsetParent !== null);
      if (!focusable.length) return;
      if (event.shiftKey && document.activeElement === focusable[0]) { event.preventDefault(); focusable.at(-1).focus(); }
      else if (!event.shiftKey && document.activeElement === focusable.at(-1)) { event.preventDefault(); focusable[0].focus(); }
    }
  });
  const activate = id => navLinks.forEach(link => {
    if (link.hash === `#${id}`) link.setAttribute('aria-current', 'location');
    else link.removeAttribute('aria-current');
  });
  let currentId = '';
  const updateActive = () => {
    const visible = chapters.filter(c => !c.hidden);
    const threshold = 180;
    let chosen = visible[0];
    for (const chapter of visible) { if (chapter.getBoundingClientRect().top <= threshold) chosen = chapter; else break; }
    if (chosen && chosen.id !== currentId) { currentId = chosen.id; activate(currentId); }
  };
  let ticking = false;
  window.addEventListener('scroll', () => { if (!ticking) { ticking = true; requestAnimationFrame(() => { updateActive(); ticking = false; }); } }, {passive: true});
  document.addEventListener('click', event => {
    const anchor = event.target.closest('a[href^="#"]');
    if (!anchor || anchor.classList.contains('guide-section-link')) return;
    const id = anchor.hash.slice(1), target = document.getElementById(id);
    if (!target) return;
    event.preventDefault();
    if (target.hidden) clearSearch(false);
    setMenu(false);
    history.pushState(null, '', '#' + id);
    target.scrollIntoView({behavior: 'auto', block: 'start'});
    if (target.matches('.guide-section')) { target.tabIndex = -1; target.focus({preventScroll:true}); activate(id); }
  });
  document.querySelectorAll('[data-copy-section]').forEach(button => button.addEventListener('click', async () => {
    const id = button.dataset.copySection;
    const url = new URL(location.href); url.hash = id;
    try {
      if (!navigator.clipboard?.writeText) throw new Error('Clipboard unavailable');
      await navigator.clipboard.writeText(url.href);
      toast(location.protocol === 'file:' ? 'Скопирована локальная ссылка. Для коллег используйте адрес после размещения на сайте.' : 'Ссылка на раздел скопирована.');
    } catch (_) {
      history.replaceState(null, '', '#' + id);
      toast('Адрес раздела показан в адресной строке. Скопируйте его оттуда.');
    }
  }));
  document.getElementById('guide-expand').addEventListener('click', () => { allDetails.filter(d => !d.closest('.guide-section').hidden).forEach(d => { d.open = true; }); });
  document.getElementById('guide-collapse').addEventListener('click', () => { allDetails.forEach(d => { d.open = false; }); });
  let printState = [];
  window.addEventListener('beforeprint', () => { printState = allDetails.map(d => d.open); allDetails.forEach(d => { d.open = true; }); });
  window.addEventListener('afterprint', () => { allDetails.forEach((d,i) => { d.open = printState[i] ?? d.open; }); });
  document.getElementById('guide-print').addEventListener('click', () => window.print());
  const revealHash = () => {
    const target = document.getElementById(location.hash.slice(1));
    if (target?.matches('.guide-section')) {
      if (target.hidden) clearSearch(false);
      target.scrollIntoView({block:'start'}); activate(target.id);
    }
  };
  window.addEventListener('hashchange', revealHash);
  window.addEventListener('popstate', revealHash);
  if (location.hash) requestAnimationFrame(revealHash); else updateActive();
})();
