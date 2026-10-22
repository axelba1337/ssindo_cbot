{{-- resources/views/chatbot/_widget.blade.php --}}
<div id="cbot"
     class="cbot cbot--widget"
     data-avatar-url="{{ asset('images/neev-logo.png') }}">
  <div class="cbot__header">
    <div class="cbot__brand">
      <img src="{{ asset('images/neev-logo.png') }}" alt="Neev" class="cbot__brand-logo">
      <span class="cbot__identity">
        <span class="cbot__title">Neev Assistant</span>
        <span class="cbot__subtitle">Asisten informasi otomatis</span>
      </span>
    </div>
    <div id="cbot-header-cta"></div>
  </div>

  <div id="cbot-messages" class="cbot__messages" aria-live="polite" aria-relevant="additions"></div>
  <div id="cbot-quick" class="quick" hidden></div>

  <form id="cbot-form" class="cbot__input" autocomplete="off">
    <input id="cbot-input" type="text" maxlength="2000" aria-label="Pesan untuk Neev Assistant" placeholder="Tulis pesan..." enterkeyhint="send">
    <button id="cbot-send" type="submit" aria-label="Kirim pesan">
      <img src="{{ asset('images/paper-plane.svg') }}" alt="" class="icon-send">
    </button>
  </form>

  <template id="cbot-message-template">
    <div class="msg">
      <div class="msg__avatar" aria-hidden="true"></div>
      <div class="msg__bubble">
        <div class="msg__text"></div>
        <div class="msg__meta"></div>
      </div>
    </div>
  </template>
</div>
