@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('content')
    <div class="content-header ms-5">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="content-title">Dashboard</h1>
                <p class="content-subtitle">Welcome back, {{ Auth::user()->name }}! Here's what's happening with your business.</p>
            </div>
            <div>
                <button class="btn btn-outline-secondary">
                    <i class="bi bi-download me-2"></i>Export Report
                </button>
            </div>
        </div>
    </div>

    <div class="content-body ms-5">
    <div class="row g-4 mb-4">
        <!-- Total Users -->
        <div class="col-md-3">
            <div class="content-card">
                <div class="card-body text-center position-relative">
                    <h6 class="card-title text-muted fw-bold mb-2" style="font-size: 0.85rem;">Total Users</h6>
                    <h2 class="fw-bold text-primary">40,689</h2>
                    <p class="mb-0 mt-2">
                        <span class="text-success fw-bold">
                            <i class="bi bi-graph-up-arrow me-1"></i> 8.5%
                        </span>
                        <span class="text-dark fw-bold">Up from yesterday</span>
                    </p>
                    <i class="bi bi-people-fill position-absolute top-0 end-0 m-3 text-primary" style="font-size: 2rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>

        <!-- Total Orders -->
        <div class="col-md-3">
            <div class="content-card">
                <div class="card-body text-center position-relative">
                    <h6 class="card-title text-muted fw-bold mb-2" style="font-size: 0.85rem;">Total Orders</h6>
                    <h2 class="fw-bold text-warning">10,293</h2>
                    <p class="mb-0 mt-2">
                        <span class="text-success fw-bold">
                            <i class="bi bi-graph-up-arrow me-1"></i> 1.3%
                        </span>
                        <span class="text-dark fw-bold">Up from yesterday</span>
                    </p>
                    <i class="bi bi-box-seam-fill position-absolute top-0 end-0 m-3 text-warning" style="font-size: 2rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>

        <!-- Total Sales -->
        <div class="col-md-3">
            <div class="content-card">
                <div class="card-body text-center position-relative">
                    <h6 class="card-title text-muted fw-bold mb-2" style="font-size: 0.85rem;">Total Sales</h6>
                    <h2 class="fw-bold text-success">$89,000</h2>
                    <p class="mb-0 mt-2">
                        <span class="text-danger fw-bold">
                            <i class="bi bi-graph-down-arrow me-1"></i> 4.3%
                        </span>
                        <span class="text-dark fw-bold">Down from yesterday</span>
                    </p>
                    <i class="bi bi-currency-dollar position-absolute top-0 end-0 m-3 text-success" style="font-size: 2rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>

        <!-- Pending Issues -->
        <div class="col-md-3">
            <div class="content-card">
                <div class="card-body text-center position-relative">
                    <h6 class="card-title text-muted fw-bold mb-2" style="font-size: 0.85rem;">Pending Issues</h6>
                    <h2 class="fw-bold text-danger">2,040</h2>
                    <p class="mb-0 mt-2">
                        <span class="text-success fw-bold">
                            <i class="bi bi-graph-up-arrow me-1"></i> 1.8%
                        </span>
                        <span class="text-dark fw-bold">Up from yesterday</span>
                    </p>
                    <i class="bi bi-clock-fill position-absolute top-0 end-0 m-3 text-danger" style="font-size: 2rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Middle Section -->
    <div class="row g-4 mb-4">
        <!-- Progress Chart -->
        <div class="col-md-8">
            <div class="content-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0 fw-bold">Business Progress</h5>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="progressDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                Monthly
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="progressDropdown">
                                <li><a class="dropdown-item" href="#">Daily</a></li>
                                <li><a class="dropdown-item" href="#">Weekly</a></li>
                                <li><a class="dropdown-item" href="#">Monthly</a></li>
                                <li><a class="dropdown-item" href="#">Annually</a></li>
                            </ul>
                        </div>
                    </div>
                    <div id="progressChart" style="height: 300px;">
                        <!-- Chart will be rendered here -->
                        <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                            <div class="text-center">
                                <i class="bi bi-bar-chart-line" style="font-size: 3rem;"></i>
                                <p class="mt-2">Chart will be displayed here</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Goals and Metrics -->
        <div class="col-md-4">
            <div class="content-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0 fw-bold">Goals & Metrics</h5>
                        <button class="btn btn-primary btn-sm rounded-circle" style="width: 35px; height: 35px;">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item border-0 px-0 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-bold">Revenue Growth</span>
                                    <br>
                                    <small class="text-muted">Target: 25% increase</small>
                                </div>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <span class="text-success fw-bold">On Track</span>
                                </div>
                            </div>
                        </li>
                        <li class="list-group-item border-0 px-0 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-bold">Customer Acquisition</span>
                                    <br>
                                    <small class="text-muted">Target: 1000 new customers</small>
                                </div>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                                    <span class="text-warning fw-bold">Behind</span>
                                </div>
                            </div>
                        </li>
                        <li class="list-group-item border-0 px-0 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-bold">Product Launch</span>
                                    <br>
                                    <small class="text-muted">Q2 2025 release</small>
                                </div>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <span class="text-success fw-bold">Completed</span>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Section -->
    <div class="row g-4">
        <!-- Recent Activities -->
        <div class="col-md-6">
            <div class="content-card">
                <div class="card-body">
                    <h5 class="card-title fw-bold mb-3">Recent Activities</h5>
                    <div class="activity-list">
                        <div class="d-flex align-items-start mb-3">
                            <div class="activity-icon me-3">
                                <i class="bi bi-person-plus-fill text-primary"></i>
                            </div>
                            <div class="flex-grow-1">
                                <p class="mb-1 fw-bold">New user registered</p>
                                <small class="text-muted">John Doe joined the platform</small>
                                <br>
                                <small class="text-muted">2 minutes ago</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="activity-icon me-3">
                                <i class="bi bi-cart-plus-fill text-success"></i>
                            </div>
                            <div class="flex-grow-1">
                                <p class="mb-1 fw-bold">New order received</p>
                                <small class="text-muted">Order #12345 for $299.99</small>
                                <br>
                                <small class="text-muted">15 minutes ago</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-start mb-3">
                            <div class="activity-icon me-3">
                                <i class="bi bi-graph-up text-warning"></i>
                            </div>
                            <div class="flex-grow-1">
                                <p class="mb-1 fw-bold">Metrics updated</p>
                                <small class="text-muted">Revenue tracking data refreshed</small>
                                <br>
                                <small class="text-muted">1 hour ago</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-md-6">
            <div class="content-card">
                <div class="card-body">
                    <h5 class="card-title fw-bold mb-3">Quick Actions</h5>
                    <div class="row g-3">
                        <div class="col-6">
                            <a href="{{ route('dashboard.metrics') }}" class="btn btn-outline-primary w-100 py-3">
                                <i class="bi bi-graph-up-arrow d-block mb-2" style="font-size: 1.5rem;"></i>
                                View Metrics
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('dashboard.users') }}" class="btn btn-outline-success w-100 py-3">
                                <i class="bi bi-people d-block mb-2" style="font-size: 1.5rem;"></i>
                                Manage Users
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('dashboard.feeds') }}" class="btn btn-outline-warning w-100 py-3">
                                <i class="bi bi-rss d-block mb-2" style="font-size: 1.5rem;"></i>
                                Data Feeds
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('dashboard.settings') }}" class="btn btn-outline-info w-100 py-3">
                                <i class="bi bi-gear d-block mb-2" style="font-size: 1.5rem;"></i>
                                Settings
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .activity-icon {
        width: 40px;
        height: 40px;
        background: rgba(var(--bs-primary-rgb), 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

    .activity-icon i {
        opacity: 0.8;
    }

    .btn-outline-primary:hover,
    .btn-outline-success:hover,
    .btn-outline-warning:hover,
    .btn-outline-info:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
</style>
@endpush

@push('scripts')
<!-- ApexCharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    // Sample chart implementation
    document.addEventListener('DOMContentLoaded', function() {
        const options = {
            series: [{
                name: 'Revenue',
                data: [31, 40, 28, 51, 42, 109, 100]
            }, {
                name: 'Orders',
                data: [11, 32, 45, 32, 34, 52, 41]
            }],
            chart: {
                height: 300,
                type: 'area',
                toolbar: {
                    show: false
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth'
            },
            xaxis: {
                type: 'datetime',
                categories: ["2024-01-01", "2024-02-01", "2024-03-01", "2024-04-01", "2024-05-01", "2024-06-01", "2024-07-01"]
            },
            colors: ['#7cb947', '#1e3c80'],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.7,
                    opacityTo: 0.9,
                }
            }
        };

        const chart = new ApexCharts(document.querySelector("#progressChart"), options);
        chart.render();
    });
</script>
@endpush

    </div>

