// public/assets/landing/js/chatbot-widget-shell.js
(() => {
  const shell    = document.getElementById('cbot-shell');
  const launcher = document.getElementById('cbot-launcher');
  const btnMin   = document.getElementById('cbot-minimize');
  const btnRes   = document.getElementById('cbot-resize');
  const header   = shell ? shell.querySelector('#cbot .cbot__header') : null;

  if (!shell || !launcher || !header) return;

  let drag = { active: false, offsetX: 0, offsetY: 0 };

  function onMouseDown(e) {
    if (e.target.closest('button')) return;

    drag.active = true;
    const rect = shell.getBoundingClientRect();
    drag.offsetX = e.clientX - rect.left;
    drag.offsetY = e.clientY - rect.top;
    document.body.classList.add('cbot-dragging');
  }

  function onMouseMove(e) {
    if (!drag.active) return;

    const vw = window.innerWidth;
    const vh = window.innerHeight;

    let x = e.clientX - drag.offsetX;
    let y = e.clientY - drag.offsetY;

    const rect = shell.getBoundingClientRect();
    const w = rect.width;
    const h = rect.height;

    const pad = 8;
    x = Math.min(vw - w - pad, Math.max(pad, x));
    y = Math.min(vh - h - pad, Math.max(pad, y));

    shell.style.left = `${x}px`;
    shell.style.top  = `${y}px`;
    shell.style.bottom = 'auto';
  }

  function onMouseUp() {
    if (!drag.active) return;
    drag.active = false;
    document.body.classList.remove('cbot-dragging');
  }

  header.addEventListener('mousedown', onMouseDown);
  window.addEventListener('mousemove', onMouseMove);
  window.addEventListener('mouseup', onMouseUp);

  btnMin?.addEventListener('click', () => {
    shell.classList.add('cbot-shell--hidden');
    launcher.classList.remove('is-hidden');
  });

  launcher.addEventListener('click', () => {
    shell.classList.remove('cbot-shell--hidden');
    launcher.classList.add('is-hidden');
  });

  launcher.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      shell.classList.remove('cbot-shell--hidden');
      launcher.classList.add('is-hidden');
    }
  });

  btnRes?.addEventListener('click', () => {
    shell.classList.toggle('cbot-shell--wide');
  });
})();
