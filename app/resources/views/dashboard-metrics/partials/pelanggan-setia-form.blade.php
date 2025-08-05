<!-- Jumlah Pelanggan Setia Form -->
<div id="pelanggan-setia-form" class="metric-specific-form" style="display: none;">
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="total_customer_count" class="form-label text-white">
                    <i class="fas fa-users me-1"></i>Total Pelanggan Bertransaksi <span class="text-danger">*</span>
                </label>
                <input type="number" class="form-control form-control-lg modal-input"
                       id="total_customer_count" name="total_customer_count" min="0" required>
                <div class="form-text text-light opacity-75">Total pelanggan yang bertransaksi hari ini</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label for="loyal_customer_definition" class="form-label text-white">
                    <i class="fas fa-heart me-1"></i>Definisi Pelanggan Setia
                </label>
                <select class="form-select modal-input" id="loyal_customer_definition" name="loyal_customer_definition">
                    <option value="repeat_purchase">Pembelian Berulang (> 1x)</option>
                    <option value="monthly_return">Kembali dalam 1 Bulan</option>
                    <option value="quarterly_return">Kembali dalam 3 Bulan</option>
                    <option value="custom">Custom Definition</option>
                </select>
                <div class="form-text text-light opacity-75">Kriteria yang digunakan untuk menentukan pelanggan setia</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="calculation-preview">
                <h6 class="text-white mb-3">Kalkulasi Real-time</h6>
                <div class="bg-dark p-3 rounded">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-white">Total Pelanggan:</span>
                        <span class="text-info" id="display_total_customers">0</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-white">Pelanggan Baru:</span>
                        <span class="text-success" id="display_new_customers">0</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-white">Pelanggan Kembali:</span>
                        <span class="text-warning" id="display_returning_customers">0</span>
                    </div>
                    <hr class="border-secondary">
                    <div class="d-flex justify-content-between">
                        <span class="text-white"><strong>% Pelanggan Setia:</strong></span>
                        <span class="text-primary" id="display_loyalty_percentage"><strong>0%</strong></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label for="loyalty_program_members" class="form-label text-white">
                    <i class="fas fa-star me-1"></i>Member Program Loyalitas <span class="text-muted">(Optional)</span>
                </label>
                <input type="number" class="form-control modal-input"
                       id="loyalty_program_members" name="loyalty_program_members" min="0">
                <div class="form-text text-light opacity-75">Jumlah pelanggan yang bergabung dengan program loyalitas</div>
            </div>

            <div class="mb-3">
                <label for="avg_purchase_frequency" class="form-label text-white">
                    <i class="fas fa-sync me-1"></i>Frekuensi Pembelian Rata-rata <span class="text-muted">(Optional)</span>
                </label>
                <div class="input-group">
                    <input type="number" class="form-control modal-input"
                           id="avg_purchase_frequency" name="avg_purchase_frequency" step="0.1" min="0">
                    <span class="input-group-text modal-input-addon">x/bulan</span>
                </div>
                <div class="form-text text-light opacity-75">Rata-rata berapa kali pelanggan berbelanja per bulan</div>
            </div>
        </div>
    </div>

    <!-- Formula Display -->
    <div class="formula-display mt-3 p-3">
        <h6 class="text-white mb-2">
            <i class="fas fa-calculator me-1"></i>Formula
        </h6>
        <p class="text-info mb-1"><strong>% Pelanggan Setia = ((Total Pelanggan - Pelanggan Baru) / Total Pelanggan) × 100%</strong></p>
        <small class="text-white">Turunan dari Jumlah Pelanggan Baru dan data input Total Pelanggan</small>
    </div>
</div>
