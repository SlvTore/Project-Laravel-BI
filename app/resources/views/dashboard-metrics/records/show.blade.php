@extends('layouts.dashboard')

@section('title', 'Records - ' . $businessMetric->metric_name)

@section('content')
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="content-title">
                    <i class="bi {{ $businessMetric->icon }} me-2"></i>
                    {{ $businessMetric->metric_name }}
                </h1>
                <p class="content-subtitle">Input data dan lihat statistik untuk metric ini</p>
            </div>
            <div class="d-flex gap-2">
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
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-graph-up"></i>
                    </div>
                    <div class="stat-content">
                        <h3>{{ $businessMetric->formatted_value }}</h3>
                        <p>Nilai Saat Ini</p>
                        <span class="stat-change {{ $businessMetric->change_status }}">
                            <i class="bi bi-arrow-{{ $businessMetric->change_percentage >= 0 ? 'up' : 'down' }}-right"></i>
                            {{ $businessMetric->formatted_change }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-calendar"></i>
                    </div>
                    <div class="stat-content">
                        <h3>{{ $recentRecords->count() }}</h3>
                        <p>Total Data Points</p>
                        <span class="stat-change stable">
                            <i class="bi bi-database"></i>
                            Records
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <div class="stat-content">
                        <h3>{{ $recentRecords->first()?->record_date?->format('d M') ?? '-' }}</h3>
                        <p>Data Terakhir</p>
                        <span class="stat-change stable">
                            <i class="bi bi-clock"></i>
                            {{ $recentRecords->first()?->record_date?->diffForHumans() ?? 'Belum ada data' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-trending-up"></i>
                    </div>
                    <div class="stat-content">
                        <h3>{{ number_format($recentRecords->avg('value') ?? 0, 0) }}</h3>
                        <p>Rata-rata</p>
                        <span class="stat-change stable">
                            <i class="bi bi-calculator"></i>
                            Average
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Chart Section -->
            <div class="col-lg-8 mb-4">
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="bi bi-graph-up me-2"></i>
                            Tren Data (30 Hari Terakhir)
                        </h5>
                        <div class="card-actions">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-primary btn-sm" data-period="7">7 Hari</button>
                                <button type="button" class="btn btn-primary btn-sm" data-period="30">30 Hari</button>
                                <button type="button" class="btn btn-outline-primary btn-sm" data-period="90">90 Hari</button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="metricChart" style="height: 350px;"></div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions & Info -->
            <div class="col-lg-4 mb-4">
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="bi bi-info-circle me-2"></i>
                            Informasi Metric
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="metric-info">
                            <div class="info-item">
                                <label>Kategori:</label>
                                <span class="badge bg-primary">{{ $businessMetric->category }}</span>
                            </div>
                            <div class="info-item">
                                <label>Unit:</label>
                                <span>{{ $businessMetric->unit }}</span>
                            </div>
                            <div class="info-item">
                                <label>Deskripsi:</label>
                                <p class="text-muted">{{ $businessMetric->description }}</p>
                            </div>
                        </div>

                        <hr>

                        <div class="quick-actions">
                            <h6>Quick Actions</h6>
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addRecordModal">
                                    <i class="bi bi-plus me-2"></i>Tambah Data Hari Ini
                                </button>
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="exportData()">
                                    <i class="bi bi-download me-2"></i>Export Data
                                </button>
                            </div>
                        </div>
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

        <!-- Specific Metric Data (if available) -->
        @if(!empty($specificData))
            @include('dashboard-metrics.records.partials.specific-data', ['data' => $specificData, 'metricName' => $businessMetric->metric_name])
        @endif
    </div>

    <!-- Add Record Modal -->
    @include('dashboard-metrics.records.partials.add-modal')

    <!-- Edit Record Modal -->
    @include('dashboard-metrics.records.partials.edit-modal')

    <!-- Delete Confirmation Modal -->
    @include('dashboard-metrics.records.partials.delete-modal')
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard/dashboard-metrics.css') }}">
<style>
.stat-card {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 1.5rem;
    height: 100%;
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    border-color: var(--primary-color);
    box-shadow: 0 10px 25px rgba(124, 185, 71, 0.15);
}

.stat-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--primary-color), #2ecc71);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
}

.stat-icon i {
    font-size: 1.5rem;
    color: white;
}

.stat-content h3 {
    color: white;
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.stat-content p {
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.stat-change {
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
    border-radius: 20px;
    font-weight: 500;
}

.stat-change.increase {
    background: rgba(40, 167, 69, 0.2);
    color: #28a745;
}

.stat-change.decrease {
    background: rgba(220, 53, 69, 0.2);
    color: #dc3545;
}

.stat-change.stable {
    background: rgba(124, 185, 71, 0.2);
    color: var(--primary-color);
}

.metric-info .info-item {
    margin-bottom: 1rem;
}

.metric-info .info-item label {
    font-weight: 600;
    color: rgba(255, 255, 255, 0.9);
    display: block;
    margin-bottom: 0.25rem;
}

.quick-actions h6 {
    color: rgba(255, 255, 255, 0.9);
    font-weight: 600;
    margin-bottom: 1rem;
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
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Chart
    initializeChart();

    // Initialize DataTable
    initializeDataTable();

    // Initialize form handlers
    initializeFormHandlers();
});

function initializeChart() {
    const chartData = @json($chartData);

    const options = {
        series: [{
            name: '{{ $businessMetric->metric_name }}',
            data: chartData.values
        }],
        chart: {
            type: 'line',
            height: 350,
            background: 'transparent',
            toolbar: {
                show: true,
                tools: {
                    download: true,
                    selection: true,
                    zoom: true,
                    zoomin: true,
                    zoomout: true,
                    pan: true,
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
            categories: chartData.dates,
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

    const chart = new ApexCharts(document.querySelector("#metricChart"), options);
    chart.render();
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
    document.getElementById('record_date').value = today;
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
    window.location.href = `{{ route('dashboard.metrics.records.show', $businessMetric) }}?export=csv`;
}
</script>
@endpush
