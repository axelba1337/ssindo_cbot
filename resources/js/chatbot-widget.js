// resources/js/chatbot-widget.js
(() => {
  const KEY_SID = 'cbot_session_id';
  const KEY_STK = 'cbot_session_tok';
  const qs = (s, r = document) => r.querySelector(s);

  const ROOT = qs('#cbot');
  const AVATAR_URL = ROOT?.dataset?.avatarUrl || '';

  function loadSession() {
    return {
      id: localStorage.getItem(KEY_SID),
      tok: localStorage.getItem(KEY_STK),
    };
  }

  function saveSession(id, tok) {
    if (id) localStorage.setItem(KEY_SID, String(id));
    if (tok) localStorage.setItem(KEY_STK, String(tok));
  }

  async function postQuery(message, sessionId) {
    const body = sessionId ? { message, session_id: sessionId } : { message };
    const res = await fetch('/api/chatbot/query', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.json();
  }

  function escapeHtml(s) {
    return String(s)
      .replace(/[&<>"']/g, m => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
      }[m]))
      .replace(/\n/g, '<br>');
  }

  function setTyping(on) {
    const wrap = qs('#cbot-messages');
    if (!wrap) return;
    let el = qs('#cbot-typing', wrap);

    if (on) {
      if (!el) {
        el = document.createElement('div');
        el.id = 'cbot-typing';
        el.className = 'typing';
        el.innerHTML = `
          <span class="dot"></span>
          <span class="dot"></span>
          <span class="dot"></span>
        `;
        wrap.appendChild(el);
      }
      wrap.scrollTop = wrap.scrollHeight;
    } else if (el) {
      el.remove();
    }
  }

  function renderMessage({ role, text }) {
    const wrap = qs('#cbot-messages');
    const tpl = qs('#cbot-message-template');
    if (!wrap || !tpl) return;

    const node = tpl.content.cloneNode(true);
    const root = node.querySelector('.msg');
    const txt = node.querySelector('.msg__text');
    const meta = node.querySelector('.msg__meta');
    const avatar = node.querySelector('.msg__avatar');

    root.classList.toggle('msg--user', role === 'user');
    root.classList.toggle('msg--bot', role !== 'user');

    txt.innerHTML = escapeHtml(text ?? '');
    if (meta) meta.style.display = 'none';

    if (role === 'user') {
      if (avatar) avatar.remove();
    } else {
      if (avatar && AVATAR_URL) {
        avatar.innerHTML = `<img src="${AVATAR_URL}" alt="Neev">`;
      }
    }

    wrap.appendChild(node);
    wrap.scrollTop = wrap.scrollHeight;
  }

  // Bubble khusus yang hanya berisi tombol WhatsApp Admin
  function renderWhatsAppOnlyBubble(link) {
    const wrap = qs('#cbot-messages');
    if (!wrap) return;

    const div = document.createElement('div');
    div.className = 'msg msg--bot';
    div.innerHTML = `
      <div class="msg__avatar">
        ${AVATAR_URL ? `<img src="${AVATAR_URL}" alt="Neev">` : ''}
      </div>
      <div class="msg__bubble">
        <a class="btn-wa-only" href="${link}" target="_blank" rel="noopener">
          WhatsApp Admin
        </a>
      </div>
    `;
    wrap.appendChild(div);
    wrap.scrollTop = wrap.scrollHeight;
  }

  function setBusy(on) {
    const b = qs('#cbot-send');
    const i = qs('#cbot-input');
    if (b) b.disabled = on;
    if (i) i.disabled = on;
  }

  const qbox = qs('#cbot-quick');

  function showQuick(items) {
    if (!qbox) return;
    qbox.innerHTML = '';
    items.forEach(t => {
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'quick__btn';
      b.textContent = t;
      b.onclick = () => send(t, true);
      qbox.appendChild(b);
    });
    qbox.hidden = false;
  }

  function hideQuick() {
    if (qbox) qbox.hidden = true;
  }

  let sid = loadSession().id || null;

  async function send(msg, fromQuick = false) {
    const input = qs('#cbot-input');
    if (!msg) return;

    renderMessage({ role: 'user', text: msg });
    if (!fromQuick && input) input.value = '';

    setTyping(true);
    setBusy(true);

    try {
      const res = await postQuery(msg, sid);
      sid = res.session_id || sid;
      saveSession(res.session_id, res.session_token);

      const answerText = res.answer || 'Baik, kami proses.';
      const hasWa = !!(res.cta_whatsapp?.show && res.cta_whatsapp?.link);
      const link = res.cta_whatsapp?.link || null;

      const containsWaUrl = /https:\/\/wa\.me\//.test(answerText);

      if (hasWa && containsWaUrl) {
        // Kasus khusus: pertanyaan "no wa admin" → hanya bubble tombol
        renderWhatsAppOnlyBubble(link);
      } else {
        // Jawaban biasa
        renderMessage({ role: 'assistant', text: answerText });

        // Jika perlu handoff (fallback / skor rendah), tambahkan bubble tombol di bawahnya
        if (hasWa) {
          renderWhatsAppOnlyBubble(link);
        }
      }
    } catch (e) {
      renderMessage({ role: 'assistant', text: 'Maaf, koneksi bermasalah. Coba lagi.' });
    } finally {
      setTyping(false);
      setBusy(false);
      hideQuick();
    }
  }

  function bind() {
    const f = qs('#cbot-form');
    const i = qs('#cbot-input');
    if (!f || !i) return;

    i.addEventListener('keydown', e => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        f.requestSubmit();
      }
    });

    f.addEventListener('submit', e => {
      e.preventDefault();
      const m = (i.value || '').trim();
      if (!m) return;
      send(m);
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    bind();
    renderMessage({
      role: 'assistant',
      text: 'Halo! Saya Neev Assistant. Pilih salah satu untuk mulai.',
    });
    showQuick([
      'Saya ingin pemasangan CCTV',
      'Produk CCTV apa yang tersedia?',
      'Jam operasional toko berapa?',
      'Butuh bantuan teknis',
    ]);
  });
})();