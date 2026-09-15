(() => {
  'use strict';
  const selection = document.querySelector('[data-selection-picker]');
  const offer = selection?.querySelector('[data-template-offer]');
  if (!selection || !offer) return;

  const positiveId = value => /^\d+$/.test(String(value)) && Number(value) > 0 ? Number(value) : null;
  const normalizedIds = values => [...new Set(values.map(positiveId).filter(value => value !== null))].sort((a, b) => a - b);
  const savedInstallerIds = normalizedIds((offer.dataset.savedInstallerIds || '').split(','));
  const savedOrderId = positiveId(offer.dataset.savedOrderId);
  const savedEngineerId = positiveId(offer.dataset.savedEngineerId);
  const currentEngineerId = positiveId(offer.dataset.currentEngineerId);
  const templateButton = offer.querySelector('form[action$="/template"] button');
  const exactCopy = offer.querySelector('[data-template-offer-exact]');
  const saveFirstCopy = offer.querySelector('[data-template-offer-save-first]');
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  let hidePending = false;

  const finalizeHide = () => {
    if (!hidePending) return;
    hidePending = false;
    offer.hidden = true;
    offer.classList.remove('fm2-order-helper--entering', 'fm2-order-helper--motion-start', 'fm2-order-helper--exiting');
  };
  const finalizeOpacityExit = event => {
    if (event.target === offer && event.propertyName === 'opacity') finalizeHide();
  };
  offer.addEventListener('transitionend', finalizeOpacityExit);
  offer.addEventListener('transitioncancel', finalizeOpacityExit);
  reducedMotion.addEventListener('change', event => { if (event.matches) finalizeHide(); });
  offer.addEventListener('animationend', event => {
    if (event.target === offer && event.animationName === 'fm2-template-offer-in') {
      offer.classList.remove('fm2-order-helper--entering');
    }
  });

  const reconcile = () => {
    const currentIds = [...selection.querySelectorAll('input[name="installerTabIds[]"]')].map(input => input.value);
    const installerIds = normalizedIds(currentIds);
    const ready = installerIds.length > 0 && currentEngineerId !== null;
    const exact = ready && savedOrderId !== null && currentEngineerId === savedEngineerId
      && installerIds.length === savedInstallerIds.length
      && installerIds.every((id, index) => id === savedInstallerIds[index]);

    if (templateButton) templateButton.disabled = !exact;
    if (exactCopy) exactCopy.hidden = !exact;
    if (saveFirstCopy) saveFirstCopy.hidden = exact;
    if ((ready && !offer.hidden && !offer.hasAttribute('inert')) || (!ready && (offer.hidden || hidePending))) return;

    if (ready) {
      hidePending = false;
      offer.classList.remove('fm2-order-helper--exiting');
      offer.hidden = false;
      offer.removeAttribute('inert');
      offer.classList.add('fm2-order-helper--entering');
      return;
    }

    offer.setAttribute('inert', '');
    offer.classList.remove('fm2-order-helper--entering');
    if (reducedMotion.matches) { hidePending = true; finalizeHide(); return; }
    offer.classList.add('fm2-order-helper--motion-start');
    void offer.offsetWidth;
    offer.classList.remove('fm2-order-helper--motion-start');
    hidePending = true;
    offer.classList.add('fm2-order-helper--exiting');
  };

  document.addEventListener('change', reconcile);
  document.addEventListener('click', reconcile);
  reconcile();
})();
