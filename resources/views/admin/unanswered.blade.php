{{-- resources/views/admin/unanswered.blade.php --}}
@extends('admin.layout')
@section('title','Unanswered')

@section('content')
<section class="card">
  <div class="card__title">Pertanyaan Belum Terjawab</div>

  <div class="table-actions">
    <input id="ua-search" type="text" placeholder="Cari pesan...">
  </div>

  <table class="table">
    <thead>
      <tr>
        <th>Waktu</th>
        <th>Session</th>
        <th>Pesan</th>
        <th>Similarity</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody id="ua-rows">
      <tr>
        <td colspan="5">Memuat data...</td>
      </tr>
    </tbody>
  </table>
</section>

{{-- MODAL Jadikan FAQ (default: hidden) --}}
<div id="ua-modal" class="modal hidden">
  <div class="modal__dialog">
    <div class="modal__header">
      <h2>Jadikan FAQ</h2>
      <button type="button" id="ua-modal-close" class="modal__close">&times;</button>
    </div>
    <div class="modal__body">
      <form id="ua-form">
        <input type="hidden" id="ua-log-id">

        <div class="form-grid">
          <label>
            Intent
            <input id="ua-intent" type="text" placeholder="mis. troubleshoot_cctv">
          </label>
          <label>
            Pertanyaan
            <textarea id="ua-question" rows="3" placeholder="Tulis pertanyaan"></textarea>
          </label>
          <label style="grid-column:1/-1">
            Jawaban
            <textarea id="ua-answer" rows="4" placeholder="Tulis jawaban"></textarea>
          </label>
        </div>
      </form>
    </div>
    <div class="modal__footer">
      <button type="button" id="ua-save" class="btn-primary">Simpan &amp; Rebuild</button>
      <button type="button" id="ua-cancel" class="btn-secondary">Batal</button>
    </div>
  </div>
</div>
@endsection

@section('scripts')
@vite(['resources/js/admin/unanswered.js'])
@endsection