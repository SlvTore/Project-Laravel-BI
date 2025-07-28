@extends('layouts.dashboard')

@section('title', 'Metrics')

@section('content')
    <div class="content-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="content-title">Metrics</h1>
                <p class="content-subtitle">Track and analyze your business performance metrics</p>
            </div>
            <div class="d-flex gap-2">
                <div class="input-group" style="width: 250px;">
                    <span class="input-group-text bg-transparent border-end-0">
                        <i class="bi bi-calendar-range text-white"></i>
                    </span>
                    <input type="text" class="form-control border-start-0" id="daterangepicker" placeholder="Select date range">
                </div>
                <button class="btn btn-primary">
                    <i class="bi bi-plus-lg me-2"></i>Add Metric
                </button>
            </div>
        </div>
    </div>

    <div class="content-body">
    <!-- Metrics Overview Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="content-card border-start border-primary border-4">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase fw-bold mb-2" style="font-size: 0.75rem;">Active Metrics</h6>
                    <h3 class="fw-bold text-primary mb-0">24</h3>
                    <small class="text-success">
                        <i class="bi bi-arrow-up"></i> 12% increase
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="content-card border-start border-success border-4">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase fw-bold mb-2" style="font-size: 0.75rem;">Goals Achieved</h6>
                    <h3 class="fw-bold text-success mb-0">18</h3>
                    <small class="text-success">
                        <i class="bi bi-arrow-up"></i> 8% increase
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="content-card border-start border-warning border-4">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase fw-bold mb-2" style="font-size: 0.75rem;">In Progress</h6>
                    <h3 class="fw-bold text-warning mb-0">6</h3>
                    <small class="text-warning">
                        <i class="bi bi-dash"></i> No change
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="content-card border-start border-danger border-4">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase fw-bold mb-2" style="font-size: 0.75rem;">Needs Attention</h6>
                    <h3 class="fw-bold text-danger mb-0">3</h3>
                    <small class="text-danger">
                        <i class="bi bi-arrow-down"></i> 2% decrease
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row g-4 mb-4">
        <!-- Revenue Chart -->
        <div class="col-md-8">
            <div class="content-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title fw-bold mb-0">Revenue Trends</h5>
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="revenueTimeframe" id="revenue7d" autocomplete="off" checked>
                            <label class="btn btn-outline-secondary btn-sm" for="revenue7d">7D</label>

                            <input type="radio" class="btn-check" name="revenueTimeframe" id="revenue30d" autocomplete="off">
                            <label class="btn btn-outline-secondary btn-sm" for="revenue30d">30D</label>

                            <input type="radio" class="btn-check" name="revenueTimeframe" id="revenue90d" autocomplete="off">
                            <label class="btn btn-outline-secondary btn-sm" for="revenue90d">90D</label>
                        </div>
                    </div>
                    <div id="revenueChart" style="height: 350px;">
                        <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                            <div class="text-center">
                                <i class="bi bi-graph-up" style="font-size: 3rem;"></i>
                                <p class="mt-2">Revenue chart will be displayed here</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="col-md-4">
            <div class="content-card">
                <div class="card-body">
                    <h5 class="card-title fw-bold mb-3">Performance Score</h5>
                    <div class="text-center mb-4">
                        <div class="position-relative d-inline-block">
                            <canvas id="performanceChart" width="150" height="150"></canvas>
                            <div class="position-absolute top-50 start-50 translate-middle text-center">
                                <h2 class="fw-bold text-primary mb-0">87</h2>
                                <small class="text-muted">Score</small>
                            </div>
                        </div>
                    </div>

                    <div class="performance-metrics">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Customer Satisfaction</span>
                            <span class="fw-bold text-success">94%</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Revenue Growth</span>
                            <span class="fw-bold text-primary">85%</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Market Share</span>
                            <span class="fw-bold text-warning">72%</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">Operational Efficiency</span>
                            <span class="fw-bold text-info">89%</span>
                        </div>
                    </div>
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
                        <h5 class="card-title fw-bold mb-0">All Metrics</h5>
                        <div class="d-flex gap-2">
                            <div class="input-group" style="width: 200px;">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="bi bi-funnel text-muted"></i>
                                </span>
                                <select class="form-select border-start-0">
                                    <option selected>All Categories</option>
                                    <option>Revenue</option>
                                    <option>Customer</option>
                                    <option>Operations</option>
                                    <option>Marketing</option>
                                </select>
                            </div>
                            <div class="input-group" style="width: 250px;">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="bi bi-search text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" placeholder="Search metrics...">
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover" id="metricsTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="fw-semibold">Metric Name</th>
                                    <th class="fw-semibold">Category</th>
                                    <th class="fw-semibold">Current Value</th>
                                    <th class="fw-semibold">Target</th>
                                    <th class="fw-semibold">Progress</th>
                                    <th class="fw-semibold">Status</th>
                                    <th class="fw-semibold">Last Updated</th>
                                    <th class="fw-semibold">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="metric-icon me-2">
                                                <i class="bi bi-currency-dollar text-success"></i>
                                            </div>
                                            <div>
                                                <span class="fw-semibold">Monthly Revenue</span>
                                                <br>
                                                <small class="text-muted">Revenue tracking</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-success-subtle text-success">Revenue</span></td>
                                    <td class="fw-semibold">$84,500</td>
                                    <td>$90,000</td>
                                    <td>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: 94%"></div>
                                        </div>
                                        <small class="text-muted">94%</small>
                                    </td>
                                    <td><span class="badge bg-success">On Track</span></td>
                                    <td>2 hours ago</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-outline-info" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="metric-icon me-2">
                                                <i class="bi bi-people text-primary"></i>
                                            </div>
                                            <div>
                                                <span class="fw-semibold">Customer Acquisition</span>
                                                <br>
                                                <small class="text-muted">New customers per month</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-primary-subtle text-primary">Customer</span></td>
                                    <td class="fw-semibold">1,247</td>
                                    <td>1,500</td>
                                    <td>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-warning" role="progressbar" style="width: 83%"></div>
                                        </div>
                                        <small class="text-muted">83%</small>
                                    </td>
                                    <td><span class="badge bg-warning">Behind</span></td>
                                    <td>5 hours ago</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-outline-info" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="metric-icon me-2">
                                                <i class="bi bi-graph-up text-info"></i>
                                            </div>
                                            <div>
                                                <span class="fw-semibold">Conversion Rate</span>
                                                <br>
                                                <small class="text-muted">Website conversion</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-info-subtle text-info">Marketing</span></td>
                                    <td class="fw-semibold">3.2%</td>
                                    <td>3.5%</td>
                                    <td>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-info" role="progressbar" style="width: 91%"></div>
                                        </div>
                                        <small class="text-muted">91%</small>
                                    </td>
                                    <td><span class="badge bg-success">On Track</span></td>
                                    <td>1 day ago</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-outline-info" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Add Button -->
    <div class="position-fixed bottom-3 end-3">
        <button class="btn btn-primary btn-lg rounded-circle shadow-lg" style="width: 60px; height: 60px;">
            <i class="bi bi-plus-lg fs-4"></i>
        </button>
    </div>
@endsection

@push('styles')
<style>
    .metric-icon {
        width: 35px;
        height: 35px;
        background: rgba(var(--bs-primary-rgb), 0.1);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }

    .table th {
        border-top: none;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
    }

    .progress {
        background-color: rgba(var(--bs-gray-200));
    }

    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
    }

    #performanceChart {
        filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
    }
</style>
@endpush

@push('scripts')
<!-- ApexCharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<!-- Chart.js for doughnut chart -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- DateRangePicker -->
<script>
    $(function() {
        // Initialize DateRangePicker
        $('#daterangepicker').daterangepicker({
            opens: 'left',
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Clear'
            }
        });

        $('#daterangepicker').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
        });

        // Initialize DataTable
        $('#metricsTable').DataTable({
            responsive: true,
            pageLength: 10,
            order: [[6, 'desc']], // Sort by last updated
            columnDefs: [
                { orderable: false, targets: [7] } // Disable sorting for actions column
            ]
        });

        // Revenue Chart
        const revenueOptions = {
            series: [{
                name: 'Revenue',
                data: [31000, 40000, 35000, 51000, 49000, 62000, 69000, 91000, 148000, 84500]
            }],
            chart: {
                height: 350,
                type: 'line',
                toolbar: {
                    show: false
                }
            },
            stroke: {
                width: 3,
                curve: 'smooth'
            },
            xaxis: {
                type: 'datetime',
                categories: ['2024-01', '2024-02', '2024-03', '2024-04', '2024-05', '2024-06', '2024-07', '2024-08', '2024-09', '2024-10']
            },
            colors: ['#7cb947'],
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'dark',
                    gradientToColors: ['#1e3c80'],
                    shadeIntensity: 1,
                    type: 'horizontal',
                    opacityFrom: 1,
                    opacityTo: 1,
                }
            },
            markers: {
                size: 4,
                colors: ["#7cb947"],
                strokeColors: "#fff",
                strokeWidth: 2,
                hover: {
                    size: 7,
                }
            },
            yaxis: {
                labels: {
                    formatter: function (val) {
                        return "$" + val.toLocaleString()
                    }
                }
            }
        };

        const revenueChart = new ApexCharts(document.querySelector("#revenueChart"), revenueOptions);
        revenueChart.render();

        // Performance Chart (Doughnut)
        const ctx = document.getElementById('performanceChart').getContext('2d');
        const performanceChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [87, 13],
                    backgroundColor: [
                        'linear-gradient(135deg, #7cb947 0%, #1e3c80 100%)',
                        '#e9ecef'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    });
</script>
@endpush

    </div>
@endsection
