@extends('layouts.dashboard')

@section('title', 'Create Metrics')

@section('content')
    <div class="content-header ms-5">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="content-title">Create Metrics</h1>
                <p class="content-subtitle">Pilih metrics yang ingin Anda track untuk bisnis Anda</p>
            </div>
            <a href="{{ route('dashboard.metrics') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>
                Kembali ke Metrics
            </a>
        </div>
    </div>

    <div class="content-body ms-5">
        <div class="dashboard-card">
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-8">
                        <h5 class="card-title fw-bold text-primary mb-3">
                            <i class="bi bi-check-square me-2"></i>
                            Metrics Mana Yang Ingin Anda Pilih?
                        </h5>
                        <p class="text-muted">
                            Pilih metrics yang paling relevan dengan tujuan bisnis Anda. Anda selalu bisa menambah metrics lainnya nanti.
                        </p>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex gap-2">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" id="metricsSearch" placeholder="Cari metrics...">
                            </div>
                            <select class="form-select" id="categoryFilter" style="max-width: 150px;">
                                <option value="">Semua Kategori</option>
                                <option value="Penjualan">Penjualan</option>
                                <option value="Keuangan">Keuangan</option>
                                <option value="Pelanggan">Pelanggan</option>
                                <option value="Produk">Produk</option>
                            </select>
                        </div>
                    </div>
                </div>

                <form action="{{ route('dashboard.metrics.store') }}" method="POST" id="metricsForm">
                    @csrf
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4" id="metricsContainer">
                        @foreach($availableMetrics as $metricName => $metricData)
                            @if(!in_array($metricName, $importedMetrics))
                                <div class="col metric-item" data-category="{{ $metricData['category'] }}">
                                    <div class="metric-card h-100" data-metric-name="{{ $metricName }}" data-category="{{ $metricData['category'] }}">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div class="metric-icon">
                                                    <i class="bi {{ $metricData['icon'] }}" style="font-size: 2rem;"></i>
                                                </div>
                                                <span class="badge bg-primary">{{ $metricData['category'] }}</span>
                                            </div>

                                            <h6 class="card-title">{{ $metricName }}</h6>
                                            <p class="card-text">{{ $metricData['description'] }}</p>
                                            <small class="text-muted">Unit: {{ $metricData['unit'] }}</small>

                                            <!-- Hidden input for selected metrics -->
                                            <input type="checkbox"
                                                   class="d-none"
                                                   id="metric_{{ $loop->index }}"
                                                   name="selected_metrics[]"
                                                   value="{{ $metricName }}"
                                                   data-category="{{ $metricData['category'] }}">

                                            <div class="selection-indicator mt-auto">
                                                <div class="selected-badge d-none">
                                                    <i class="bi bi-check-circle-fill me-2"></i>
                                                    Terpilih
                                                </div>
                                                <div class="select-prompt">
                                                    <i class="bi bi-plus-circle me-2"></i>
                                                    Klik untuk memilih
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="col metric-item" data-category="{{ $metricData['category'] }}">
                                    <div class="metric-card h-100 disabled">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div class="metric-icon">
                                                    <i class="bi {{ $metricData['icon'] }}" style="font-size: 2rem; opacity: 0.5;"></i>
                                                </div>
                                                <span class="badge bg-success">Sudah Diimport</span>
                                            </div>

                                            <h6 class="card-title text-muted">{{ $metricName }}</h6>
                                            <p class="card-text text-muted">{{ $metricData['description'] }}</p>
                                            <small class="text-muted">Unit: {{ $metricData['unit'] }}</small>

                                            <div class="selection-indicator mt-auto">
                                                <div class="selected-badge d-none text-success">
                                                    <i class="bi bi-check-circle-fill me-2"></i>
                                                    Sudah diimport
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach

                        @if(count($availableMetrics) == count($importedMetrics))
                            <div class="col-12">
                                <div class="alert alert-info text-center persistent-card">
                                    <i class="bi bi-check-circle me-2"></i>
                                    All metrics available have been imported to your dashboard!
                                    <br>
                                    <a href="{{ route('dashboard.metrics') }}" class="btn btn-primary mt-3">
                                        <i class="bi bi-arrow-left me-2"></i>
                                        Back to Dashboard Metrics
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Selected Metrics Summary -->
                    <div class="row mt-5" id="selectedSummary" style="display: none;">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <h6 class="alert-heading">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Ringkasan Metrics Terpilih
                                </h6>
                                <div id="selectedMetricsList"></div>
                                <hr>
                                <p class="mb-0">
                                    <strong>Total Terpilih:</strong> <span id="selectedCount">0</span> metrics
                                </p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Floating Import Button -->
        @if(count($availableMetrics) > count($importedMetrics))
        <button type="submit" form="metricsForm" class="import-btn" id="importBtn" disabled>
            <i class="bi bi-plus-circle me-2"></i>
            Import Metrics Terpilih
        </button>
        @endif
    </div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard/dashboard-metrics.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/dashboard/dashboard-metrics/create-new.js') }}"></script>
@endpush
