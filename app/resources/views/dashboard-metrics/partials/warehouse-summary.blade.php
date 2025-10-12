{{-- Warehouse Summary Panel --}}
<div class="warehouse-summary-panel">
    {{-- Monthly Summary Card --}}
    <div class="card bg-gradient-dark border-secondary mb-3">
        <div class="card-header bg-transparent border-secondary">
            <h6 class="mb-0 text-white">
                <i class="fas fa-calendar-check me-2"></i>Ringkasan Periode
            </h6>
        </div>
        <div class="card-body">
            @if($type==='sales')
                <div class="summary-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">
                            <i class="fas fa-money-bill-wave me-1"></i>Total Revenue
                        </span>
                        <span class="text-success fw-bold fs-5" id="stat-revenue">
                            Rp {{ number_format((float)($warehouseData['monthly']['total_revenue'] ?? 0), 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="progress mt-2" style="height: 5px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                    </div>
                </div>

                <div class="summary-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">
                            <i class="fas fa-shopping-cart me-1"></i>Total Transaksi
                        </span>
                        <span class="text-info fw-bold fs-5" id="stat-transactions">
                            {{ number_format((int)($warehouseData['monthly']['transaction_count'] ?? 0), 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="progress mt-2" style="height: 5px;">
                        <div class="progress-bar bg-info" role="progressbar" style="width: 85%"></div>
                    </div>
                </div>

                <div class="summary-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">
                            <i class="fas fa-boxes me-1"></i>Total Quantity
                        </span>
                        <span class="text-warning fw-bold fs-5" id="stat-quantity">
                            {{ number_format((int)($warehouseData['monthly']['total_quantity'] ?? 0), 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="progress mt-2" style="height: 5px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: 70%"></div>
                    </div>
                </div>

                <div class="summary-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">
                            <i class="fas fa-calculator me-1"></i>Avg per Trans
                        </span>
                        <span class="text-light fw-bold">
                            @php
                                $avgPerTrans = ($warehouseData['monthly']['transaction_count'] ?? 0) > 0
                                    ? ($warehouseData['monthly']['total_revenue'] / $warehouseData['monthly']['transaction_count'])
                                    : 0;
                            @endphp
                            Rp {{ number_format($avgPerTrans, 0, ',', '.') }}
                        </span>
                    </div>
                </div>

            @elseif($type==='cogs')
                <div class="summary-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">
                            <i class="fas fa-money-bill-wave me-1"></i>Total COGS
                        </span>
                        <span class="text-danger fw-bold fs-5" id="stat-cogs">
                            Rp {{ number_format((float)($warehouseData['monthly']['total_cogs'] ?? 0), 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="progress mt-2" style="height: 5px;">
                        <div class="progress-bar bg-danger" role="progressbar" style="width: 100%"></div>
                    </div>
                </div>

            @elseif($type==='margin')
                <div class="summary-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">
                            <i class="fas fa-percentage me-1"></i>Total Margin
                        </span>
                        <span class="text-success fw-bold fs-5" id="stat-margin">
                            Rp {{ number_format((float)($warehouseData['monthly']['total_margin'] ?? 0), 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="progress mt-2" style="height: 5px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                    </div>
                </div>

            @elseif($type==='new_customers')
                <div class="summary-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">
                            <i class="fas fa-user-plus me-1"></i>Pelanggan Baru
                        </span>
                        <span class="text-primary fw-bold fs-5" id="stat-new-customers">
                            {{ number_format((int)($warehouseData['monthly']['new_customers'] ?? 0), 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="progress mt-2" style="height: 5px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: 100%"></div>
                    </div>
                </div>

            @elseif($type==='returning_customers')
                <div class="summary-item mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">
                            <i class="fas fa-user-check me-1"></i>Pelanggan Setia
                        </span>
                        <span class="text-success fw-bold fs-5" id="stat-returning-customers">
                            {{ number_format((int)($warehouseData['monthly']['returning_customers'] ?? 0), 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="progress mt-2" style="height: 5px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Top Products/Items Card --}}
    @if(in_array($type, ['sales', 'top_products']) && isset($warehouseData['top']))
        <div class="card bg-gradient-dark border-secondary mb-3">
            <div class="card-header bg-transparent border-secondary">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 text-white">
                        <i class="fas fa-trophy me-2"></i>Top Produk
                    </h6>
                    <span class="badge bg-warning text-dark">Revenue</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @php $top = $warehouseData['top'] ?? []; @endphp
                    @forelse($top as $index => $product)
                        <div class="list-group-item bg-transparent text-white border-secondary">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-1">
                                        @if($index === 0)
                                            <i class="fas fa-crown text-warning me-2"></i>
                                        @elseif($index === 1)
                                            <i class="fas fa-medal text-secondary me-2"></i>
                                        @elseif($index === 2)
                                            <i class="fas fa-award text-warning me-2"></i>
                                        @else
                                            <span class="badge bg-secondary me-2">{{ $index + 1 }}</span>
                                        @endif
                                        <strong class="small">{{ $product->product_name }}</strong>
                                    </div>
                                    <div class="small text-muted">
                                        Qty: {{ number_format((float)($product->total_qty ?? 0), 0, ',', '.') }}
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="text-success fw-bold small">
                                        Rp {{ number_format((float)($product->total_revenue ?? 0), 0, ',', '.') }}
                                    </div>
                                </div>
                            </div>
                            <div class="progress mt-2" style="height: 3px;">
                                @php
                                    $maxRevenue = $top->max('total_revenue') ?? 1;
                                    $percentage = ($maxRevenue > 0) ? (($product->total_revenue / $maxRevenue) * 100) : 0;
                                @endphp
                                <div class="progress-bar bg-success" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="list-group-item bg-transparent text-center text-muted py-4">
                            <i class="fas fa-box-open fa-2x mb-2"></i>
                            <p class="mb-0 small">Belum ada data produk</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    {{-- Customer Details for Customer Metrics --}}
    @if(in_array($type, ['new_customers', 'returning_customers']) && isset($warehouseData['customers']) && count($warehouseData['customers']) > 0)
        <div class="card bg-gradient-dark border-secondary">
            <div class="card-header bg-transparent border-secondary">
                <h6 class="mb-0 text-white">
                    @if($type === 'new_customers')
                        <i class="fas fa-users me-2"></i>Pelanggan Baru Terbaru
                    @else
                        <i class="fas fa-crown me-2"></i>Pelanggan Setia Teraktif
                    @endif
                </h6>
            </div>
            <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                <div class="list-group list-group-flush">
                    @foreach($warehouseData['customers'] as $customer)
                        <div class="list-group-item bg-transparent text-white border-secondary">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <strong class="small d-block mb-1">{{ $customer->customer_name }}</strong>
                                    <div class="small text-muted">
                                        @if($type === 'new_customers')
                                            <i class="far fa-calendar me-1"></i>{{ \Carbon\Carbon::parse($customer->first_purchase_date)->format('d M Y') }}
                                        @else
                                            <i class="fas fa-shopping-bag me-1"></i>{{ $customer->total_purchases ?? 0 }}x transaksi
                                        @endif
                                    </div>
                                    @if($customer->email)
                                        <div class="small text-muted">
                                            <i class="far fa-envelope me-1"></i>{{ $customer->email }}
                                        </div>
                                    @endif
                                </div>
                                <div class="text-end">
                                    <div class="text-success fw-bold small">
                                        Rp {{ number_format($customer->total_spent, 0, ',', '.') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>

<style>
.summary-item {
    animation: slideIn 0.5s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.list-group-item {
    transition: all 0.2s ease;
}

.list-group-item:hover {
    background-color: rgba(102, 126, 234, 0.1) !important;
    transform: translateX(5px);
}

.progress {
    background-color: rgba(255, 255, 255, 0.1);
}
</style>
