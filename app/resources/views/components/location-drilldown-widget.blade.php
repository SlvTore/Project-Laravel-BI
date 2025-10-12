{{-- OLAP Location Drill-Down Widget Component --}}
<div class="dashboard-card p-4 mb-4" id="location-drilldown-widget">
    {{-- Header with Level Selector --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
            <i class="bi bi-geo-alt-fill me-2 text-primary"></i>
            Analisis Penjualan Per Lokasi
        </h5>
        <div class="btn-group btn-group-sm" role="group" aria-label="Level selector">
            <button type="button" class="btn btn-outline-primary active" data-level="provinsi" title="Lihat per Provinsi">
                <i class="bi bi-map"></i> Provinsi
            </button>
            <button type="button" class="btn btn-outline-primary" data-level="kota" title="Lihat per Kota">
                <i class="bi bi-building"></i> Kota
            </button>
            <button type="button" class="btn btn-outline-primary" data-level="kecamatan" title="Lihat per Kecamatan">
                <i class="bi bi-pin-map"></i> Kecamatan
            </button>
        </div>
    </div>

    {{-- Breadcrumb Navigation for Drill-Up --}}
    <nav aria-label="breadcrumb" id="drill-breadcrumb" class="mb-3" style="display: none;">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
                <a href="#" class="drill-up" data-level="provinsi">
                    <i class="bi bi-house-door"></i> Semua Provinsi
                </a>
            </li>
            {{-- Dynamic breadcrumbs will be added by JavaScript --}}
        </ol>
    </nav>

    {{-- Loading State --}}
    <div id="drill-loading" class="text-center py-5" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Memuat data...</span>
        </div>
        <p class="mt-3 text-muted mb-0">
            <i class="bi bi-hourglass-split me-2"></i>
            Memuat data lokasi...
        </p>
    </div>

    {{-- Chart Container --}}
    <div class="chart-container mb-4" style="position: relative; height: 400px;">
        <canvas id="location-drilldown-chart"></canvas>
    </div>

    {{-- Stats Summary Cards --}}
    <div class="row g-3 mb-3" id="location-stats">
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div class="stat-content">
                    <small class="text-muted d-block mb-1">Total Revenue</small>
                    <h4 class="mb-0" id="stat-revenue">Rp 0</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <div class="stat-content">
                    <small class="text-muted d-block mb-1">Total Margin</small>
                    <h4 class="mb-0" id="stat-margin">Rp 0</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-receipt"></i>
                </div>
                <div class="stat-content">
                    <small class="text-muted d-block mb-1">Total Transaksi</small>
                    <h4 class="mb-0" id="stat-transactions">0</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-percent"></i>
                </div>
                <div class="stat-content">
                    <small class="text-muted d-block mb-1">Avg Margin %</small>
                    <h4 class="mb-0" id="stat-margin-pct">0%</h4>
                </div>
            </div>
        </div>
    </div>

    {{-- Controls --}}
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label small mb-2">
                <i class="bi bi-graph-up me-1"></i>
                Metrik Tampilan:
            </label>
            <select id="drill-metric-selector" class="form-select form-select-sm">
                <option value="revenue" selected>Revenue (Pendapatan)</option>
                <option value="margin">Margin (Keuntungan)</option>
                <option value="transactions">Jumlah Transaksi</option>
                <option value="margin_pct">Margin % (Persentase)</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label small mb-2">
                <i class="bi bi-info-circle me-1"></i>
                Keterangan:
            </label>
            <p class="small text-muted mb-0">
                💡 <strong>Klik bar chart</strong> untuk melihat detail level berikutnya.
                Gunakan breadcrumb untuk kembali ke level sebelumnya.
            </p>
        </div>
    </div>
</div>

{{-- Push Styles --}}
@push('styles')
<style>
    /* Widget Container */
    #location-drilldown-widget {
        background: linear-gradient(135deg, rgba(0, 0, 0, 0.6) 0%, rgba(0, 0, 0, 0.4) 100%);
        border: 1px solid rgba(0, 212, 255, 0.2);
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    /* Stat Cards */
    #location-drilldown-widget .stat-card {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        padding: 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: all 0.3s ease;
    }

    #location-drilldown-widget .stat-card:hover {
        background: rgba(255, 255, 255, 0.08);
        border-color: rgba(0, 212, 255, 0.3);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 212, 255, 0.2);
    }

    #location-drilldown-widget .stat-icon {
        font-size: 2rem;
        color: #00d4ff;
        opacity: 0.8;
    }

    #location-drilldown-widget .stat-content {
        flex: 1;
    }

    #location-drilldown-widget .stat-card h4 {
        margin: 0;
        font-size: 1.5rem;
        color: #fff;
        font-weight: 600;
    }

    #location-drilldown-widget .stat-card small {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Breadcrumb */
    #location-drilldown-widget .breadcrumb {
        background: rgba(0, 212, 255, 0.1);
        padding: 10px 16px;
        border-radius: 8px;
        border: 1px solid rgba(0, 212, 255, 0.2);
    }

    #location-drilldown-widget .breadcrumb-item a {
        color: #00d4ff;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    #location-drilldown-widget .breadcrumb-item a:hover {
        text-decoration: underline;
        color: #00e5ff;
    }

    #location-drilldown-widget .breadcrumb-item.active {
        color: rgba(255, 255, 255, 0.7);
    }

    #location-drilldown-widget .breadcrumb-item + .breadcrumb-item::before {
        color: rgba(255, 255, 255, 0.5);
    }

    /* Level Buttons */
    #location-drilldown-widget .btn-group .btn {
        transition: all 0.2s ease;
    }

    #location-drilldown-widget .btn-group .btn:hover {
        transform: translateY(-1px);
    }

    #location-drilldown-widget .btn-group .btn.active {
        background-color: #00d4ff;
        border-color: #00d4ff;
        color: #000;
        font-weight: 600;
    }

    /* Chart Container */
    #location-drilldown-widget .chart-container {
        background: rgba(0, 0, 0, 0.2);
        border-radius: 8px;
        padding: 16px;
    }

    #location-drilldown-widget .chart-container canvas {
        cursor: pointer;
    }

    /* Form Controls */
    #location-drilldown-widget .form-select,
    #location-drilldown-widget .form-control {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #fff;
    }

    #location-drilldown-widget .form-select:focus,
    #location-drilldown-widget .form-control:focus {
        background: rgba(255, 255, 255, 0.15);
        border-color: #00d4ff;
        color: #fff;
        box-shadow: 0 0 0 0.2rem rgba(0, 212, 255, 0.25);
    }

    #location-drilldown-widget .form-select option {
        background: #1a1a1a;
        color: #fff;
    }

    /* Loading State */
    #drill-loading {
        background: rgba(0, 0, 0, 0.2);
        border-radius: 8px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        #location-drilldown-widget .stat-card {
            flex-direction: column;
            text-align: center;
        }

        #location-drilldown-widget .stat-icon {
            font-size: 1.5rem;
        }

        #location-drilldown-widget .stat-card h4 {
            font-size: 1.25rem;
        }

        #location-drilldown-widget .btn-group {
            width: 100%;
        }

        #location-drilldown-widget .btn-group .btn {
            flex: 1;
            font-size: 0.75rem;
        }
    }
</style>
@endpush

{{-- Push Scripts --}}
@push('scripts')
{{-- Chart.js Library --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

{{-- Location Drill-Down Logic --}}
<script src="{{ asset('js/location-drilldown.js') }}"></script>
@endpush
