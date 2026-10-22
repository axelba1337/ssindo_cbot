{{-- resources/views/chatbot/widget.blade.php --}}
@extends('layouts.app')
@section('title', 'Neev Assistant')

@section('content')
  <link rel="stylesheet" href="{{ asset('assets/landing/css/chatbot-widget.css') }}?v={{ filemtime(public_path('assets/landing/css/chatbot-widget.css')) }}">
  <div style="padding:clamp(12px, 3vw, 24px)">
    @include('chatbot._widget')
  </div>
  <script>window.CBOT_DEBUG = false;</script>
@endsection
