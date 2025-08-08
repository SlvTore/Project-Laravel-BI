@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Header Section -->
        <div class="col-12 mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Business Intelligence Dashboard</h1>
                    <p class="text-muted">Analisis dan ringkasan statistik bisnis</p>
                </div>
            </div>
        </div>

        <!-- Key Metrics Overview -->
        <div class="col-12 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Ringkasan Metrik Utama</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="p-3">
                                <div class="h4 mb-1 text-primary">{{ number_format(12500000, 0, ',', '.') }}</div>
                                <div class="small text-muted">Total Revenue (Rp)</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <div class="h4 mb-1 text-success">{{ number_format(450) }}</div>
                                <div class="small text-muted">Total Customers</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <div class="h4 mb-1 text-info">{{ number_format(25.5, 1) }}%</div>
                                <div class="small text-muted">Growth Rate</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <div class="h4 mb-1 text-warning">{{ number_format(8.2, 1) }}%</div>
                                <div class="small text-muted">Conversion Rate</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Summary -->
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Grafik Performa</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="summaryChart" width="400" height="200"></canvas>
                    </div>
                    <hr>
                    <div class="text-center small text-muted">
                        Data diperbarui: {{ now()->format('d M Y H:i') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Business Info -->
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informasi Bisnis</h6>
                </div>
                <div class="card-body">
                    @if(auth()->user()->primaryBusiness())
                        @php $business = auth()->user()->primaryBusiness() @endphp
                        <h6 class="text-dark">{{ $business->business_name }}</h6>
                        <p class="text-muted small">{{ $business->industry }}</p>
                        
                        <div class="mb-3">
                            <div class="small text-muted">Didirikan</div>
                            <div class="text-dark">{{ $business->founded_date ? $business->founded_date->format('d M Y') : 'N/A' }}</div>
                        </div>
                        
                        @if($business->website)
                        <div class="mb-3">
                            <div class="small text-muted">Website</div>
                            <a href="{{ $business->website }}" target="_blank" class="text-primary">{{ $business->website }}</a>
                        </div>
                        @endif
                        
                        <div class="small text-muted mt-3">
                            <i class="bi bi-info-circle"></i> Akses sebagai Business Investigator
                        </div>
                    @else
                        <p class="text-muted">Informasi bisnis tidak tersedia</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Access Limitation Notice -->
        <div class="col-12">
            <div class="alert alert-info border-left-info">
                <div class="d-flex align-items-center">
                    <i class="bi bi-info-circle text-info me-3 fs-4"></i>
                    <div>
                        <h6 class="alert-heading mb-1">Akses Terbatas</h6>
                        <p class="mb-0">Sebagai Business Investigator, Anda memiliki akses view-only ke ringkasan statistik dashboard. Untuk akses penuh ke data dan fitur lainnya, hubungi Business Owner untuk upgrade akses.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.chart-area {
    position: relative;
    height: 300px;
}

.alert-info {
    background-color: #d1ecf1;
    border-color: #bee5eb;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Sample chart for demonstration
const ctx = document.getElementById('summaryChart').getContext('2d');
const summaryChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        datasets: [{
            label: 'Revenue Trend',
            data: [2000000, 2500000, 2200000, 2800000, 3100000, 3500000],
            borderColor: '#4e73df',
            backgroundColor: 'rgba(78, 115, 223, 0.1)',
            tension: 0.3
        }, {
            label: 'Customers',
            data: [120, 150, 140, 180, 200, 220],
            borderColor: '#1cc88a',
            backgroundColor: 'rgba(28, 200, 138, 0.1)',
            tension: 0.3,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Revenue (Rp)'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Customers'
                },
                grid: {
                    drawOnChartArea: false,
                }
            }
        },
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        }
    }
});
</script>
@endpush