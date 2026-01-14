{{-- resources/views/chatbot/widget.blade.php --}}
@extends('layouts.app')
@section('title', 'Neev Assistant')

@section('content')
<div id="cbot"
     class="cbot cbot--widget"
     aria-live="polite"
     data-avatar-url="{{ asset('images/neev-logo.png') }}">
  <div class="cbot__header">
    <div class="cbot__brand">
      <img src="{{ asset('images/neev-logo.png') }}" alt="Neev" class="cbot__brand-logo">
      <span class="cbot__title">Neev Assistant</span>
      <span id="cbot-status" class="badge">Online</span>
    </div>
    <div id="cbot-header-cta"></div>
  </div>

  <div id="cbot-messages" class="cbot__messages"></div>
  <div id="cbot-quick" class="quick" hidden></div>

  <form id="cbot-form" class="cbot__input" autocomplete="off">
    <input id="cbot-input" type="text" placeholder="Tulis pesan..." />
    <button id="cbot-send" type="submit" aria-label="Kirim">
      <img src="{{ asset('images/paper-plane.svg') }}" alt="Kirim" class="icon-send">
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

<script>window.CBOT_DEBUG = false;</script>
@endsection