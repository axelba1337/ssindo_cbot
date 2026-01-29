{{-- resources/views/admin/dashboard.blade.php --}}
@extends('admin.layout')
@section('title','Dashboard')

@section('content')
<section class="card-grid">
  <div class="card">
    <div class="card__title">Total Pertanyaan (Hari Ini)</div>
    <div id="kpi-total" class="card__value">0</div>
  </div>
  <div class="card">
    <div class="card__title">Auto Answer Rate</div>
    <div id="kpi-auto" class="card__value">0%</div>
  </div>
  <div class="card">
    <div class="card__title">Avg Similarity</div>
    <div id="kpi-avg" class="card__value">0%</div>
  </div>
</section>

<section class="card">
  <div class="card__title">Tren 7 Hari</div>
  <canvas id="trendChart" height="140"></canvas>
</section>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="{{ asset('assets/landing/js/admin/dashboard.js') }}" defer></script>
@endsection
