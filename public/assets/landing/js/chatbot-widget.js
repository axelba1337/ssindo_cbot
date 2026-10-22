// public/assets/landing/js/chatbot-widget.js
(() => {
  const qs = (s, r = document) => r.querySelector(s);

  const ROOT = qs('#cbot');
  if (!ROOT) return;

  const AVATAR_URL = ROOT.dataset.avatarUrl || '';

  async function postQuery(message, sessionToken) {
    const body = sessionToken ? { message, session_token: sessionToken } : { message };
    const res = await fetch('/api/chatbot/query', {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
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
      return;
    }

    if (el) el.remove();
  }

  function safeWebUrl(value) {
    try {
      const url = new URL(String(value));
      return ['http:', 'https:'].includes(url.protocol) ? url.href : null;
    } catch {
      return null;
    }
  }

  function renderMessage({ role, text, sources = [] }) {
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

    if (role !== 'user' && Array.isArray(sources) && sources.length > 0) {
      const sourceWrap = document.createElement('div');
      sourceWrap.className = 'msg__sources';

      const label = document.createElement('strong');
      label.textContent = 'Sources';
      sourceWrap.appendChild(label);

      const list = document.createElement('ul');
      sources.forEach(source => {
        const href = safeWebUrl(source?.url);
        if (!href) return;

        const item = document.createElement('li');
        const link = document.createElement('a');
        link.href = href;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.textContent = source?.title || new URL(href).hostname;
        item.appendChild(link);
        list.appendChild(item);
      });

      if (list.children.length > 0) {
        sourceWrap.appendChild(list);
        txt.insertAdjacentElement('afterend', sourceWrap);
      }
    }

    if (role === 'user') {
      if (avatar) avatar.remove();
    } else {
      if (avatar && AVATAR_URL) {
        const image = document.createElement('img');
        image.src = AVATAR_URL;
        image.alt = 'Neev';
        avatar.replaceChildren(image);
      }
    }

    wrap.appendChild(node);
    wrap.scrollTop = wrap.scrollHeight;
  }

  function renderWhatsAppOnlyBubble(link) {
    const wrap = qs('#cbot-messages');
    const href = safeWebUrl(link);
    if (!wrap || !href || new URL(href).hostname !== 'wa.me') return;

    const div = document.createElement('div');
    div.className = 'msg msg--bot';

    const avatar = document.createElement('div');
    avatar.className = 'msg__avatar';
    if (AVATAR_URL) {
      const image = document.createElement('img');
      image.src = AVATAR_URL;
      image.alt = 'Neev';
      avatar.appendChild(image);
    }

    const bubble = document.createElement('div');
    bubble.className = 'msg__bubble';
    const anchor = document.createElement('a');
    anchor.className = 'btn-wa-only';
    anchor.href = href;
    anchor.target = '_blank';
    anchor.rel = 'noopener noreferrer';
    anchor.textContent = 'WhatsApp Admin';
    bubble.appendChild(anchor);
    div.append(avatar, bubble);
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

  // The widget does not restore old visible messages after a page refresh, so
  // start a fresh backend conversation too. This prevents invisible history
  // from affecting a new chat.
  let sid = null;

  async function send(msg, fromQuick = false) {
    const input = qs('#cbot-input');
    if (!msg) return;

    renderMessage({ role: 'user', text: msg });
    if (!fromQuick && input) input.value = '';

    setTyping(true);
    setBusy(true);

    try {
      const res = await postQuery(msg, sid);
      sid = res.session_token || res.session_id || sid;

      const answerText = res.answer || 'Baik, kami proses.';
      const hasWa = !!(res.cta_whatsapp?.show && res.cta_whatsapp?.link);
      const link = res.cta_whatsapp?.link || null;

      const containsWaUrl = /https:\/\/wa\.me\//.test(answerText);

      if (hasWa && containsWaUrl) {
        renderWhatsAppOnlyBubble(link);
      } else {
        renderMessage({
          role: 'assistant',
          text: answerText,
          sources: res.sources || [],
        });
        if (hasWa) renderWhatsAppOnlyBubble(link);
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

    const wrap = qs('#cbot-messages');
    const alreadyHasMsg = wrap && wrap.children && wrap.children.length > 0;

    if (!alreadyHasMsg) {
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
    }
  });
})();
