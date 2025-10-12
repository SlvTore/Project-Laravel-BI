{{-- Enhanced Warehouse Data Section --}}
<div class="warehouse-section card shadow-lg border-0 mb-4">
    <div class="card-header bg-gradient-primary text-white py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-database me-2"></i>Data Warehouse Analytics
            </h5>
            <div class="d-flex gap-2">
                <span class="badge bg-light text-dark">
                    <i class="fas fa-layer-group me-1"></i>OLAP
                </span>
                <button class="btn btn-sm btn-outline-light" onclick="refreshWarehouseData()" id="refreshWarehouseBtn">
                    <i class="fas fa-sync-alt me-1"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <div class="card-body bg-dark text-white">
        @if(!empty($warehouseData) && ($warehouseData['available'] ?? false))
            {{-- Enhanced Control Panel --}}
            <div class="warehouse-controls mb-4">
                <div class="row g-3">
                    {{-- Date Range Selector --}}
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">
                            <i class="far fa-calendar-alt me-1"></i>Periode Waktu
                        </label>
                        <select class="form-select form-select-sm" id="dateRangeSelector" onchange="updateWarehouseData()">
                            <option value="7">7 Hari Terakhir</option>
                            <option value="30" selected>30 Hari Terakhir</option>
                            <option value="60">60 Hari Terakhir</option>
                            <option value="90">90 Hari Terakhir</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>

                    {{-- Custom Date Inputs (hidden by default) --}}
                    <div class="col-md-3 d-none" id="customDateInputs">
                        <label class="form-label small fw-semibold">Dari Tanggal</label>
                        <input type="date" class="form-control form-control-sm" id="startDate">
                    </div>
                    <div class="col-md-3 d-none" id="customDateInputs2">
                        <label class="form-label small fw-semibold">Sampai Tanggal</label>
                        <input type="date" class="form-control form-control-sm" id="endDate">
                    </div>

                    {{-- Grouping/Aggregation --}}
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">
                            <i class="fas fa-layer-group me-1"></i>Group By
                        </label>
                        <select class="form-select form-select-sm" id="groupBySelector" onchange="updateWarehouseData()">
                            <option value="daily" selected>Harian</option>
                            <option value="weekly">Mingguan</option>
                            <option value="monthly">Bulanan</option>
                        </select>
                    </div>

                    {{-- Per Page Selector --}}
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">
                            <i class="fas fa-list me-1"></i>Tampilkan
                        </label>
                        <select class="form-select form-select-sm" id="perPageSelector" onchange="updateWarehouseData()">
                            <option value="10">10 baris</option>
                            <option value="25">25 baris</option>
                            <option value="50" selected>50 baris</option>
                            <option value="100">100 baris</option>
                        </select>
                    </div>

                    {{-- Export Options --}}
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">
                            <i class="fas fa-download me-1"></i>Export
                        </label>
                        <div class="btn-group w-100" role="group">
                            <button type="button" class="btn btn-sm btn-success" onclick="exportToExcel()" title="Export to Excel">
                                <i class="fas fa-file-excel"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger" onclick="exportToPDF()" title="Export to PDF">
                                <i class="fas fa-file-pdf"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-info" onclick="exportToCSV()" title="Export to CSV">
                                <i class="fas fa-file-csv"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            @php $type = $warehouseData['type'] ?? 'sales'; @endphp

            {{-- Data Display Area --}}
            <div class="warehouse-data-display">
                <div class="row g-4">
                    {{-- Main Data Table --}}
                    <div class="col-lg-8">
                        <div class="card bg-gradient-dark border-secondary">
                            <div class="card-header bg-transparent border-secondary">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 text-white">
                                        @switch($type)
                                            @case('sales') <i class="fas fa-chart-line me-2"></i>Tren Penjualan @break
                                            @case('cogs') <i class="fas fa-money-bill-wave me-2"></i>Tren COGS @break
                                            @case('margin') <i class="fas fa-percentage me-2"></i>Tren Margin @break
                                            @case('new_customers') <i class="fas fa-user-plus me-2"></i>Pelanggan Baru @break
                                            @case('returning_customers') <i class="fas fa-user-check me-2"></i>Pelanggan Setia @break
                                            @case('top_products') <i class="fas fa-trophy me-2"></i>Produk Terlaris @break
                                        @endswitch
                                    </h6>
                                    <span class="badge bg-primary" id="dataCount">
                                        {{ count($warehouseData['daily'] ?? []) }} records
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                                    <table class="table table-dark table-hover table-striped mb-0" id="warehouseDailyTable">
                                        <thead class="sticky-top bg-dark">
                                            <tr>
                                                <th class="border-secondary">Tanggal</th>
                                                @if($type==='sales')
                                                    <th class="text-end border-secondary">Revenue</th>
                                                    <th class="text-end border-secondary">Transaksi</th>
                                                    <th class="text-end border-secondary">Qty</th>
                                                    <th class="text-end border-secondary">Avg/Trans</th>
                                                @elseif($type==='cogs')
                                                    <th class="text-end border-secondary">Total COGS</th>
                                                    <th class="text-end border-secondary">Qty Terjual</th>
                                                    <th class="text-end border-secondary">Avg COGS/Unit</th>
                                                @elseif($type==='margin')
                                                    <th class="text-end border-secondary">Total Margin</th>
                                                    <th class="text-end border-secondary">Margin %</th>
                                                    <th class="text-end border-secondary">Revenue</th>
                                                @elseif($type==='new_customers')
                                                    <th class="text-end border-secondary">Jumlah Baru</th>
                                                @elseif($type==='returning_customers')
                                                    <th class="text-end border-secondary">Jumlah Setia</th>
                                                @elseif($type==='top_products')
                                                    <th class="border-secondary">Produk</th>
                                                    <th class="text-end border-secondary">Revenue</th>
                                                    <th class="text-end border-secondary">Qty</th>
                                                    <th class="text-end border-secondary">Avg Price</th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $dailyRows = $warehouseData['daily'] ?? []; @endphp
                                            @forelse($dailyRows as $index => $row)
                                                <tr class="warehouse-row" data-date="{{ $row->date ?? $row->sales_date }}">
                                                    <td class="fw-semibold border-secondary">
                                                        {{ \Carbon\Carbon::parse($row->date ?? $row->sales_date)->format('d M Y') }}
                                                    </td>
                                                    @if($type==='sales')
                                                        <td class="text-end fw-bold text-success border-secondary">
                                                            Rp {{ number_format((float)($row->gross_revenue ?? 0), 0, ',', '.') }}
                                                        </td>
                                                        <td class="text-end border-secondary">
                                                            <span class="badge bg-info">{{ (int)($row->transaction_count ?? 0) }}</span>
                                                        </td>
                                                        <td class="text-end border-secondary">{{ number_format((int)($row->item_count ?? 0), 0, ',', '.') }}</td>
                                                        <td class="text-end text-muted border-secondary">
                                                            Rp {{ number_format((float)($row->avg_unit_price ?? 0), 0, ',', '.') }}
                                                        </td>
                                                    @elseif($type==='cogs')
                                                        <td class="text-end fw-bold text-danger border-secondary">
                                                            Rp {{ number_format((float)($row->total_cogs ?? 0), 0, ',', '.') }}
                                                        </td>
                                                        <td class="text-end border-secondary">
                                                            {{ number_format((float)($row->total_quantity_sold ?? 0), 1) }}
                                                        </td>
                                                        <td class="text-end text-muted border-secondary">
                                                            @php
                                                                $avgCogs = ($row->total_quantity_sold ?? 0) > 0
                                                                    ? ($row->total_cogs / $row->total_quantity_sold)
                                                                    : 0;
                                                            @endphp
                                                            Rp {{ number_format($avgCogs, 0, ',', '.') }}
                                                        </td>
                                                    @elseif($type==='margin')
                                                        <td class="text-end fw-bold text-success border-secondary">
                                                            Rp {{ number_format((float)($row->total_margin ?? 0), 0, ',', '.') }}
                                                        </td>
                                                        <td class="text-end border-secondary">
                                                            <span class="badge {{ ($row->margin_percentage ?? 0) > 30 ? 'bg-success' : (($row->margin_percentage ?? 0) > 15 ? 'bg-warning' : 'bg-danger') }}">
                                                                {{ number_format((float)($row->margin_percentage ?? 0), 1) }}%
                                                            </span>
                                                        </td>
                                                        <td class="text-end text-info border-secondary">
                                                            Rp {{ number_format((float)($row->total_revenue ?? 0), 0, ',', '.') }}
                                                        </td>
                                                    @elseif($type==='new_customers')
                                                        <td class="text-end fw-bold border-secondary">
                                                            <span class="badge bg-primary">{{ (int)($row->new_customers ?? 0) }}</span>
                                                        </td>
                                                    @elseif($type==='returning_customers')
                                                        <td class="text-end fw-bold border-secondary">
                                                            <span class="badge bg-success">{{ (int)($row->returning_customers ?? 0) }}</span>
                                                        </td>
                                                    @elseif($type==='top_products')
                                                        <td class="fw-semibold border-secondary">{{ $row->product_name }}</td>
                                                        <td class="text-end text-success fw-bold border-secondary">
                                                            Rp {{ number_format((float)($row->revenue ?? 0), 0, ',', '.') }}
                                                        </td>
                                                        <td class="text-end border-secondary">{{ number_format((float)($row->quantity_sold ?? 0), 1) }}</td>
                                                        <td class="text-end text-muted border-secondary">
                                                            Rp {{ number_format((float)(($row->revenue ?? 0) / ($row->quantity_sold ?? 1)), 0, ',', '.') }}
                                                        </td>
                                                    @endif
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="10" class="text-center text-muted py-5">
                                                        <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                                        <p class="mb-0">Tidak ada data untuk periode yang dipilih</p>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            {{-- Pagination --}}
                            @if(($warehouseData['pagination']['total'] ?? 0) > 0)
                            <div class="card-footer bg-transparent border-secondary">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="text-muted small">
                                        Menampilkan {{ min(($warehouseData['pagination']['page'] - 1) * $warehouseData['pagination']['per_page'] + 1, $warehouseData['pagination']['total']) }}
                                        - {{ min($warehouseData['pagination']['page'] * $warehouseData['pagination']['per_page'], $warehouseData['pagination']['total']) }}
                                        dari {{ $warehouseData['pagination']['total'] }} records
                                    </div>
                                    <nav>
                                        <ul class="pagination pagination-sm mb-0">
                                            <li class="page-item {{ $warehouseData['pagination']['page'] <= 1 ? 'disabled' : '' }}">
                                                <a class="page-link" href="#" onclick="changePage({{ $warehouseData['pagination']['page'] - 1 }}); return false;">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                            @for($i = max(1, $warehouseData['pagination']['page'] - 2); $i <= min($warehouseData['pagination']['total_pages'], $warehouseData['pagination']['page'] + 2); $i++)
                                                <li class="page-item {{ $i == $warehouseData['pagination']['page'] ? 'active' : '' }}">
                                                    <a class="page-link" href="#" onclick="changePage({{ $i }}); return false;">{{ $i }}</a>
                                                </li>
                                            @endfor
                                            <li class="page-item {{ $warehouseData['pagination']['page'] >= $warehouseData['pagination']['total_pages'] ? 'disabled' : '' }}">
                                                <a class="page-link" href="#" onclick="changePage({{ $warehouseData['pagination']['page'] + 1 }}); return false;">
                                                    <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Summary & Top Items Panel --}}
                    <div class="col-lg-4">
                        @include('dashboard-metrics.partials.warehouse-summary', ['type' => $type, 'warehouseData' => $warehouseData])
                    </div>
                </div>
            </div>

            {{-- Loading Overlay --}}
            <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark bg-opacity-75 d-none align-items-center justify-content-center" id="warehouseLoading" style="z-index: 1000;">
                <div class="text-center text-white">
                    <div class="spinner-border text-light mb-3" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="fs-5">Memperbarui data warehouse...</p>
                </div>
            </div>
        @else
            {{-- No Data State --}}
            <div class="text-center py-5">
                <i class="fas fa-database fa-4x text-muted mb-4"></i>
                <h4 class="text-white mb-3">Data Warehouse Belum Tersedia</h4>
                <p class="text-muted mb-4">
                    Pastikan migrasi telah dijalankan dan proses ETL telah selesai.<br>
                    Anda dapat menjalankan backfill untuk mengisi data warehouse dari transaksi yang ada.
                </p>
                <button class="btn btn-warning" onclick="triggerBackfill()" id="backfillBtn">
                    <i class="fas fa-database me-2"></i> Jalankan Backfill
                </button>
            </div>
        @endif
    </div>
</div>

<style>
/* Enhanced Warehouse Section Styling */
.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.bg-gradient-dark {
    background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
}

.warehouse-section {
    border-radius: 15px;
    overflow: hidden;
    animation: fadeInUp 0.5s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.warehouse-controls {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
    padding: 1.5rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.warehouse-row {
    transition: all 0.2s ease;
}

.warehouse-row:hover {
    background-color: rgba(102, 126, 234, 0.2) !important;
    transform: translateX(5px);
}

.table-dark thead.sticky-top {
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.card-header.bg-transparent {
    backdrop-filter: blur(10px);
}

/* Pagination Styling */
.pagination .page-link {
    background-color: #2d3748;
    border-color: #4a5568;
    color: #cbd5e0;
}

.pagination .page-link:hover {
    background-color: #4a5568;
    border-color: #667eea;
    color: #fff;
}

.pagination .page-item.active .page-link {
    background-color: #667eea;
    border-color: #667eea;
}

.pagination .page-item.disabled .page-link {
    background-color: #1a202c;
    border-color: #2d3748;
    color: #4a5568;
}

/* Badge Enhancements */
.badge {
    padding: 0.35rem 0.65rem;
    font-weight: 600;
}

/* Responsive adjustments */
@media (max-width: 992px) {
    .col-lg-8, .col-lg-4 {
        margin-bottom: 1rem;
    }
}
</style>
