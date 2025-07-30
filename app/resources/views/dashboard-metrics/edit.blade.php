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
        <div>
            <a href="{{ route('dashboard.metrics') }}"
               class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
            <button class="btn btn-excel" data-bs-toggle="modal" data-bs-target="#addRecordModal">
                <i class="fas fa-plus"></i> Add New Row
            </button>
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

    <!-- Excel-like Data Table with Inline Editing -->
    <div class="datatable-container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 text-white">Data Records - Live Excel Interface</h5>
            <div>
                <button class="btn btn-excel btn-sm me-2" data-bs-toggle="modal" data-bs-target="#addRecordModal">
                    <i class="fas fa-plus"></i> Add New Row
                </button>
                <button class="btn btn-outline-success btn-sm me-2" onclick="exportToExcel()">
                    <i class="fas fa-file-excel"></i> Export Excel
                </button>
                <button class="btn btn-outline-primary btn-sm me-2" onclick="refreshTable()">
                    <i class="fas fa-sync"></i> Refresh
                </button>
                <button class="btn btn-outline-danger btn-sm" onclick="deleteSelected()">
                    <i class="fas fa-trash"></i> Delete Selected
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table id="recordsTable" class="table table-striped table-hover w-100">
                <thead class="mt-2">
                    <tr>
                        <th width="50">#</th>
                        <th width="50"><input type="checkbox" id="selectAll"></th>
                        <th width="120">Date</th>
                        <th width="120">Value</th>
                        <th width="150">Formatted Value</th>
                        <th>Notes</th>
                        <th width="130">Created At</th>
                        <th width="100">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data will be loaded via AJAX -->
                </tbody>
            </table>
        </div>
    </div>

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
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="">Change:</span>
                            <span class="fw-bold {{ ($businessMetric->change_percentage ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                <i class="bi bi-arrow-{{ ($businessMetric->change_percentage ?? 0) >= 0 ? 'up' : 'down' }}-right me-1"></i>
                                {{ $businessMetric->formatted_change ?? '0%' }}
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

<!-- Add Record Modal -->
<div class="modal fade" id="addRecordModal" tabindex="-1" aria-labelledby="addRecordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content modal-glass">
            <div class="modal-header border-0">
                <h5 class="modal-title text-white" id="addRecordModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>Add New {{ $businessMetric->metric_name }} Record
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addRecordForm">
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="record_date" class="form-label text-white">
                                    <i class="fas fa-calendar me-1"></i>Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control form-control-lg modal-input"
                                       id="record_date" name="record_date" required>
                                <div class="form-text text-light opacity-75">Select the date for this record</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="record_value" class="form-label text-white">
                                    <i class="fas fa-calculator me-1"></i>Value <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    @if($businessMetric->unit === 'currency')
                                        <span class="input-group-text modal-input-addon">Rp</span>
                                    @endif
                                    <input type="number" class="form-control form-control-lg modal-input"
                                           id="record_value" name="value" step="0.01" min="0" required>
                                    @if($businessMetric->unit === 'percentage')
                                        <span class="input-group-text modal-input-addon">%</span>
                                    @endif
                                </div>
                                <div class="form-text text-light opacity-75">Enter the {{ strtolower($businessMetric->metric_name) }} value</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="record_notes" class="form-label text-white">
                            <i class="fas fa-sticky-note me-1"></i>Notes <span class="text-muted">(Optional)</span>
                        </label>
                        <textarea class="form-control modal-input" id="record_notes" name="notes"
                                  rows="3" placeholder="Add any additional notes or context for this record..."></textarea>
                        <div class="form-text text-light opacity-75">Optional notes or comments about this record</div>
                    </div>

                    <!-- Preview Section -->
                    <div class="mt-4 p-3 preview-section">
                        <h6 class="text-white mb-2">
                            <i class="fas fa-eye me-1"></i>Preview
                        </h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="preview-item">
                                    <small class="text-muted">Date</small>
                                    <div class="preview-value text-white" id="preview_date">-</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="preview-item">
                                    <small class="text-muted">Value</small>
                                    <div class="preview-value text-white" id="preview_value">-</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="preview-item">
                                    <small class="text-muted">Formatted</small>
                                    <div class="preview-value text-success" id="preview_formatted">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 justify-content-between">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-excel-gradient">
                        <i class="fas fa-save me-1"></i>Save Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap5.min.css">
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
        text-shadow: 0 0 10px rgba(40, 167, 69, 0.5);
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

    .table-responsive {
        border-radius: 10px;
        overflow: hidden;
    }

    #recordsTable {
        border-radius: 10px;
    }

    #recordsTable thead th {
        background-color: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(8px);
        color: white;
        border: none;
        font-weight: 600;
        padding: 15px 10px;
    }

    #recordsTable tbody td {
        padding: 12px 10px;
        vertical-align: middle;
        border-color: #f1f3f4;
    }

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

    /* Excel-like table styling */
    #recordsTable {
        border-collapse: separate !important;
        border-spacing: 0 !important;
    }

    #recordsTable tbody tr:hover {
        background-color: rgba(102, 126, 234, 0.08) !important;
    }

    #recordsTable tbody td {
        border-left: 1px solid #e9ecef;
        border-bottom: 1px solid #e9ecef;
        position: relative;
    }

    #recordsTable tbody td:first-child {
        border-left: none;
    }

    #recordsTable tbody tr:last-child td {
        border-bottom: 2px solid #dee2e6;
    }

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
        border-color: #667eea !important;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25) !important;
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
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.2.9/js/responsive.bootstrap5.min.js"></script>

<script>
let trendChart, summaryChart, recordsTable;
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
    initializeDataTable();
    bindEvents();
    initializeModal();
});

function initializeCharts() {
    // Get initial chart data or use defaults
    const chartData = {
        values: @json($chartData['values'] ?? []),
        dates: @json($chartData['dates'] ?? []),
        labels: @json($chartData['labels'] ?? [])
    };

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
                text: 'Date'
            }
        },
        yaxis: {
            title: {
                text: '{{ $businessMetric->unit ?? "Value" }}'
            },
            labels: {
                formatter: function(value) {
                    return formatValue(value);
                }
            }
        },
        tooltip: {
            y: {
                formatter: function(value) {
                    return formatValue(value);
                }
            }
        },
        grid: {
            borderColor: '#f1f3f4'
        }
    };

    trendChart = new ApexCharts(document.querySelector("#trendChart"), trendOptions);
    trendChart.render();

    // Summary Chart (Donut)
    const summaryOptions = {
        series: [
            {{ $statistics['this_month'] ?? 50 }},
            {{ $statistics['last_month'] ?? 30 }},
            {{ ($statistics['avg_value'] ?? 100) - ($statistics['this_month'] ?? 50) }}
        ],
        chart: {
            type: 'donut',
            height: 300
        },
        labels: ['This Month', 'Last Month', 'Other'],
        colors: ['#28a745', '#ffc107', '#6c757d'],
        legend: {
            position: 'bottom'
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '70%'
                }
            }
        },
        tooltip: {
            y: {
                formatter: function(value) {
                    return formatValue(value);
                }
            }
        }
    };

    summaryChart = new ApexCharts(document.querySelector("#summaryChart"), summaryOptions);
    summaryChart.render();
}

function initializeDataTable() {
    recordsTable = $('#recordsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("dashboard.metrics.records.edit", $businessMetric->id) }}',
            data: function(d) {
                d.draw = d.draw;
                d.start = d.start;
                d.length = d.length;
                d.search = d.search.value;
                d.order = d.order;
                d.columns = d.columns;
            },
            error: function(xhr, error, thrown) {
                console.error('DataTables AJAX error:', xhr.responseText);
                toastr.error('Failed to load data: ' + xhr.statusText);
            }
        },
        columns: [
            {
                data: null,
                name: 'row_number',
                orderable: false,
                searchable: false,
                render: function(data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            {
                data: 'id',
                name: 'select',
                orderable: false,
                searchable: false,
                render: function(data) {
                    return '<input type="checkbox" class="record-checkbox" value="' + data + '">';
                }
            },
            {
                data: 'record_date',
                name: 'record_date',
                render: function(data, type, row) {
                    if (type === 'display') {
                        return '<span class="editable-cell" data-field="record_date" data-id="' + row.id + '" data-type="date">' +
                               moment(data).format('DD/MM/YYYY') + '</span>';
                    }
                    return data;
                }
            },
            {
                data: 'value',
                name: 'value',
                render: function(data, type, row) {
                    if (type === 'display') {
                        return '<span class="editable-cell" data-field="value" data-id="' + row.id + '" data-type="number">' +
                               formatValue(data) + '</span>';
                    }
                    return data;
                }
            },
            {
                data: 'formatted_value',
                name: 'formatted_value',
                orderable: false,
                searchable: false
            },
            {
                data: 'notes',
                name: 'notes',
                render: function(data, type, row) {
                    if (type === 'display') {
                        const displayText = data ? (data.length > 50 ? data.substring(0, 50) + '...' : data) : '-';
                        return '<span class="editable-cell" data-field="notes" data-id="' + row.id + '" data-type="text" title="Click to edit" data-full-text="' + (data || '') + '">' +
                               displayText + '</span>';
                    }
                    return data || '';
                }
            },
            {
                data: 'created_at',
                name: 'created_at',
                render: function(data) {
                    return moment(data).format('DD/MM/YYYY HH:mm');
                }
            },
            {
                data: 'id',
                name: 'actions',
                orderable: false,
                searchable: false,
                render: function(data) {
                    return `
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-success btn-sm" onclick="saveInlineEdit(${data})" title="Save Changes" style="display:none;" id="save-${data}">
                                <i class="fas fa-check"></i>
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="cancelInlineEdit(${data})" title="Cancel Edit" style="display:none;" id="cancel-${data}">
                                <i class="fas fa-times"></i>
                            </button>
                            <button class="btn btn-outline-danger btn-sm" onclick="deleteRecord(${data})" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        order: [[2, 'asc']], // Order by date column ascending (oldest first, new records at bottom)
        pageLength: 25,
        responsive: true,
        dom: '<"d-flex justify-content-between"lf>rtip',
        language: {
            search: "Search records:",
            lengthMenu: "Show _MENU_ records per page",
            info: "Showing _START_ to _END_ of _TOTAL_ records",
            infoEmpty: "No records available",
            infoFiltered: "(filtered from _MAX_ total records)",
            zeroRecords: "No matching records found",
            emptyTable: "No data available in table",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            },
            processing: "Loading data..."
        },
        drawCallback: function(settings) {
            bindInlineEditEvents();
            // Update statistics after table draw
            updateStatistics();
        }
    });
}

function bindEvents() {
    // Chart period buttons
    $('.chart-period').on('click', function() {
        $('.chart-period').removeClass('active btn-primary').addClass('btn-outline-primary');
        $(this).removeClass('btn-outline-primary').addClass('active btn-primary');

        const period = $(this).data('period');
        updateChart(period);
    });

    // Select all checkbox
    $('#selectAll').on('change', function() {
        $('.record-checkbox').prop('checked', this.checked);
    });

    // Bind inline editing events (will be called after each table draw)
    bindInlineEditEvents();
}

function addNewRecord() {
    // Use inline editing instead
    addInlineRecord();
}

function editRecord(id) {
    // Records are now edited inline - this function can trigger inline edit mode
    const $row = recordsTable.row(`#record-${id}`).node();
    if ($row) {
        $($row).find('.editable-cell[data-field="record_date"]').click();
    }
    toastr.info('Click on any cell to edit it directly');
}

function deleteRecord(id) {
    if (confirm('Are you sure you want to delete this record?')) {
        $.ajax({
            url: `{{ url('/dashboard/metrics/records') }}/${id}`,
            method: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                recordsTable.ajax.reload();
                updateChart($('.chart-period.active').data('period'));
                toastr.success(response.message || 'Record deleted successfully');
            },
            error: function() {
                toastr.error('Failed to delete record');
            }
        });
    }
}

function deleteSelected() {
    const selectedIds = $('.record-checkbox:checked').map(function() {
        return this.value;
    }).get();

    if (selectedIds.length === 0) {
        toastr.warning('Please select records to delete');
        return;
    }

    if (confirm(`Are you sure you want to delete ${selectedIds.length} selected record(s)?`)) {
        $.ajax({
            url: '{{ route("dashboard.metrics.records.bulk-delete") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                ids: selectedIds
            },
            success: function(response) {
                toastr.success(response.message || 'Records deleted successfully');

                // Reload table to update row numbers
                recordsTable.ajax.reload();
                updateChart($('.chart-period.active').data('period') || 30);
                $('#selectAll').prop('checked', false);

                // Update statistics
                setTimeout(updateStatistics, 500);
            },
            error: function(xhr) {
                toastr.error('Failed to delete selected records: ' + (xhr.responseJSON?.message || xhr.statusText));
            }
        });
    }
}

function refreshTable() {
    recordsTable.ajax.reload();
    updateChart($('.chart-period.active').data('period') || 30);
    setTimeout(updateStatistics, 500);
    toastr.info('Table refreshed');
}

function exportToExcel() {
    window.location.href = '{{ route("dashboard.metrics.records.export", $businessMetric->id) }}';
}

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
            }
        },
        error: function() {
            toastr.error('Failed to update chart');
        }
    });
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

function addInlineRecord() {
    console.log('addInlineRecord called'); // Debug log

    // Check if recordsTable is initialized
    if (!recordsTable) {
        toastr.error('Table not initialized');
        return;
    }

    // Get current date as default for new record
    const today = moment().format('YYYY-MM-DD');
    const newRowId = 'new_' + Date.now();

    try {
        // Add a new row with today's date so it appears at the bottom (with ascending order)
        const newRow = recordsTable.row.add({
            id: newRowId,
            record_date: today,
            value: 0,
            formatted_value: formatValue(0),
            notes: '',
            created_at: moment().format('YYYY-MM-DD HH:mm:ss')
        }).draw(false);

        console.log('New row added successfully'); // Debug log

        // Make the new row editable immediately
        const $newRowNode = $(newRow.node());
        $newRowNode.addClass('new-record');
        $newRowNode.attr('id', 'record-' + newRowId);

        // Rebind events to include the new row
        setTimeout(() => {
            bindInlineEditEvents();

            // Focus on the value field instead of date (since date is already set to today)
            const $valueCell = $newRowNode.find('.editable-cell[data-field="value"]');
            console.log('Value cell found:', $valueCell.length); // Debug log

            if ($valueCell.length > 0) {
                $valueCell.click();
                toastr.success('New row added! Enter the value for today\'s record.');
            } else {
                toastr.warning('Row added, but editing may not work properly. Try refreshing the table.');
            }
        }, 300);

    } catch (error) {
        console.error('Error adding row:', error);
        toastr.error('Failed to add new row: ' + error.message);
    }
}

function updateStatistics() {
    // Update the total records count from table info
    const info = recordsTable.page.info();
    $('#totalRecords').text(info.recordsTotal);

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

function formatNumber(value) {
    if (!value) return '0';
    return new Intl.NumberFormat('id-ID').format(value);
}

function saveInlineEdit(id, callback) {
    const $inputs = $(`.inline-edit-input[data-id="${id}"]`);

    if ($inputs.length === 0) {
        toastr.warning('No changes to save');
        return;
    }

    const data = {
        _token: '{{ csrf_token() }}'
    };

    let hasChanges = false;

    // Only send the fields that are actually being edited
    $inputs.each(function() {
        const field = $(this).data('field');
        let value = $(this).val();

        if (field === 'record_date') {
            data.record_date = value;
            hasChanges = true;
        } else if (field === 'value') {
            data.value = parseFloat(value) || 0;
            hasChanges = true;
        } else if (field === 'notes') {
            data.notes = value;
            hasChanges = true;
        }
    });

    if (!hasChanges) {
        toastr.warning('No changes detected');
        cancelInlineEdit(id);
        return;
    }

    // Client-side validation
    if (data.record_date && moment(data.record_date).isAfter(moment())) {
        toastr.error('Date cannot be in the future');
        return;
    }

    if (data.value !== undefined && data.value < 0) {
        toastr.error('Value must be positive');
        return;
    }

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

            // Reload table to get fresh data and maintain proper order
            recordsTable.ajax.reload(function() {
                if (callback && typeof callback === 'function') {
                    callback();
                }
            }, false);

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

    // Remove new record row if cancelling a new record
    if (id.toString().startsWith('new_')) {
        recordsTable.ajax.reload();
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

// Test function to ensure everything is working
function testAddRecord() {
    console.log('=== DEBUG TEST ===');
    console.log('recordsTable initialized:', !!recordsTable);
    console.log('moment available:', typeof moment !== 'undefined');
    console.log('jQuery available:', typeof $ !== 'undefined');
    console.log('formatValue test:', formatValue(1000));
    console.log('businessMetricId:', businessMetricId);

    if (recordsTable) {
        console.log('Table info:', recordsTable.page.info());
    }

    // Test adding a row directly
    try {
        addInlineRecord();
    } catch (error) {
        console.error('Error in addInlineRecord:', error);
        toastr.error('Error: ' + error.message);
    }
}

// Make functions available globally for debugging
window.addInlineRecord = addInlineRecord;
window.testAddRecord = testAddRecord;

// Modal Functions
function initializeModal() {
    // Set default date to today
    $('#record_date').val(moment().format('YYYY-MM-DD'));

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

function updatePreview() {
    const date = $('#record_date').val();
    const value = parseFloat($('#record_value').val()) || 0;
    const notes = $('#record_notes').val();

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
            $('#record_value').addClass('is-invalid');
            showFieldError('#record_value', 'Value seems too large. Please verify.');
        } else {
            $('#record_value').removeClass('is-invalid');
            clearFieldError('#record_value');
        }
    } else {
        animateTextChange('#preview_value', '-');
        animateTextChange('#preview_formatted', '-');
    }

    // Update notes preview
    if (notes && notes.length > 100) {
        $('#record_notes').addClass('is-invalid');
        showFieldError('#record_notes', `Notes are quite long (${notes.length} characters). Consider shortening.`);
    } else {
        $('#record_notes').removeClass('is-invalid');
        clearFieldError('#record_notes');
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

            // Reload table
            recordsTable.ajax.reload();

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
</script>
@endpush
