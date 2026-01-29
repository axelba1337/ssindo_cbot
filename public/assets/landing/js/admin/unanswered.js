// assets/landing/admin/unanswered.js
// List pertanyaan "unanswered" + modal jadikan FAQ

(() => {
  const $  = (s) => document.querySelector(s);
  const tbody   = $('#ua-rows');
  const search  = $('#ua-search');

  const modal   = $('#ua-modal');
  const modalClose = $('#ua-modal-close');
  const modalCancel= $('#ua-cancel');
  const saveBtn    = $('#ua-save');

  const logIdEl  = $('#ua-log-id');
  const intentEl = $('#ua-intent');
  const qEl      = $('#ua-question');
  const aEl      = $('#ua-answer');

  let allRows = [];

  function notify(message, isError = false) {
    const box = document.getElementById('notif');
    const msg = document.getElementById('notif-msg');
    if (!box || !msg) return;

    msg.textContent = message;
    box.classList.remove('hidden','error');
    box.classList.add('show');
    if (isError) box.classList.add('error');

    setTimeout(() => {
      box.classList.remove('show');
      setTimeout(() => box.classList.add('hidden'), 300);
    }, 2500);
  }

  async function getJSON(url, opts = {}) {
    const r = await fetch(url, {
      headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
      ...opts,
    });
    if (!r.ok) throw new Error(`${r.status} ${r.statusText}`);
    return r.json();
  }

  function similarityPct(val) {
    const v = Number(val || 0);
    return `${Math.round(v * 100)}%`;
  }

  function renderTable(filter = '') {
    if (!tbody) return;
    const q = filter.trim().toLowerCase();

    const rows = allRows.filter(r => {
      if (!q) return true;
      return (r.user_message || '').toLowerCase().includes(q);
    });

    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="5">Tidak ada data.</td></tr>`;
      return;
    }

    tbody.innerHTML = '';
    rows.forEach(r => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${r.created_at ?? '-'}</td>
        <td>${r.session_id ?? '-'}</td>
        <td class="clip">${r.user_message}</td>
        <td>${similarityPct(r.similarity)}</td>
        <td>
          <button type="button" class="btn-secondary btn-sm" data-faq="${r.id}">
            Jadikan FAQ
          </button>
        </td>
      `;
      tbody.appendChild(tr);
    });
  }

  async function loadData() {
    try {
      const data = await getJSON('/api/admin/unanswered');
      allRows = Array.isArray(data) ? data : [];
      renderTable(search?.value || '');
    } catch (e) {
      if (tbody) tbody.innerHTML = `<tr><td colspan="5">Gagal memuat data.</td></tr>`;
      // console.error(e);
    }
  }

  function openModalFromRow(row) {
    logIdEl.value  = row.id;
    intentEl.value = row.intent || ''; // biasanya kosong, admin isi sendiri
    qEl.value      = row.user_message || '';
    aEl.value      = row.bot_answer || '';
    modal.classList.remove('hidden');
  }

  function closeModal() {
    modal.classList.add('hidden');
    logIdEl.value = '';
    intentEl.value = '';
    qEl.value = '';
    aEl.value = '';
  }

  // klik tombol "Jadikan FAQ" di tabel
  tbody?.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-faq]');
    if (!btn) return;
    const id = btn.getAttribute('data-faq');
    const row = allRows.find(r => String(r.id) === String(id));
    if (!row) return;
    openModalFromRow(row);
  });

  // close / cancel
  modalClose?.addEventListener('click', closeModal);
  modalCancel?.addEventListener('click', closeModal);

  // search
  search?.addEventListener('input', (e) => {
    renderTable(e.target.value);
  });

  // Simpan & Rebuild
  saveBtn?.addEventListener('click', async () => {
    const id = logIdEl.value;
    const payload = {
      intent:   intentEl.value,
      question: qEl.value,
      answer:   aEl.value,
    };

    if (!payload.question || !payload.answer) {
      notify('Pertanyaan dan jawaban wajib diisi.', true);
      return;
    }

    try {
      await getJSON(`/api/admin/unanswered/${id}/to-faq`, {
        method: 'POST',
        body: JSON.stringify(payload),
      });
      notify('FAQ berhasil dibuat & knowledge di-update.');
      closeModal();
      await loadData();
    } catch (e) {
      notify('Gagal menyimpan FAQ.', true);
      // console.error(e);
    }
  });

  // init
  loadData();
})();