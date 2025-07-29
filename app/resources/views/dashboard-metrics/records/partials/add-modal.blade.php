<!-- Add Record Modal -->
<div class="modal fade" id="addRecordModal" tabindex="-1" aria-labelledby="addRecordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addRecordModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>
                    Tambah Data - {{ $businessMetric->metric_name }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('dashboard.metrics.records.store', $businessMetric) }}" method="POST" id="addRecordForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="record_date" class="form-label">Tanggal</label>
                                <input type="date" class="form-control" id="record_date" name="record_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="value" class="form-label">Nilai</label>
                                <div class="input-group">
                                    @if($businessMetric->unit == 'Rp')
                                        <span class="input-group-text">Rp</span>
                                    @endif
                                    <input type="number" class="form-control" id="value" name="value" step="0.01" min="0" required>
                                    @if($businessMetric->unit == '%')
                                        <span class="input-group-text">%</span>
                                    @elseif($businessMetric->unit && $businessMetric->unit != 'Rp')
                                        <span class="input-group-text">{{ $businessMetric->unit }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Catatan (Opsional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Tambahkan catatan untuk data ini..."></textarea>
                    </div>

                    @if(in_array($businessMetric->metric_name, ['Total Penjualan', 'Biaya Pokok Penjualan']))
                        <div class="row">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">
                                    <i class="bi bi-graph-up me-2"></i>
                                    Data Penjualan Detail
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="total_revenue" class="form-label">Total Pendapatan</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="total_revenue" name="total_revenue" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="total_cogs" class="form-label">Total COGS</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="total_cogs" name="total_cogs" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="transaction_count" class="form-label">Jumlah Transaksi</label>
                                    <input type="number" class="form-control" id="transaction_count" name="transaction_count" min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sales_notes" class="form-label">Catatan Penjualan</label>
                                    <input type="text" class="form-control" id="sales_notes" name="sales_notes" placeholder="Catatan khusus...">
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($businessMetric->metric_name == 'Penjualan Produk Terlaris')
                        <div class="row">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">
                                    <i class="bi bi-box me-2"></i>
                                    Data Produk
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="product_name" class="form-label">Nama Produk</label>
                                    <input type="text" class="form-control" id="product_name" name="product_name" placeholder="Nama produk...">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="product_category" class="form-label">Kategori</label>
                                    <input type="text" class="form-control" id="product_category" name="product_category" placeholder="Kategori produk...">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="quantity_sold" class="form-label">Jumlah Terjual</label>
                                    <input type="number" class="form-control" id="quantity_sold" name="quantity_sold" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="unit_price" class="form-label">Harga Satuan</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="unit_price" name="unit_price" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="cost_per_unit" class="form-label">Biaya per Unit</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="cost_per_unit" name="cost_per_unit" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(in_array($businessMetric->metric_name, ['Jumlah Pelanggan Baru', 'Jumlah Pelanggan Setia']))
                        <div class="row">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">
                                    <i class="bi bi-people me-2"></i>
                                    Data Pelanggan
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="customer_name" class="form-label">Nama Pelanggan</label>
                                    <input type="text" class="form-control" id="customer_name" name="customer_name" placeholder="Nama pelanggan...">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="customer_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="customer_email" name="customer_email" placeholder="email@example.com">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="customer_phone" class="form-label">Telepon</label>
                                    <input type="text" class="form-control" id="customer_phone" name="customer_phone" placeholder="08xxxxxxxxxx">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="total_spent" class="form-label">Total Belanja</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="total_spent" name="total_spent" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check me-2"></i>
                        Simpan Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
