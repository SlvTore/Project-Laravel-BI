<!-- Specific Metric Data Section -->
<div class="row mt-4">
    @if($metricName == 'Total Penjualan' && isset($data['recent_sales']))
        <div class="col-12">
            <div class="content-card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="bi bi-graph-up me-2"></i>
                        Data Penjualan Terbaru
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Total Pendapatan</th>
                                    <th>COGS</th>
                                    <th>Laba Kotor</th>
                                    <th>Margin</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data['recent_sales'] as $sale)
                                    <tr>
                                        <td>{{ $sale->sales_date->format('d M Y') }}</td>
                                        <td>{{ $sale->formatted_revenue }}</td>
                                        <td>{{ $sale->formatted_cogs }}</td>
                                        <td>{{ $sale->formatted_gross_profit }}</td>
                                        <td>
                                            <span class="badge bg-{{ $sale->profit_margin > 0 ? 'success' : 'danger' }}">
                                                {{ $sale->formatted_profit_margin }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($metricName == 'Penjualan Produk Terlaris' && isset($data['top_products']))
        <div class="col-lg-8">
            <div class="content-card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="bi bi-trophy me-2"></i>
                        Top 10 Produk Terlaris (Bulan Ini)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Produk</th>
                                    <th>Terjual</th>
                                    <th>Pendapatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data['top_products'] as $index => $product)
                                    <tr>
                                        <td>
                                            @if($index < 3)
                                                <i class="bi bi-award-fill text-warning"></i>
                                            @else
                                                {{ $index + 1 }}
                                            @endif
                                        </td>
                                        <td>{{ $product->product_name }}</td>
                                        <td>{{ number_format($product->total_quantity) }}</td>
                                        <td>{{ 'Rp ' . number_format($product->total_revenue, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        @if(isset($data['categories']))
            <div class="col-lg-4">
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="bi bi-pie-chart me-2"></i>
                            Penjualan per Kategori
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="categoryChart"></div>
                    </div>
                </div>
            </div>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const categoryData = @json($data['categories']);

                const options = {
                    series: categoryData.map(cat => parseFloat(cat.total_revenue)),
                    chart: {
                        type: 'donut',
                        height: 300
                    },
                    labels: categoryData.map(cat => cat.category),
                    colors: ['#7cb947', '#2ecc71', '#3498db', '#f39c12', '#e74c3c'],
                    theme: {
                        mode: 'dark'
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

                const chart = new ApexCharts(document.querySelector("#categoryChart"), options);
                chart.render();
            });
            </script>
        @endif
    @endif

    @if($metricName == 'Jumlah Pelanggan Baru' && isset($data['new_customers']))
        <div class="col-12">
            <div class="content-card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="bi bi-person-plus me-2"></i>
                        Pelanggan Baru Terbaru
                    </h5>
                    <div class="card-actions">
                        <span class="badge bg-primary">{{ $data['monthly_count'] ?? 0 }} bulan ini</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Tanggal Bergabung</th>
                                    <th>Total Belanja</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data['new_customers'] as $customer)
                                    <tr>
                                        <td>{{ $customer->customer_name }}</td>
                                        <td>{{ $customer->email ?? '-' }}</td>
                                        <td>{{ $customer->first_purchase_date->format('d M Y') }}</td>
                                        <td>{{ $customer->formatted_total_spent }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($metricName == 'Jumlah Pelanggan Setia' && isset($data['loyal_customers']))
        <div class="col-lg-8">
            <div class="content-card">
                <div class="card-header">
                    <h5 class="card-title">
                        <i class="bi bi-heart-fill me-2"></i>
                        Top Pelanggan Setia
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Total Pembelian</th>
                                    <th>Total Belanja</th>
                                    <th>Rata-rata per Order</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data['loyal_customers'] as $customer)
                                    <tr>
                                        <td>{{ $customer->customer_name }}</td>
                                        <td>{{ $customer->total_purchases }}x</td>
                                        <td>{{ $customer->formatted_total_spent }}</td>
                                        <td>{{ $customer->formatted_average_order_value }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        @if(isset($data['loyalty_stats']))
            <div class="col-lg-4">
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="bi bi-graph-up me-2"></i>
                            Statistik Loyalitas
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="loyalty-stats">
                            <div class="stat-item">
                                <h3>{{ number_format($data['loyalty_stats']['total_customers']) }}</h3>
                                <p>Total Pelanggan</p>
                            </div>
                            <div class="stat-item">
                                <h3>{{ number_format($data['loyalty_stats']['loyal_customers']) }}</h3>
                                <p>Pelanggan Setia</p>
                                <small class="text-muted">
                                    {{ $data['loyalty_stats']['total_customers'] > 0 ? round(($data['loyalty_stats']['loyal_customers'] / $data['loyalty_stats']['total_customers']) * 100, 1) : 0 }}%
                                </small>
                            </div>
                            <div class="stat-item">
                                <h3>{{ number_format($data['loyalty_stats']['returning_customers']) }}</h3>
                                <p>Pelanggan Berulang</p>
                                <small class="text-muted">
                                    {{ $data['loyalty_stats']['total_customers'] > 0 ? round(($data['loyalty_stats']['returning_customers'] / $data['loyalty_stats']['total_customers']) * 100, 1) : 0 }}%
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <style>
            .loyalty-stats .stat-item {
                text-align: center;
                padding: 1rem;
                margin-bottom: 1rem;
                background: rgba(255, 255, 255, 0.05);
                border-radius: 8px;
            }

            .loyalty-stats .stat-item h3 {
                color: var(--primary-color);
                font-size: 2rem;
                font-weight: 600;
                margin-bottom: 0.5rem;
            }

            .loyalty-stats .stat-item p {
                color: rgba(255, 255, 255, 0.8);
                margin-bottom: 0.25rem;
            }
            </style>
        @endif
    @endif
</div>
