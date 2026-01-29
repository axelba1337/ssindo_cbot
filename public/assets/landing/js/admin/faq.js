// assets/landing/admin/faq.js
// CRUD FAQ + auto rebuild knowledge + NOTIFIKASI

(() => {
  const $  = (s) => document.querySelector(s);
  const $$ = (s) => document.querySelectorAll(s);

  const form       = $('#faq-form');
  const idInput    = $('#faq-id');
  const intentEl   = $('#faq-intent');
  const questionEl = $('#faq-question');
  const answerEl   = $('#faq-answer');
  const resetBtn   = $('#faq-reset');
  const searchEl   = $('#faq-search');
  const tbody      = $('#faq-rows');

  let allRows = [];

  // ==========================
  // NOTIFIKASI
  // ==========================
  function notify(message, isError = false) {
    const box = document.getElementById('notif');
    const msg = document.getElementById('notif-msg');

    if (!box || !msg) return;

    msg.textContent = message;

    box.classList.remove('hidden', 'error');
    box.classList.add('show');

    if (isError) box.classList.add('error');

    setTimeout(() => {
      box.classList.remove('show');
      setTimeout(() => box.classList.add('hidden'), 300);
    }, 2500);
  }

  async function getJSON(url, opts = {}) {
    const res = await fetch(url, {
      headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
      ...opts,
    });
    if (!res.ok) throw new Error(`${res.status} ${res.statusText}`);
    return res.json();
  }

  function renderTable(filter = '') {
    if (!tbody) return;
    const q = filter.trim().toLowerCase();

    const rows = allRows.filter(r => {
      if (!q) return true;
      return (
        (r.intent || '').toLowerCase().includes(q) ||
        (r.question || '').toLowerCase().includes(q)
      );
    });

    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="6">Tidak ada data.</td></tr>`;
      return;
    }

    tbody.innerHTML = '';
    rows.forEach(r => {
      const tr = document.createElement('tr');

      const status = r.is_active ? 'Aktif' : 'Nonaktif';

      tr.innerHTML = `
        <td>${r.id}</td>
        <td>${r.intent ?? '-'}</td>
        <td class="clip">${r.question}</td>
        <td>${status}</td>
        <td>${r.updated_at ?? '-'}</td>
        <td>
          <button type="button" class="btn-secondary btn-sm" data-edit="${r.id}">Edit</button>
          <button type="button" class="danger btn-sm" data-del="${r.id}">Hapus</button>
        </td>
      `;
      tbody.appendChild(tr);
    });
  }

  async function loadFaq() {
    try {
      const data = await getJSON('/api/admin/faq');
      allRows = Array.isArray(data) ? data : [];
      renderTable(searchEl?.value || '');
      notify("FAQ berhasil dimuat");
    } catch (e) {
      if (tbody) tbody.innerHTML = `<tr><td colspan="6">Gagal memuat data.</td></tr>`;
      notify("Gagal memuat FAQ", true);
    }
  }

  function resetForm() {
    idInput.value    = '';
    intentEl.value   = '';
    questionEl.value = '';
    answerEl.value   = '';
  }

  // ==========================
  // SUBMIT (CREATE/UPDATE)
  // ==========================
  form?.addEventListener('submit', async (ev) => {
    ev.preventDefault();

    const payload = {
      id:       idInput.value || null,
      intent:   intentEl.value,
      question: questionEl.value,
      answer:   answerEl.value,
    };

    if (!payload.question || !payload.answer) {
      notify("Pertanyaan dan jawaban wajib diisi", true);
      return;
    }

    try {
      await getJSON('/api/admin/faq', {
        method: 'POST',
        body: JSON.stringify(payload),
      });

      notify("FAQ berhasil disimpan & knowledge diperbarui");

      await loadFaq();
      resetForm();
    } catch (e) {
      notify("Gagal menyimpan FAQ", true);
    }
  });

  resetBtn?.addEventListener('click', (ev) => {
    ev.preventDefault();
    resetForm();
    notify("Form dibersihkan");
  });

  // filter
  searchEl?.addEventListener('input', (e) => {
    renderTable(e.target.value);
  });

  // ==========================
  // EDIT / DELETE
  // ==========================
  tbody?.addEventListener('click', async (e) => {
    const btn = e.target.closest('button');
    if (!btn) return;

    const id = btn.getAttribute('data-edit') || btn.getAttribute('data-del');
    if (!id) return;

    if (btn.hasAttribute('data-edit')) {
      const row = allRows.find(r => String(r.id) === String(id));
      if (!row) return;

      idInput.value    = row.id;
      intentEl.value   = row.intent ?? '';
      questionEl.value = row.question ?? '';
      answerEl.value   = row.answer ?? '';

      window.scrollTo({ top: 0, behavior: 'smooth' });

      notify("Mode edit diaktifkan");
    }

    if (btn.hasAttribute('data-del')) {
      if (!confirm(`Hapus FAQ #${id}?`)) return;

      try {
        await getJSON(`/api/admin/faq/${id}`, { method: 'DELETE' });
        notify("FAQ berhasil dihapus");
        await loadFaq();
      } catch (err) {
        notify("Gagal menghapus FAQ", true);
      }
    }
  });

  loadFaq();
})();