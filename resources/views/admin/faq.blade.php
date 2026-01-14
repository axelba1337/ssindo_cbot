{{-- resources/views/admin/faq.blade.php --}}
@extends('admin.layout')
@section('title','FAQ')

@section('content')
<section class="card">
  <div class="card__title">Tambah / Ubah FAQ</div>

  <form id="faq-form" class="form-grid">
    <input type="hidden" id="faq-id" name="id" />

    <label>
      Intent
      <input type="text" id="faq-intent" name="intent" placeholder="mis. order_flow">
    </label>

    <label>
      Pertanyaan
      <input type="text" id="faq-question" name="question" placeholder="Tulis pertanyaan">
    </label>

    <label style="grid-column:1 / -1;">
      Jawaban
      <textarea id="faq-answer" name="answer" rows="4" placeholder="Tulis jawaban"></textarea>
    </label>

    <div class="row-actions">
      <button type="submit" class="btn-primary" id="faq-save">
        Simpan &amp; Rebuild Knowledge
      </button>
      <button type="button" class="btn-secondary" id="faq-reset">
        Reset
      </button>
      {{-- checkbox "Aktif" DIHILANGKAN --}}
      {{-- tombol Rebuild Knowledge terpisah DIHILANGKAN --}}
    </div>
  </form>
</section>

<section class="card">
  <div class="card__title">Daftar FAQ</div>

  <div class="table-actions">
    <input type="text" id="faq-search" placeholder="Cari intent/pertanyaan...">
  </div>

  <table class="table">
    <thead>
      <tr>
        <th style="width:60px;">ID</th>
        <th style="width:160px;">Intent</th>
        <th>Pertanyaan</th>
        <th style="width:90px;">Status</th>
        <th style="width:180px;">Updated</th>
        <th style="width:140px;">Aksi</th>
      </tr>
    </thead>
    <tbody id="faq-rows">
      <tr>
        <td colspan="6">Memuat data...</td>
      </tr>
    </tbody>
  </table>
</section>
@endsection

@section('scripts')
@vite(['resources/js/admin/faq.js'])
@endsection