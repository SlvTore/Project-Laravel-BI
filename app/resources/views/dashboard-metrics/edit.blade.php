@extends('layouts.dashboard')

@section('title', 'Edit Records - ' . $businessMetric->metric_name)

@section('content')
<div class="container-fluid ms-4 p-5" >
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1 text-white">{{ $businessMetric->metric_name }} - Complete Records Management</h2>
            <p class="text-white mb-0">{{ $businessMetric->business->business_name ?? 'Business' }} - {{ $businessMetric->description }}</p>
        </div>
        <div >
            <a href="{{ route('dashboard.metrics') }}"
               class="btn btn-outline-secondary me-3">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4 ">
        <div class="col-lg-3 col-md-6">
            <div class="stats-card">
                <div class="metric-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="card-title">Total Records</div>
                <div class="stats-value" id="totalRecords">{{ $statistics['total_records'] ?? 0 }}</div>
                <div class="stats-change">Data points collected</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card">
                <div class="metric-icon">
                    <i class="fas fa-calculator"></i>
                </div>
                <div class="card-title">Average Value</div>
                <div class="stats-value" id="avgValue">{{ number_format($statistics['avg_value'] ?? 0) }}</div>
                <div class="stats-change">Overall average</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card">
                <div class="metric-icon">
                    <i class="fas fa-chart-area"></i>
                </div>
                <div class="card-title">Growth Rate</div>
                <div class="stats-value {{ ($statistics['growth_rate'] ?? 0) >= 0 ? 'growth-positive' : 'growth-negative' }}" id="growthRate">
                    {{ number_format($statistics['growth_rate'] ?? 0, 1) }}%
                </div>
                <div class="stats-change">Month over month</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card">
                <div class="metric-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="card-title">Last Update</div>
                <div class="stats-value" style="font-size: 1.2rem;" id="lastUpdate">
                    {{ ($statistics['last_update'] ?? null) ? \Carbon\Carbon::parse($statistics['last_update'])->format('d M Y') : 'N/A' }}
                </div>
                <div class="stats-change">Most recent entry</div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="chart-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 text-white">Trend Analysis</h5>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-light btn-sm chart-period" data-period="7">7 Days</button>
                        <button type="button" class="btn btn-outline-light btn-sm chart-period active" data-period="30">30 Days</button>
                        <button type="button" class="btn btn-outline-light btn-sm chart-period" data-period="90">90 Days</button>
                    </div>
                </div>
                <div id="trendChart" style="height: 300px;"></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="chart-container">
                <h5 class="mb-3 text-white">Performance Summary</h5>
                <div id="summaryChart" style="height: 300px;"></div>
            </div>
        </div>
    </div>

        <!-- Inline, self-contained ApexCharts initializer (no jQuery required) -->
        <script>
        (function() {
            if (window.__chartsInitialized) return; // prevent double init
            window.__chartsInitialized = true;

            // Data from backend (fallback-safe)
            const CHART_DATA = {
                values: @json($chartData['values'] ?? []),
                dates: @json($chartData['dates'] ?? []),
                labels: @json($chartData['labels'] ?? [])
            };
            const WAREHOUSE = @json($warehouseData ?? []);
            const METRIC_NAME = @json($businessMetric->metric_name);

            function fmt(val) {
                try {
                    if (typeof formatValue === 'function') return formatValue(val);
                    return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(val || 0);
                } catch (_) { return String(val ?? ''); }
            }

            function loadApex(cb) {
                if (window.ApexCharts) return cb();
                const s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/apexcharts';
                s.onload = cb;
                s.onerror = function() { console.error('Failed to load ApexCharts'); };
                document.head.appendChild(s);
            }

            let trendChart, summaryChart;
            function renderCharts() {
                const trendEl = document.querySelector('#trendChart');
                const summaryEl = document.querySelector('#summaryChart');
                if (!trendEl || !summaryEl) return;

                const trendOptions = {
                    series: [{ name: METRIC_NAME, data: CHART_DATA.values || [] }],
                    chart: { type: 'line', height: 300, animations: { enabled: true, speed: 600 } },
                    stroke: { curve: 'smooth', width: 3 },
                    colors: ['#667eea'],
                    xaxis: { categories: CHART_DATA.labels || [], title: { text: 'Tanggal' } },
                    yaxis: { labels: { formatter: fmt }, title: { text: 'Value' } },
                    tooltip: { y: { formatter: fmt } },
                    grid: { borderColor: 'rgba(255,255,255,0.1)' }
                };
                trendChart = new ApexCharts(trendEl, trendOptions);
                trendChart.render();

                // Summary based on warehouse monthly if available; fallback to statistics
                let series, labels, colors;
                if (WAREHOUSE && WAREHOUSE.available && WAREHOUSE.monthly) {
                    const rev = Number(WAREHOUSE.monthly.total_revenue || 0);
                    const tx = Number(WAREHOUSE.monthly.transaction_count || 0);
                    const avg = tx > 0 ? rev / tx : 0;
                    series = [rev, avg, tx * 1000];
                    labels = ['Total Revenue Bulan Ini', 'Rata-rata/Transaksi', 'Jumlah Transaksi (x1000)'];
                    colors = ['#28a745', '#17a2b8', '#ffc107'];
                } else {
                    series = [
                        {{ $statistics['this_month'] ?? 0 }},
                        {{ $statistics['last_month'] ?? 0 }},
                        Math.max(0, ({{ $statistics['avg_value'] ?? 0 }}) - ({{ $statistics['this_month'] ?? 0 }}))
                    ];
                    labels = ['Bulan Ini', 'Bulan Lalu', 'Lainnya'];
                    colors = ['#28a745', '#ffc107', '#6c757d'];
                }
                const summaryOptions = {
                    series, labels, colors,
                    chart: { type: 'donut', height: 300 },
                    legend: { position: 'bottom' },
                    tooltip: { y: { formatter: fmt } },
                    plotOptions: { pie: { donut: { size: '70%' } } }
                };
                summaryChart = new ApexCharts(summaryEl, summaryOptions);
                summaryChart.render();
            }

            function attachPeriodButtons() {
                // Period button functionality is handled by the main script section
                console.log('Period buttons managed by main script - no duplicate handlers attached');
            }

            function updateChartFetch(days) {
                // Chart update functionality is handled by the main script section
                console.log('Chart update managed by main script');
            }

            document.addEventListener('DOMContentLoaded', function() {
                loadApex(function() {
                    try { renderCharts(); attachPeriodButtons(); } catch (e) { console.error(e); }
                });
            });
        })();
        </script>

    <!-- Warehouse Data Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="content-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 text-white">
                        <i class="fas fa-database me-2"></i>Warehouse Data
                    </h5>
                    <small class="text-white-50">Sumber: OLAP (dim_*/fact_sales)</small>
                </div>

                @if(!empty($warehouseData) && ($warehouseData['available'] ?? false))
                    <div class="warehouse-data-content" id="warehouseDataContent">
                        @php $type = $warehouseData['type'] ?? 'sales'; @endphp
                        <div class="row g-4">
                            <div class="border border-white rounded p-3 col-lg-8">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="text-white mb-0">
                                        @switch($type)
                                            @case('sales') Data Harian Penjualan (30 hari) @break
                                            @case('cogs') Data Harian COGS (30 hari) @break
                                            @case('margin') Data Harian Margin (30 hari) @break
                                            @case('new_customers') Pelanggan Baru Harian (30 hari) @break
                                            @case('returning_customers') Pelanggan Setia Harian (30 hari) @break
                                            @case('top_products') Penjualan Produk Harian (30 hari) @break
                                            @default Data Harian (30 hari)
                                        @endswitch
                                    </h6>
                                    <button class="btn btn-sm btn-outline-light" onclick="refreshWarehouseData()" id="refreshWarehouseBtn">
                                        <i class="fas fa-sync-alt me-1"></i> Refresh
                                    </button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-light table-striped table-hover mb-0" id="warehouseDailyTable">
                                        <thead>
                                            <tr>
                                                <th>Tanggal</th>
                                                @if($type==='sales')
                                                    <th class="text-end">Revenue</th>
                                                    <th class="text-end">Transaksi</th>
                                                    <th class="text-end">Qty</th>
                                                @elseif($type==='cogs')
                                                    <th class="text-end">Total COGS</th>
                                                @elseif($type==='margin')
                                                    <th class="text-end">Total Margin</th>
                                                @elseif($type==='new_customers')
                                                    <th class="text-end">Pelanggan Baru</th>
                                                @elseif($type==='returning_customers')
                                                    <th class="text-end">Pelanggan Setia</th>
                                                @elseif($type==='top_products')
                                                    <th>Produk</th>
                                                    <th class="text-end">Revenue</th>
                                                    <th class="text-end">Qty</th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $dailyRows = $warehouseData['daily'] ?? []; @endphp
                                            @forelse($dailyRows as $row)
                                                <tr class="warehouse-row" data-date="{{ $row->sales_date }}">
                                                    <td class="fw-medium">{{ \Carbon\Carbon::parse($row->sales_date)->format('d M Y') }}</td>
                                                    @if($type==='sales')
                                                        <td class="text-end fw-bold text-success">Rp {{ number_format((float)($row->total_gross_revenue ?? 0),0) }}</td>
                                                        <td class="text-end">{{ (int)($row->transaction_count ?? 0) }}</td>
                                                        <td class="text-end">{{ (int)($row->total_quantity ?? 0) }}</td>
                                                    @elseif($type==='cogs')
                                                        <td class="text-end fw-bold text-danger">Rp {{ number_format((float)($row->total_cogs ?? 0),0) }}</td>
                                                    @elseif($type==='margin')
                                                        <td class="text-end fw-bold text-success">Rp {{ number_format((float)($row->total_margin ?? 0),0) }}</td>
                                                    @elseif($type==='new_customers')
                                                        <td class="text-end fw-bold">{{ (int)($row->new_customers ?? 0) }}</td>
                                                    @elseif($type==='returning_customers')
                                                        <td class="text-end fw-bold">{{ (int)($row->returning_customers ?? 0) }}</td>
                                                    @elseif($type==='top_products')
                                                        <td class="fw-medium">{{ $row->product_name }}</td>
                                                        <td class="text-end text-success fw-bold">Rp {{ number_format((float)($row->total_revenue ?? 0),0) }}</td>
                                                        <td class="text-end">{{ (int)($row->total_quantity ?? 0) }}</td>
                                                    @endif
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="8" class="text-center text-white-50">Tidak ada data harian.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                @if(in_array($type,['sales','top_products']))
                                    <div class="border border-white rounded p-2 mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="text-white mb-0">
                                                @if($type==='sales') Top Produk (30 hari) @else Top Produk (Agg 30 hari) @endif
                                            </h6>
                                            <small class="text-white-50"><i class="fas fa-trophy me-1"></i>Revenue</small>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-light table-sm table-hover mb-0" id="warehouseProductsTable">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Produk</th>
                                                        <th class="text-end">Revenue</th>
                                                        <th class="text-end">Qty</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php $top = $warehouseData['top'] ?? []; @endphp
                                                    @forelse($top as $index => $p)
                                                        <tr>
                                                            <td>
                                                                @if($index==0)<i class="fas fa-crown text-warning"></i>
                                                                @elseif($index==1)<i class="fas fa-medal text-secondary"></i>
                                                                @elseif($index==2)<i class="fas fa-award text-warning"></i>
                                                                @else {{ $index+1 }} @endif
                                                            </td>
                                                            <td class="fw-medium">{{ $p->product_name }}</td>
                                                            <td class="text-end text-success fw-bold">Rp {{ number_format((float)($p->total_revenue ?? 0),0) }}</td>
                                                            <td class="text-end">{{ (int)($p->total_qty ?? $p->total_quantity ?? 0) }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr><td colspan="4" class="text-center text-white-50">Belum ada data produk.</td></tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif

                                <div class="stats-card warehouse-summary">
                                    <div class="card-title">
                                        <i class="fas fa-calendar-check me-2"></i>Ringkasan Bulan Ini
                                    </div>
                                    @if($type==='sales')
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-white-50">Total Revenue</span>
                                            <span class="stats-value text-success">Rp {{ number_format((float)($warehouseData['monthly']['total_revenue'] ?? 0),0) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-white-50">Transaksi</span>
                                            <span class="stats-value text-info">{{ (int)($warehouseData['monthly']['transaction_count'] ?? 0) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-white-50">Total Qty</span>
                                            <span class="stats-value text-warning">{{ (int)($warehouseData['monthly']['total_quantity'] ?? 0) }}</span>
                                        </div>
                                    @elseif($type==='cogs')
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-white-50">Total COGS</span>
                                            <span class="stats-value text-danger">Rp {{ number_format((float)($warehouseData['monthly']['total_cogs'] ?? 0),0) }}</span>
                                        </div>
                                    @elseif($type==='margin')
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-white-50">Total Margin</span>
                                            <span class="stats-value text-success">Rp {{ number_format((float)($warehouseData['monthly']['total_margin'] ?? 0),0) }}</span>
                                        </div>
                                    @elseif($type==='new_customers')
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-white-50">Pelanggan Baru</span>
                                            <span class="stats-value text-primary">{{ (int)($warehouseData['monthly']['new_customers'] ?? 0) }}</span>
                                        </div>
                                        @if(isset($warehouseData['customers']) && count($warehouseData['customers']) > 0)
                                            <div class="mt-3">
                                                <h6 class="text-white-50 mb-2">
                                                    <i class="fas fa-users me-1"></i>
                                                    Detail Pelanggan Baru (10 Terbaru)
                                                </h6>
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-dark mb-0" id="newCustomersTable">
                                                        <thead>
                                                            <tr>
                                                                <th>Nama</th>
                                                                <th>Email</th>
                                                                <th>Tgl. Bergabung</th>
                                                                <th class="text-end">Total Belanja</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($warehouseData['customers'] as $customer)
                                                                <tr>
                                                                    <td class="fw-semibold">{{ $customer->customer_name }}</td>
                                                                    <td class="text-muted small">{{ $customer->email ?? '-' }}</td>
                                                                    <td class="small">{{ \Carbon\Carbon::parse($customer->first_purchase_date)->format('d M Y') }}</td>
                                                                    <td class="text-end fw-semibold text-success">
                                                                        Rp {{ number_format($customer->total_spent, 0) }}
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        @endif
                                        @if(count($warehouseData['daily'] ?? []) === 0)
                                            <div class="alert alert-info mt-3 mb-0">
                                                <small><i class="fas fa-info-circle me-1"></i>
                                                Data pelanggan baru tersedia dari periode sebelumnya. Periode aktual mungkin berbeda dari range 30 hari terakhir.
                                                </small>
                                            </div>
                                        @endif
                                    @elseif($type==='returning_customers')
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-white-50">Pelanggan Setia</span>
                                            <span class="stats-value text-success">{{ (int)($warehouseData['monthly']['returning_customers'] ?? 0) }}</span>
                                        </div>
                                        @if(isset($warehouseData['customers']) && count($warehouseData['customers']) > 0)
                                            <div class="mt-3">
                                                <h6 class="text-white-50 mb-2">
                                                    <i class="fas fa-crown me-1"></i>
                                                    Detail Pelanggan Setia (10 Teraktif)
                                                </h6>
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-dark mb-0" id="returningCustomersTable">
                                                        <thead>
                                                            <tr>
                                                                <th>Nama</th>
                                                                <th>Email</th>
                                                                <th>Transaksi Terakhir</th>
                                                                <th class="text-end">Total Transaksi</th>
                                                                <th class="text-end">Total Belanja</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($warehouseData['customers'] as $customer)
                                                                <tr>
                                                                    <td class="fw-semibold">{{ $customer->customer_name }}</td>
                                                                    <td class="text-muted small">{{ $customer->email ?? '-' }}</td>
                                                                    <td class="small">{{ \Carbon\Carbon::parse($customer->last_purchase_date)->format('d M Y') }}</td>
                                                                    <td class="text-end">
                                                                        <span class="badge bg-primary">{{ $customer->total_purchases ?? 0 }}x</span>
                                                                    </td>
                                                                    <td class="text-end fw-semibold text-success">
                                                                        Rp {{ number_format($customer->total_spent, 0) }}
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        @endif
                                        @if(count($warehouseData['daily'] ?? []) === 0)
                                            <div class="alert alert-info mt-3 mb-0">
                                                <small><i class="fas fa-info-circle me-1"></i>
                                                Data pelanggan setia tersedia dari periode sebelumnya. Periode aktual mungkin berbeda dari range 30 hari terakhir.
                                                </small>
                                            </div>
                                        @endif
                                    @elseif($type==='top_products')
                                        <div class="text-white-50 small">Menampilkan agregasi penjualan produk selama 30 hari terakhir.</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Loading overlay for warehouse data -->
                    <div class="warehouse-loading d-none" id="warehouseLoading">
                        <div class="text-center text-white">
                            <div class="spinner-border text-light mb-3" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p>Memperbarui data warehouse...</p>
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning mb-0" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle me-3 fa-2x"></i>
                            <div>
                                <strong>Data Warehouse belum tersedia untuk metrik ini.</strong>
                                <p class="mb-2">Pastikan migrasi telah dijalankan, proses transform sudah selesai, dan lakukan backfill bila diperlukan.</p>
                                <button class="btn btn-warning btn-sm" onclick="triggerBackfill()" id="backfillBtn">
                                    <i class="fas fa-database me-1"></i> Jalankan Backfill
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Data Records section removed as per request -->

    <!-- Sidebar Information -->
    <div class="row mt-4">
        <div class="col-md-6">
            <!-- Metric Info Card -->
            <div class="content-card mb-4 p-3">
                <div class="card-body">
                    <h6 class="card-title fw-bold mb-3 text-white">Metric Information</h6>

                    <div class="d-flex align-items-center mb-3">
                        <div class="metric-icon me-3 text-white">
                            <i class="bi {{ $businessMetric->icon ?? 'bi-graph-up' }}"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 text-white">{{ $businessMetric->metric_name }}</h6>
                            <small class="text-white">{{ $businessMetric->category ?? 'General' }} Metric</small>
                        </div>
                    </div>

                    <div class="metric-stats text-white">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="">Current Value:</span>
                            <span class="fw-bold text-white">{{ number_format($businessMetric->current_value ?? 0, 0) }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="">Previous Value:</span>
                            <span class="fw-bold text-white">{{ number_format($businessMetric->previous_value ?? 0, 0) }}</span>
                        </div>
                        @php $growth = $statistics['growth_rate'] ?? ($businessMetric->change_percentage ?? 0); @endphp
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="">Change:</span>
                            <span class="fw-bold {{ ($growth ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                <i class="bi bi-arrow-{{ ($growth ?? 0) >= 0 ? 'up' : 'down' }}-right me-1"></i>
                                {{ number_format(($growth ?? 0),1) }}%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <!-- Activity Timeline -->
            <div class="content-card">
                <div class="card-body p-3">
                    <h6 class="card-title fw-bold mb-3 text-white">Recent Activity</h6>

                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <small class="text-white">{{ $businessMetric->updated_at->diffForHumans() }}</small>
                                <p class="mb-1 text-white">Metric was last updated</p>
                                <small class="text-white">Value: {{ number_format($businessMetric->current_value ?? 0, 0) }}</small>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <small class="text-white">{{ $businessMetric->created_at->diffForHumans() }}</small>
                                <p class="mb-1 text-white">Metric was created</p>
                                <small class="text-white">Added to dashboard</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Record Modal removed per request -->

    <!-- AI Business Assistant Card -->
    <div class="datatable-container mt-2 ms-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 text-white">
                <i class="fas fa-robot me-2"></i>AI Business Assistant
            </h5>
            <div class="ai-status">
                <span class="badge bg-success" id="aiStatus">
                    <i class="fas fa-circle me-1"></i>Online
                </span>
            </div>
        </div>

        <!-- Chat Interface -->
        <div class="ai-chat-container">
            <!-- Chat Messages -->
            <div class="chat-messages" id="chatMessages">
                <div class="message ai-message">
                    <div class="message-avatar">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="message-content">
                        <div class="message-header">
                            <strong>AI Assistant</strong>
                            <small class="text-muted">{{ now()->format('H:i') }}</small>
                        </div>
                        <div class="message-text">
                            Halo! Saya adalah asisten AI untuk analisis bisnis Anda. Saya dapat membantu menganalisis data {{ $businessMetric->metric_name }} dan memberikan insights untuk pengambilan keputusan.
                            <br>
                            Beberapa contoh pertanyaan yang bisa Anda ajukan:
                            <ul class="mt-1 mb-0">
                                <li>Bagaimana tren performa metric ini dalam 30 hari terakhir?</li>
                                <li>Apa rekomendasi untuk meningkatkan {{ strtolower($businessMetric->metric_name) }}?</li>
                                <li>Analisis pola data dan berikan strategi bisnis</li>
                                <li>Identifikasi potensi risiko dari data saat ini</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Action Buttons -->
            <div class="quick-actions mb-3">
                <button class="btn btn-outline-light btn-sm quick-question"
                        data-question="Analisis tren performa dalam 30 hari terakhir dan berikan rekomendasi">
                    <i class="fas fa-chart-line me-1"></i>Analisis Tren
                </button>
                <button class="btn btn-outline-light btn-sm quick-question"
                        data-question="Berikan 3 strategi untuk meningkatkan metrik ini berdasarkan data yang ada">
                    <i class="fas fa-lightbulb me-1"></i>Strategi Peningkatan
                </button>
                <button class="btn btn-outline-light btn-sm quick-question"
                        data-question="Identifikasi potensi risiko atau masalah dari pola data saat ini">
                    <i class="fas fa-exclamation-triangle me-1"></i>Analisis Risiko
                </button>
                <button class="btn btn-outline-light btn-sm quick-question"
                        data-question="Bandingkan performa saat ini dengan rata-rata industri dan berikan benchmark">
                    <i class="fas fa-balance-scale me-1"></i>Benchmark Industri
                </button>
            </div>

            <!-- Chat Input -->
            <div class="chat-input-container">
                <form id="aiChatForm" class="d-flex gap-2">
                    <div class="flex-grow-1">
                        <textarea class="form-control chat-input" id="aiQuestion"
                                  placeholder="Tanyakan tentang data metrics, tren, atau minta saran strategi bisnis..."
                                  rows="2" maxlength="500"></textarea>
                        <div class="chat-input-footer">
                            <small class="text-white">
                                <span id="charCount">0</span>/500 karakter
                            </small>
                            <small class="text-white">
                                Press Ctrl+Enter to send
                            </small>
                        </div>
                    </div>
                    <div class="d-flex flex-column gap-1">
                        <button type="submit" class="btn btn-primary chat-send-btn" id="sendBtn">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="clearChatBtn" title="Clear Chat">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<style>
    .stats-card {
        background-color: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(8px);
        color: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .stats-card .card-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 10px;
        opacity: 0.9;
    }

    .stats-card .stats-value {
        font-size: 2rem;
        font-weight: bold;
        margin-bottom: 5px;
        transition: all 0.3s ease;
    }

    .stats-card .stats-value.updated {
        transform: scale(1.1);
        color: #28a745;
        text-shadow: 0 0 15px rgba(40, 167, 69, 0.6);
        animation: statsUpdate 1.5s ease-in-out;
    }

    @keyframes statsUpdate {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); color: #28a745; text-shadow: 0 0 20px rgba(40, 167, 69, 0.8); }
        100% { transform: scale(1); }
    }

    .stats-card .stats-change {
        font-size: 0.9rem;
        opacity: 0.8;
    }

    .chart-container {
        background-color: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(8px);
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        margin-bottom: 30px;
    }

    .datatable-container {
        background-color: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(8px);
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    }

    .btn-excel {
        background: linear-gradient(45deg, #28a745, #20c997);
        border: none;
        border-radius: 8px;
        color: white;
        font-weight: 600;
    }

    .btn-excel:hover {
        background: linear-gradient(45deg, #20c997, #28a745);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
    }

    .table-responsive { border-radius: 10px; overflow: hidden; }

    .growth-positive {
        color: #28a745;
    }

    .growth-negative {
        color: #dc3545;
    }

    .metric-icon {
        font-size: 2.5rem;
        margin-bottom: 10px;
        opacity: 0.8;
    }

    .timeline {
        position: relative;
        padding-left: 30px;
    }

    .timeline::before {
        content: '';
        position: absolute;
        left: 10px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: rgba(255, 255, 255, 0.2);
    }

    .timeline-item {
        position: relative;
        margin-bottom: 20px;
    }

    .timeline-marker {
        position: absolute;
        left: -25px;
        top: 5px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        border: 2px solid rgba(255, 255, 255, 0.1);
    }

    .timeline-content {
        background: rgba(255, 255, 255, 0.05);
        padding: 12px;
        border-radius: 8px;
        border-left: 3px solid var(--primary-color);
    }

    .metric-stats {
        background: rgba(255, 255, 255, 0.05);
        padding: 15px;
        border-radius: 8px;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .content-card {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 15px;
        backdrop-filter: blur(20px);
    }

    /* Inline editing styles */
    .editable-cell {
        cursor: pointer;
        padding: 8px;
        border-radius: 4px;
        transition: all 0.2s ease;
        min-height: 20px;
        display: inline-block;
        width: 100%;
    }

    .editable-cell:hover {
        background-color: rgba(102, 126, 234, 0.1);
        border: 1px dashed rgba(102, 126, 234, 0.3);
    }

    .editable-cell.editing {
        background-color: rgba(40, 167, 69, 0.1);
        border: 2px solid #28a745;
    }

    .inline-edit-input {
        border: none !important;
        box-shadow: none !important;
        background: transparent !important;
        color: inherit !important;
        padding: 2px 4px !important;
        width: 100% !important;
        font-size: inherit !important;
    }

    .inline-edit-input:focus {
        background: rgba(255, 255, 255, 0.9) !important;
        color: #333 !important;
        outline: none !important;
    }

    .new-record {
        background-color: rgba(40, 167, 69, 0.05) !important;
        border-left: 4px solid #28a745 !important;
    }

    .new-record td {
        animation: highlightNew 2s ease-out;
    }

    @keyframes highlightNew {
        0% { background-color: rgba(40, 167, 69, 0.3); }
        100% { background-color: transparent; }
    }

    /* Records table styles removed */

    /* Tooltip for editable cells */
    .editable-cell::before {
        content: "Click to edit";
        position: absolute;
        top: -25px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s ease;
        z-index: 1000;
    }

    .editable-cell:hover::before {
        opacity: 1;
    }

    /* Modal Styling */
    .modal-glass {
        background: rgba(255, 255, 255, 0.05) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        backdrop-filter: blur(20px) !important;
        border-radius: 15px !important;
    }

    .modal-glass .modal-header {
        background: linear-gradient(135deg, #7cb947 0%, #1e3c80 100%);
        border-radius: 15px 15px 0 0 !important;
        padding: 20px 25px;
    }

    .modal-glass .modal-body {
        background: rgba(255, 255, 255, 0.02);
        border-radius: 0;
    }

    .modal-glass .modal-footer {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 0 0 15px 15px !important;
        padding: 20px 25px;
    }

    .modal-input {
        background: rgba(255, 255, 255, 0.1) !important;
        border: 2px solid rgba(255, 255, 255, 0.2) !important;
        border-radius: 10px !important;
        color: white !important;
        backdrop-filter: blur(10px);
        transition: all 0.3s ease;
    }

    .modal-input:focus {
        background: rgba(255, 255, 255, 0.15) !important;
        border-color: #7cb947 !important;
        box-shadow: 0 0 0 0.2rem rgba(124, 185, 103, 0.25) !important;
        color: white !important;
    }

    .modal-input::placeholder {
        color: rgba(255, 255, 255, 0.6) !important;
    }

    .modal-input-addon {
        background: rgba(102, 126, 234, 0.3) !important;
        border: 2px solid rgba(255, 255, 255, 0.2) !important;
        color: white !important;
        border-radius: 10px !important;
    }

    .btn-excel-gradient {
        background: linear-gradient(45deg, #28a745, #20c997) !important;
        border: none !important;
        border-radius: 10px !important;
        color: white !important;
        font-weight: 600 !important;
        padding: 10px 20px !important;
        transition: all 0.3s ease !important;
    }

    .btn-excel-gradient:hover {
        background: linear-gradient(45deg, #20c997, #28a745) !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4) !important;
        color: white !important;
    }

    .preview-section {
        background: rgba(102, 126, 234, 0.1);
        border: 1px solid rgba(102, 126, 234, 0.3);
        border-radius: 10px;
        backdrop-filter: blur(10px);
    }

    .preview-item {
        text-align: center;
        padding: 10px;
    }

    .preview-value {
        font-size: 1.1rem;
        font-weight: 600;
        margin-top: 5px;
        min-height: 24px;
    }

    /* Custom scrollbar for modal */
    .modal-body::-webkit-scrollbar {
        width: 6px;
    }

    .modal-body::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 3px;
    }

    .modal-body::-webkit-scrollbar-thumb {
        background: rgba(102, 126, 234, 0.6);
        border-radius: 3px;
    }

    .modal-body::-webkit-scrollbar-thumb:hover {
        background: rgba(102, 126, 234, 0.8);
    }

    /* Form validation styling */
    .was-validated .form-control:invalid {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
    }

    .was-validated .form-control:valid {
        border-color: #28a745 !important;
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important;
    }

    /* Loading state for form */
    .btn-loading {
        position: relative;
        pointer-events: none;
    }

    .btn-loading::after {
        content: "";
        position: absolute;
        width: 16px;
        height: 16px;
        margin: auto;
        border: 2px solid transparent;
        border-top-color: #ffffff;
        border-radius: 50%;
        animation: button-loading-spinner 1s ease infinite;
        top: 0;
        left: 0;
        bottom: 0;
        right: 0;
    }

    @keyframes button-loading-spinner {
        from {
            transform: rotate(0turn);
        }
        to {
            transform: rotate(1turn);
        }
    }

    /* Metric-specific form styling */
    .metric-specific-form {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        padding: 20px;
        margin: 20px 0;
    }

    .formula-display {
        background: rgba(102, 126, 234, 0.1);
        border: 1px solid rgba(102, 126, 234, 0.3);
        border-radius: 8px;
        backdrop-filter: blur(5px);
    }

    .calculation-preview {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 10px;
        padding: 15px;
    }

    .calculation-preview .bg-dark {
        background: rgba(0, 0, 0, 0.3) !important;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* Dynamic form visibility */
    .metric-specific-form.active {
        display: block !important;
        animation: fadeInUp 0.3s ease-out;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Auto-calculation fields */
    .auto-calculate {
        background: rgba(40, 167, 69, 0.1) !important;
        border-color: rgba(40, 167, 69, 0.3) !important;
    }

    .auto-calculate:focus {
        background: rgba(40, 167, 69, 0.15) !important;
        border-color: rgba(40, 167, 69, 0.5) !important;
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important;
    }

    /* AI Chat Styles */
    .ai-chat-container {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 15px;
        padding: 20px;
        min-height: 400px;
        display: flex;
        flex-direction: column;
    }

    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding-right: 10px;
        margin-bottom: 20px;
        max-height: 400px;
    }

    .message {
        display: flex;
        gap: 12px;
        margin-bottom: 20px;
        animation: messageSlideIn 0.3s ease-out;
    }

    .message-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }

    .ai-message .message-avatar {
        background: linear-gradient(135deg, #7cb947 0%, #1e3c80 100%);
        color: white;
    }

    .user-message {
        flex-direction: row-reverse;
    }

    .user-message .message-avatar {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
    }

    .message-content {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 15px;
        padding: 15px;
        max-width: 80%;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .user-message .message-content {
        background: rgba(40, 167, 69, 0.2);
        border-color: rgba(40, 167, 69, 0.3);
    }

    .message-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }

    .message-header strong {
        color: white;
    }

    .message-text {
        color: #f8f9fa;
        line-height: 1.5;
        white-space: pre-wrap;
    }

    .message-text ul {
        margin: 8px 0;
        padding-left: 20px;
    }

    .message-text li {
        margin-bottom: 4px;
    }

    .quick-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .quick-question {
        border-radius: 20px;
        transition: all 0.3s ease;
    }

    .quick-question:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .chat-input-container {
        margin-top: auto;
    }

    .chat-input {
        background: rgba(255, 255, 255, 0.1) !important;
        border: 2px solid rgba(255, 255, 255, 0.2) !important;
        border-radius: 12px !important;
        color: white !important;
        resize: none;
        backdrop-filter: blur(10px);
    }

    .chat-input:focus {
        background: rgba(255, 255, 255, 0.15) !important;
        border-color: #7cb947 !important;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25) !important;
        color: white !important;
    }

    .chat-input::placeholder {
        color: rgba(255, 255, 255, 0.6) !important;
    }

    .chat-input-footer {
        display: flex;
        justify-content: space-between;
        margin-top: 4px;
        padding: 0 4px;
    }

    .chat-send-btn {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #7cb947 0%, #1e3c80 100%);
        border: none;
        transition: all 0.3s ease;
    }

    .chat-send-btn:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }

    .chat-send-btn:disabled {
        opacity: 0.6;
        transform: none;
    }

    .ai-status .badge {
        animation: pulse 2s infinite;
    }

    @keyframes messageSlideIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }

    .typing-indicator {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #6c757d;
        font-style: italic;
    }

    .typing-dots {
        display: flex;
        gap: 3px;
    }

    .typing-dots span {
        width: 6px;
        height: 6px;
        background: #6c757d;
        border-radius: 50%;
        animation: typingAnimation 1.4s infinite ease-in-out;
    }

    .typing-dots span:nth-child(1) { animation-delay: -0.32s; }
    .typing-dots span:nth-child(2) { animation-delay: -0.16s; }
    .typing-dots span:nth-child(3) { animation-delay: 0s; }

    @keyframes typingAnimation {
        0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; }
        40% { transform: scale(1); opacity: 1; }
    }

    /* Scrollbar Styling */
    .chat-messages::-webkit-scrollbar {
        width: 6px;
    }

    .chat-messages::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 3px;
    }

    .chat-messages::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.3);
        border-radius: 3px;
    }

    .chat-messages::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.5);
    }

    /* Warehouse Data Styling */
    .warehouse-data-content {
        animation: fadeInUp 0.6s ease-out;
    }

    .warehouse-row:hover {
        background-color: rgba(255, 255, 255, 0.1) !important;
        transform: translateX(5px);
        transition: all 0.3s ease;
    }

    .warehouse-product-row:hover {
        background-color: rgba(255, 255, 255, 0.1) !important;
        transform: scale(1.02);
        transition: all 0.3s ease;
    }

    .warehouse-summary {
        border: 1px solid rgba(40, 167, 69, 0.3);
        background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(23, 162, 184, 0.1));
    }

    .warehouse-loading {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 10;
        background: rgba(0, 0, 0, 0.8);
        border-radius: 10px;
        padding: 30px;
        min-width: 200px;
    }

    #warehouseDailyTable tbody tr td:nth-child(2),
    #warehouseProductsTable tbody tr td:nth-child(3) {
        font-weight: 600;
        background: linear-gradient(45deg, transparent, rgba(40, 167, 69, 0.1));
    }

    /* Warehouse table hover styling consistency */
    #warehouseDailyTable tbody tr:hover,
    #warehouseProductsTable tbody tr:hover,
    #newCustomersTable tbody tr:hover,
    #returningCustomersTable tbody tr:hover {
        background-color: rgba(40, 167, 69, 0.08) !important;
        cursor: pointer;
    }

    #warehouseDailyTable tbody tr:hover td,
    #warehouseProductsTable tbody tr:hover td,
    #newCustomersTable tbody tr:hover td,
    #returningCustomersTable tbody tr:hover td {
        background-color: inherit !important;
    }

    /* Maintain special column styling on hover */
    #warehouseDailyTable tbody tr:hover td:nth-child(2),
    #warehouseProductsTable tbody tr:hover td:nth-child(3) {
        background: linear-gradient(45deg, rgba(40, 167, 69, 0.08), rgba(40, 167, 69, 0.15)) !important;
        font-weight: 600;
    }

    /* Customer table specific styling */
    #newCustomersTable, #returningCustomersTable {
        font-size: 0.875rem;
    }

    #newCustomersTable tbody tr, #returningCustomersTable tbody tr {
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .fa-crown { color: #ffd700 !important; }
    .fa-medal { color: #c0c0c0 !important; }
    .fa-award { color: #cd7f32 !important; }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
let trendChart, summaryChart;
const businessMetricId = {{ $businessMetric->id }};

$(document).ready(function() {
    // Configure toastr
    toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "5000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };

    initializeCharts();
    // Bind chart period buttons (keep charts interactive)
    $('.chart-period').on('click', function() {
        $('.chart-period').removeClass('active btn-primary').addClass('btn-outline-light');
        $(this).removeClass('btn-outline-light').addClass('active btn-primary');
        const period = $(this).data('period');
        updateChart(period);
    });
    // Records table and CRUD disabled per request
});

function initializeCharts() {
    // Get initial chart data or use defaults
    const chartData = {
        values: @json($chartData['values'] ?? []),
        dates: @json($chartData['dates'] ?? []),
        labels: @json($chartData['labels'] ?? [])
    };

    // Get warehouse data for enhanced charts (if available)
    const warehouseData = @json($warehouseData ?? []);
    const hasWarehouseData = warehouseData && warehouseData.available;

    // Trend Chart
    const trendOptions = {
        series: [{
            name: '{{ $businessMetric->metric_name }}',
            data: chartData.values
        }],
        chart: {
            type: 'line',
            height: 300,
            toolbar: {
                show: true,
                tools: {
                    download: true,
                    selection: false,
                    zoom: true,
                    zoomin: true,
                    zoomout: true,
                    pan: false,
                    reset: true
                }
            },
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800
            }
        },
        stroke: {
            curve: 'smooth',
            width: 3
        },
        colors: ['#667eea'],
        xaxis: {
            categories: chartData.labels,
            title: {
                text: 'Tanggal',
                style: { color: '#fff' }
            },
            labels: {
                style: { colors: '#fff' }
            }
        },
        yaxis: {
            title: {
                text: '{{ $businessMetric->unit ?? "Value" }}',
                style: { color: '#fff' }
            },
            labels: {
                style: { colors: '#fff' },
                formatter: function(value) {
                    return formatValue(value);
                }
            }
        },
        tooltip: {
            theme: 'dark',
            y: {
                formatter: function(value) {
                    return formatValue(value);
                }
            }
        },
        grid: {
            borderColor: 'rgba(255,255,255,0.1)',
            strokeDashArray: 3
        }
    };

    trendChart = new ApexCharts(document.querySelector("#trendChart"), trendOptions);
    trendChart.render();

    // Summary Chart (Enhanced with warehouse data)
    let summaryData, summaryLabels, summaryColors;

    if (hasWarehouseData && warehouseData.monthly) {
        // Use warehouse monthly data
        const monthlyRevenue = warehouseData.monthly.total_revenue || 0;
        const transactionCount = warehouseData.monthly.transaction_count || 0;
        const avgPerTransaction = transactionCount > 0 ? monthlyRevenue / transactionCount : 0;

        summaryData = [monthlyRevenue, avgPerTransaction, transactionCount * 1000]; // Scale up transaction count for visibility
        summaryLabels = ['Total Revenue Bulan Ini', 'Rata-rata per Transaksi', 'Jumlah Transaksi (x1000)'];
        summaryColors = ['#28a745', '#17a2b8', '#ffc107'];
    } else {
        // Fallback to traditional data
        summaryData = [
            {{ $statistics['this_month'] ?? 50 }},
            {{ $statistics['last_month'] ?? 30 }},
            {{ ($statistics['avg_value'] ?? 100) - ($statistics['this_month'] ?? 50) }}
        ];
        summaryLabels = ['Bulan Ini', 'Bulan Lalu', 'Lainnya'];
        summaryColors = ['#28a745', '#ffc107', '#6c757d'];
    }

    const summaryOptions = {
        series: summaryData,
        chart: {
            type: 'donut',
            height: 300,
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800
            }
        },
        labels: summaryLabels,
        colors: summaryColors,
        legend: {
            position: 'bottom',
            labels: {
                colors: '#fff'
            }
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '70%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            showAlways: true,
                            label: 'Total',
                            fontSize: '16px',
                            fontWeight: 600,
                            color: '#fff',
                            formatter: function (w) {
                                return formatValue(w.globals.seriesTotals.reduce((a, b) => a + b, 0));
                            }
                        },
                        value: {
                            show: true,
                            fontSize: '14px',
                            fontWeight: 400,
                            color: '#fff',
                            formatter: function (val) {
                                return formatValue(val);
                            }
                        }
                    }
                }
            }
        },
        tooltip: {
            theme: 'dark',
            y: {
                formatter: function(value) {
                    return formatValue(value);
                }
            }
        },
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    width: 200
                },
                legend: {
                    position: 'bottom'
                }
            }
        }]
    };

    summaryChart = new ApexCharts(document.querySelector("#summaryChart"), summaryOptions);
    summaryChart.render();

    // Update statistics cards with warehouse data
    if (hasWarehouseData) {
        updateStatisticsCardsWithWarehouse(warehouseData);
    }
}

// Records DataTable removed per request

// bindEvents related to DataTable/CRUD removed per request

// addNewRecord removed

// editRecord removed

// deleteRecord removed

// deleteSelected removed

// refreshTable removed

// exportToExcel removed

function updateChart(days) {
    $.ajax({
        url: '{{ route("dashboard.metrics.records.edit", $businessMetric->id) }}',
        data: { period: days },
        success: function(response) {
            if (response.chartData) {
                trendChart.updateSeries([{
                    name: '{{ $businessMetric->metric_name }}',
                    data: response.chartData.values
                }]);

                trendChart.updateOptions({
                    xaxis: {
                        categories: response.chartData.labels
                    }
                });

                // Animate statistics cards update
                animateStatisticsUpdate();
            }
        },
        error: function() {
            toastr.error('Failed to update chart');
        }
    });
}

function updateStatisticsCardsWithWarehouse(warehouseData) {
    if (!warehouseData || !warehouseData.available) return;

    // Update Total Records with transaction count
    if (warehouseData.monthly && warehouseData.monthly.transaction_count) {
        const totalRecords = document.getElementById('totalRecords');
        if (totalRecords) {
            animateValueUpdate(totalRecords, warehouseData.monthly.transaction_count);
        }
    }

    // Update Average Value with average per transaction
    if (warehouseData.monthly) {
        const avgValue = document.getElementById('avgValue');
        if (avgValue && warehouseData.monthly.transaction_count > 0) {
            const avgPerTransaction = warehouseData.monthly.total_revenue / warehouseData.monthly.transaction_count;
            animateValueUpdate(avgValue, Math.round(avgPerTransaction), true);
        }
    }

    // Update Growth Rate (already calculated from backend)
    // Last Update from warehouse data
    if (warehouseData.daily_sales && warehouseData.daily_sales.length > 0) {
        const lastUpdate = document.getElementById('lastUpdate');
        if (lastUpdate) {
            const latestDate = warehouseData.daily_sales[warehouseData.daily_sales.length - 1].sales_date;
            const formattedDate = moment(latestDate).format('DD MMM YYYY');
            lastUpdate.textContent = formattedDate;
        }
    }
}

function animateValueUpdate(element, newValue, isCurrency = false) {
    const currentValue = parseInt(element.textContent.replace(/[^\d]/g, '')) || 0;
    const increment = (newValue - currentValue) / 20;
    let current = currentValue;

    element.classList.add('updated');

    const timer = setInterval(() => {
        current += increment;
        if ((increment > 0 && current >= newValue) || (increment < 0 && current <= newValue)) {
            current = newValue;
            clearInterval(timer);
            setTimeout(() => element.classList.remove('updated'), 1000);
        }

        if (isCurrency) {
            element.textContent = formatCurrency(Math.round(current));
        } else {
            element.textContent = Math.round(current).toLocaleString();
        }
    }, 50);
}

function animateStatisticsUpdate() {
    // Add pulse animation to all stats cards
    $('.stats-card .stats-value').addClass('updated');
    setTimeout(() => {
        $('.stats-card .stats-value').removeClass('updated');
    }, 1500);
}

function refreshWarehouseData() {
    const refreshBtn = document.getElementById('refreshWarehouseBtn');
    const warehouseContent = document.getElementById('warehouseDataContent');
    const loading = document.getElementById('warehouseLoading');

    // Show loading
    refreshBtn.disabled = true;
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Loading...';

    if (warehouseContent && loading) {
        warehouseContent.style.opacity = '0.5';
        loading.classList.remove('d-none');
    }

    // Refresh page to get updated warehouse data
    setTimeout(() => {
        window.location.reload();
    }, 1000);
}

function triggerBackfill() {
    const backfillBtn = document.getElementById('backfillBtn');
    if (!backfillBtn) return;

    if (confirm('Jalankan backfill untuk mengisi data warehouse dari transaksi yang ada?')) {
        backfillBtn.disabled = true;
        backfillBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Processing...';

        $.ajax({
            url: '{{ route("dashboard.data-feeds.backfill-facts") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                toastr.success('Backfill berhasil! Data warehouse telah diperbarui.');
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            },
            error: function(xhr, status, error) {
                toastr.error('Gagal menjalankan backfill: ' + error);
                backfillBtn.disabled = false;
                backfillBtn.innerHTML = '<i class="fas fa-database me-1"></i> Jalankan Backfill';
            }
        });
    }
}

// Inline editing functions
let editingData = {};

function bindInlineEditEvents() {
    $('.editable-cell').off('click').on('click', function() {
        const $cell = $(this);
        const field = $cell.data('field');
        const id = $cell.data('id');
        const type = $cell.data('type');
        const currentValue = $cell.text().trim();

        // Don't edit if already editing
        if ($cell.hasClass('editing')) return;

        // Store original value
        editingData[id] = editingData[id] || {};
        editingData[id][field] = currentValue;

        $cell.addClass('editing');

        let input;
        let inputValue = currentValue;

        if (type === 'date') {
            // Parse the current date format (DD/MM/YYYY) to YYYY-MM-DD
            if (currentValue && currentValue !== '-') {
                inputValue = moment(currentValue, 'DD/MM/YYYY').format('YYYY-MM-DD');
            } else {
                inputValue = moment().format('YYYY-MM-DD'); // Default to today
            }
            input = `<input type="date" class="form-control form-control-sm inline-edit-input" value="${inputValue}" data-field="${field}" data-id="${id}">`;
        } else if (type === 'number') {
            // Remove formatting for editing
            const numValue = currentValue.replace(/[^\d.-]/g, '');
            inputValue = numValue || '0';
            input = `<input type="number" class="form-control form-control-sm inline-edit-input" value="${inputValue}" step="0.01" data-field="${field}" data-id="${id}">`;
        } else {
            // For text fields, get the full text if available
            const textValue = $cell.data('full-text') || currentValue;
            inputValue = textValue === '-' ? '' : textValue;
            input = `<input type="text" class="form-control form-control-sm inline-edit-input" value="${inputValue}" data-field="${field}" data-id="${id}">`;
        }

        $cell.html(input);
        $cell.find('input').focus().select();

        // Show save/cancel buttons
        $(`#save-${id}, #cancel-${id}`).show();

        // Handle Enter key
        $cell.find('input').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                saveInlineEdit(id);
            }
        });

        // Handle Escape key
        $cell.find('input').on('keydown', function(e) {
            if (e.which === 27) {
                e.preventDefault();
                cancelInlineEdit(id);
            }
        });

        // Handle Tab key to move to next editable cell
        $cell.find('input').on('keydown', function(e) {
            if (e.which === 9) { // Tab key
                e.preventDefault();
                const $row = $cell.closest('tr');
                const $nextCell = $row.find('.editable-cell').eq($row.find('.editable-cell').index($cell) + 1);
                if ($nextCell.length) {
                    saveInlineEdit(id, function() {
                        $nextCell.click();
                    });
                } else {
                    saveInlineEdit(id);
                }
            }
        });

        // Auto-save on blur for better UX
        $cell.find('input').on('blur', function() {
            // Small delay to allow clicking save/cancel buttons
            setTimeout(() => {
                if ($cell.hasClass('editing')) {
                    saveInlineEdit(id);
                }
            }, 200);
        });
    });
}

// addInlineRecord removed

function updateStatistics() {
    // Fetch updated statistics from server
    $.ajax({
        url: `{{ route('dashboard.metrics.overview', $businessMetric->id) }}`,
        method: 'GET',
        success: function(response) {
            const stats = response.statistics;

            // Update all statistics cards
            $('#totalRecords').text(stats.total_records || 0);
            $('#avgValue').text(formatNumber(stats.avg_value || 0));

            const growthRate = stats.growth_rate || 0;
            $('#growthRate').text(growthRate.toFixed(1) + '%')
                .removeClass('growth-positive growth-negative')
                .addClass(growthRate >= 0 ? 'growth-positive' : 'growth-negative');

            $('#lastUpdate').text(stats.last_update ?
                moment(stats.last_update).format('DD MMM YYYY') : 'N/A');

            // Animate the updated values
            $('.stats-value').each(function() {
                $(this).addClass('updated');
                setTimeout(() => {
                    $(this).removeClass('updated');
                }, 1000);
            });
        },
        error: function(xhr) {
            console.error('Failed to update statistics:', xhr.responseText);
    }
    });
}

// addInlineRecord removed

    // Determine if this is a new record or update
    const isNewRecord = id.toString().startsWith('new_');
    let url, method;

    if (isNewRecord) {
        // For new records, we need all required fields
        if (!data.record_date) {
            data.record_date = moment().format('YYYY-MM-DD');
        }
        if (data.value === undefined) {
            data.value = 0;
        }
        if (data.notes === undefined) {
            data.notes = '';
        }

        url = '{{ route("dashboard.metrics.records.store", $businessMetric->id) }}';
        method = 'POST';
    } else {
        url = `{{ url('/dashboard/metrics/records') }}/${id}`;
        method = 'POST';
        data._method = 'PUT';
    }

    // Show loading state
    $(`#save-${id}`).html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

    $.ajax({
        url: url,
        method: method,
        data: data,
        success: function(response) {
            toastr.success(response.message || 'Record saved successfully');

            // Clear editing data
            delete editingData[id];

            // Hide save/cancel buttons
            $(`#save-${id}, #cancel-${id}`).hide();
            $(`#save-${id}`).html('<i class="fas fa-check"></i>').prop('disabled', false);

            // Table disabled; call callback directly if provided
            if (callback && typeof callback === 'function') {
                callback();
            }

            // Update charts
            updateChart($('.chart-period.active').data('period') || 30);

            // Update statistics
            setTimeout(updateStatistics, 500);
        },
        error: function(xhr) {
            $(`#save-${id}`).html('<i class="fas fa-check"></i>').prop('disabled', false);

            if (xhr.responseJSON && xhr.responseJSON.errors) {
                const errors = xhr.responseJSON.errors;
                let errorMessage = 'Validation errors:<br>';
                Object.keys(errors).forEach(field => {
                    errorMessage += `• ${field}: ${errors[field].join(', ')}<br>`;
                });
                toastr.error(errorMessage);
            } else {
                toastr.error('Failed to save record: ' + (xhr.responseJSON?.message || xhr.statusText));
            }

            // Keep editing state on error so user can fix the issue
        }
    });
}

function cancelInlineEdit(id) {
    $(`.inline-edit-input[data-id="${id}"]`).each(function() {
        const $input = $(this);
        const $cell = $input.closest('.editable-cell');
        const field = $input.data('field');

        // Restore original value
        let originalValue = '';
        if (editingData[id] && editingData[id][field]) {
            originalValue = editingData[id][field];
        }

        $cell.removeClass('editing').html(originalValue);
    });

    // Hide save/cancel buttons
    $(`#save-${id}, #cancel-${id}`).hide();

    // Clear editing data
    if (editingData[id]) {
        delete editingData[id];
    }
}

function formatValue(value) {
    try {
        @if($businessMetric->unit === 'currency')
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(value);
        @elseif($businessMetric->unit === 'percentage')
            return parseFloat(value).toFixed(1) + '%';
        @else
            return new Intl.NumberFormat('id-ID').format(value);
        @endif
    } catch (error) {
        console.error('Error formatting value:', error);
        return value.toString();
    }
}

// Test helpers removed

// Modal Functions
function initializeModal() {
    // Set default date to today
    $('#record_date').val(moment().format('YYYY-MM-DD'));

    // Initialize metric-specific form
    initializeMetricSpecificForm();

    // Bind preview update events
    $('#record_date, #record_value, #record_notes').on('input change', updatePreview);

    // Handle form submission
    $('#addRecordForm').on('submit', handleFormSubmit);

    // Reset form when modal is hidden
    $('#addRecordModal').on('hidden.bs.modal', resetForm);

    // Focus on date field when modal is shown
    $('#addRecordModal').on('shown.bs.modal', function() {
        $('#record_date').focus();
        updatePreview();
    });

    // Keyboard shortcuts
    $('#addRecordModal').on('keydown', function(e) {
        // Save with Ctrl+Enter
        if ((e.ctrlKey || e.metaKey) && e.which === 13) {
            e.preventDefault();
            $('#addRecordForm').submit();
        }
        // Close with Escape (already handled by Bootstrap, but added for consistency)
        if (e.which === 27) {
            $(this).modal('hide');
        }
    });

    // Auto-format number input
    $('#record_value').on('blur', function() {
        const value = parseFloat($(this).val());
        if (!isNaN(value)) {
            $(this).val(value.toFixed(2));
            updatePreview();
        }
    });

    // Update preview on modal show
    updatePreview();
}

function initializeMetricSpecificForm() {
    const metricName = '{{ $businessMetric->metric_name }}';

    // Hide generic value field for specific metrics that have their own input fields
    const metricsWithSpecificForms = [
        'Total Penjualan',
        'Biaya Pokok Penjualan (COGS)',
        'Margin Keuntungan (Profit Margin)',
        'Jumlah Pelanggan Baru',
        'Jumlah Pelanggan Setia',
        'Penjualan Produk Terlaris'
    ];

    if (metricsWithSpecificForms.includes(metricName)) {
        $('#generic-value-field').hide();

        // Show specific form
        $('.metric-specific-form').hide();
        const formId = getFormIdByMetricName(metricName);
        $(`#${formId}`).addClass('active').show();

        // Initialize specific form handlers
        initializeSpecificFormHandlers(metricName);
    }
}

function getFormIdByMetricName(metricName) {
    const formMap = {
        'Total Penjualan': 'total-penjualan-form',
        'Biaya Pokok Penjualan (COGS)': 'cogs-form',
        'Margin Keuntungan (Profit Margin)': 'margin-keuntungan-form',
        'Jumlah Pelanggan Baru': 'pelanggan-baru-form',
        'Jumlah Pelanggan Setia': 'pelanggan-setia-form',
        'Penjualan Produk Terlaris': 'produk-terlaris-form'
    };
    return formMap[metricName] || '';
}

function initializeSpecificFormHandlers(metricName) {
    switch(metricName) {
        case 'Total Penjualan':
            initializeTotalPenjualanForm();
            break;
        case 'Biaya Pokok Penjualan (COGS)':
            initializeCOGSForm();
            break;
        case 'Margin Keuntungan (Profit Margin)':
            initializeMarginKeuntunganForm();
            break;
        case 'Jumlah Pelanggan Baru':
            initializePelangganBaruForm();
            break;
        case 'Jumlah Pelanggan Setia':
            initializePelangganSetiaForm();
            break;
        case 'Penjualan Produk Terlaris':
            initializeProdukTerlarisForm();
            break;
    }
}

function initializeTotalPenjualanForm() {
    // Update record_value when total_revenue changes
    $('#total_revenue').on('input', function() {
        const value = parseFloat($(this).val()) || 0;
        $('#record_value').val(value);
        updatePreview();
    });

    // Validation
    $('#total_revenue').on('blur', function() {
        const value = parseFloat($(this).val());
        if (!isNaN(value) && value > 0) {
            $(this).removeClass('is-invalid').addClass('is-valid');
        }
    });
}

function initializeCOGSForm() {
    // Update record_value when total_cogs changes
    $('#total_cogs').on('input', function() {
        const value = parseFloat($(this).val()) || 0;
        $('#record_value').val(value);
        updatePreview();
    });
}

function initializeMarginKeuntunganForm() {
    // Load existing data and calculate margin
    loadMarginCalculationData();

    // Recalculate when period changes
    $('#margin_period').on('change', loadMarginCalculationData);
}

function initializePelangganBaruForm() {
    // Update record_value when new_customer_count changes
    $('#new_customer_count').on('input', function() {
        const value = parseInt($(this).val()) || 0;
        $('#record_value').val(value);
        updatePreview();
    });

    // Calculate acquisition cost per customer
    $('#customer_acquisition_cost, #new_customer_count').on('input', function() {
        calculateAcquisitionMetrics();
    });
}

function initializePelangganSetiaForm() {
    // Real-time calculation
    $('#total_customer_count, #new_customer_count').on('input', function() {
        calculateLoyaltyMetrics();
    });

    // Load new customer count from previous input if available
    loadNewCustomerCount();
}

function initializeProdukTerlarisForm() {
    // Calculate revenue generated
    $('#quantity_sold, #unit_price, #cost_per_unit').on('input', function() {
        calculateProductMetrics();
    });

    // Auto-uppercase SKU
    $('#product_sku').on('input', function() {
        $(this).val($(this).val().toUpperCase());
    });
}

function loadMarginCalculationData() {
    const period = $('#margin_period').val();

    // Fetch data via AJAX
    $.ajax({
        url: '{{ route("dashboard.metrics.calculation.data", $businessMetric->id) }}',
        method: 'GET',
        data: { period: period },
        success: function(response) {
            $('#ref_total_revenue').text(formatCurrency(response.total_revenue || 0));
            $('#ref_total_cogs').text(formatCurrency(response.total_cogs || 0));

            const margin = calculateMarginPercentage(response.total_revenue || 0, response.total_cogs || 0);
            $('#calculated_margin').text(margin + '%');
            $('#record_value').val(margin);
            updatePreview();
        },
        error: function() {
            console.error('Failed to load calculation data');
        }
    });
}

function calculateLoyaltyMetrics() {
    const totalCustomers = parseInt($('#total_customer_count').val()) || 0;
    const newCustomers = parseInt($('#new_customer_count').val()) || 0;
    const returningCustomers = Math.max(0, totalCustomers - newCustomers);
    const loyaltyPercentage = totalCustomers > 0 ? ((returningCustomers / totalCustomers) * 100).toFixed(1) : 0;

    $('#display_total_customers').text(totalCustomers);
    $('#display_new_customers').text(newCustomers);
    $('#display_returning_customers').text(returningCustomers);
    $('#display_loyalty_percentage').text(loyaltyPercentage + '%');

    // Update main record value
    $('#record_value').val(loyaltyPercentage);
    updatePreview();
}

function calculateProductMetrics() {
    const quantity = parseInt($('#quantity_sold').val()) || 0;
    const price = parseFloat($('#unit_price').val()) || 0;
    const cost = parseFloat($('#cost_per_unit').val()) || 0;

    const revenue = quantity * price;
    const profitPerUnit = price - cost;

    $('#calculated_revenue').text(formatCurrency(revenue));
    $('#calculated_profit_per_unit').text(formatCurrency(profitPerUnit));

    // Update main record value with revenue
    $('#record_value').val(revenue);
    updatePreview();
}

function calculateAcquisitionMetrics() {
    const newCustomers = parseInt($('#new_customer_count').val()) || 0;
    const acquisitionCost = parseFloat($('#customer_acquisition_cost').val()) || 0;

    if (newCustomers > 0 && acquisitionCost > 0) {
        const costPerCustomer = acquisitionCost / newCustomers;
        // You can display this somewhere if needed
        console.log('Cost per customer acquisition:', formatCurrency(costPerCustomer));
    }
}

function loadNewCustomerCount() {
    // Try to get new customer count from today's data
    const today = moment().format('YYYY-MM-DD');
    $.ajax({
        url: '{{ route("dashboard.metrics.daily.data", $businessMetric->business_id) }}',
        method: 'GET',
        data: { date: today },
        success: function(response) {
            if (response.new_customer_count) {
                $('#new_customer_count').val(response.new_customer_count);
                calculateLoyaltyMetrics();
            }
        },
        error: function() {
            // Ignore error, it's optional data
        }
    });
}

function calculateMarginPercentage(revenue, cogs) {
    if (revenue <= 0) return 0;
    return (((revenue - cogs) / revenue) * 100).toFixed(1);
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
}

function updatePreview() {
    const date = $('#record_date').val();
    let value = parseFloat($('#record_value').val()) || 0;
    const notes = $('#record_notes').val();
    const metricName = '{{ $businessMetric->metric_name }}';

    // Get value from metric-specific forms if available
    if (!value || value === 0) {
        value = getValueFromSpecificForm(metricName);
    }

    // Update date preview with animation
    if (date) {
        const formattedDate = moment(date).format('DD MMM YYYY');
        animateTextChange('#preview_date', formattedDate);

        // Validate date (not in future)
        const selectedDate = moment(date);
        const today = moment();
        if (selectedDate.isAfter(today)) {
            $('#record_date').addClass('is-invalid');
            showFieldError('#record_date', 'Date cannot be in the future');
        } else {
            $('#record_date').removeClass('is-invalid');
            clearFieldError('#record_date');
        }
    } else {
        animateTextChange('#preview_date', '-');
    }

    // Update value preview with animation
    if (value > 0) {
        animateTextChange('#preview_value', new Intl.NumberFormat('id-ID').format(value));
        animateTextChange('#preview_formatted', formatValue(value));

        // Validate value (reasonable range)
        if (value > 999999999999) { // 1 trillion
            showFieldError('#record_value', 'Value seems too large. Please verify.');
        } else {
            clearFieldError('#record_value');
        }
    } else {
        animateTextChange('#preview_value', '-');
        animateTextChange('#preview_formatted', '-');
    }

    // Update notes preview
    if (notes) {
        const truncatedNotes = notes.length > 50 ? notes.substring(0, 50) + '...' : notes;
        animateTextChange('#preview_notes', truncatedNotes);

        if (notes.length > 100) {
            $('#record_notes').addClass('is-invalid');
            showFieldError('#record_notes', `Notes are quite long (${notes.length} characters). Consider shortening.`);
        } else {
            $('#record_notes').removeClass('is-invalid');
            clearFieldError('#record_notes');
        }
    } else {
        animateTextChange('#preview_notes', 'No notes');
        $('#record_notes').removeClass('is-invalid');
        clearFieldError('#record_notes');
    }
}

function getValueFromSpecificForm(metricName) {
    switch(metricName) {
        case 'Total Penjualan':
            return parseFloat($('#total_revenue').val()) || 0;
        case 'Biaya Pokok Penjualan (COGS)':
            return parseFloat($('#total_cogs').val()) || 0;
        case 'Jumlah Pelanggan Baru':
            return parseInt($('#new_customer_count').val()) || 0;
        case 'Jumlah Pelanggan Setia':
            const totalCustomers = parseInt($('#total_customer_count').val()) || 0;
            const newCustomers = parseInt($('#new_customer_count').val()) || 0;
            if (totalCustomers > 0) {
                return parseFloat((((totalCustomers - newCustomers) / totalCustomers) * 100).toFixed(1));
            }
            return 0;
        case 'Penjualan Produk Terlaris':
            const quantity = parseInt($('#quantity_sold').val()) || 0;
            const price = parseFloat($('#unit_price').val()) || 0;
            return quantity * price;
        case 'Margin Keuntungan (Profit Margin)':
            return parseFloat($('#calculated_margin').text().replace('%', '')) || 0;
        default:
            return parseFloat($('#record_value').val()) || 0;
    }
}

function animateTextChange(selector, newText) {
    const $element = $(selector);
    if ($element.text() !== newText) {
        $element.fadeOut(150, function() {
            $(this).text(newText).fadeIn(150);
        });
    }
}

function showFieldError(selector, message) {
    const $field = $(selector);
    const $group = $field.closest('.mb-3');

    // Remove existing error
    $group.find('.invalid-feedback').remove();

    // Add new error
    $group.append(`<div class="invalid-feedback d-block">${message}</div>`);
}

function clearFieldError(selector) {
    const $field = $(selector);
    const $group = $field.closest('.mb-3');
    $group.find('.invalid-feedback').remove();
}

function handleFormSubmit(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);

    // Add CSRF token
    formData.append('_token', '{{ csrf_token() }}');

    // Validate form
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
    }

    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
    submitBtn.classList.add('btn-loading');
    submitBtn.disabled = true;

    // Submit form
    $.ajax({
        url: '{{ route("dashboard.metrics.records.store", $businessMetric->id) }}',
        method: 'POST',
        data: Object.fromEntries(formData),
        success: function(response) {
            toastr.success(response.message || 'Record added successfully!');

            // Hide modal
            $('#addRecordModal').modal('hide');

            // Table disabled

            // Update charts
            updateChart($('.chart-period.active').data('period') || 30);

            // Update statistics
            setTimeout(updateStatistics, 500);

            // Reset form
            resetForm();
        },
        error: function(xhr) {
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                const errors = xhr.responseJSON.errors;
                let errorMessage = 'Please check the following errors:<br>';
                Object.keys(errors).forEach(field => {
                    errorMessage += `• ${errors[field].join(', ')}<br>`;

                    // Highlight error fields
                    const fieldElement = $(`[name="${field}"]`);
                    fieldElement.addClass('is-invalid');
                    fieldElement.closest('.mb-3').append(
                        `<div class="invalid-feedback d-block">${errors[field].join(', ')}</div>`
                    );
                });
                toastr.error(errorMessage);
            } else {
                toastr.error('Failed to add record: ' + (xhr.responseJSON?.message || xhr.statusText));
            }
        },
        complete: function() {
            // Reset button state
            submitBtn.innerHTML = originalText;
            submitBtn.classList.remove('btn-loading');
            submitBtn.disabled = false;
        }
    });
}

function resetForm() {
    const form = document.getElementById('addRecordForm');
    form.reset();
    form.classList.remove('was-validated');

    // Clear validation errors
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();

    // Reset date to today
    $('#record_date').val(moment().format('YYYY-MM-DD'));

    // Reset preview
    updatePreview();
}

// AI Chat functionality
let chatMessages = [];

$(document).ready(function() {
    initializeAIChat();
});

function initializeAIChat() {
    // Character counter
    $('#aiQuestion').on('input', function() {
        const length = $(this).val().length;
        $('#charCount').text(length);

        if (length > 450) {
            $('#charCount').addClass('text-warning');
        } else {
            $('#charCount').removeClass('text-warning');
        }
    });

    // Chat form submission
    $('#aiChatForm').on('submit', function(e) {
        e.preventDefault();
        const question = $('#aiQuestion').val().trim();
        if (question) {
            sendAIMessage(question);
        }
    });

    // Quick question buttons
    $('.quick-question').on('click', function() {
        const question = $(this).data('question');
        sendAIMessage(question);
    });

    // Clear chat
    $('#clearChatBtn').on('click', function() {
        if (confirm('Hapus semua percakapan?')) {
            clearChat();
        }
    });

    // Keyboard shortcuts
    $('#aiQuestion').on('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.which === 13) {
            e.preventDefault();
            $('#aiChatForm').submit();
        }
    });

    // Auto-resize textarea
    $('#aiQuestion').on('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });
}

function sendAIMessage(question) {
    if (!question.trim()) return;

    // Add user message to chat
    addMessage('user', question);

    // Clear input
    $('#aiQuestion').val('').trigger('input');

    // Show typing indicator
    showTypingIndicator();

    // Disable send button
    $('#sendBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

    // Send request to AI
    $.ajax({
        url: '{{ route("dashboard.metrics.ai-chat", $businessMetric->id) }}',
        method: 'POST',
        data: {
            question: question,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            hideTypingIndicator();

            if (response.success) {
                addMessage('ai', response.response);
                updateAIStatus('online');
            } else {
                addMessage('ai', 'Maaf, terjadi kesalahan: ' + (response.error || 'Tidak dapat memproses pertanyaan Anda.'));
                updateAIStatus('error');
            }
        },
        error: function(xhr) {
            hideTypingIndicator();
            updateAIStatus('error');

            let errorMessage = 'Maaf, terjadi kesalahan saat menghubungi AI assistant.';

            if (xhr.status === 429) {
                errorMessage = 'Terlalu banyak permintaan. Silakan coba lagi dalam beberapa saat.';
            } else if (xhr.status === 500) {
                errorMessage = 'Terjadi kesalahan server. Silakan coba lagi nanti.';
            } else if (xhr.status === 403) {
                errorMessage = 'API key tidak valid atau akses ditolak.';
            }

            addMessage('ai', errorMessage);
        },
        complete: function() {
            // Re-enable send button
            $('#sendBtn').prop('disabled', false).html('<i class="fas fa-paper-plane"></i>');
        }
    });
}

function addMessage(type, content) {
    const timestamp = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    const avatar = type === 'ai' ? '<i class="fas fa-robot"></i>' : '<i class="fas fa-user"></i>';
    const name = type === 'ai' ? 'AI Assistant' : 'Anda';

    const messageHtml = `
        <div class="message ${type}-message">
            <div class="message-avatar">
                ${avatar}
            </div>
            <div class="message-content">
                <div class="message-header">
                    <strong>${name}</strong>
                    <small class="text-muted">${timestamp}</small>
                </div>
                <div class="message-text">${formatAIResponse(content)}</div>
            </div>
        </div>
    `;

    $('#chatMessages').append(messageHtml);
    scrollToBottom();

    // Store message
    chatMessages.push({
        type: type,
        content: content,
        timestamp: timestamp
    });
}

function formatAIResponse(text) {
    // Convert markdown-like formatting to HTML
    text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    text = text.replace(/\*(.*?)\*/g, '<em>$1</em>');

    // Convert numbered lists
    text = text.replace(/(\d+\.\s)/g, '<br>$1');

    // Convert bullet points
    text = text.replace(/(-\s)/g, '<br>• ');

    // Convert line breaks
    text = text.replace(/\n\n/g, '<br><br>');
    text = text.replace(/\n/g, '<br>');

    return text;
}

function showTypingIndicator() {
    const typingHtml = `
        <div class="message ai-message" id="typingIndicator">
            <div class="message-avatar">
                <i class="fas fa-robot"></i>
            </div>
            <div class="message-content">
                <div class="typing-indicator">
                    <span>AI sedang mengetik</span>
                    <div class="typing-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>
        </div>
    `;

    $('#chatMessages').append(typingHtml);
    scrollToBottom();
}

function hideTypingIndicator() {
    $('#typingIndicator').remove();
}

function scrollToBottom() {
    const chatContainer = $('#chatMessages');
    chatContainer.scrollTop(chatContainer[0].scrollHeight);
}

function clearChat() {
    // Keep only the initial AI message
    $('#chatMessages').find('.message').not(':first').remove();
    chatMessages = [];
    toastr.success('Percakapan telah dihapus');
}

// Update AI status based on connection
function updateAIStatus(status) {
    const statusElement = $('#aiStatus');
    if (status === 'online') {
        statusElement.removeClass('bg-danger bg-warning').addClass('bg-success')
                   .html('<i class="fas fa-circle me-1"></i>Online');
    } else if (status === 'error') {
        statusElement.removeClass('bg-success bg-warning').addClass('bg-danger')
                   .html('<i class="fas fa-exclamation-circle me-1"></i>Error');
    } else {
        statusElement.removeClass('bg-success bg-danger').addClass('bg-warning')
                   .html('<i class="fas fa-clock me-1"></i>Connecting');
    }
}

function initializeAIChat() {
    // Character counter
    $('#aiQuestion').on('input', function() {
        const length = $(this).val().length;
        $('#charCount').text(length);

        if (length > 450) {
            $('#charCount').addClass('text-warning');
        } else {
            $('#charCount').removeClass('text-warning');
        }
    });

    // Chat form submission
    $('#aiChatForm').on('submit', function(e) {
        e.preventDefault();
        const question = $('#aiQuestion').val().trim();
        if (question) {
            sendAIMessage(question);
        }
    });

    // Quick question buttons
    $('.quick-question').on('click', function() {
        const question = $(this).data('question');
        sendAIMessage(question);
    });

    // Clear chat
    $('#clearChatBtn').on('click', function() {
        if (confirm('Hapus semua percakapan?')) {
            clearChat();
        }
    });

    // Keyboard shortcuts
    $('#aiQuestion').on('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.which === 13) {
            e.preventDefault();
            $('#aiChatForm').submit();
        }
    });

    // Auto-resize textarea
    $('#aiQuestion').on('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });
}

function sendAIMessage(question) {
    if (!question.trim()) return;

    // Add user message to chat
    addMessage('user', question);

    // Clear input
    $('#aiQuestion').val('').trigger('input');

    // Show typing indicator
    showTypingIndicator();

    // Disable send button
    $('#sendBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

    // Send request to AI
    $.ajax({
        url: '{{ route("dashboard.metrics.ai-chat", $businessMetric->id) }}',
        method: 'POST',
        data: {
            question: question,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            hideTypingIndicator();

            if (response.success) {
                addMessage('ai', response.response);
            } else {
                addMessage('ai', 'Maaf, terjadi kesalahan: ' + (response.error || 'Tidak dapat memproses pertanyaan Anda.'));
            }
        },
        error: function(xhr) {
            hideTypingIndicator();
            let errorMessage = 'Maaf, terjadi kesalahan saat menghubungi AI assistant.';

            if (xhr.status === 429) {
                errorMessage = 'Terlalu banyak permintaan. Silakan coba lagi dalam beberapa saat.';
            } else if (xhr.status === 500) {
                errorMessage = 'Terjadi kesalahan server. Silakan coba lagi nanti.';
            }

            addMessage('ai', errorMessage);
        },
        complete: function() {
            // Re-enable send button
            $('#sendBtn').prop('disabled', false).html('<i class="fas fa-paper-plane"></i>');
        }
    });
}

function addMessage(type, content) {
    const timestamp = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    const avatar = type === 'ai' ? '<i class="fas fa-robot"></i>' : '<i class="fas fa-user"></i>';
    const name = type === 'ai' ? 'AI Assistant' : 'Anda';

    const messageHtml = `
        <div class="message ${type}-message">
            <div class="message-avatar">
                ${avatar}
            </div>
            <div class="message-content">
                <div class="message-header">
                    <strong>${name}</strong>
                    <small class="text-muted">${timestamp}</small>
                </div>
                <div class="message-text">${formatAIResponse(content)}</div>
            </div>
        </div>
    `;

    $('#chatMessages').append(messageHtml);
    scrollToBottom();

    // Store message
    chatMessages.push({
        type: type,
        content: content,
        timestamp: timestamp
    });
}

function formatAIResponse(text) {
    // Convert markdown-like formatting to HTML
    text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    text = text.replace(/\*(.*?)\*/g, '<em>$1</em>');

    // Convert numbered lists
    text = text.replace(/(\d+\.\s)/g, '<br>$1');

    // Convert bullet points
    text = text.replace(/(-\s)/g, '<br>• ');

    // Convert line breaks
    text = text.replace(/\n\n/g, '<br><br>');
    text = text.replace(/\n/g, '<br>');

    return text;
}

function showTypingIndicator() {
    const typingHtml = `
        <div class="message ai-message" id="typingIndicator">
            <div class="message-avatar">
                <i class="fas fa-robot"></i>
            </div>
            <div class="message-content">
                <div class="typing-indicator">
                    <span>AI sedang mengetik</span>
                    <div class="typing-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>
        </div>
    `;

    $('#chatMessages').append(typingHtml);
    scrollToBottom();
}

function hideTypingIndicator() {
    $('#typingIndicator').remove();
}

function scrollToBottom() {
    const chatContainer = $('#chatMessages');
    chatContainer.scrollTop(chatContainer[0].scrollHeight);
}

function clearChat() {
    // Keep only the initial AI message
    $('#chatMessages').find('.message').not(':first').remove();
    chatMessages = [];
    toastr.success('Percakapan telah dihapus');
}

// Update AI status based on connection
function updateAIStatus(status) {
    const statusElement = $('#aiStatus');
    if (status === 'online') {
        statusElement.removeClass('bg-danger bg-warning').addClass('bg-success')
                   .html('<i class="fas fa-circle me-1"></i>Online');
    } else if (status === 'error') {
        statusElement.removeClass('bg-success bg-warning').addClass('bg-danger')
                   .html('<i class="fas fa-exclamation-circle me-1"></i>Error');
    } else {
        statusElement.removeClass('bg-success bg-danger').addClass('bg-warning')
                   .html('<i class="fas fa-clock me-1"></i>Connecting');
    }
}

// Export chat function (optional)
function exportChat() {
    const chatData = {
        metric: '{{ $businessMetric->metric_name }}',
        business: '{{ $businessMetric->business->business_name ?? "Business" }}',
        timestamp: new Date().toISOString(),
        messages: chatMessages
    };

    const dataStr = JSON.stringify(chatData, null, 2);
    const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);

    const exportFileDefaultName = `ai_chat_${Date.now()}.json`;

    const linkElement = document.createElement('a');
    linkElement.setAttribute('href', dataUri);
    linkElement.setAttribute('download', exportFileDefaultName);
    linkElement.click();
}
</script>
@endpush
