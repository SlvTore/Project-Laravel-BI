<!-- COGS Form -->
<div id="cogs-form" class="metric-specific-form" style="display: none;">
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="total_cogs" class="form-label text-white">
                    <i class="fas fa-boxes me-1"></i>Total Biaya Pokok Penjualan <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text modal-input-addon">Rp</span>
                    <input type="number" class="form-control form-control-lg modal-input"
                           id="total_cogs" name="total_cogs" step="0.01" min="0" required>
                </div>
                <div class="form-text text-light opacity-75">Masukkan total COGS harian</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label for="cogs_notes" class="form-label text-white">
                    <i class="fas fa-sticky-note me-1"></i>Catatan Biaya <span class="text-muted">(Optional)</span>
                </label>
                <textarea class="form-control modal-input" id="cogs_notes" name="cogs_notes"
                          rows="2" placeholder="Detail biaya produksi, material, dll..."></textarea>
                <div class="form-text text-light opacity-75">Detail komponen biaya produksi</div>
            </div>
        </div>
    </div>

    <!-- Formula Display -->
    <div class="formula-display mt-3 p-3">
        <h6 class="text-white mb-2">
            <i class="fas fa-calculator me-1"></i>Formula
        </h6>
        <p class="text-info mb-1"><strong>COGS = Σ (Total Biaya Produksi dan Material dalam periode)</strong></p>
        <small class="text-white">Komponen penting untuk menghitung Margin Keuntungan dan Rotasi Stok</small>
    </div>
</div>
