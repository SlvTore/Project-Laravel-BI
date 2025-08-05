<!-- Total Penjualan Form -->
<div id="total-penjualan-form" class="metric-specific-form" style="display: none;">
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="total_revenue" class="form-label text-white">
                    <i class="fas fa-money-bill-wave me-1"></i>Total Pendapatan <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text modal-input-addon">Rp</span>
                    <input type="number" class="form-control form-control-lg modal-input"
                           id="total_revenue" name="total_revenue" step="0.01" min="0" required>
                </div>
                <div class="form-text text-light opacity-75">Masukkan total pendapatan harian</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label for="transaction_count" class="form-label text-white">
                    <i class="fas fa-receipt me-1"></i>Jumlah Transaksi <span class="text-muted">(Optional)</span>
                </label>
                <input type="number" class="form-control form-control-lg modal-input"
                       id="transaction_count" name="transaction_count" min="0">
                <div class="form-text text-light opacity-75">Jumlah transaksi dalam hari ini</div>
            </div>
        </div>
    </div>

    <!-- Formula Display -->
    <div class="formula-display mt-3 p-3">
        <h6 class="text-white mb-2">
            <i class="fas fa-calculator me-1"></i>Formula
        </h6>
        <p class="text-info mb-1"><strong>Total Penjualan = Σ (Penjualan dalam periode)</strong></p>
        <small class="text-white">Data ini akan digunakan untuk perhitungan Revenue Growth dan Margin Keuntungan</small>
    </div>
</div>
