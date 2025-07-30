@extends('layouts.dashboard')

@section('title', 'Edit Records - ' . $businessMetric->name)

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap5.min.css">
<style>
    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
    }

    .stats-card .stats-change {
        font-size: 0.9rem;
        opacity: 0.8;
    }

    .chart-container {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        margin-bottom: 30px;
    }

    .datatable-container {
        background: white;
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
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">{{ $businessMetric->name }} Records</h2>
            <p class="text-muted mb-0">{{ $businessMetric->business->name }} - {{ $businessMetric->description }}</p>
        </div>
        <div>
            <a href="{{ route('dashboard.metrics') }}"
               class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
            <button class="btn btn-excel" onclick="addNewRecord()">
                <i class="fas fa-plus"></i> Tambah Record
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="stats-card">
                <div class="metric-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="card-title">Total Records</div>
                <div class="stats-value" id="totalRecords">{{ $statistics['total_records'] }}</div>
                <div class="stats-change">Data points collected</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card">
                <div class="metric-icon">
                    <i class="fas fa-calculator"></i>
                </div>
                <div class="card-title">Average Value</div>
                <div class="stats-value" id="avgValue">{{ $businessMetric->formatted_value(round($statistics['avg_value'])) }}</div>
                <div class="stats-change">Overall average</div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card">
                <div class="metric-icon">
                    <i class="fas fa-chart-area"></i>
                </div>
                <div class="card-title">Growth Rate</div>
                <div class="stats-value {{ $statistics['growth_rate'] >= 0 ? 'growth-positive' : 'growth-negative' }}" id="growthRate">
                    {{ number_format($statistics['growth_rate'], 1) }}%
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
                    {{ $statistics['last_update'] ? $statistics['last_update']->format('d M Y') : 'N/A' }}
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
                    <h5 class="mb-0">Trend Analysis</h5>
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
                <h5 class="mb-3">Performance Summary</h5>
                <div id="summaryChart" style="height: 300px;"></div>
            </div>
        </div>
    </div>

    <!-- Excel-like Data Table -->
    <div class="datatable-container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Data Records</h5>
            <div>
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
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>Date</th>
                        <th>Value</th>
                        <th>Formatted Value</th>
                        <th>Notes</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data will be loaded via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Record Modal -->
<div class="modal fade" id="recordModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="recordModalTitle">Add New Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="recordForm">
                @csrf
                <input type="hidden" id="recordId" name="record_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="record_date" class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="record_date" name="record_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="value" class="form-label">Value <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="value" name="value" step="0.01" required>
                                <div class="form-text">{{ $businessMetric->unit ?? 'Enter the metric value' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Metric-specific fields -->
                    @if($businessMetric->type === 'sales_data')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="revenue" class="form-label">Revenue</label>
                                <input type="number" class="form-control" id="revenue" name="revenue" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cogs" class="form-label">COGS</label>
                                <input type="number" class="form-control" id="cogs" name="cogs" step="0.01">
                            </div>
                        </div>
                    </div>
                    @elseif($businessMetric->type === 'product_sales')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="product_name" class="form-label">Product Name</label>
                                <input type="text" class="form-control" id="product_name" name="product_name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="quantity_sold" class="form-label">Quantity Sold</label>
                                <input type="number" class="form-control" id="quantity_sold" name="quantity_sold">
                            </div>
                        </div>
                    </div>
                    @elseif($businessMetric->type === 'customers')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customer_name" class="form-label">Customer Name</label>
                                <input type="text" class="form-control" id="customer_name" name="customer_name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customer_type" class="form-label">Customer Type</label>
                                <select class="form-control" id="customer_type" name="customer_type">
                                    <option value="new">New Customer</option>
                                    <option value="returning">Returning Customer</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-excel">
                        <i class="fas fa-save"></i> Save Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
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
const metricType = '{{ $businessMetric->type }}';

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
});

function initializeCharts() {
    // Trend Chart
    const trendOptions = {
        series: [{
            name: '{{ $businessMetric->name }}',
            data: @json($chartData['values']->toArray())
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
            categories: @json($chartData['labels']->toArray()),
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
            {{ $statistics['this_month'] }},
            {{ $statistics['last_month'] }},
            {{ $statistics['avg_value'] - $statistics['this_month'] }}
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
            url: '{{ route("dashboard.metrics.records.show", $businessMetric->id) }}',
            data: function(d) {
                d.draw = d.draw;
                d.start = d.start;
                d.length = d.length;
                d.search = d.search.value;
                d.order = d.order;
                d.columns = d.columns;
            }
        },
        columns: [
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
                render: function(data) {
                    return moment(data).format('DD/MM/YYYY');
                }
            },
            {
                data: 'value',
                name: 'value',
                render: function(data) {
                    return formatValue(data);
                }
            },
            {
                data: 'formatted_value',
                name: 'formatted_value'
            },
            {
                data: 'notes',
                name: 'notes',
                render: function(data) {
                    return data ? (data.length > 50 ? data.substring(0, 50) + '...' : data) : '-';
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
                            <button class="btn btn-outline-primary" onclick="editRecord(${data})" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="deleteRecord(${data})" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        order: [[1, 'desc']],
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
            }
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

    // Record form submission
    $('#recordForm').on('submit', function(e) {
        e.preventDefault();
        saveRecord();
    });
}

function updateChart(days) {
    $.ajax({
        url: '{{ route("dashboard.metrics.records.show", $businessMetric->id) }}',
        data: { period: days },
        success: function(response) {
            trendChart.updateSeries([{
                name: '{{ $businessMetric->name }}',
                data: response.chartData.values
            }]);

            trendChart.updateOptions({
                xaxis: {
                    categories: response.chartData.labels
                }
            });

            updateStatistics(response.statistics);
        },
        error: function() {
            toastr.error('Failed to update chart');
        }
    });
}

function updateStatistics(stats) {
    $('#totalRecords').text(stats.total_records);
    $('#avgValue').text(formatValue(Math.round(stats.avg_value)));
    $('#growthRate').text(parseFloat(stats.growth_rate).toFixed(1) + '%')
        .removeClass('growth-positive growth-negative')
        .addClass(stats.growth_rate >= 0 ? 'growth-positive' : 'growth-negative');
    $('#lastUpdate').text(stats.last_update ? moment(stats.last_update).format('DD MMM YYYY') : 'N/A');
}

function addNewRecord() {
    $('#recordModalTitle').text('Add New Record');
    $('#recordForm')[0].reset();
    $('#recordId').val('');
    $('#record_date').val(moment().format('YYYY-MM-DD'));
    $('#recordModal').modal('show');
}

function editRecord(id) {
    $('#recordModalTitle').text('Edit Record');

    $.ajax({
        url: `{{ url('/dashboard/metrics/records') }}/${id}`,
        method: 'GET',
        success: function(record) {
            $('#recordId').val(record.id);
            $('#record_date').val(moment(record.record_date).format('YYYY-MM-DD'));
            $('#value').val(record.value);
            $('#notes').val(record.notes);

            // Fill metric-specific fields
            if (record.sales_data) {
                $('#revenue').val(record.sales_data.revenue);
                $('#cogs').val(record.sales_data.cogs);
            } else if (record.product_sales) {
                $('#product_name').val(record.product_sales.product_name);
                $('#quantity_sold').val(record.product_sales.quantity_sold);
            } else if (record.customer) {
                $('#customer_name').val(record.customer.customer_name);
                $('#customer_type').val(record.customer.customer_type);
            }

            $('#recordModal').modal('show');
        },
        error: function() {
            toastr.error('Failed to load record details');
        }
    });
}

function saveRecord() {
    const formData = new FormData($('#recordForm')[0]);
    const recordId = $('#recordId').val();
    const url = recordId ?
        `{{ url('/dashboard/metrics/records') }}/${recordId}` :
        '{{ route("dashboard.metrics.records.store", $businessMetric->id) }}';
    const method = recordId ? 'PUT' : 'POST';

    if (recordId) {
        formData.append('_method', 'PUT');
    }

    $.ajax({
        url: url,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            $('#recordModal').modal('hide');
            recordsTable.ajax.reload();
            updateChart($('.chart-period.active').data('period'));
            toastr.success(response.message || 'Record saved successfully');
        },
        error: function(xhr) {
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                const errors = xhr.responseJSON.errors;
                let errorMessage = 'Validation errors:\n';
                Object.keys(errors).forEach(field => {
                    errorMessage += `${field}: ${errors[field].join(', ')}\n`;
                });
                toastr.error(errorMessage);
            } else {
                toastr.error('Failed to save record');
            }
        }
    });
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
                recordsTable.ajax.reload();
                updateChart($('.chart-period.active').data('period'));
                $('#selectAll').prop('checked', false);
                toastr.success(response.message || 'Records deleted successfully');
            },
            error: function() {
                toastr.error('Failed to delete selected records');
            }
        });
    }
}

function refreshTable() {
    recordsTable.ajax.reload();
    updateChart($('.chart-period.active').data('period'));
    toastr.info('Table refreshed');
}

function exportToExcel() {
    window.location.href = '{{ route("dashboard.metrics.records.export", $businessMetric->id) }}';
}

function formatValue(value) {
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
}

// Include moment.js for date formatting
if (typeof moment === 'undefined') {
    const script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js';
    document.head.appendChild(script);
}
</script>
@endpush
