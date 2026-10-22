// public/assets/landing/js/chatbot-widget-shell.js
(() => {
  const shell = document.getElementById('cbot-shell');
  const launcher = document.getElementById('cbot-launcher');
  const minimizeButton = document.getElementById('cbot-minimize');
  const resizeButton = document.getElementById('cbot-resize');
  const header = shell?.querySelector('#cbot .cbot__header');
  const input = shell?.querySelector('#cbot-input');
  const mobileQuery = window.matchMedia('(max-width: 767px)');
  const touchQuery = window.matchMedia('(pointer: coarse)');

  if (!shell || !launcher || !header) return;

  const edgePadding = 8;
  let drag = { active: false, pointerId: null, offsetX: 0, offsetY: 0 };
  let resizeFrame = null;

  function syncViewport() {
    const viewport = window.visualViewport;
    const height = viewport?.height || window.innerHeight;
    const top = viewport?.offsetTop || 0;
    const keyboardOffset = Math.max(0, window.innerHeight - height - top);
    shell.style.setProperty('--cbot-viewport-height', `${height}px`);
    shell.style.setProperty('--cbot-viewport-top', `${top}px`);
    shell.style.setProperty('--cbot-keyboard-offset', `${keyboardOffset}px`);
    launcher.style.setProperty('--cbot-keyboard-offset', `${keyboardOffset}px`);
    if (resizeButton) {
      resizeButton.hidden = mobileQuery.matches || touchQuery.matches;
      resizeButton.disabled = resizeButton.hidden;
    }
  }

  function resetPosition() {
    ['left', 'right', 'top', 'bottom'].forEach(property => {
      shell.style.removeProperty(property);
    });
  }

  function clampPosition() {
    if (shell.classList.contains('cbot-shell--hidden')) return;

    if (mobileQuery.matches || touchQuery.matches) {
      resetPosition();
      return;
    }

    const hasCustomPosition = Boolean(shell.style.left || shell.style.top);
    if (!hasCustomPosition) return;

    const rect = shell.getBoundingClientRect();
    const maxX = Math.max(edgePadding, window.innerWidth - rect.width - edgePadding);
    const visibleTop = window.visualViewport?.offsetTop || 0;
    const visibleHeight = window.visualViewport?.height || window.innerHeight;
    const maxY = Math.max(visibleTop + edgePadding, visibleTop + visibleHeight - rect.height - edgePadding);
    const x = Math.min(maxX, Math.max(edgePadding, rect.left));
    const y = Math.min(maxY, Math.max(visibleTop + edgePadding, rect.top));

    shell.style.left = `${x}px`;
    shell.style.top = `${y}px`;
    shell.style.right = 'auto';
    shell.style.bottom = 'auto';
  }

  function setOpen(isOpen, returnFocus = false) {
    shell.classList.toggle('cbot-shell--hidden', !isOpen);
    shell.inert = !isOpen;
    shell.setAttribute('aria-hidden', String(!isOpen));
    launcher.classList.toggle('is-hidden', isOpen);
    launcher.setAttribute('aria-expanded', String(isOpen));

    if (isOpen) {
      window.requestAnimationFrame(() => {
        syncViewport();
        clampPosition();
        if (!mobileQuery.matches && !touchQuery.matches) input?.focus({ preventScroll: true });
      });
    } else if (returnFocus) {
      launcher.focus({ preventScroll: true });
    }

    document.dispatchEvent(new CustomEvent('neev:assistant-state', { detail: { open: isOpen } }));
  }

  function openAssistant(question = '') {
    const message = typeof question === 'string' ? question.trim() : '';
    if (message && input) {
      input.value = message;
      input.dispatchEvent(new Event('input', { bubbles: true }));
    }

    setOpen(true);
  }

  function onPointerDown(event) {
    if (mobileQuery.matches || touchQuery.matches || event.pointerType !== 'mouse' || event.button !== 0 || event.target.closest('button')) return;

    const rect = shell.getBoundingClientRect();
    drag = {
      active: true,
      pointerId: event.pointerId,
      offsetX: event.clientX - rect.left,
      offsetY: event.clientY - rect.top,
    };

    header.setPointerCapture?.(event.pointerId);
    document.body.classList.add('cbot-dragging');
    event.preventDefault();
  }

  function onPointerMove(event) {
    if (!drag.active || event.pointerId !== drag.pointerId) return;

    const rect = shell.getBoundingClientRect();
    const maxX = Math.max(edgePadding, window.innerWidth - rect.width - edgePadding);
    const maxY = Math.max(edgePadding, window.innerHeight - rect.height - edgePadding);
    const x = Math.min(maxX, Math.max(edgePadding, event.clientX - drag.offsetX));
    const y = Math.min(maxY, Math.max(edgePadding, event.clientY - drag.offsetY));

    shell.style.left = `${x}px`;
    shell.style.top = `${y}px`;
    shell.style.right = 'auto';
    shell.style.bottom = 'auto';
  }

  function onPointerUp(event) {
    if (!drag.active || event.pointerId !== drag.pointerId) return;

    header.releasePointerCapture?.(event.pointerId);
    drag = { active: false, pointerId: null, offsetX: 0, offsetY: 0 };
    document.body.classList.remove('cbot-dragging');
  }

  function handleViewportResize() {
    if (resizeFrame) window.cancelAnimationFrame(resizeFrame);
    resizeFrame = window.requestAnimationFrame(() => {
      syncViewport();
      if ((mobileQuery.matches || touchQuery.matches) && drag.active) onPointerUp({ pointerId: drag.pointerId });
      clampPosition();
    });
  }

  header.addEventListener('pointerdown', onPointerDown);
  header.addEventListener('pointermove', onPointerMove);
  header.addEventListener('pointerup', onPointerUp);
  header.addEventListener('pointercancel', onPointerUp);

  minimizeButton?.addEventListener('click', () => setOpen(false, true));
  launcher.addEventListener('click', () => setOpen(true));

  document.addEventListener('neev:assistant-open', event => {
    openAssistant(event.detail?.question);
  });

  window.NeevAssistant = window.NeevAssistant || {};
  window.NeevAssistant.open = openAssistant;
  window.NeevAssistant.close = () => setOpen(false);

  resizeButton?.addEventListener('click', () => {
    if (mobileQuery.matches || touchQuery.matches) return;
    const isWide = shell.classList.toggle('cbot-shell--wide');
    resizeButton.setAttribute('aria-pressed', String(isWide));
    window.requestAnimationFrame(clampPosition);
  });

  window.addEventListener('resize', handleViewportResize);
  window.visualViewport?.addEventListener('resize', handleViewportResize);
  window.visualViewport?.addEventListener('scroll', handleViewportResize);
  mobileQuery.addEventListener('change', handleViewportResize);
  touchQuery.addEventListener('change', handleViewportResize);

  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && !shell.classList.contains('cbot-shell--hidden')) {
      setOpen(false, true);
    }
  });

  const startsOpen = !shell.classList.contains('cbot-shell--hidden');
  syncViewport();
  setOpen(startsOpen);
  resizeButton?.setAttribute('aria-pressed', String(shell.classList.contains('cbot-shell--wide')));
})();
