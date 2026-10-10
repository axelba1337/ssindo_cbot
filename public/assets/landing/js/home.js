(() => {
  const body = document.body;
  const menuButton = document.querySelector('[data-menu-toggle]');
  const navigation = document.querySelector('[data-main-nav]');
  const modal = document.getElementById('redirect-modal');
  const dialog = modal?.querySelector('[role="dialog"]');
  const continueButton = modal?.querySelector('[data-modal-continue]');
  const NEEVWORK_URL = body.dataset.neevworkUrl || 'https://neevwork.com/';
  let lastFocusedElement = null;

  function setMenuState(isOpen) {
    navigation?.classList.toggle('is-open', isOpen);
    menuButton?.setAttribute('aria-expanded', String(isOpen));

    const accessibleLabel = menuButton?.querySelector('.sr-only');
    if (accessibleLabel) accessibleLabel.textContent = isOpen ? 'Tutup menu' : 'Buka menu';
  }

  function closeMenu() {
    setMenuState(false);
  }

  menuButton?.addEventListener('click', () => {
    const isOpen = menuButton.getAttribute('aria-expanded') === 'true';
    setMenuState(!isOpen);
  });

  navigation?.querySelectorAll('a[href^="#"]').forEach(link => {
    link.addEventListener('click', closeMenu);
  });

  function openModal(trigger) {
    if (!modal || !dialog) return;

    lastFocusedElement = trigger;
    modal.hidden = false;
    body.classList.add('modal-open');
    window.requestAnimationFrame(() => dialog.focus());
  }

  function closeModal() {
    if (!modal || modal.hidden) return;

    modal.hidden = true;
    body.classList.remove('modal-open');
    lastFocusedElement?.focus();
  }

  document.querySelectorAll('[data-neevwork-cta]').forEach(trigger => {
    trigger.addEventListener('click', event => {
      event.preventDefault();
      openModal(trigger);
    });
  });

  modal?.querySelectorAll('[data-modal-close]').forEach(button => {
    button.addEventListener('click', closeModal);
  });

  modal?.querySelector('[data-modal-backdrop]')?.addEventListener('click', event => {
    if (event.target === event.currentTarget) closeModal();
  });

  continueButton?.addEventListener('click', () => {
    window.location.href = NEEVWORK_URL;
  });

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
      if (modal && !modal.hidden) closeModal();
      else closeMenu();
      return;
    }

    if (event.key !== 'Tab' || !modal || modal.hidden || !dialog) return;

    const focusable = [...dialog.querySelectorAll(
      'button:not([disabled]), a[href], input:not([disabled]), [tabindex]:not([tabindex="-1"])'
    )];
    if (focusable.length === 0) return;

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  });

  document.addEventListener('click', event => {
    if (!navigation?.classList.contains('is-open')) return;
    if (navigation.contains(event.target) || menuButton?.contains(event.target)) return;
    closeMenu();
  });

  window.addEventListener('resize', () => {
    if (window.innerWidth > 920) closeMenu();
  });
})();
