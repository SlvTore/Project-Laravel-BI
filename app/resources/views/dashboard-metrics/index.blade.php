@extends('layouts.dashboard')

@section('title', 'Metrics')

@section('content')
    <div class="content-header ms-5">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="content-title">Metrics</h1>
                <p class="content-subtitle">Track and analyze your business performance metrics</p>
            </div>
            @if($businessMetrics->count() > 0)
            <div class="d-flex gap-2">
                <a href="{{ route('dashboard.metrics.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>
                    Add Metrics
                </a>
            </div>
            @endif
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
                    <h3 class="text-white mb-3">Belum Ada Metrics</h3>
                    <p class="text-muted mb-4 fs-5">
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
                    <div class="content-card border-start border-primary border-4 ">
                        <div class="card-body p-3">
                            <h6 class="text-muted text-uppercase fw-bold mb-2" style="font-size: 0.75rem;">Total Metrics</h6>
                            <h3 class="fw-bold text-primary mb-0">{{ $businessMetrics->count() }}</h3>
                            <small class="text-muted">
                                <i class="bi bi-graph-up me-1"></i>
                                Active metrics tracking
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="content-card border-start border-success border-4">
                        <div class="card-body p-3">
                            <h6 class="text-muted text-uppercase fw-bold mb-2" style="font-size: 0.75rem;">Positive Trends</h6>
                            <h3 class="fw-bold text-success mb-0">{{ $businessMetrics->where('change_percentage', '>', 0)->count() }}</h3>
                            <small class="text-success">
                                <i class="bi bi-arrow-up me-1"></i>
                                Improving metrics
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="content-card border-start border-warning border-4">
                        <div class="card-body p-3">
                            <h6 class="text-muted text-uppercase fw-bold mb-2" style="font-size: 0.75rem;">Stable</h6>
                            <h3 class="fw-bold text-warning mb-0">{{ $businessMetrics->where('change_percentage', '=', 0)->count() }}</h3>
                            <small class="text-warning">
                                <i class="bi bi-dash me-1"></i>
                                No change
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="content-card border-start border-danger border-4">
                        <div class="card-body p-3 ">
                            <h6 class="text-muted text-uppercase fw-bold mb-2" style="font-size: 0.75rem;">Needs Attention</h6>
                            <h3 class="fw-bold text-danger mb-0">{{ $businessMetrics->where('change_percentage', '<', 0)->count() }}</h3>
                            <small class="text-danger">
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
                                                    <a href="{{ route('dashboard.metrics.edit', $businessMetric->id) }}" class="btn btn-outline-primary" title="Edit">
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
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard/dashboard-metrics.css') }}">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/moment/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script src="{{ asset('js/dashboard/dashboard-metrics/index.js') }}"></script>
@endpush
