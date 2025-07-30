@extends('layouts.dashboard')

@section('title', 'Records - ' . $businessMetric->metric_name)

@section('content')
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="content-title">
                    <i class="bi {{ $businessMetric->icon }} me-2"></i>
                    {{ $businessMetric->metric_name }} - Records
                </h1>
                <p class="content-subtitle">Kelola data records untuk metric ini</p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#statisticsModal">
                    <i class="bi bi-graph-up me-2"></i>
                    Show Statistics
                </button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRecordModal">
                    <i class="bi bi-plus-circle me-2"></i>
                    Tambah Data
                </button>
                <a href="{{ route('dashboard.metrics') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>
                    Kembali
                </a>
            </div>
        </div>
    </div>

    <div class="content-body">
        <!-- Quick Stats Bar -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="quick-stat-card">
                    <div class="stat-icon bg-primary">
                        <i class="bi bi-graph-up"></i>
                    </div>
                    <div class="stat-info">
                        <h4>{{ $businessMetric->formatted_value }}</h4>
                        <p>Current Value</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="quick-stat-card">
                    <div class="stat-icon bg-success">
                        <i class="bi bi-database"></i>
                    </div>
                    <div class="stat-info">
                        <h4>{{ $recentRecords->count() }}</h4>
                        <p>Total Records</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="quick-stat-card">
                    <div class="stat-icon bg-warning">
                        <i class="bi bi-calendar"></i>
                    </div>
                    <div class="stat-info">
                        <h4>{{ $recentRecords->first()?->record_date?->format('d M') ?? '-' }}</h4>
                        <p>Latest Entry</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="quick-stat-card">
                    <div class="stat-icon bg-info">
                        <i class="bi bi-calculator"></i>
                    </div>
                    <div class="stat-info">
                        <h4>{{ number_format($recentRecords->avg('value') ?? 0, 0) }}</h4>
                        <p>Average</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="row">
            <div class="col-12">
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="bi bi-table me-2"></i>
                            Data Records
                        </h5>
                        <div class="card-actions">
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addRecordModal">
                                <i class="bi bi-plus me-2"></i>
                                Tambah Data
                            </button>
                            <button type="button" class="btn btn-success btn-sm" onclick="exportData()">
                                <i class="bi bi-download me-2"></i>
                                Export
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="recordsTable" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Nilai</th>
                                        <th>Catatan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentRecords as $record)
                                        <tr>
                                            <td>{{ $record->record_date->format('d M Y') }}</td>
                                            <td>{{ $record->formatted_value }}</td>
                                            <td>{{ $record->notes ?? '-' }}</td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary"
                                                            onclick="editRecord({{ $record->id }}, '{{ $record->value }}', '{{ $record->notes }}')"
                                                            data-bs-toggle="modal" data-bs-target="#editRecordModal">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger"
                                                            onclick="deleteRecord({{ $record->id }})">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">
                                                Belum ada data. Klik "Tambah Data" untuk memulai.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Modal -->
    <div class="modal fade" id="statisticsModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-graph-up me-2"></i>
                        {{ $businessMetric->metric_name }} - Statistics Overview
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-lg-8">
                            <div class="chart-container">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6>Trend Analysis</h6>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-outline-primary btn-sm chart-period" data-period="7">7 Days</button>
                                        <button type="button" class="btn btn-primary btn-sm chart-period active" data-period="30">30 Days</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm chart-period" data-period="90">90 Days</button>
                                    </div>
                                </div>
                                <div id="trendChart" style="height: 300px;"></div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="chart-container">
                                <h6 class="mb-3">Performance Distribution</h6>
                                <div id="distributionChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Statistics -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-detail-card">
                                <h6>Maximum Value</h6>
                                <h4 class="text-success">{{ number_format($recentRecords->max('value') ?? 0, 0) }}</h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-detail-card">
                                <h6>Minimum Value</h6>
                                <h4 class="text-danger">{{ number_format($recentRecords->min('value') ?? 0, 0) }}</h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-detail-card">
                                <h6>Standard Deviation</h6>
                                <h4 class="text-info">{{ number_format($recentRecords->count() > 1 ? sqrt($recentRecords->map(function($r) use ($recentRecords) { return pow($r->value - $recentRecords->avg('value'), 2); })->sum() / ($recentRecords->count() - 1)) : 0, 2) }}</h4>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-detail-card">
                                <h6>Median Value</h6>
                                <h4 class="text-warning">{{ number_format($recentRecords->median('value') ?? 0, 0) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="{{ route('dashboard.metrics.records.show', $businessMetric->id) }}/edit" class="btn btn-primary">
                        <i class="bi bi-gear me-2"></i>
                        Advanced View
                    </a>
                </div>
            </div>
        </div>
    </div>    <!-- Add Record Modal -->
    @include('dashboard-metrics.records.partials.add-modal')

    <!-- Edit Record Modal -->
    @include('dashboard-metrics.records.partials.edit-modal')

    <!-- Delete Confirmation Modal -->
    @include('dashboard-metrics.records.partials.delete-modal')
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard/dashboard-metrics.css') }}">
<style>
.quick-stat-card {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 1rem;
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
    margin-bottom: 1rem;
}

.quick-stat-card:hover {
    transform: translateY(-2px);
    border-color: var(--primary-color);
    box-shadow: 0 5px 15px rgba(124, 185, 71, 0.15);
}

.quick-stat-card .stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    color: white;
    font-size: 1.25rem;
}

.quick-stat-card .stat-info h4 {
    color: white;
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.quick-stat-card .stat-info p {
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 0;
    font-size: 0.85rem;
}

.chart-container {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.stat-detail-card {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 1rem;
    text-align: center;
    margin-bottom: 1rem;
}

.stat-detail-card h6 {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.8rem;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-detail-card h4 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0;
}

.modal-content {
    background: var(--dark-bg);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.modal-header {
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.modal-footer {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.modal-title {
    color: white;
}

.btn-group .btn {
    border-color: rgba(255, 255, 255, 0.2);
}
</style>
@endpush

@push('scripts')
<!-- ApexCharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<script>
let trendChart, distributionChart;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable
    initializeDataTable();

    // Initialize form handlers
    initializeFormHandlers();

    // Initialize modal event
    initializeModalEvents();
});

function initializeModalEvents() {
    const statisticsModal = document.getElementById('statisticsModal');
    statisticsModal.addEventListener('shown.bs.modal', function () {
        // Initialize charts when modal is shown
        initializeCharts();
    });
}

function initializeCharts() {
    const chartData = @json($chartData);

    // Trend Chart
    const trendOptions = {
        series: [{
            name: '{{ $businessMetric->metric_name }}',
            data: chartData.values || []
        }],
        chart: {
            type: 'line',
            height: 300,
            background: 'transparent',
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
            }
        },
        theme: {
            mode: 'dark'
        },
        colors: ['#7cb947'],
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth',
            width: 3
        },
        xaxis: {
            categories: chartData.dates || [],
            labels: {
                style: {
                    colors: 'rgba(255, 255, 255, 0.7)'
                }
            }
        },
        yaxis: {
            labels: {
                style: {
                    colors: 'rgba(255, 255, 255, 0.7)'
                },
                formatter: function (val) {
                    return val.toLocaleString();
                }
            }
        },
        grid: {
            borderColor: 'rgba(255, 255, 255, 0.1)',
            strokeDashArray: 5
        },
        markers: {
            size: 5,
            colors: ['#7cb947'],
            strokeColors: '#fff',
            strokeWidth: 2,
            hover: {
                size: 7
            }
        },
        tooltip: {
            theme: 'dark',
            y: {
                formatter: function (val) {
                    return val.toLocaleString() + ' {{ $businessMetric->unit }}';
                }
            }
        }
    };

    // Destroy existing chart if it exists
    if (trendChart) {
        trendChart.destroy();
    }

    trendChart = new ApexCharts(document.querySelector("#trendChart"), trendOptions);
    trendChart.render();

    // Distribution Chart (Donut)
    const distributionOptions = {
        series: [
            {{ $recentRecords->where('value', '>', $recentRecords->avg('value'))->count() }},
            {{ $recentRecords->where('value', '<=', $recentRecords->avg('value'))->count() }}
        ],
        chart: {
            type: 'donut',
            height: 300,
            background: 'transparent'
        },
        theme: {
            mode: 'dark'
        },
        labels: ['Above Average', 'Below Average'],
        colors: ['#28a745', '#dc3545'],
        legend: {
            position: 'bottom',
            labels: {
                colors: 'rgba(255, 255, 255, 0.7)'
            }
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '70%'
                }
            }
        },
        tooltip: {
            theme: 'dark'
        }
    };

    // Destroy existing chart if it exists
    if (distributionChart) {
        distributionChart.destroy();
    }

    distributionChart = new ApexCharts(document.querySelector("#distributionChart"), distributionOptions);
    distributionChart.render();

    // Bind period change events
    document.querySelectorAll('.chart-period').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.chart-period').forEach(b => {
                b.classList.remove('btn-primary');
                b.classList.add('btn-outline-primary');
            });
            this.classList.remove('btn-outline-primary');
            this.classList.add('btn-primary');

            // You can implement period change logic here
            const period = this.dataset.period;
            updateChartPeriod(period);
        });
    });
}

function updateChartPeriod(period) {
    // Implement AJAX call to get data for different periods
    // For now, this is a placeholder
    console.log('Updating chart for period:', period);
}

function initializeDataTable() {
    $('#recordsTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json'
        },
        order: [[0, 'desc']],
        pageLength: 25,
        responsive: true
    });
}

function initializeFormHandlers() {
    // Set today's date as default
    const today = new Date().toISOString().split('T')[0];
    const dateInput = document.getElementById('record_date');
    if (dateInput) {
        dateInput.value = today;
    }
}

function editRecord(id, value, notes) {
    document.getElementById('edit_record_id').value = id;
    document.getElementById('edit_value').value = value;
    document.getElementById('edit_notes').value = notes || '';
}

function deleteRecord(id) {
    if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `{{ route('dashboard.metrics.records.destroy', ['businessMetric' => $businessMetric->id, 'record' => '__ID__']) }}`.replace('__ID__', id);

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';

        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'DELETE';

        form.appendChild(csrfToken);
        form.appendChild(methodField);
        document.body.appendChild(form);
        form.submit();
    }
}

function exportData() {
    window.location.href = `{{ route('dashboard.metrics.records.export', $businessMetric) }}`;
}
</script>
@endpush
