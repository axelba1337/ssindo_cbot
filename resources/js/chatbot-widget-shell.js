// resources/js/chatbot-widget-shell.js
// Khusus halaman /chatbot/widget
// - drag lewat header
// - minimize → tampilkan launcher bulat
// - toggle resize (compact / wide) pakai icon resize

(() => {
  const shell    = document.getElementById('cbot-shell');
  const launcher = document.getElementById('cbot-launcher');
  const btnMin   = document.getElementById('cbot-minimize');
  const btnRes   = document.getElementById('cbot-resize');
  const header   = shell ? shell.querySelector('#cbot .cbot__header') : null;

  if (!shell || !launcher || !header) return;

  // ------ DRAG ------
  let drag = { active: false, offsetX: 0, offsetY: 0 };

  function onMouseDown(e) {
    // drag hanya jika klik di header, bukan di tombol
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

    // batas supaya tetap di layar
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

  // ------ MINIMIZE / SHOW ------
  btnMin?.addEventListener('click', () => {
    shell.classList.add('cbot-shell--hidden');
    launcher.classList.remove('is-hidden');
  });

  launcher.addEventListener('click', () => {
    shell.classList.remove('cbot-shell--hidden');
    launcher.classList.add('is-hidden');
  });

  // ------ RESIZE TOGGLE (compact / wide) ------
  btnRes?.addEventListener('click', () => {
    shell.classList.toggle('cbot-shell--wide');
  });

})();