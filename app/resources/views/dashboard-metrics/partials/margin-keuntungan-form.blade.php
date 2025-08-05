<!-- Margin Keuntungan Form -->
<div id="margin-keuntungan-form" class="metric-specific-form" style="display: none;">
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Kalkulasi Otomatis:</strong> Margin Keuntungan dihitung berdasarkan data Total Penjualan dan COGS yang sudah diinput.
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="calculation-preview">
                <h6 class="text-white mb-3">Data Referensi (Periode Terpilih)</h6>
                <div class="bg-dark p-3 rounded">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-white">Total Pendapatan:</span>
                        <span class="text-success" id="ref_total_revenue">Rp 0</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-white">Total COGS:</span>
                        <span class="text-warning" id="ref_total_cogs">Rp 0</span>
                    </div>
                    <hr class="border-secondary">
                    <div class="d-flex justify-content-between">
                        <span class="text-white"><strong>Margin Keuntungan:</strong></span>
                        <span class="text-primary" id="calculated_margin"><strong>0%</strong></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label for="margin_period" class="form-label text-white">
                    <i class="fas fa-calendar me-1"></i>Periode Kalkulasi
                </label>
                <select class="form-select modal-input" id="margin_period" name="margin_period">
                    <option value="daily">Harian</option>
                    <option value="weekly">Mingguan</option>
                    <option value="monthly" selected>Bulanan</option>
                    <option value="yearly">Tahunan</option>
                </select>
                <div class="form-text text-light opacity-75">Pilih periode untuk kalkulasi margin</div>
            </div>

            <div class="mb-3">
                <label for="margin_target" class="form-label text-white">
                    <i class="fas fa-target me-1"></i>Target Margin <span class="text-muted">(Optional)</span>
                </label>
                <div class="input-group">
                    <input type="number" class="form-control modal-input"
                           id="margin_target" name="margin_target" step="0.1" min="0" max="100">
                    <span class="input-group-text modal-input-addon">%</span>
                </div>
                <div class="form-text text-light opacity-75">Target margin keuntungan yang diinginkan</div>
            </div>
        </div>
    </div>

    <!-- Formula Display -->
    <div class="formula-display mt-3 p-3">
        <h6 class="text-white mb-2">
            <i class="fas fa-calculator me-1"></i>Formula
        </h6>
        <p class="text-info mb-1"><strong>Margin Keuntungan = ((Pendapatan - COGS) / Pendapatan) × 100%</strong></p>
        <small class="text-white">Turunan langsung dari Total Penjualan dan COGS. Indikator kesehatan bisnis yang vital.</small>
    </div>
</div>
