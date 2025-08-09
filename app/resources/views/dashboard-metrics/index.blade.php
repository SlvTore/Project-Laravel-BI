@extends('layouts.dashboard')

@section('title', 'Metrics')

@section('content')
    <div class="content-header ms-5">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="content-title">Metrics</h1>
                <p class="content-subtitle">Track and analyze your business performance metrics</p>
            </div>
        </div>
    </div>

    <div class="content-body ms-5">
        @if($businessMetrics->count() == 0)
            <!-- Empty State -->
            <div class="empty-state-container">
                <div class="text-center py-5">
                    <div class="empty-state-icon mb-4">
                        <i class="bi bi-graph-up" style="font-size: 5rem; color: var(--primary-color); opacity: 0.6;"></i>
                    </div>
                    <h3 class="text-white fw-bold mb-3">Belum Ada Metrics</h3>
                    <p class="text-white mb-4 fs-5">
                        Mulai tracking performa bisnis Anda dengan menambahkan metrics pertama.<br>
                        Pilih dari metrics yang telah disediakan atau buat custom metrics.
                    </p>
                    <a href="{{ route('dashboard.metrics.create') }}" class="btn btn-primary btn-lg">
                        <i class="bi bi-plus-circle me-2"></i>
                        Tambah Metrics Pertama
                    </a>
                </div>
            </div>
        @else
            <!-- Metrics Overview Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="content-card border-start border-primary border-4">
                        <div class="card-body p-3">
                            <h6 class="text-white text-uppercase fw-bold mb-2" style="font-size: 0.75rem;">Total Metrics</h6>
                            <h3 class="fw-bold text-white mb-0">{{ $businessMetrics->count() }}</h3>
                            <small class="text-white">
                                <i class="bi bi-graph-up me-1"></i>
                                Active metrics tracking
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="content-card border-start border-success border-4">
                        <div class="card-body p-3">
                            <h6 class="text-white text-uppercase fw-bold mb-2" style="font-size: 0.75rem;">Positive Trends</h6>
                            <h3 class="fw-bold text-white mb-0">{{ $businessMetrics->where('change_percentage', '>', 0)->count() }}</h3>
                            <small class="text-white">
                                <i class="bi bi-arrow-up me-1"></i>
                                Improving metrics
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="content-card border-start border-warning border-4">
                        <div class="card-body p-3">
                            <h6 class=" text-white text-uppercase fw-bold mb-2" style="font-size: 0.75rem;">Stable</h6>
                            <h3 class="fw-bold text-white text-warning mb-0">{{ $businessMetrics->where('change_percentage', '=', 0)->count() }}</h3>
                            <small class="text-white">
                                <i class="bi bi-dash me-1"></i>
                                No change
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="content-card border-start border-danger border-4">
                        <div class="card-body p-3 ">
                            <h6 class="text-white text-uppercase fw-bold mb-2" style="font-size: 0.75rem;">Needs Attention</h6>
                            <h3 class="fw-bold text-white mb-0">{{ $businessMetrics->where('change_percentage', '<', 0)->count() }}</h3>
                            <small class="text-white">
                                <i class="bi bi-arrow-down me-1"></i>
                                Declining metrics
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Metrics Table -->
            <div class="row">
                <div class="col-12">
                    <div class="content-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title fw-bold mb-0 ms-3">All Metrics</h5>
                                <div class="d-flex gap-2 me-2 mt-2">
                                    <div class="input-group" style="width: 200px;">
                                        <span class="input-group-text bg-white border-end-0">
                                            <i class="bi bi-funnel"></i>
                                        </span>
                                        <select class="form-select border-start-0">
                                            <option value="">All Categories</option>
                                            <option value="sales">Sales</option>
                                            <option value="revenue">Revenue</option>
                                            <option value="customer">Customer</option>
                                            <option value="product">Product</option>
                                        </select>
                                    </div>
                                     <div class="input-group" style="width: 250px;">
                                        <span class="input-group-text bg-white border-end-0">
                                            <i class="bi bi-calendar-range"></i>
                                        </span>
                                        <input type="text" class="form-control border-start-0" id="daterangepicker" placeholder="Select date range">
                                    </div>
                                    <div class="input-group" style="width: 250px;">
                                        <span class="input-group-text bg-white border-end-0">
                                            <i class="bi bi-search"></i>
                                        </span>
                                        <input type="text" class="form-control border-start-0" placeholder="Search metrics..." id="searchInput">
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover" id="metricsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Metric Name</th>
                                            <th>Current Value</th>
                                            <th>Changes</th>
                                            <th>Created At</th>
                                            <th>Updated At</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($businessMetrics as $businessMetric)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="metric-icon me-2">
                                                        <i class="bi {{ $businessMetric->icon ?? 'bi-graph-up' }}"></i>
                                                    </div>
                                                    <div>
                                                        <span class="fw-semibold">{{ $businessMetric->metric_name }}</span>
                                                        @if($businessMetric->description)
                                                        <br><small class="text-muted">{{ Str::limit($businessMetric->description, 50) }}</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="fw-semibold">{{ $businessMetric->formatted_value }}</td>
                                            <td>
                                                @if($businessMetric->change_percentage != 0)
                                                    <span class="badge bg-{{ $businessMetric->change_percentage > 0 ? 'success' : 'danger' }}">
                                                        <i class="bi bi-arrow-{{ $businessMetric->change_percentage > 0 ? 'up' : 'down' }}-right me-1"></i>
                                                        {{ $businessMetric->formatted_change }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">No change</span>
                                                @endif
                                            </td>
                                            <td>{{ $businessMetric->created_at->format('M d, Y') }}</td>
                                            <td>{{ $businessMetric->updated_at->diffForHumans() }}</td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-success" title="View Records Overview" onclick="showMetricOverview({{ $businessMetric->id }})">
                                                        <i class="bi bi-graph-up"></i>
                                                    </button>
                                                    <a href="{{ route('dashboard.metrics.records.edit', $businessMetric) }}" class="btn btn-outline-primary" title="Manage Records">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <button class="btn btn-outline-info" title="View Details" onclick="viewMetricDetails({{ $businessMetric->id }})">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <form action="{{ route('dashboard.metrics.destroy', $businessMetric->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this metric?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger" title="Delete">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Floating Add Button -->
        @if($businessMetrics->count() > 0)
        <div class="floating-add-btn">
            <a href="{{ route('dashboard.metrics.create') }}" class="btn btn-primary btn-lg rounded-circle shadow-lg">
                <i class="bi bi-plus-lg fs-4"></i>
            </a>
        </div>
        @endif
    </div>

    <!-- Metric Overview Modal -->
    <div class="modal fade" id="metricOverviewModal" tabindex="-1" aria-labelledby="metricOverviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content modal-glass">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-white" id="metricOverviewModalLabel">
                        <i class="bi bi-graph-up me-2"></i>
                        <span id="modalMetricName">Metric Overview</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Loading State -->
                    <div id="modalLoading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="text-white mt-3">Loading metric data...</p>
                    </div>

                    <!-- Content Container -->
                    <div id="modalContent" style="display: none;">
                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-lg-3 col-md-6">
                                <div class="overview-stats-card">
                                    <div class="metric-icon">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div class="card-title">Total Records</div>
                                    <div class="stats-value" id="modalTotalRecords">0</div>
                                    <div class="stats-change">Data points collected</div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="overview-stats-card">
                                    <div class="metric-icon">
                                        <i class="fas fa-calculator"></i>
                                    </div>
                                    <div class="card-title">Average Value</div>
                                    <div class="stats-value" id="modalAvgValue">0</div>
                                    <div class="stats-change">Overall average</div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="overview-stats-card">
                                    <div class="metric-icon">
                                        <i class="fas fa-chart-area"></i>
                                    </div>
                                    <div class="card-title">Growth Rate</div>
                                    <div class="stats-value" id="modalGrowthRate">0%</div>
                                    <div class="stats-change">Month over month</div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <div class="overview-stats-card">
                                    <div class="metric-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="card-title">Last Update</div>
                                    <div class="stats-value" style="font-size: 1.2rem;" id="modalLastUpdate">N/A</div>
                                    <div class="stats-change">Most recent entry</div>
                                </div>
                            </div>
                        </div>

                        <!-- Charts -->
                        <div class="row mb-4">
                            <div class="col-lg-8">
                                <div class="chart-container-modal">
                                    <h6 class="mb-3 text-white">Trend Analysis (30 Days)</h6>
                                    <div id="modalTrendChart" style="height: 250px;"></div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="chart-container-modal">
                                    <h6 class="mb-3 text-white">Performance Summary</h6>
                                    <div id="modalSummaryChart" style="height: 250px;"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Records -->
                        <div class="recent-records">
                            <h6 class="text-white mb-3">Recent Records (Last 10)</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-dark table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Value</th>
                                            <th>Formatted Value</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody id="modalRecentRecords">
                                        <!-- Records will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Close</button>
                    <a href="#" id="manageRecordsBtn" class="btn btn-primary">
                        <i class="bi bi-pencil me-1"></i>Manage Records
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard/dashboard-metrics.css') }}">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<style>
    /* Modal Glass Effect */
    .modal-glass {
        background: rgba(255, 255, 255, 0.05) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        backdrop-filter: blur(20px) !important;
        border-radius: 15px !important;
    }

    .modal-glass .modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

    /* Overview Stats Cards */
    .overview-stats-card {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        backdrop-filter: blur(8px);
    }

    .overview-stats-card .card-title {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 10px;
        opacity: 0.9;
    }

    .overview-stats-card .stats-value {
        font-size: 1.8rem;
        font-weight: bold;
        margin-bottom: 5px;
    }

    .overview-stats-card .stats-change {
        font-size: 0.85rem;
        opacity: 0.8;
    }

    .overview-stats-card .metric-icon {
        font-size: 2rem;
        margin-bottom: 10px;
        opacity: 0.8;
    }

    /* Chart Containers for Modal */
    .chart-container-modal {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 15px;
        padding: 20px;
        backdrop-filter: blur(8px);
        margin-bottom: 20px;
    }

    /* Recent Records Table */
    .recent-records {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 15px;
        padding: 20px;
        backdrop-filter: blur(8px);
    }

    .table-dark {
        background: transparent !important;
    }

    .table-dark td, .table-dark th {
        border-color: rgba(255, 255, 255, 0.1) !important;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/moment/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="{{ asset('js/dashboard/dashboard-metrics/index.js') }}"></script>

<script>
let modalTrendChart, modalSummaryChart;

function showMetricOverview(metricId) {
    // Show modal
    $('#metricOverviewModal').modal('show');

    // Show loading state
    $('#modalLoading').show();
    $('#modalContent').hide();

    // Fetch metric data
    $.ajax({
        url: `/dashboard/metrics/${metricId}/overview`,
        method: 'GET',
        success: function(response) {
            // Update modal title
            $('#modalMetricName').text(response.metric.metric_name + ' Overview');

            // Update statistics
            $('#modalTotalRecords').text(response.statistics.total_records || 0);
            $('#modalAvgValue').text(formatNumber(response.statistics.avg_value || 0));

            const growthRate = response.statistics.growth_rate || 0;
            $('#modalGrowthRate').text(growthRate.toFixed(1) + '%')
                .removeClass('text-success text-danger')
                .addClass(growthRate >= 0 ? 'text-success' : 'text-danger');

            $('#modalLastUpdate').text(response.statistics.last_update ?
                moment(response.statistics.last_update).format('DD MMM YYYY') : 'N/A');

            // Update manage records button
            $('#manageRecordsBtn').attr('href', `/dashboard/metrics/records/${metricId}/edit`);

            // Load charts
            loadModalCharts(response.chartData, response.metric);

            // Load recent records
            loadRecentRecords(response.recentRecords, response.metric);

            // Hide loading and show content
            $('#modalLoading').hide();
            $('#modalContent').show();
        },
        error: function(xhr) {
            $('#modalLoading').html(`
                <div class="text-center py-5">
                    <i class="bi bi-exclamation-circle fs-1 text-danger"></i>
                    <p class="text-white mt-3">Failed to load metric data</p>
                    <p class="text-muted">${xhr.responseJSON?.message || 'Please try again later'}</p>
                </div>
            `);
        }
    });
}

function loadModalCharts(chartData, metric) {
    // Destroy existing charts
    if (modalTrendChart) {
        modalTrendChart.destroy();
    }
    if (modalSummaryChart) {
        modalSummaryChart.destroy();
    }

    // Trend Chart
    const trendOptions = {
        series: [{
            name: metric.metric_name,
            data: chartData.values || []
        }],
        chart: {
            type: 'line',
            height: 250,
            toolbar: { show: false }
        },
        stroke: {
            curve: 'smooth',
            width: 3
        },
        colors: ['#667eea'],
        xaxis: {
            categories: chartData.labels || [],
            labels: { style: { colors: '#fff' } }
        },
        yaxis: {
            labels: {
                style: { colors: '#fff' },
                formatter: function(value) {
                    return formatValue(value, metric.unit);
                }
            }
        },
        grid: {
            borderColor: 'rgba(255,255,255,0.1)'
        },
        tooltip: {
            theme: 'dark',
            y: {
                formatter: function(value) {
                    return formatValue(value, metric.unit);
                }
            }
        }
    };

    modalTrendChart = new ApexCharts(document.querySelector("#modalTrendChart"), trendOptions);
    modalTrendChart.render();

    // Summary Chart (Donut)
    const summaryOptions = {
        series: [
            chartData.this_month || 0,
            chartData.last_month || 0,
            Math.max(0, (chartData.avg_value || 0) - (chartData.this_month || 0))
        ],
        chart: {
            type: 'donut',
            height: 250
        },
        labels: ['This Month', 'Last Month', 'Other'],
        colors: ['#28a745', '#ffc107', '#6c757d'],
        legend: {
            position: 'bottom',
            labels: { colors: '#fff' }
        },
        plotOptions: {
            pie: {
                donut: { size: '70%' }
            }
        },
        tooltip: {
            theme: 'dark',
            y: {
                formatter: function(value) {
                    return formatValue(value, metric.unit);
                }
            }
        }
    };

    modalSummaryChart = new ApexCharts(document.querySelector("#modalSummaryChart"), summaryOptions);
    modalSummaryChart.render();
}

function loadRecentRecords(records, metric) {
    const tbody = $('#modalRecentRecords');
    tbody.empty();

    if (!records || records.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="4" class="text-center text-muted">No records available</td>
            </tr>
        `);
        return;
    }

    records.forEach(record => {
        tbody.append(`
            <tr>
                <td>${moment(record.record_date).format('DD MMM YYYY')}</td>
                <td>${formatNumber(record.value)}</td>
                <td>${formatValue(record.value, metric.unit)}</td>
                <td>${record.notes || '-'}</td>
            </tr>
        `);
    });
}

function formatValue(value, unit) {
    if (!value) return '0';

    try {
        if (unit === 'currency') {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(value);
        } else if (unit === 'percentage') {
            return parseFloat(value).toFixed(1) + '%';
        } else {
            return new Intl.NumberFormat('id-ID').format(value);
        }
    } catch (error) {
        return value.toString();
    }
}

function formatNumber(value) {
    if (!value) return '0';
    return new Intl.NumberFormat('id-ID').format(value);
}

// Clean up charts when modal is hidden
$('#metricOverviewModal').on('hidden.bs.modal', function() {
    if (modalTrendChart) {
        modalTrendChart.destroy();
        modalTrendChart = null;
    }
    if (modalSummaryChart) {
        modalSummaryChart.destroy();
        modalSummaryChart = null;
    }
});
</script>
@endpush
